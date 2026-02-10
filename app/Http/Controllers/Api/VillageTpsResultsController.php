<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ElectionYear;
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

        // votes rows
        $rows = DB::table('tps_candidate_votes as tcv')
            ->join('polling_stations as ps', 'ps.id', '=', 'tcv.polling_station_id')
            ->join('candidates as c', 'c.id', '=', 'tcv.candidate_id')
            ->join('parties as p', 'p.id', '=', 'c.party_id')
            ->where('tcv.election_year_id', $electionYear->id)
            ->where('ps.village_id', $villageId)
            ->select([
                'p.id as party_id',
                'p.name as party_name',
                'c.id as candidate_id',
                'c.name as candidate_name',
                'ps.id as tps_id',
                'ps.code as tps_code',
                'tcv.votes as votes',
            ])
            ->orderBy('p.name')
            ->orderBy('c.name')
            ->get();

        $tpsIds = $tps->pluck('id')->all();

        $parties = [];
        $partyIndex = [];

        $grandTotalsByTps = array_fill_keys($tpsIds, 0);
        $grandTotal = 0;

        foreach ($rows as $r) {
            $pid = (int) $r->party_id;
            $cid = (int) $r->candidate_id;
            $tid = (int) $r->tps_id;
            $v = (int) $r->votes;

            if (!isset($partyIndex[$pid])) {
                $partyIndex[$pid] = count($parties);
                $parties[] = [
                    'party_id' => $pid,
                    'party_name' => $r->party_name,
                    'candidates' => [],
                    'totals_by_tps' => array_fill_keys($tpsIds, 0),
                    'total' => 0,
                ];
            }

            $pi = $partyIndex[$pid];

            if (!isset($parties[$pi]['candidates'][$cid])) {
                $parties[$pi]['candidates'][$cid] = [
                    'candidate_id' => $cid,
                    'candidate_name' => $r->candidate_name,
                    'votes_by_tps' => array_fill_keys($tpsIds, 0),
                    'total' => 0,
                ];
            }

            $parties[$pi]['candidates'][$cid]['votes_by_tps'][$tid] += $v;
            $parties[$pi]['candidates'][$cid]['total'] += $v;

            $parties[$pi]['totals_by_tps'][$tid] += $v;
            $parties[$pi]['total'] += $v;

            $grandTotalsByTps[$tid] += $v;
            $grandTotal += $v;
        }

        // convert candidates dict to list
        foreach ($parties as &$p) {
            $p['candidates'] = array_values($p['candidates']);
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
