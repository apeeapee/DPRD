<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ElectionYear;
use App\Models\Area;
use App\Models\Party;
use App\Models\Candidate;
use App\Models\AreaElectionSummary;
use App\Models\AreaPartyResult;
use App\Models\AreaCandidateResult;
use Illuminate\Support\Str;

class DummyResultsSeeder extends Seeder
{
    public function run(): void
    {
        $year = ElectionYear::where('year', 2024)->firstOrFail();
        $areas = Area::all();
        $parties = Party::all();

        foreach ($areas as $area) {
            // Summary
            $registered = rand(200_000, 1_500_000);
            $votesCast = (int) round($registered * (rand(60, 85) / 100));

            AreaElectionSummary::updateOrCreate(
                ['area_id' => $area->id, 'election_year_id' => $year->id],
                ['registered_voters' => $registered, 'votes_cast' => $votesCast]
            );

            // Distribusi suara per partai (total = votesCast)
            $weights = [];
            $sumW = 0;
            foreach ($parties as $party) {
                $w = rand(5, 30);
                $weights[$party->id] = $w;
                $sumW += $w;
            }

            $partyVotes = [];
            $remaining = $votesCast;

            $partyIds = $parties->pluck('id')->values();
            foreach ($partyIds as $i => $pid) {
                if ($i === $partyIds->count() - 1) {
                    $partyVotes[$pid] = $remaining;
                } else {
                    $v = (int) floor(($weights[$pid] / $sumW) * $votesCast);
                    $v = max(0, min($v, $remaining));
                    $partyVotes[$pid] = $v;
                    $remaining -= $v;
                }
            }

            // Simpan party results + candidate results (3 calon per partai per area)
            foreach ($parties as $party) {
                $pv = (int) ($partyVotes[$party->id] ?? 0);

                AreaPartyResult::updateOrCreate(
                    ['area_id' => $area->id, 'election_year_id' => $year->id, 'party_id' => $party->id],
                    ['votes' => $pv]
                );

                // 3 calon per partai per area
                $candidateIds = [];
                for ($k = 1; $k <= 3; $k++) {
                    $partyLabel = $party->code ?? ('PARTY_' . $party->id);
                    $name = "Calon {$partyLabel} {$area->name} #{$k}";

                    // ⚠️ Asumsi kolom candidates minimal: name, party_id
                    // Kalau candidates kamu punya kolom lain wajib (mis. area_id), tambahin di sini.
                    $candidate = Candidate::updateOrCreate(
                        ['name' => $name, 'party_id' => $party->id],
                        ['name' => $name, 'party_id' => $party->id]
                    );

                    $candidateIds[] = $candidate->id;
                }

                // Bagi suara partai ke 3 calon
                $c1 = (int) floor($pv * (rand(25, 45) / 100));
                $c2 = (int) floor($pv * (rand(20, 40) / 100));
                $c3 = max(0, $pv - $c1 - $c2);
                $split = [$c1, $c2, $c3];

                foreach ($candidateIds as $idx => $cid) {
                    AreaCandidateResult::updateOrCreate(
                        [
                            'area_id' => $area->id,
                            'election_year_id' => $year->id,
                            'candidate_id' => $cid,
                        ],
                        ['votes' => (int)$split[$idx]]
                    );
                }
            }
        }
    }
}
