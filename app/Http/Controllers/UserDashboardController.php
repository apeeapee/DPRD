<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\AreaCandidateResult;
use App\Models\AreaElectionSummary;
use App\Models\AreaPartyResult;
use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\Party;

class UserDashboardController extends Controller
{
    public function index(Request $request)
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
            $agg = AreaElectionSummary::query()
                ->where('election_year_id', $yearModel->id)
                ->whereIn('area_id', $areaIds)
                ->selectRaw('COALESCE(SUM(registered_voters),0) as registered_voters, COALESCE(SUM(votes_cast),0) as votes_cast')
                ->first();
            $registered = (int) ($agg->registered_voters ?? 0);
            $votesCast = (int) ($agg->votes_cast ?? 0);
        }

        $progressPct = $registered > 0 ? (int) round(($votesCast / $registered) * 100) : 0;
        $progressPct = max(0, min(100, $progressPct));

        // Party distribution (top 10)
        $partyChart = ['labels' => [], 'values' => []];
        if ($yearModel && $areaIds->isNotEmpty()) {
            $partyAgg = AreaPartyResult::query()
                ->where('election_year_id', $yearModel->id)
                ->whereIn('area_id', $areaIds)
                ->selectRaw('party_id, COALESCE(SUM(votes),0) as votes')
                ->groupBy('party_id')
                ->orderByDesc('votes')
                ->limit(10)
                ->get();

            $partyNames = Party::query()
                ->whereIn('id', $partyAgg->pluck('party_id'))
                ->pluck('name', 'id');

            $partyChart['labels'] = $partyAgg->map(fn ($r) => (string) ($partyNames[$r->party_id] ?? '—'))->values()->all();
            $partyChart['values'] = $partyAgg->map(fn ($r) => (int) ($r->votes ?? 0))->values()->all();
        }

        // Recent updates: top candidates (top 8)
        $recentCandidates = [];
        if ($yearModel && $areaIds->isNotEmpty()) {
            $candAgg = AreaCandidateResult::query()
                ->where('election_year_id', $yearModel->id)
                ->whereIn('area_id', $areaIds)
                ->selectRaw('candidate_id, COALESCE(SUM(votes),0) as votes')
                ->groupBy('candidate_id')
                ->orderByDesc('votes')
                ->limit(8)
                ->get();

            $candidates = Candidate::query()
                ->with('party:id,name')
                ->whereIn('id', $candAgg->pluck('candidate_id'))
                ->get(['id', 'name', 'party_id'])
                ->keyBy('id');

            $recentCandidates = $candAgg->map(function ($row) use ($candidates) {
                $c = $candidates->get($row->candidate_id);
                return [
                    'name' => (string) ($c?->name ?? '—'),
                    'party' => (string) ($c?->party?->name ?? '—'),
                    'votes' => (int) ($row->votes ?? 0),
                ];
            })->values()->all();
        }

        $kpi = [
            'areas_total' => (int) $areas->count(),
            'parties_total' => (int) Party::query()->count(),
            'candidates_total' => (int) Candidate::query()->count(),
            'registered_voters' => $registered,
            'votes_cast' => $votesCast,
            'progress_pct' => $progressPct,
        ];

        return view('user.dashboard', compact('kpi', 'partyChart', 'recentCandidates'));
    }
}
