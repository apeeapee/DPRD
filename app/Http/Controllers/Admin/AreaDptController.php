<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AreaElectionSummary;
use App\Models\ElectionYear;
use App\Services\VoteAggregationService;
use Illuminate\Http\Request;

class AreaDptController extends Controller
{
    private array $targetRegencies = [
        'Kabupaten Karanganyar',
        'Kabupaten Sragen',
        'Kabupaten Wonogiri',
    ];

    public function index(Request $request, VoteAggregationService $agg)
    {
        $year = (int) ($request->query('year') ?? 2024);

        $electionYear = ElectionYear::query()->where('year', $year)->first();
        if (!$electionYear) {
            $electionYear = ElectionYear::query()->orderBy('year', 'desc')->first();
        }

        $areas = Area::query()
            ->whereIn('name', $this->targetRegencies)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $summariesByAreaId = collect();
        if ($electionYear) {
            $summariesByAreaId = AreaElectionSummary::query()
                ->where('election_year_id', $electionYear->id)
                ->whereIn('area_id', $areas->pluck('id')->all())
                ->get()
                ->keyBy('area_id');
        }

        $rows = $areas->map(function ($area) use ($electionYear, $summariesByAreaId, $agg) {
            $summary = $summariesByAreaId->get($area->id);

            $votesCast = $electionYear
                ? $agg->computeAreaVotesFromVillageSummaries($electionYear->id, $area->id)
                : 0;

            $registered = (int) ($summary?->registered_voters ?? 0);
            $progressPct = $registered > 0 ? (int) round(($votesCast / $registered) * 100) : 0;
            $progressPct = max(0, min(100, $progressPct));

            return [
                'area_id' => (int) $area->id,
                'area_name' => (string) $area->name,
                'registered_voters' => (int) ($summary?->registered_voters ?? 0),
                'votes_cast' => (int) $votesCast,
                'progress_pct' => (int) $progressPct,
            ];
        });

        return view('admin/dpt/index', [
            'year' => $electionYear?->year ?? $year,
            'yearRow' => $electionYear,
            'years' => ElectionYear::query()->orderBy('year', 'desc')->get(['id', 'year']),
            'rows' => $rows,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'election_year_id' => ['required', 'exists:election_years,id'],
            'dpt' => ['required', 'array'],
            'dpt.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $electionYearId = (int) $validated['election_year_id'];

        $areas = Area::query()
            ->whereIn('name', $this->targetRegencies)
            ->get(['id']);

        $allowedAreaIds = $areas->pluck('id')->map(fn ($id) => (string) $id)->all();
        $allowedSet = array_fill_keys($allowedAreaIds, true);

        foreach ((array) $validated['dpt'] as $areaId => $dpt) {
            $areaId = (string) $areaId;
            if (!isset($allowedSet[$areaId])) {
                continue;
            }

            AreaElectionSummary::query()->updateOrCreate(
                [
                    'election_year_id' => $electionYearId,
                    'area_id' => (int) $areaId,
                ],
                [
                    'registered_voters' => (int) ($dpt ?? 0),
                ]
            );
        }

        $year = ElectionYear::find($electionYearId);

        return redirect()
            ->route('admin.dpt.index', ['year' => $year?->year])
            ->with('status', 'DPT berhasil disimpan.');
    }

    public function sync(Request $request, VoteAggregationService $agg)
    {
        $validated = $request->validate([
            'election_year_id' => ['required', 'exists:election_years,id'],
        ]);

        $electionYearId = (int) $validated['election_year_id'];
        $agg->syncAllForYearAndRegencies($electionYearId, $this->targetRegencies);

        $year = ElectionYear::find($electionYearId);

        return redirect()
            ->route('admin.dpt.index', ['year' => $year?->year])
            ->with('status', 'Akumulasi suara masuk berhasil disinkronkan dari data TPS.');
    }
}
