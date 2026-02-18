<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\ElectionYear;
use App\Models\Party;
use App\Models\PollingStation;
use App\Models\TpsCandidateVote;
use App\Models\Area;
use App\Models\Village;
use App\Models\VillageVoteSummary;
use Illuminate\Database\Seeder;

class TpsDummySeeder extends Seeder
{
    public function run(): void
    {
        $year = ElectionYear::firstOrCreate(['year' => 2024]);

        $parties = Party::query()->orderBy('code')->get();
        if ($parties->count() === 0) {
            return;
        }

        $areasByName = Area::query()->pluck('id', 'name')->all();

        $villages = Village::query()
            ->join('subdistricts', 'subdistricts.id', '=', 'villages.subdistrict_id')
            ->get([
                'villages.id as id',
                'subdistricts.regency_name as regency_name',
            ]);

        foreach ($villages as $village) {
            $regencyName = (string) ($village->regency_name ?? '');
            $areaId = $areasByName[$regencyName] ?? null;
            if (!$areaId) {
                continue;
            }

            // Buat caleg dummy khusus kabupaten ini (3 caleg per partai)
            $candidates = collect();
            foreach ($parties as $party) {
                $partyLabel = $party->code ?? ('P' . $party->id);
                for ($k = 1; $k <= 3; $k++) {
                    $name = 'Calon ' . $partyLabel . ' ' . $regencyName . ' #' . $k;

                    $candidate = Candidate::query()->firstOrCreate(
                        ['party_id' => $party->id, 'area_id' => $areaId, 'name' => $name],
                        ['party_id' => $party->id, 'area_id' => $areaId, 'name' => $name, 'number' => $k]
                    );

                    $candidates->push($candidate);
                }
            }

            // 5 TPS per desa
            $tpsList = [];
            for ($i = 1; $i <= 5; $i++) {
                $code = 'TPS' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);

                $tps = PollingStation::query()->updateOrCreate(
                    ['village_id' => $village->id, 'code' => $code],
                    ['village_id' => $village->id, 'code' => $code, 'sort_order' => $i]
                );

                $tpsList[] = $tps;
            }

            $villageTotal = 0;

            foreach ($tpsList as $tps) {
                foreach ($candidates as $candidate) {
                    $votes = rand(0, 120);

                    TpsCandidateVote::query()->updateOrCreate(
                        [
                            'election_year_id' => $year->id,
                            'polling_station_id' => $tps->id,
                            'candidate_id' => $candidate->id,
                        ],
                        ['votes' => $votes]
                    );

                    $villageTotal += (int) $votes;
                }
            }

            // Sinkronkan rekap "Suara Masuk" desa dari total TPS
            VillageVoteSummary::query()->updateOrCreate(
                ['election_year_id' => $year->id, 'village_id' => $village->id],
                ['votes_cast' => $villageTotal]
            );
        }
    }
}
