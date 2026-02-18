<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElectionYear;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Models\VillageVoteSummary;
use Illuminate\Http\Request;
use App\Services\VoteAggregationService;

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

        return view('admin/village-votes/index', [
            'year' => $electionYear?->year ?? $year,
            'yearRow' => $electionYear,
            'years' => ElectionYear::query()->orderBy('year', 'desc')->get(['id', 'year']),
            'subdistricts' => $subdistricts,
            'villages' => $villages,
            'subdistrictId' => $subdistrictId,
            'villageId' => $villageId,
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

        app(VoteAggregationService::class)
            ->syncVillageAndAreaFromVillageId((int) $validated['election_year_id'], (int) $validated['village_id']);

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

        app(VoteAggregationService::class)
            ->syncVillageAndAreaFromVillageId((int) $villageVote->election_year_id, (int) $villageVote->village_id);

        $year = ElectionYear::find($villageVote->election_year_id);

        return redirect()
            ->route('admin.village-votes.index', ['year' => $year?->year])
            ->with('status', 'Suara masuk desa berhasil diperbarui.');
    }

    public function destroy(VillageVoteSummary $villageVote)
    {
        $year = ElectionYear::find($villageVote->election_year_id);
        $electionYearId = (int) $villageVote->election_year_id;
        $villageId = (int) $villageVote->village_id;
        $villageVote->delete();

        app(VoteAggregationService::class)
            ->syncVillageAndAreaFromVillageId($electionYearId, $villageId);

        return redirect()
            ->route('admin.village-votes.index', ['year' => $year?->year])
            ->with('status', 'Data suara desa dihapus.');
    }
}
