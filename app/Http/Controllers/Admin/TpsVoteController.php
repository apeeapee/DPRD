<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\PollingStation;
use App\Models\Subdistrict;
use App\Models\TpsCandidateVote;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\VoteAggregationService;

class TpsVoteController extends Controller
{
    private array $targetRegencies = [
        'Kabupaten Karanganyar',
        'Kabupaten Sragen',
        'Kabupaten Wonogiri',
    ];

    public function index(Request $request)
    {
        $year = (int) ($request->query('year') ?? 2024);
        $subdistrictId = $request->query('subdistrict_id');
        $villageId = $request->query('village_id');

        $electionYear = ElectionYear::query()->where('year', $year)->first();
        if (!$electionYear) {
            $electionYear = ElectionYear::query()->orderBy('year', 'desc')->first();
        }

        $subdistricts = Subdistrict::query()
            ->whereIn('regency_name', $this->targetRegencies)
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

        $tps = collect();
        $totalsByTps = collect();

        if ($villageId) {
            $tps = PollingStation::query()
                ->where('village_id', $villageId)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get(['id', 'village_id', 'code', 'sort_order']);

            if ($electionYear && $tps->isNotEmpty()) {
                $totalsByTps = TpsCandidateVote::query()
                    ->select([
                        'polling_station_id',
                        DB::raw('SUM(votes) as total_votes'),
                        DB::raw('COUNT(*) as rows_count'),
                    ])
                    ->where('election_year_id', $electionYear->id)
                    ->whereIn('polling_station_id', $tps->pluck('id')->all())
                    ->groupBy('polling_station_id')
                    ->get()
                    ->keyBy('polling_station_id');
            }
        }

        return view('admin/tps-votes/index', [
            'year' => $electionYear?->year ?? $year,
            'years' => ElectionYear::query()->orderBy('year', 'desc')->get(['id', 'year']),
            'subdistricts' => $subdistricts,
            'villages' => $villages,
            'subdistrictId' => $subdistrictId,
            'villageId' => $villageId,
            'tps' => $tps,
            'totalsByTps' => $totalsByTps,
        ]);
    }

    public function create(Request $request)
    {
        $year = (int) ($request->query('year') ?? 2024);
        $subdistrictId = $request->query('subdistrict_id');
        $villageId = $request->query('village_id');

        $electionYear = ElectionYear::query()->where('year', $year)->first();
        if (!$electionYear) {
            $electionYear = ElectionYear::query()->orderBy('year', 'desc')->first();
        }

        $subdistricts = Subdistrict::query()
            ->whereIn('regency_name', $this->targetRegencies)
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

        $area = $villageId ? $this->resolveAreaForVillageId((int) $villageId) : null;

        $candidatesQuery = Candidate::query()
            ->with(['party:id,code,name'])
            ->orderBy('party_id')
            ->orderByRaw('COALESCE(number, 999999) asc')
            ->orderBy('name');

        if ($area) {
            $candidatesQuery->where('area_id', $area->id);
        } else {
            $candidatesQuery->whereRaw('1=0');
        }

        $candidates = $candidatesQuery->get(['id', 'party_id', 'area_id', 'name', 'number']);

        return view('admin/tps-votes/create', [
            'year' => $electionYear?->year ?? $year,
            'years' => ElectionYear::query()->orderBy('year', 'desc')->get(['id', 'year']),
            'subdistricts' => $subdistricts,
            'villages' => $villages,
            'subdistrictId' => $subdistrictId,
            'villageId' => $villageId,
            'area' => $area,
            'candidates' => $candidates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'election_year_id' => ['required', 'exists:election_years,id'],
            'village_id' => ['required', 'exists:villages,id'],
            'tps_code' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'votes' => ['nullable', 'array'],
            'votes.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $area = $this->resolveAreaForVillageId((int) $validated['village_id']);
        if (!$area) {
            return back()
                ->withErrors(['village_id' => 'Kabupaten untuk desa ini tidak ditemukan di data Area.'])
                ->withInput();
        }

        $pollingStation = PollingStation::firstOrCreate(
            [
                'village_id' => $validated['village_id'],
                'code' => $validated['tps_code'],
            ],
            [
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]
        );

        if (array_key_exists('sort_order', $validated) && $validated['sort_order'] !== null) {
            $pollingStation->update(['sort_order' => (int) $validated['sort_order']]);
        }

        $allowedCandidateIds = Candidate::query()
            ->where('area_id', $area->id)
            ->pluck('id')
            ->all();

        $this->upsertVotes(
            (int) $validated['election_year_id'],
            (int) $pollingStation->id,
            (array) ($validated['votes'] ?? []),
            $allowedCandidateIds
        );

        app(VoteAggregationService::class)
            ->syncVillageAndAreaFromVillageId((int) $validated['election_year_id'], (int) $validated['village_id']);

        $year = ElectionYear::find($validated['election_year_id']);

        return redirect()
            ->route('admin.tps-votes.index', [
                'year' => $year?->year,
                'village_id' => $validated['village_id'],
            ])
            ->with('status', 'Suara TPS berhasil disimpan.');
    }

    public function edit(Request $request, PollingStation $pollingStation)
    {
        $year = (int) ($request->query('year') ?? 2024);
        $electionYear = ElectionYear::query()->where('year', $year)->first();
        if (!$electionYear) {
            $electionYear = ElectionYear::query()->orderBy('year', 'desc')->first();
        }

        $village = Village::query()
            ->join('subdistricts', 'subdistricts.id', '=', 'villages.subdistrict_id')
            ->where('villages.id', $pollingStation->village_id)
            ->select([
                'villages.id as id',
                'villages.name as village_name',
                'subdistricts.id as subdistrict_id',
                'subdistricts.name as subdistrict_name',
                'subdistricts.regency_name as regency_name',
            ])
            ->first();

        $area = $pollingStation->village_id ? $this->resolveAreaForVillageId((int) $pollingStation->village_id) : null;

        $candidatesQuery = Candidate::query()
            ->with(['party:id,code,name'])
            ->orderBy('party_id')
            ->orderByRaw('COALESCE(number, 999999) asc')
            ->orderBy('name');

        if ($area) {
            $candidatesQuery->where('area_id', $area->id);
        } else {
            $candidatesQuery->whereRaw('1=0');
        }

        $candidates = $candidatesQuery->get(['id', 'party_id', 'area_id', 'name', 'number']);

        $votes = [];
        if ($electionYear) {
            $votes = TpsCandidateVote::query()
                ->where('election_year_id', $electionYear->id)
                ->where('polling_station_id', $pollingStation->id)
                ->pluck('votes', 'candidate_id')
                ->all();
        }

        return view('admin/tps-votes/edit', [
            'year' => $electionYear?->year ?? $year,
            'yearRow' => $electionYear,
            'years' => ElectionYear::query()->orderBy('year', 'desc')->get(['id', 'year']),
            'pollingStation' => $pollingStation,
            'village' => $village,
            'area' => $area,
            'candidates' => $candidates,
            'votes' => $votes,
        ]);
    }

    public function update(Request $request, PollingStation $pollingStation)
    {
        $validated = $request->validate([
            'election_year_id' => ['required', 'exists:election_years,id'],
            'votes' => ['nullable', 'array'],
            'votes.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $area = $pollingStation->village_id ? $this->resolveAreaForVillageId((int) $pollingStation->village_id) : null;
        if (!$area) {
            return back()->withErrors(['election_year_id' => 'Kabupaten untuk TPS ini tidak ditemukan di data Area.']);
        }

        $allowedCandidateIds = Candidate::query()
            ->where('area_id', $area->id)
            ->pluck('id')
            ->all();

        $this->upsertVotes(
            (int) $validated['election_year_id'],
            (int) $pollingStation->id,
            (array) ($validated['votes'] ?? []),
            $allowedCandidateIds
        );

        app(VoteAggregationService::class)
            ->syncVillageAndAreaFromVillageId((int) $validated['election_year_id'], (int) $pollingStation->village_id);

        $year = ElectionYear::find($validated['election_year_id']);

        return redirect()
            ->route('admin.tps-votes.edit', [$pollingStation, 'year' => $year?->year])
            ->with('status', 'Suara TPS berhasil diperbarui.');
    }

    public function destroy(Request $request, PollingStation $pollingStation)
    {
        $year = (int) ($request->query('year') ?? 2024);
        $electionYear = ElectionYear::query()->where('year', $year)->first();

        $villageId = (int) $pollingStation->village_id;
        $pollingStation->delete();

        if ($electionYear) {
            app(VoteAggregationService::class)
                ->syncVillageAndAreaFromVillageId((int) $electionYear->id, $villageId);
        }

        return redirect()
            ->route('admin.tps-votes.index')
            ->with('status', 'TPS dihapus.');
    }

    private function upsertVotes(int $electionYearId, int $pollingStationId, array $votesByCandidateId, array $allowedCandidateIds): void
    {
        $allowedCandidateIds = array_values(array_unique(array_map('intval', $allowedCandidateIds)));
        $validCandidateIdSet = array_fill_keys(array_map(fn ($id) => (string) $id, $allowedCandidateIds), true);

        // Clean up any stray votes for candidates outside allowed set
        if (!empty($allowedCandidateIds)) {
            TpsCandidateVote::query()
                ->where('election_year_id', $electionYearId)
                ->where('polling_station_id', $pollingStationId)
                ->whereNotIn('candidate_id', $allowedCandidateIds)
                ->delete();
        }

        foreach ($votesByCandidateId as $candidateId => $votes) {
            $candidateId = (string) $candidateId;
            if (!isset($validCandidateIdSet[$candidateId])) {
                continue;
            }

            $votesInt = (int) ($votes ?? 0);

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

    private function resolveAreaForVillageId(int $villageId): ?Area
    {
        $regencyName = Subdistrict::query()
            ->join('villages', 'villages.subdistrict_id', '=', 'subdistricts.id')
            ->where('villages.id', $villageId)
            ->value('subdistricts.regency_name');

        if (!$regencyName) {
            return null;
        }

        return Area::query()->where('name', $regencyName)->first(['id', 'name']);
    }
}
