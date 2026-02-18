<?php

namespace App\Services;

use App\Models\Area;
use App\Models\AreaElectionSummary;
use App\Models\PollingStation;
use App\Models\Subdistrict;
use App\Models\TpsCandidateVote;
use App\Models\Village;
use App\Models\VillageVoteSummary;
use Illuminate\Support\Facades\DB;

class VoteAggregationService
{
    public function resolveAreaForVillageId(int $villageId): ?Area
    {
        $regencyName = Subdistrict::query()
            ->join('villages', 'villages.subdistrict_id', '=', 'subdistricts.id')
            ->where('villages.id', $villageId)
            ->value('subdistricts.regency_name');

        if (!$regencyName) {
            return null;
        }

        return Area::query()->where('name', $regencyName)->first(['id', 'name']);
    }

    /**
     * Returns: ['has_rows' => bool, 'votes_cast' => int]
     */
    public function computeVillageVotesFromTps(int $electionYearId, int $villageId): array
    {
        $pollingStationIds = PollingStation::query()
            ->where('village_id', $villageId)
            ->pluck('id')
            ->all();

        if (empty($pollingStationIds)) {
            return ['has_rows' => false, 'votes_cast' => 0];
        }

        $agg = TpsCandidateVote::query()
            ->where('election_year_id', $electionYearId)
            ->whereIn('polling_station_id', $pollingStationIds)
            ->selectRaw('COALESCE(SUM(votes),0) as votes_cast, COUNT(*) as rows_count')
            ->first();

        $rowsCount = (int) ($agg->rows_count ?? 0);
        $votesCast = (int) ($agg->votes_cast ?? 0);

        return ['has_rows' => $rowsCount > 0, 'votes_cast' => $votesCast];
    }

    /**
     * If there are TPS vote rows for the village+year, sync VillageVoteSummary to match.
     * Returns synced votes_cast, or null when no TPS data exists.
     */
    public function syncVillageSummaryFromTpsIfAny(int $electionYearId, int $villageId): ?int
    {
        $computed = $this->computeVillageVotesFromTps($electionYearId, $villageId);
        if (!$computed['has_rows']) {
            return null;
        }

        VillageVoteSummary::query()->updateOrCreate(
            [
                'election_year_id' => $electionYearId,
                'village_id' => $villageId,
            ],
            [
                'votes_cast' => (int) $computed['votes_cast'],
            ]
        );

        return (int) $computed['votes_cast'];
    }

    public function computeAreaVotesFromVillageSummaries(int $electionYearId, int $areaId): int
    {
        $area = Area::query()->find($areaId);
        if (!$area) {
            return 0;
        }

        $sum = VillageVoteSummary::query()
            ->join('villages', 'villages.id', '=', 'village_vote_summaries.village_id')
            ->join('subdistricts', 'subdistricts.id', '=', 'villages.subdistrict_id')
            ->where('village_vote_summaries.election_year_id', $electionYearId)
            ->where('subdistricts.regency_name', $area->name)
            ->selectRaw('COALESCE(SUM(village_vote_summaries.votes_cast),0) as votes_cast')
            ->value('votes_cast');

        return (int) ($sum ?? 0);
    }

    public function syncAreaSummaryVotesCast(int $electionYearId, int $areaId): int
    {
        $votesCast = $this->computeAreaVotesFromVillageSummaries($electionYearId, $areaId);

        AreaElectionSummary::query()->updateOrCreate(
            [
                'election_year_id' => $electionYearId,
                'area_id' => $areaId,
            ],
            [
                'votes_cast' => $votesCast,
            ]
        );

        return $votesCast;
    }

    /**
     * Sync village summaries (only where TPS data exists), then sync area summary.
     */
    public function syncVillageAndAreaFromVillageId(int $electionYearId, int $villageId): void
    {
        DB::transaction(function () use ($electionYearId, $villageId) {
            $this->syncVillageSummaryFromTpsIfAny($electionYearId, $villageId);

            $area = $this->resolveAreaForVillageId($villageId);
            if ($area) {
                $this->syncAreaSummaryVotesCast($electionYearId, $area->id);
            }
        });
    }

    /**
     * Bulk sync: all villages under 3 regencies only.
     */
    public function syncAllForYearAndRegencies(int $electionYearId, array $regencyNames): void
    {
        DB::transaction(function () use ($electionYearId, $regencyNames) {
            $villages = Village::query()
                ->join('subdistricts', 'subdistricts.id', '=', 'villages.subdistrict_id')
                ->whereIn('subdistricts.regency_name', $regencyNames)
                ->select(['villages.id as id'])
                ->get();

            foreach ($villages as $v) {
                $this->syncVillageSummaryFromTpsIfAny($electionYearId, (int) $v->id);
            }

            $areas = Area::query()->whereIn('name', $regencyNames)->get(['id']);
            foreach ($areas as $a) {
                $this->syncAreaSummaryVotesCast($electionYearId, (int) $a->id);
            }
        });
    }

    /**
     * Party aggregation from TPS votes for one or more regencies.
     * Returns array of rows: ['party_id' => int, 'party_name' => string, 'votes' => int]
     */
    public function computePartyResultsFromTpsForRegencies(int $electionYearId, array $regencyNames, int $limit = 10): array
    {
        $rows = DB::table('tps_candidate_votes as tcv')
            ->join('polling_stations as ps', 'ps.id', '=', 'tcv.polling_station_id')
            ->join('villages as v', 'v.id', '=', 'ps.village_id')
            ->join('subdistricts as s', 's.id', '=', 'v.subdistrict_id')
            ->join('candidates as c', 'c.id', '=', 'tcv.candidate_id')
            ->join('parties as p', 'p.id', '=', 'c.party_id')
            ->where('tcv.election_year_id', $electionYearId)
            ->whereIn('s.regency_name', $regencyNames)
            ->groupBy('p.id', 'p.name')
            ->select([
                'p.id as party_id',
                'p.name as party_name',
                DB::raw('COALESCE(SUM(tcv.votes),0) as votes'),
            ])
            ->orderByDesc('votes')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'party_id' => (int) $r->party_id,
            'party_name' => (string) $r->party_name,
            'votes' => (int) ($r->votes ?? 0),
        ])->values()->all();
    }

    /**
     * Candidate aggregation from TPS votes for one or more regencies.
     * Returns array of rows: ['candidate_id' => int, 'candidate_name' => string, 'party_id' => int, 'party_name' => string, 'votes' => int]
     */
    public function computeCandidateResultsFromTpsForRegencies(int $electionYearId, array $regencyNames, int $limit = 8): array
    {
        $rows = DB::table('tps_candidate_votes as tcv')
            ->join('polling_stations as ps', 'ps.id', '=', 'tcv.polling_station_id')
            ->join('villages as v', 'v.id', '=', 'ps.village_id')
            ->join('subdistricts as s', 's.id', '=', 'v.subdistrict_id')
            ->join('candidates as c', 'c.id', '=', 'tcv.candidate_id')
            ->join('parties as p', 'p.id', '=', 'c.party_id')
            ->where('tcv.election_year_id', $electionYearId)
            ->whereIn('s.regency_name', $regencyNames)
            ->groupBy('c.id', 'c.name', 'p.id', 'p.name')
            ->select([
                'c.id as candidate_id',
                'c.name as candidate_name',
                'p.id as party_id',
                'p.name as party_name',
                DB::raw('COALESCE(SUM(tcv.votes),0) as votes'),
            ])
            ->orderByDesc('votes')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'candidate_id' => (int) $r->candidate_id,
            'candidate_name' => (string) $r->candidate_name,
            'party_id' => (int) $r->party_id,
            'party_name' => (string) $r->party_name,
            'votes' => (int) ($r->votes ?? 0),
        ])->values()->all();
    }

    public function computePartyResultsFromTpsForArea(int $electionYearId, int $areaId, int $limit = 10): array
    {
        $area = Area::query()->find($areaId);
        if (!$area) {
            return [];
        }

        return $this->computePartyResultsFromTpsForRegencies($electionYearId, [$area->name], $limit);
    }

    public function computeCandidateResultsFromTpsForArea(int $electionYearId, int $areaId, int $limit = 50): array
    {
        $area = Area::query()->find($areaId);
        if (!$area) {
            return [];
        }

        return $this->computeCandidateResultsFromTpsForRegencies($electionYearId, [$area->name], $limit);
    }
}
