<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\PollingStation;
use App\Models\Subdistrict;
use App\Models\Party;
use App\Models\TpsCandidateVote;
use App\Models\Village;
use App\Services\VoteAggregationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TpsVotesImportController extends Controller
{
    public function index(Request $request)
    {
        $years = ElectionYear::query()->orderByDesc('year')->get();
        $areas = Area::query()->orderBy('name')->get(['id', 'name', 'type']);
        $subdistricts = Subdistrict::query()->orderBy('name')->get();

        $year = (int) ($request->input('year') ?? ($years->first()?->year ?? 2024));
        $subdistrictId = $request->input('subdistrict_id');
        $areaId = $request->input('area_id');

        return view('admin.import.tps-votes', compact('years', 'year', 'areas', 'areaId', 'subdistricts', 'subdistrictId'));
    }

    public function store(Request $request, VoteAggregationService $agg)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'subdistrict_id' => ['nullable', 'integer', 'exists:subdistricts,id'],
            'subdistrict_name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'create_missing_tps' => ['nullable', 'boolean'],
            'create_missing_subdistrict_villages' => ['nullable', 'boolean'],
            'create_missing_parties_candidates' => ['nullable', 'boolean'],
        ]);

        $year = (int) $validated['year'];
        $electionYear = ElectionYear::query()->where('year', $year)->first();
        if (!$electionYear) {
            return back()->withErrors(['year' => 'Tahun pemilu tidak ditemukan.']);
        }

        $area = Area::query()->find((int) $validated['area_id']);
        if (!$area) {
            return back()->withErrors(['area_id' => 'Kabupaten/Kota tidak ditemukan.']);
        }

        $subdistrictId = isset($validated['subdistrict_id']) ? (int) $validated['subdistrict_id'] : null;
        $createMissingTps = (bool) ($validated['create_missing_tps'] ?? false);
        $createMissingSubdistrictVillages = (bool) ($validated['create_missing_subdistrict_villages'] ?? false);
        $createMissingPartiesCandidates = (bool) ($validated['create_missing_parties_candidates'] ?? false);

        $targetSubdistrict = null;
        if ($subdistrictId) {
            $targetSubdistrict = Subdistrict::query()->find($subdistrictId);

            if ($targetSubdistrict && (string) $targetSubdistrict->regency_name !== (string) $area->name) {
                return back()->withErrors([
                    'subdistrict_id' => 'Kecamatan yang dipilih tidak sesuai dengan Kabupaten/Kota yang dipilih.',
                ]);
            }
        } else {
            $subdistrictName = trim((string) ($validated['subdistrict_name'] ?? ''));
            if ($subdistrictName === '') {
                return back()->withErrors(['subdistrict_name' => 'Isi nama Kecamatan atau pilih Kecamatan yang sudah ada.']);
            }

            $targetSubdistrict = Subdistrict::query()
                ->where('regency_name', (string) $area->name)
                ->where('name', $subdistrictName)
                ->first();

            if (!$targetSubdistrict) {
                if (!$createMissingSubdistrictVillages) {
                    return back()->withErrors(['subdistrict_name' => 'Kecamatan belum ada di sistem. Centang opsi buat otomatis untuk menambahkannya.']);
                }

                $targetSubdistrict = Subdistrict::query()->create([
                    'regency_name' => (string) $area->name,
                    'name' => $subdistrictName,
                ]);
            }

            $subdistrictId = (int) $targetSubdistrict->id;
        }

        if (!$targetSubdistrict) {
            return back()->withErrors(['subdistrict_id' => 'Kecamatan tidak ditemukan.']);
        }

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return back()->withErrors(['file' => 'Upload file gagal. Coba upload ulang.']);
        }

        // Force to local disk so we always have a real filesystem path for PhpSpreadsheet.
        $storedPath = $file->store('imports', 'local');
        if (!$storedPath) {
            return back()->withErrors(['file' => 'Gagal menyimpan file upload.']);
        }

        $fullPath = Storage::disk('local')->path($storedPath);
        if (!is_string($fullPath) || $fullPath === '' || !file_exists($fullPath)) {
            return back()->withErrors(['file' => 'File tersimpan tidak ditemukan di server.']);
        }

        $spreadsheet = IOFactory::load($fullPath);

        $result = [
            'year' => $year,
            'sheets_total' => 0,
            'villages_ok' => 0,
            'villages_failed' => 0,
            'rows_imported' => 0,
            'cells_imported' => 0,
            'tps_created' => 0,
            'parties_created' => 0,
            'candidates_created' => 0,
            'errors' => [],
        ];

        $affectedVillageIds = [];

        $villageRows = Village::query()
            ->where('subdistrict_id', (int) $targetSubdistrict->id)
            ->get(['id', 'name']);

        $villageMap = [];
        foreach ($villageRows as $v) {
            $villageMap[$this->normalizeKey($v->name)] = (int) $v->id;
        }

        $candidateRows = Candidate::query()
            ->where('area_id', $area->id)
            ->get(['id', 'party_id', 'name']);

        $candidateMap = []; // fallback: name-only
        $candidateMapByParty = []; // party_id|name
        foreach ($candidateRows as $c) {
            $nameKey = $this->normalizeKey($c->name);
            $candidateMap[$nameKey] = (int) $c->id;
            $candidateMapByParty[((int) $c->party_id) . '|' . $nameKey] = (int) $c->id;
        }

        $partyRows = Party::query()->get(['id', 'code', 'name']);
        $partyByCode = [];
        $partyByName = [];
        foreach ($partyRows as $p) {
            $partyByCode[$this->normalizePartyCode($p->code)] = (int) $p->id;
            $partyByName[$this->normalizeKey($p->name)] = (int) $p->id;
        }

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $result['sheets_total']++;

            $sheetName = trim((string) $sheet->getTitle());
            if ($sheetName === '') {
                $result['villages_failed']++;
                $result['errors'][] = "Sheet tanpa nama dilewati.";
                continue;
            }

            $villageKey = $this->normalizeKey($sheetName);
            $villageId = $villageMap[$villageKey] ?? null;

            if (!$villageId) {
                if (!$createMissingSubdistrictVillages) {
                    $result['villages_failed']++;
                    $result['errors'][] = "Desa tidak ditemukan untuk sheet: {$sheetName}";
                    continue;
                }

                $village = Village::query()->create([
                    'subdistrict_id' => (int) $targetSubdistrict->id,
                    'name' => $sheetName,
                ]);
                $villageId = (int) $village->id;
                $villageMap[$villageKey] = $villageId;
            }

            $village = Village::query()->find($villageId);
            if (!$village) {
                $result['villages_failed']++;
                $result['errors'][] = "Gagal memuat desa untuk sheet: {$sheetName}";
                continue;
            }

            $tpsRows = PollingStation::query()
                ->where('village_id', $villageId)
                ->get(['id', 'code']);

            $tpsMap = [];
            foreach ($tpsRows as $t) {
                $tpsMap[$this->normalizeKey($t->code)] = (int) $t->id;
            }

            $highestRow = (int) $sheet->getHighestRow();
            $highestCol = (string) $sheet->getHighestColumn();
            $highestColIndex = (int) Coordinate::columnIndexFromString($highestCol);

            [$headerRow, $colParty, $colCandidate, $tpsColumns] = $this->detectMatrixHeader($sheet, $highestRow, $highestColIndex);
            if (!$headerRow || !$colCandidate || empty($tpsColumns)) {
                $result['villages_failed']++;
                $result['errors'][] = "Header tidak dikenali di sheet '{$sheetName}'. Pastikan ada kolom 'Caleg' dan kolom TPS (mis. 'TPS 01').";
                continue;
            }

            $votes = []; // [polling_station_id][candidate_id] => votes
            $missingCandidates = [];
            $missingCandidates = [];
            $missingTpsHeaders = [];

            $lastPartyRaw = '';

            foreach ($tpsColumns as $tpsColIndex => $tpsCode) {
                $tpsKey = $this->normalizeKey($tpsCode);
                if (isset($tpsMap[$tpsKey])) {
                    continue;
                }

                if (!$createMissingTps) {
                    $missingTpsHeaders[$tpsCode] = true;
                    continue;
                }

                $pollingStation = PollingStation::create([
                    'village_id' => $villageId,
                    'code' => $tpsCode,
                    'sort_order' => $this->inferTpsSortOrder($tpsCode),
                ]);
                $tpsMap[$tpsKey] = (int) $pollingStation->id;
                $result['tps_created']++;
            }

            for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                $partyRaw = '';
                $partyIdForRow = null;
                if ($colParty) {
                    $partyRaw = trim((string) $sheet->getCellByColumnAndRow($colParty, $r)->getFormattedValue());
                    if ($partyRaw !== '') {
                        $lastPartyRaw = $partyRaw;
                    } else {
                        $partyRaw = $lastPartyRaw;
                    }

                    [$partyCodeTmp, $partyNameTmp] = $this->extractParty($partyRaw);
                    if ($partyCodeTmp !== '') {
                        $partyIdForRow = $partyByCode[$this->normalizePartyCode($partyCodeTmp)] ?? null;
                    }
                    if (!$partyIdForRow && $partyNameTmp !== '') {
                        $partyIdForRow = $partyByName[$this->normalizeKey($partyNameTmp)] ?? null;
                    }
                }

                $candidateName = trim((string) $sheet->getCellByColumnAndRow($colCandidate, $r)->getFormattedValue());
                if ($candidateName === '') {
                    continue;
                }

                if ($this->shouldSkipCandidateRow($candidateName)) {
                    continue;
                }

                $candidateKey = $this->normalizeKey($candidateName);
                $candidateId = null;
                if ($partyIdForRow) {
                    $candidateId = $candidateMapByParty[((int) $partyIdForRow) . '|' . $candidateKey] ?? null;
                }
                if (!$candidateId) {
                    $candidateId = $candidateMap[$candidateKey] ?? null;
                }
                if (!$candidateId) {
                    if (!$createMissingPartiesCandidates) {
                        $missingCandidates[$candidateName] = true;
                        continue;
                    }

                    // Create party/candidate on the fly (best-effort) so votes can be imported.
                    [$partyCode, $partyName] = $this->extractParty($partyRaw);

                    $partyId = null;
                    if ($partyCode !== '') {
                        $partyId = $partyByCode[$this->normalizePartyCode($partyCode)] ?? null;
                    }
                    if (!$partyId && $partyName !== '') {
                        $partyId = $partyByName[$this->normalizeKey($partyName)] ?? null;
                    }

                    if (!$partyId) {
                        if ($partyCode === '' && $partyName === '') {
                            $missingCandidates[$candidateName] = true;
                            continue;
                        }

                        $codeToCreate = $partyCode !== ''
                            ? $partyCode
                            : strtoupper(substr(preg_replace('/\W+/', '', $partyName) ?? 'PARTAI', 0, 30));

                        $p = Party::query()->firstOrCreate(
                            ['code' => $codeToCreate],
                            ['name' => $partyName !== '' ? $partyName : $codeToCreate]
                        );
                        $partyId = (int) $p->id;
                        $partyByCode[$this->normalizePartyCode((string) $p->code)] = $partyId;
                        $partyByName[$this->normalizeKey((string) $p->name)] = $partyId;
                        // Count as created only when it was actually newly created
                        if ($p->wasRecentlyCreated) {
                            $result['parties_created']++;
                        }
                    }

                    $c = Candidate::query()->create([
                        'party_id' => $partyId,
                        'area_id' => (int) $area->id,
                        'name' => $candidateName,
                        'number' => null,
                    ]);
                    $candidateId = (int) $c->id;
                    $candidateMap[$candidateKey] = $candidateId;
                    $candidateMapByParty[((int) $partyId) . '|' . $candidateKey] = $candidateId;
                    $result['candidates_created']++;
                }

                $rowHadAnyValue = false;

                foreach ($tpsColumns as $tpsColIndex => $tpsCode) {
                    $tpsId = $tpsMap[$this->normalizeKey($tpsCode)] ?? null;
                    if (!$tpsId) {
                        continue;
                    }

                    $raw = $sheet->getCellByColumnAndRow($tpsColIndex, $r)->getCalculatedValue();
                    if ($raw === null || $raw === '') {
                        continue;
                    }

                    if (!is_numeric($raw)) {
                        continue;
                    }

                    $votesInt = (int) $raw;
                    $votes[$tpsId][$candidateId] = $votesInt;
                    $rowHadAnyValue = true;
                }

                if ($rowHadAnyValue) {
                    $result['rows_imported']++;
                }
            }

            if (!empty($missingCandidates)) {
                $names = array_slice(array_keys($missingCandidates), 0, 15);
                $more = count($missingCandidates) > 15 ? ' (dan lainnya)' : '';
                $result['errors'][] = "Sheet '{$sheetName}': calon tidak ditemukan: " . implode(', ', $names) . $more;
            }

            if (!empty($missingTpsHeaders)) {
                $names = array_slice(array_keys($missingTpsHeaders), 0, 20);
                $more = count($missingTpsHeaders) > 20 ? ' (dan lainnya)' : '';
                $result['errors'][] = "Sheet '{$sheetName}': TPS belum ada di sistem: " . implode(', ', $names) . $more;
            }

            $cellsImported = 0;

            DB::transaction(function () use (&$cellsImported, $votes, $electionYear, $candidateRows) {
                $allowedCandidateIds = $candidateRows->pluck('id')->map(fn ($id) => (int) $id)->all();

                foreach ($votes as $pollingStationId => $votesByCandidateId) {
                    $pollingStationId = (int) $pollingStationId;

                    foreach ($votesByCandidateId as $candidateId => $v) {
                        $candidateId = (int) $candidateId;
                        $votesInt = (int) ($v ?? 0);

                        if ($votesInt > 0) {
                            TpsCandidateVote::updateOrCreate(
                                [
                                    'election_year_id' => (int) $electionYear->id,
                                    'polling_station_id' => $pollingStationId,
                                    'candidate_id' => $candidateId,
                                ],
                                [
                                    'votes' => $votesInt,
                                ]
                            );
                            $cellsImported++;
                        } else {
                            TpsCandidateVote::query()
                                ->where('election_year_id', (int) $electionYear->id)
                                ->where('polling_station_id', $pollingStationId)
                                ->where('candidate_id', $candidateId)
                                ->delete();
                        }
                    }
                }
            });

            $result['cells_imported'] += $cellsImported;
            $affectedVillageIds[] = $villageId;

            $agg->syncVillageAndAreaFromVillageId((int) $electionYear->id, $villageId);

            $result['villages_ok']++;
        }

        // Dedup affected villages (just for reporting/readability)
        $affectedVillageIds = array_values(array_unique($affectedVillageIds));

        $request->session()->flash('import_result', $result);

        return redirect()->route('admin.import.tps-votes.index', [
            'year' => $year,
            'subdistrict_id' => $subdistrictId,
            'area_id' => (int) $area->id,
        ]);
    }

    /**
     * Detects header row and TPS columns.
     *
     * Returns: [headerRow, colCandidateIndex, tpsColumns]
     * - colCandidateIndex: 1-based column index
     * - tpsColumns: [colIndex => normalizedTpsCode]
     */
    private function detectMatrixHeader($sheet, int $highestRow, int $highestColIndex): array
    {
        $maxScan = min($highestRow, 25);

        for ($r = 1; $r <= $maxScan; $r++) {
            $row = $sheet->rangeToArray(
                'A' . $r . ':' . Coordinate::stringFromColumnIndex($highestColIndex) . $r,
                null,
                true,
                true,
                false
            );

            $cells = $row[0] ?? [];

            $candidateCol = null;
            $partyCol = null;
            $tpsCols = [];

            foreach ($cells as $idxZero => $val) {
                $colIndex = $idxZero + 1;
                $v = trim((string) $val);
                if ($v === '') {
                    continue;
                }

                if ($this->normalizeKey($v) === $this->normalizeKey('Caleg')) {
                    $candidateCol = $colIndex;
                }

                if (
                    $this->normalizeKey($v) === $this->normalizeKey('Parpol') ||
                    $this->normalizeKey($v) === $this->normalizeKey('Partai')
                ) {
                    $partyCol = $colIndex;
                }

                $tpsCode = $this->normalizeTpsHeader($v);
                if ($tpsCode) {
                    $tpsCols[$colIndex] = $tpsCode;
                }
            }

            if ($candidateCol && !empty($tpsCols)) {
                return [$r, $partyCol, $candidateCol, $tpsCols];
            }
        }

        return [null, null, null, []];
    }

    private function normalizeTpsHeader(string $v): ?string
    {
        $s = trim($v);
        if ($s === '') {
            return null;
        }

        // Accept: "TPS 01", "TPS01", "Tps 1"
        if (!preg_match('/^\s*tps\s*0*(\d{1,4})\s*$/i', $s, $m)) {
            return null;
        }

        $num = (int) $m[1];
        if ($num <= 0) {
            return null;
        }

        $numStr = $num < 100 ? str_pad((string) $num, 2, '0', STR_PAD_LEFT) : (string) $num;

        return 'TPS ' . $numStr;
    }

    private function inferTpsSortOrder(string $tpsCode): int
    {
        if (preg_match('/(\d{1,4})/', $tpsCode, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    private function shouldSkipCandidateRow(string $candidateName): bool
    {
        $key = $this->normalizeKey($candidateName);

        if ($key === $this->normalizeKey('Suara Partai')) {
            return true;
        }

        if (str_contains($key, $this->normalizeKey('total'))) {
            return true;
        }

        return false;
    }

    private function normalizeKey(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[\p{P}\p{S}]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return trim($s);
    }

    private function normalizePartyCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $code = preg_replace('/\s+/', '', $code) ?? $code;
        return $code;
    }

    /**
     * Extracts party code and party name from a raw Excel cell.
     * Examples:
     * - "Partai Kebangkitan Bangsa (PKB)" => ["PKB", "Partai Kebangkitan Bangsa"]
     * - "Gerindra" => ["GERINDRA", "Gerindra"]
     */
    private function extractParty(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['', ''];
        }

        $code = '';
        $name = $raw;

        if (preg_match('/\(([^\)]+)\)\s*$/u', $raw, $m)) {
            $code = strtoupper(trim((string) $m[1]));
            $name = trim((string) preg_replace('/\s*\([^\)]*\)\s*$/u', '', $raw));
        }

        if ($code === '') {
            // Fallback: use the whole text as code-ish (best effort)
            $code = strtoupper(trim((string) preg_replace('/\s+/u', ' ', $raw)));
        }

        $code = substr((string) preg_replace('/[^A-Z0-9\-]/', '', $code), 0, 30);

        return [$code, $name];
    }
}
