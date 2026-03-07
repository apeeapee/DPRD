<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

use App\Models\Area;
use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\Party;
use App\Models\PollingStation;
use App\Services\VoteAggregationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'dprd:cleanup-dummy {--execute : Actually delete data (default is dry-run)} {--delete-tps : Also delete dummy TPS codes like TPS01 (no space)} {--area= : Restrict cleanup to one Kabupaten/Kota (Area name) for candidates/TPS} {--year=2024 : Election year (for resync)} {--no-sync : Skip recomputing village/area summaries}',
    function (VoteAggregationService $agg) {
    $driver = DB::connection()->getDriverName();

    $execute = (bool) $this->option('execute');
    $deleteTps = (bool) $this->option('delete-tps');
    $areaName = $this->option('area');
    $year = (int) ($this->option('year') ?? 2024);
    $noSync = (bool) $this->option('no-sync');

    $area = null;
    if (is_string($areaName) && trim($areaName) !== '') {
        $area = Area::query()->where('name', trim($areaName))->first(['id', 'name']);
        if (!$area) {
            $this->error("Area tidak ditemukan: {$areaName}");
            return 1;
        }
    }

    $dummyPartyQuery = Party::query();
    if ($driver === 'pgsql') {
        $dummyPartyQuery->whereRaw("code ~ '^P[0-9]{2}$'");
    } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
        $dummyPartyQuery->whereRaw("code REGEXP '^P[0-9]{2}$'");
    } else {
        // sqlite fallback
        $dummyPartyQuery
            ->whereRaw("length(code) = 3")
            ->where('code', 'like', 'P__')
            ->whereRaw("substr(code,2,1) between '0' and '9'")
            ->whereRaw("substr(code,3,1) between '0' and '9'");
    }

    // Only delete dummy parties that don't have any non-dummy candidate.
    $dummyPartyIdsToDelete = $dummyPartyQuery
        ->whereDoesntHave('candidates', function ($q) {
            $q->where('name', 'not like', 'Calon %');
        })
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    $dummyCandidateQuery = Candidate::query()
        ->whereIn('party_id', $dummyPartyIdsToDelete)
        ->where('name', 'like', 'Calon %');

    if ($area) {
        $dummyCandidateQuery->where('area_id', (int) $area->id);
    }

    $dummyCandidateIds = $dummyCandidateQuery
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    // Affected villages for resync: villages that have TPS votes for dummy candidates.
    $affectedVillageIds = [];
    if (!empty($dummyCandidateIds)) {
        $affectedVillageIds = DB::table('tps_candidate_votes as tcv')
            ->join('polling_stations as ps', 'ps.id', '=', 'tcv.polling_station_id')
            ->whereIn('tcv.candidate_id', $dummyCandidateIds)
            ->distinct()
            ->pluck('ps.village_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    $dummyVotesCount = empty($dummyCandidateIds)
        ? 0
        : (int) DB::table('tps_candidate_votes')->whereIn('candidate_id', $dummyCandidateIds)->count();

    $dummyTpsIds = [];
    if ($deleteTps) {
        $dummyTpsQuery = PollingStation::query();
        if ($driver === 'pgsql') {
            $dummyTpsQuery->whereRaw("code ~ '^TPS[0-9]{2}$'");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $dummyTpsQuery->whereRaw("code REGEXP '^TPS[0-9]{2}$'");
        } else {
            $dummyTpsQuery
                ->whereRaw("length(code) = 5")
                ->where('code', 'like', 'TPS__')
                ->whereRaw("substr(code,4,1) between '0' and '9'")
                ->whereRaw("substr(code,5,1) between '0' and '9'");
        }

        if ($area) {
            $dummyTpsQuery
                ->join('villages as v', 'v.id', '=', 'polling_stations.village_id')
                ->join('subdistricts as s', 's.id', '=', 'v.subdistrict_id')
                ->where('s.regency_name', (string) $area->name)
                ->select(['polling_stations.id', 'polling_stations.village_id']);
        }

        // Only delete dummy TPS that don't have any non-dummy votes.
        $dummyTpsIds = $dummyTpsQuery
            ->whereNotExists(function ($q) use ($driver) {
                $q->select(DB::raw(1))
                    ->from('tps_candidate_votes as tcv2')
                    ->join('candidates as c2', 'c2.id', '=', 'tcv2.candidate_id')
                    ->join('parties as p2', 'p2.id', '=', 'c2.party_id')
                    ->whereColumn('tcv2.polling_station_id', 'polling_stations.id')
                    ->where(function ($qq) use ($driver) {
                        // non-dummy vote = NOT (dummy party code AND candidate name starts with 'Calon ')
                        if ($driver === 'pgsql') {
                            $qq->whereRaw("NOT (p2.code ~ '^P[0-9]{2}$' AND c2.name ILIKE 'Calon %')");
                        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                            $qq->whereRaw("NOT (p2.code REGEXP '^P[0-9]{2}$' AND c2.name LIKE 'Calon %')");
                        } else {
                            $qq->whereRaw("NOT (p2.code like 'P__' AND length(p2.code)=3 AND c2.name LIKE 'Calon %')");
                        }
                    });
            })
            ->pluck('polling_stations.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!empty($dummyTpsIds)) {
            $moreVillageIds = PollingStation::query()->whereIn('id', $dummyTpsIds)->pluck('village_id')->map(fn ($id) => (int) $id)->all();
            $affectedVillageIds = array_values(array_unique(array_merge($affectedVillageIds, $moreVillageIds)));
        }
    }

    $this->line('=== DPRD Cleanup Dummy Data ===');
    $this->line('Mode: ' . ($execute ? 'EXECUTE (DELETE)' : 'DRY-RUN'));
    $this->line('Driver DB: ' . $driver);
    $this->line('Area filter: ' . ($area ? $area->name : '—'));
    $this->line('Year for sync: ' . $year);
    $this->newLine();

    $this->line('Will delete:');
    $this->line('- Parties (dummy code P##, only if no non-dummy candidates): ' . count($dummyPartyIdsToDelete));
    $this->line('- Candidates (dummy "Calon ..." under those parties): ' . count($dummyCandidateIds));
    $this->line('- TPS Candidate Votes (via cascade; counted pre-delete): ' . $dummyVotesCount);
    $this->line('- Polling Stations (dummy TPS01.., safe-only): ' . ($deleteTps ? count($dummyTpsIds) : 0));
    $this->line('- Villages to resync: ' . ($noSync ? 0 : count($affectedVillageIds)));

    if (!$execute) {
        $this->newLine();
        $this->comment('Dry-run only. Re-run with --execute to actually delete.');
        $this->comment('Optional: add --delete-tps to remove TPS01.. dummy polling stations.');
        return 0;
    }

    DB::transaction(function () use ($dummyPartyIdsToDelete, $dummyCandidateIds, $deleteTps, $dummyTpsIds) {
        if ($deleteTps && !empty($dummyTpsIds)) {
            PollingStation::query()->whereIn('id', $dummyTpsIds)->delete();
        }

        // Delete candidates first (safe), then parties.
        if (!empty($dummyCandidateIds)) {
            Candidate::query()->whereIn('id', $dummyCandidateIds)->delete();
        }

        if (!empty($dummyPartyIdsToDelete)) {
            Party::query()->whereIn('id', $dummyPartyIdsToDelete)->delete();
        }
    });

    if (!$noSync) {
        $yearRow = ElectionYear::query()->where('year', $year)->first(['id']);
        if ($yearRow) {
            foreach (array_chunk($affectedVillageIds, 200) as $chunk) {
                foreach ($chunk as $villageId) {
                    $agg->syncVillageAndAreaFromVillageId((int) $yearRow->id, (int) $villageId);
                }
            }
        }
    }

    $this->info('Cleanup completed.');
    return 0;
    }
)->purpose('Dry-run / cleanup seeded dummy parties, candidates, and TPS');

Artisan::command(
    'dprd:keep-regencies {--execute : Actually delete data (default is dry-run)} {--regencies= : Comma-separated Area names to keep} {--delete-dummy-candidates : Also delete candidates named like "Calon ..."} {--delete-dummy-tps : Also delete polling stations coded like TPS01 (no space)} {--no-sync : Skip recomputing village/area summaries}',
    function (VoteAggregationService $agg) {
        $execute = (bool) $this->option('execute');
        $noSync = (bool) $this->option('no-sync');

        $regenciesRaw = (string) ($this->option('regencies') ?? '');
        $keep = array_values(array_filter(array_map('trim', $regenciesRaw !== ''
            ? explode(',', $regenciesRaw)
            : [
                'Kabupaten Karanganyar',
                'Kabupaten Sragen',
                'Kabupaten Wonogiri',
            ])));

        $deleteDummyCandidates = (bool) $this->option('delete-dummy-candidates');
        $deleteDummyTps = (bool) $this->option('delete-dummy-tps');

        $driver = DB::connection()->getDriverName();

        $keepAreaIds = Area::query()->whereIn('name', $keep)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $deleteAreaIds = Area::query()->whereNotIn('name', $keep)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $deleteSubdistrictIds = DB::table('subdistricts')
            ->whereNotIn('regency_name', $keep)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $deleteVillageIds = empty($deleteSubdistrictIds)
            ? []
            : DB::table('villages')->whereIn('subdistrict_id', $deleteSubdistrictIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $deletePollingStationIds = empty($deleteVillageIds)
            ? []
            : DB::table('polling_stations')->whereIn('village_id', $deleteVillageIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $deleteCandidateIds = Candidate::query()
            ->where(function ($q) use ($keepAreaIds) {
                // delete candidates whose area is not in keep (including null)
                if (!empty($keepAreaIds)) {
                    $q->whereNull('area_id')->orWhereNotIn('area_id', $keepAreaIds);
                } else {
                    $q->whereNotNull('area_id');
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $dummyCandidateIds = [];
        if ($deleteDummyCandidates) {
            $dummyCandidateIds = Candidate::query()
                ->where('name', 'like', 'Calon %')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $deleteCandidateIds = array_values(array_unique(array_merge($deleteCandidateIds, $dummyCandidateIds)));

        $dummyTpsIds = [];
        if ($deleteDummyTps) {
            $q = PollingStation::query();
            if ($driver === 'pgsql') {
                $q->whereRaw("code ~ '^TPS[0-9]{2}$'");
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                $q->whereRaw("code REGEXP '^TPS[0-9]{2}$'");
            } else {
                $q->whereRaw("length(code) = 5")->where('code', 'like', 'TPS__');
            }

            // Only delete dummy TPS outside keep regencies, or those that have no votes at all.
            $dummyTpsIds = $q
                ->where(function ($qq) use ($keep) {
                    $qq->whereIn('village_id', function ($sq) use ($keep) {
                        $sq->select('v.id')
                            ->from('villages as v')
                            ->join('subdistricts as s', 's.id', '=', 'v.subdistrict_id')
                            ->whereNotIn('s.regency_name', $keep);
                    });
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $votesToBeDeletedByPollingStations = empty($deletePollingStationIds) ? 0 : (int) DB::table('tps_candidate_votes')->whereIn('polling_station_id', $deletePollingStationIds)->count();
        $votesToBeDeletedByCandidates = empty($deleteCandidateIds) ? 0 : (int) DB::table('tps_candidate_votes')->whereIn('candidate_id', $deleteCandidateIds)->count();

        $this->line('=== DPRD Keep Regencies ===');
        $this->line('Mode: ' . ($execute ? 'EXECUTE (DELETE)' : 'DRY-RUN'));
        $this->line('Keep regencies: ' . implode(', ', $keep));
        $this->line('Driver DB: ' . $driver);
        $this->newLine();

        $this->line('Will delete (outside keep):');
        $this->line('- Areas: ' . count($deleteAreaIds));
        $this->line('- Subdistricts: ' . count($deleteSubdistrictIds));
        $this->line('- Villages: ' . count($deleteVillageIds));
        $this->line('- Polling stations: ' . count($deletePollingStationIds));
        $this->line('- Candidates: ' . count($deleteCandidateIds));
        $this->line('- Votes deleted (by PS scope): ' . $votesToBeDeletedByPollingStations);
        $this->line('- Votes deleted (by Candidate scope): ' . $votesToBeDeletedByCandidates);
        $this->line('- Dummy TPS (TPS01..) deleted: ' . count($dummyTpsIds));

        if (!$execute) {
            $this->newLine();
            $this->comment('Dry-run only. Re-run with --execute to actually delete.');
            $this->comment('Optional: --delete-dummy-candidates and --delete-dummy-tps');
            return 0;
        }

        DB::transaction(function () use ($deletePollingStationIds, $deleteVillageIds, $deleteSubdistrictIds, $deleteCandidateIds, $deleteAreaIds, $dummyTpsIds) {
            if (!empty($dummyTpsIds)) {
                PollingStation::query()->whereIn('id', $dummyTpsIds)->delete();
            }
            if (!empty($deletePollingStationIds)) {
                PollingStation::query()->whereIn('id', $deletePollingStationIds)->delete();
            }
            if (!empty($deleteVillageIds)) {
                DB::table('villages')->whereIn('id', $deleteVillageIds)->delete();
            }
            if (!empty($deleteSubdistrictIds)) {
                DB::table('subdistricts')->whereIn('id', $deleteSubdistrictIds)->delete();
            }

            // Delete candidates before deleting areas (area_id is nullOnDelete).
            if (!empty($deleteCandidateIds)) {
                Candidate::query()->whereIn('id', $deleteCandidateIds)->delete();
            }

            if (!empty($deleteAreaIds)) {
                Area::query()->whereIn('id', $deleteAreaIds)->delete();
            }
        });

        if (!$noSync) {
            $years = ElectionYear::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($years as $electionYearId) {
                $agg->syncAllForYearAndRegencies($electionYearId, $keep);
            }
        }

        $this->info('Done.');
        return 0;
    }
)->purpose('Keep only selected regencies and delete the rest (dry-run by default)');
