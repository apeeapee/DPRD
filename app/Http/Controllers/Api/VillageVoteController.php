<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ElectionYear;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VillageVoteController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->query('year') ?? 2024);
        $subdistrictId = $request->query('subdistrict_id');
        $villageId = $request->query('village_id');

        $electionYear = ElectionYear::query()->where('year', $year)->first();
        if (!$electionYear) {
            return response()->json([
                'count' => 0,
                'data' => [],
                'message' => 'Election year not found',
            ]);
        }

        $targetRegencies = [
            'Kabupaten Karanganyar',
            'Kabupaten Sragen',
            'Kabupaten Wonogiri',
        ];

        $query = Village::query()
            ->join('subdistricts', 'subdistricts.id', '=', 'villages.subdistrict_id')
            ->leftJoin('village_vote_summaries as vvs', function ($join) use ($electionYear) {
                $join->on('vvs.village_id', '=', 'villages.id')
                    ->where('vvs.election_year_id', '=', $electionYear->id);
            })
            ->whereIn('subdistricts.regency_name', $targetRegencies)
            ->when($subdistrictId, fn ($q) => $q->where('villages.subdistrict_id', $subdistrictId))
            ->when($villageId, fn ($q) => $q->where('villages.id', $villageId))
            ->orderBy('subdistricts.regency_name')
            ->orderBy('subdistricts.name')
            ->orderBy('villages.name')
            ->select([
                'villages.id as village_id',
                'villages.name as village_name',
                'subdistricts.id as subdistrict_id',
                'subdistricts.name as subdistrict_name',
                'subdistricts.regency_name as regency_name',
                DB::raw('COALESCE(vvs.votes_cast, 0) as votes_cast'),
            ]);

        $items = $query->get();

        return response()->json([
            'count' => $items->count(),
            'data' => $items,
        ]);
    }
}
