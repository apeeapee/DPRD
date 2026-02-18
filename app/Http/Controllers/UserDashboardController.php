<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\AreaElectionSummary;
use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\Party;
use App\Services\VoteAggregationService;

class UserDashboardController extends Controller
{
    public function index(Request $request, VoteAggregationService $voteAgg)
    {
        $year = 2024;
        $yearModel = ElectionYear::query()->where('year', $year)->first();

        $targets = [
            'Kabupaten Karanganyar',
            'Kabupaten Sragen',
            'Kabupaten Wonogiri',
        ];

        $areas = Area::query()->whereIn('name', $targets)->get(['id', 'name']);
        $areaIds = $areas->pluck('id');

        $registered = 0;
        $votesCast = 0;
        if ($yearModel && $areaIds->isNotEmpty()) {
            $summaryAgg = AreaElectionSummary::query()
                ->where('election_year_id', $yearModel->id)
                ->whereIn('area_id', $areaIds)
                ->selectRaw('COALESCE(SUM(registered_voters),0) as registered_voters, COALESCE(SUM(votes_cast),0) as votes_cast')
                ->first();
            $registered = (int) ($summaryAgg->registered_voters ?? 0);
            $votesCast = (int) ($summaryAgg->votes_cast ?? 0);
        }

        $progressPct = $registered > 0 ? (int) round(($votesCast / $registered) * 100) : 0;
        $progressPct = max(0, min(100, $progressPct));

        // Party distribution (top 10)
        $partyChart = ['labels' => [], 'values' => []];
        if ($yearModel && $areaIds->isNotEmpty()) {
            $partyRows = $voteAgg->computePartyResultsFromTpsForRegencies($yearModel->id, $targets, 10);
            $partyChart['labels'] = collect($partyRows)->map(fn ($r) => (string) ($r['party_name'] ?? '—'))->values()->all();
            $partyChart['values'] = collect($partyRows)->map(fn ($r) => (int) ($r['votes'] ?? 0))->values()->all();
        }

        // Recent updates: top candidates (top 8)
        $recentCandidates = [];
        if ($yearModel && $areaIds->isNotEmpty()) {
            $candRows = $voteAgg->computeCandidateResultsFromTpsForRegencies($yearModel->id, $targets, 8);
            $recentCandidates = collect($candRows)->map(fn ($r) => [
                'name' => (string) ($r['candidate_name'] ?? '—'),
                'party' => (string) ($r['party_name'] ?? '—'),
                'votes' => (int) ($r['votes'] ?? 0),
            ])->values()->all();
        }

        $kpi = [
            'areas_total' => (int) $areas->count(),
            'parties_total' => (int) Party::query()->count(),
            'candidates_total' => (int) Candidate::query()->count(),
            'registered_voters' => $registered,
            'votes_cast' => $votesCast,
            'progress_pct' => $progressPct,
        ];

        // Top wilayah (real, based on votes_cast from summaries; top party derived from TPS)
        $topRegions = [];
        if ($yearModel && $areaIds->isNotEmpty()) {
            $summaries = AreaElectionSummary::query()
                ->where('election_year_id', $yearModel->id)
                ->whereIn('area_id', $areaIds)
                ->get(['area_id', 'registered_voters', 'votes_cast']);
            $summaryByAreaId = $summaries->keyBy('area_id');

            foreach ($areas as $area) {
                $s = $summaryByAreaId->get($area->id);
                $areaVotes = (int) ($s?->votes_cast ?? 0);

                $topParty = null;
                $partyRows = $voteAgg->computePartyResultsFromTpsForArea($yearModel->id, $area->id, 1);
                if (!empty($partyRows)) {
                    $topParty = (string) ($partyRows[0]['party_name'] ?? '—');
                }

                $topRegions[] = [
                    'area_id' => (int) $area->id,
                    'area_name' => (string) $area->name,
                    'votes_cast' => $areaVotes,
                    'top_party' => $topParty,
                ];
            }

            usort($topRegions, fn ($a, $b) => ($b['votes_cast'] <=> $a['votes_cast']) ?: strcmp($a['area_name'], $b['area_name']));
        }

        $topPartyChips = collect($topRegions)
            ->map(fn ($r) => (string) ($r['top_party'] ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->take(4)
            ->all();

        return view('user.dashboard', compact('kpi', 'partyChart', 'recentCandidates', 'topRegions', 'topPartyChips'));
    }
}
