<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElectionYear;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Models\VillageVoteSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VillageVoteSummaryController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->query('year') ?? 2024);
        $subdistrictId = $request->query('subdistrict_id');
        $villageId = $request->query('village_id');

        $electionYear = ElectionYear::query()->where('year', $year)->first();
        if (!$electionYear) {
            $electionYear = ElectionYear::query()->orderBy('year', 'desc')->first();
        }

        $targetRegencies = [
            'Kabupaten Karanganyar',
            'Kabupaten Sragen',
            'Kabupaten Wonogiri',
        ];

        $subdistricts = Subdistrict::query()
            ->whereIn('regency_name', $targetRegencies)
            ->orderBy('regency_name')
            ->orderBy('name')
            ->get(['id', 'regency_name', 'name']);

        $villages = collect();
        if ($subdistrictId) {
            $villages = Village::query()
                ->where('subdistrict_id', $subdistrictId)
                ->orderBy('name')
                ->get(['id', 'subdistrict_id', 'name']);
        }

        $rows = Village::query()
            ->join('subdistricts', 'subdistricts.id', '=', 'villages.subdistrict_id')
            ->leftJoin('village_vote_summaries as vvs', function ($join) use ($electionYear) {
                if ($electionYear) {
                    $join->on('vvs.village_id', '=', 'villages.id')
                        ->where('vvs.election_year_id', '=', $electionYear->id);
                } else {
                    $join->on('vvs.village_id', '=', 'villages.id');
                }
            })
            ->whereIn('subdistricts.regency_name', $targetRegencies)
            ->when($subdistrictId, fn ($q) => $q->where('villages.subdistrict_id', $subdistrictId))
            ->when($villageId, fn ($q) => $q->where('villages.id', $villageId))
            ->orderBy('subdistricts.regency_name')
            ->orderBy('subdistricts.name')
            ->orderBy('villages.name')
            ->select([
                DB::raw('COALESCE(vvs.id, 0) as vote_id'),
                'villages.id as village_id',
                'villages.name as village_name',
                'subdistricts.id as subdistrict_id',
                'subdistricts.name as subdistrict_name',
                'subdistricts.regency_name as regency_name',
                DB::raw('COALESCE(vvs.votes_cast, 0) as votes_cast'),
            ])
            ->paginate(20)
            ->withQueryString();

        return view('admin/village-votes/index', [
            'year' => $electionYear?->year ?? $year,
            'years' => ElectionYear::query()->orderBy('year', 'desc')->get(['id', 'year']),
            'subdistricts' => $subdistricts,
            'villages' => $villages,
            'subdistrictId' => $subdistrictId,
            'villageId' => $villageId,
            'rows' => $rows,
        ]);
    }

    public function create(Request $request)
    {
        $year = (int) ($request->query('year') ?? 2024);
        $targetRegencies = [
            'Kabupaten Karanganyar',
            'Kabupaten Sragen',
            'Kabupaten Wonogiri',
        ];

        return view('admin/village-votes/create', [
            'year' => $year,
            'years' => ElectionYear::query()->orderBy('year', 'desc')->get(['id', 'year']),
            'subdistricts' => Subdistrict::query()
                ->whereIn('regency_name', $targetRegencies)
                ->orderBy('regency_name')
                ->orderBy('name')
                ->get(['id', 'regency_name', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'election_year_id' => ['required', 'exists:election_years,id'],
            'village_id' => ['required', 'exists:villages,id'],
            'votes_cast' => ['required', 'integer', 'min:0'],
        ]);

        VillageVoteSummary::updateOrCreate(
            [
                'election_year_id' => $validated['election_year_id'],
                'village_id' => $validated['village_id'],
            ],
            [
                'votes_cast' => $validated['votes_cast'],
            ]
        );

        return redirect()
            ->route('admin.village-votes.index', ['year' => ElectionYear::find($validated['election_year_id'])?->year])
            ->with('status', 'Suara masuk desa berhasil disimpan.');
    }

    public function edit(VillageVoteSummary $villageVote)
    {
        $village = Village::query()
            ->join('subdistricts', 'subdistricts.id', '=', 'villages.subdistrict_id')
            ->where('villages.id', $villageVote->village_id)
            ->select([
                'villages.id as id',
                'villages.name as village_name',
                'subdistricts.name as subdistrict_name',
                'subdistricts.regency_name as regency_name',
            ])
            ->first();

        $year = ElectionYear::find($villageVote->election_year_id);

        return view('admin/village-votes/edit', [
            'villageVote' => $villageVote,
            'year' => $year,
            'village' => $village,
        ]);
    }

    public function update(Request $request, VillageVoteSummary $villageVote)
    {
        $validated = $request->validate([
            'votes_cast' => ['required', 'integer', 'min:0'],
        ]);

        $villageVote->update([
            'votes_cast' => $validated['votes_cast'],
        ]);

        $year = ElectionYear::find($villageVote->election_year_id);

        return redirect()
            ->route('admin.village-votes.index', ['year' => $year?->year])
            ->with('status', 'Suara masuk desa berhasil diperbarui.');
    }

    public function destroy(VillageVoteSummary $villageVote)
    {
        $year = ElectionYear::find($villageVote->election_year_id);
        $villageVote->delete();

        return redirect()
            ->route('admin.village-votes.index', ['year' => $year?->year])
            ->with('status', 'Data suara desa dihapus.');
    }
}
