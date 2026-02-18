<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\ElectionYear;
use App\Models\AreaElectionSummary;
use Illuminate\Http\Request;
use App\Services\VoteAggregationService;

class AreaController extends Controller
{
    /**
     * GET /api/areas?year=2024
     * List kab/kota + ringkasan pemilih untuk peta.
     */
    public function index(Request $request)
    {
        $year = (int) $request->query('year', 2024);

        $electionYear = ElectionYear::query()
            ->where('year', $year)
            ->first();

        if (!$electionYear) {
            return response()->json([
                'message' => 'Tahun pemilu tidak ditemukan.',
                'year' => $year,
            ], 404);
        }

        // Ambil area + summary per area (registered_voters, votes_cast)
        // NOTE: sesuaikan nama kolom foreign key bila berbeda.
        $areas = Area::query()
            ->select(['id', 'name', 'type', 'latitude', 'longitude']) // sesuaikan kolom area kamu
            ->with(['electionSummaries' => function ($q) use ($electionYear) {
                $q->select([
                    'id',
                    'area_id',
                    'election_year_id',
                    'registered_voters',
                    'votes_cast',
                ])->where('election_year_id', $electionYear->id);
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($area) use ($year) {
                $summary = $area->electionSummaries->first();

                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'type' => $area->type ?? 'KAB/KOTA',
                    'lat' => $area->latitude ?? null,
                    'lng' => $area->longitude ?? null,
                    'year' => $year,
                    'summary' => [
                        'registered_voters' => (int)($summary->registered_voters ?? 0),
                        'votes_cast' => (int)($summary->votes_cast ?? 0),
                    ],
                ];
            });

        return response()->json([
            'year' => $year,
            'count' => $areas->count(),
            'data' => $areas,
        ]);
    }

    /**
     * GET /api/areas/{id}?year=2024
     * Detail area: summary + perolehan partai + perolehan calon
     */
    public function show(Request $request, Area $area)
    {
        $year = (int) $request->query('year', 2024);

        $electionYear = ElectionYear::query()
            ->where('year', $year)
            ->first();

        if (!$electionYear) {
            return response()->json([
                'message' => 'Tahun pemilu tidak ditemukan.',
                'year' => $year,
            ], 404);
        }

        // 1) Summary area
        $summary = AreaElectionSummary::query()
            ->select(['area_id', 'election_year_id', 'registered_voters', 'votes_cast'])
            ->where('area_id', $area->id)
            ->where('election_year_id', $electionYear->id)
            ->first();

        $agg = app(VoteAggregationService::class);

        // 2) Perolehan suara per partai (dihitung dari data TPS)
        $partyResults = $agg->computePartyResultsFromTpsForArea($electionYear->id, (int) $area->id, 50);

        // 3) Perolehan suara per calon (dihitung dari data TPS)
        $candidateResults = $agg->computeCandidateResultsFromTpsForArea($electionYear->id, (int) $area->id, 50);

        return response()->json([
            'year' => $year,
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'type' => $area->type ?? 'KAB/KOTA',
            ],
            'summary' => [
                'registered_voters' => (int)($summary->registered_voters ?? 0),
                'votes_cast' => (int)($summary->votes_cast ?? 0),
            ],
            'party_results' => $partyResults,
            'candidate_results' => $candidateResults,
        ]);
    }
}
