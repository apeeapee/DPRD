<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\Party;
use App\Models\PollingStation;
use App\Models\TpsCandidateVote;
use App\Models\Village;
use Illuminate\Database\Seeder;

class FixTpsCandidateScopeSeeder extends Seeder
{
    public function run(): void
    {
        $year = ElectionYear::query()->where('year', 2024)->first();
        if (!$year) {
            $year = ElectionYear::query()->orderBy('year')->first();
        }
        if (!$year) {
            return;
        }

        $areasByName = Area::query()->pluck('id', 'name')->all();
        $parties = Party::query()->get(['id', 'code']);

        // For each village, move votes from global dummy candidates ("Calon PXX #k")
        // into area-scoped candidates ("Calon PXX <Kabupaten Name> #k" with area_id).
        $villages = Village::query()
            ->join('subdistricts', 'subdistricts.id', '=', 'villages.subdistrict_id')
            ->get([
                'villages.id as id',
                'subdistricts.regency_name as regency_name',
            ]);

        foreach ($villages as $v) {
            $regencyName = (string) ($v->regency_name ?? '');
            if ($regencyName === '') {
                continue;
            }

            $areaId = $areasByName[$regencyName] ?? null;
            if (!$areaId) {
                continue;
            }

            $pollingStationIds = PollingStation::query()
                ->where('village_id', $v->id)
                ->pluck('id')
                ->all();

            if (empty($pollingStationIds)) {
                continue;
            }

            foreach ($parties as $party) {
                $partyLabel = $party->code ?? ('P' . $party->id);

                for ($k = 1; $k <= 3; $k++) {
                    $globalName = 'Calon ' . $partyLabel . ' #' . $k;

                    $globalCandidate = Candidate::query()
                        ->where('party_id', $party->id)
                        ->where('name', $globalName)
                        ->first(['id', 'party_id', 'name']);

                    if (!$globalCandidate) {
                        continue;
                    }

                    $scopedName = 'Calon ' . $partyLabel . ' ' . $regencyName . ' #' . $k;

                    $scopedCandidate = Candidate::query()->firstOrCreate(
                        [
                            'party_id' => $party->id,
                            'area_id' => $areaId,
                            'name' => $scopedName,
                        ],
                        [
                            'party_id' => $party->id,
                            'area_id' => $areaId,
                            'name' => $scopedName,
                            'number' => $k,
                        ]
                    );

                    TpsCandidateVote::query()
                        ->where('election_year_id', $year->id)
                        ->whereIn('polling_station_id', $pollingStationIds)
                        ->where('candidate_id', $globalCandidate->id)
                        ->update(['candidate_id' => $scopedCandidate->id]);
                }
            }
        }

        // Also backfill area_id for area-named dummy candidates created by DummyResultsSeeder
        // Format: "Calon <partyCode> <Kabupaten Name> #<k>".
        foreach ($areasByName as $areaName => $areaId) {
            Candidate::query()
                ->whereNull('area_id')
                ->where('name', 'like', '% ' . $areaName . ' #%')
                ->update(['area_id' => $areaId]);
        }
    }
}
