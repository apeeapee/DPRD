<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\Subdistrict;
use App\Models\PollingStation;
use App\Models\TpsCandidateVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VillageTpsResultsController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->query('year') ?? 2024);
        $villageId = $request->query('village_id');

        if (!$villageId) {
            return response()->json([
                'message' => 'village_id is required',
                'data' => null,
            ], 422);
        }

        $electionYear = ElectionYear::query()->where('year', $year)->first();
        if (!$electionYear) {
            return response()->json([
                'message' => 'Election year not found',
                'data' => null,
            ], 404);
        }

        $tps = PollingStation::query()
            ->where('village_id', $villageId)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'sort_order']);

        $tpsIds = $tps->pluck('id')->all();

        // Resolve village's regency -> Area, so we can return full candidate list (including zeros)
        $regencyName = Subdistrict::query()
            ->join('villages', 'villages.subdistrict_id', '=', 'subdistricts.id')
            ->where('villages.id', $villageId)
            ->value('subdistricts.regency_name');

        $area = $regencyName ? Area::query()->where('name', $regencyName)->first(['id', 'name']) : null;

        $candidates = collect();
        if ($area) {
            $candidates = Candidate::query()
                ->join('parties as p', 'p.id', '=', 'candidates.party_id')
                ->where('candidates.area_id', $area->id)
                ->select([
                    'p.id as party_id',
                    'p.name as party_name',
                    'candidates.id as candidate_id',
                    'candidates.name as candidate_name',
                    'candidates.number as candidate_number',
                    'candidates.party_id as candidate_party_id',
                ])
                ->orderBy('p.name')
                ->orderByRaw('COALESCE(candidates.number, 999999) asc')
                ->orderBy('candidates.name')
                ->get();
        }

        // votes map: [candidate_id][tps_id] = votes
        $voteRows = DB::table('tps_candidate_votes as tcv')
            ->join('polling_stations as ps', 'ps.id', '=', 'tcv.polling_station_id')
            ->where('tcv.election_year_id', $electionYear->id)
            ->where('ps.village_id', $villageId)
            ->select([
                'tcv.candidate_id as candidate_id',
                'tcv.polling_station_id as tps_id',
                DB::raw('COALESCE(SUM(tcv.votes),0) as votes'),
            ])
            ->groupBy('tcv.candidate_id', 'tcv.polling_station_id')
            ->get();

        $votesMap = [];
        foreach ($voteRows as $vr) {
            $cid = (int) $vr->candidate_id;
            $tid = (int) $vr->tps_id;
            $votesMap[$cid][$tid] = (int) ($vr->votes ?? 0);
        }

        $parties = [];
        $partyIndex = [];
        $grandTotalsByTps = array_fill_keys($tpsIds, 0);
        $grandTotal = 0;

        foreach ($candidates as $c) {
            $pid = (int) $c->party_id;
            $cid = (int) $c->candidate_id;

            if (!isset($partyIndex[$pid])) {
                $partyIndex[$pid] = count($parties);
                $parties[] = [
                    'party_id' => $pid,
                    'party_name' => $c->party_name,
                    'candidates' => [],
                    'totals_by_tps' => array_fill_keys($tpsIds, 0),
                    'total' => 0,
                ];
            }

            $pi = $partyIndex[$pid];

            $votesByTps = array_fill_keys($tpsIds, 0);
            $total = 0;

            foreach ($tpsIds as $tid) {
                $v = (int) ($votesMap[$cid][$tid] ?? 0);
                $votesByTps[$tid] = $v;
                $total += $v;

                $parties[$pi]['totals_by_tps'][$tid] += $v;
                $grandTotalsByTps[$tid] += $v;
            }

            $parties[$pi]['total'] += $total;
            $grandTotal += $total;

            $parties[$pi]['candidates'][] = [
                'candidate_id' => $cid,
                'candidate_name' => (string) $c->candidate_name,
                'votes_by_tps' => $votesByTps,
                'total' => $total,
            ];
        }

        return response()->json([
            'data' => [
                'tps' => $tps,
                'parties' => $parties,
                'grand_totals_by_tps' => $grandTotalsByTps,
                'grand_total' => $grandTotal,
            ],
        ]);
    }
}
