<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\PollingStation;
use App\Models\TpsCandidateVote;
use App\Models\Village;
use App\Services\VoteAggregationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VillageTpsInlineController extends Controller
{
    public function storeTps(Request $request, VoteAggregationService $agg)
    {
        $validated = $request->validate([
            'election_year_id' => ['required', 'exists:election_years,id'],
            'village_id' => ['required', 'exists:villages,id'],
            'tps_code' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $area = $agg->resolveAreaForVillageId((int) $validated['village_id']);
        if (!$area) {
            return back()
                ->withErrors(['village_id' => 'Kabupaten untuk desa ini tidak ditemukan di data Area.'])
                ->withInput();
        }

        PollingStation::firstOrCreate(
            [
                'village_id' => (int) $validated['village_id'],
                'code' => (string) $validated['tps_code'],
            ],
            [
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]
        );

        $agg->syncVillageAndAreaFromVillageId((int) $validated['election_year_id'], (int) $validated['village_id']);

        $year = ElectionYear::find((int) $validated['election_year_id']);
        $village = Village::query()->find((int) $validated['village_id']);

        return redirect()
            ->route('admin.village-votes.index', [
                'year' => $year?->year,
                'subdistrict_id' => $village?->subdistrict_id,
                'village_id' => $validated['village_id'],
            ])
            ->with('status', 'TPS berhasil ditambahkan.');
    }

    public function destroyTps(Request $request, PollingStation $pollingStation, VoteAggregationService $agg)
    {
        $validated = $request->validate([
            'election_year_id' => ['required', 'exists:election_years,id'],
        ]);

        $villageId = (int) $pollingStation->village_id;
        $pollingStation->delete();

        $agg->syncVillageAndAreaFromVillageId((int) $validated['election_year_id'], $villageId);

        $year = ElectionYear::find((int) $validated['election_year_id']);
        $village = Village::query()->find($villageId);

        return redirect()
            ->route('admin.village-votes.index', [
                'year' => $year?->year,
                'subdistrict_id' => $village?->subdistrict_id,
                'village_id' => $villageId,
            ])
            ->with('status', 'TPS berhasil dihapus.');
    }

    public function bulkUpsertVotes(Request $request, VoteAggregationService $agg)
    {
        $validated = $request->validate([
            'election_year_id' => ['required', 'exists:election_years,id'],
            'village_id' => ['required', 'exists:villages,id'],
            'votes' => ['required', 'array'],
        ]);

        $electionYearId = (int) $validated['election_year_id'];
        $villageId = (int) $validated['village_id'];

        $area = $agg->resolveAreaForVillageId($villageId);
        if (!$area) {
            return back()->withErrors(['village_id' => 'Kabupaten untuk desa ini tidak ditemukan di data Area.']);
        }

        $allowedCandidateIds = Candidate::query()
            ->where('area_id', $area->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $allowedTpsIds = PollingStation::query()
            ->where('village_id', $villageId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $allowedCandidateSet = array_fill_keys(array_map('strval', $allowedCandidateIds), true);
        $allowedTpsSet = array_fill_keys(array_map('strval', $allowedTpsIds), true);

        $votes = (array) $validated['votes'];

        DB::transaction(function () use ($votes, $electionYearId, $allowedCandidateIds, $allowedCandidateSet, $allowedTpsSet) {
            foreach ($votes as $tpsId => $votesByCandidateId) {
                $tpsId = (string) $tpsId;
                if (!isset($allowedTpsSet[$tpsId])) {
                    continue;
                }

                $pollingStationId = (int) $tpsId;

                if (!empty($allowedCandidateIds)) {
                    TpsCandidateVote::query()
                        ->where('election_year_id', $electionYearId)
                        ->where('polling_station_id', $pollingStationId)
                        ->whereNotIn('candidate_id', $allowedCandidateIds)
                        ->delete();
                }

                foreach ((array) $votesByCandidateId as $candidateId => $v) {
                    $candidateId = (string) $candidateId;
                    if (!isset($allowedCandidateSet[$candidateId])) {
                        continue;
                    }

                    $votesInt = (int) ($v ?? 0);
                    if ($votesInt > 0) {
                        TpsCandidateVote::updateOrCreate(
                            [
                                'election_year_id' => $electionYearId,
                                'polling_station_id' => $pollingStationId,
                                'candidate_id' => (int) $candidateId,
                            ],
                            [
                                'votes' => $votesInt,
                            ]
                        );
                    } else {
                        TpsCandidateVote::query()
                            ->where('election_year_id', $electionYearId)
                            ->where('polling_station_id', $pollingStationId)
                            ->where('candidate_id', (int) $candidateId)
                            ->delete();
                    }
                }
            }
        });

        $agg->syncVillageAndAreaFromVillageId($electionYearId, $villageId);

        $year = ElectionYear::find($electionYearId);
        $village = Village::query()->find($villageId);

        return redirect()
            ->route('admin.village-votes.index', [
                'year' => $year?->year,
                'subdistrict_id' => $village?->subdistrict_id,
                'village_id' => $villageId,
            ])
            ->with('status', 'Suara TPS berhasil disimpan.');
    }
}
