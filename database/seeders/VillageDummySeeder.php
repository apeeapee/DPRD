<?php

namespace Database\Seeders;

use App\Models\ElectionYear;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Models\VillageVoteSummary;
use Illuminate\Database\Seeder;

class VillageDummySeeder extends Seeder
{
    public function run(): void
    {
        $year = ElectionYear::firstOrCreate(['year' => 2024]);

        $structure = [
            'Kabupaten Sragen' => [
                'Sragen' => ['Kroyo', 'Kedawung', 'Karangmalang'],
                'Gemolong' => ['Gemolong', 'Jatibatur', 'Kragilan'],
            ],
            'Kabupaten Karanganyar' => [
                'Karanganyar' => ['Bejen', 'Jantiharjo', 'Popongan'],
                'Tawangmangu' => ['Tawangmangu', 'Kalisoro', 'Gondosuli'],
            ],
            'Kabupaten Wonogiri' => [
                'Wonogiri' => ['Girisuko', 'Giritontro', 'Purwantoro'],
                'Pracimantoro' => ['Pracimantoro', 'Sambiroto', 'Watangrejo'],
            ],
        ];

        foreach ($structure as $regencyName => $subdistricts) {
            foreach ($subdistricts as $subdistrictName => $villages) {
                $subdistrict = Subdistrict::firstOrCreate(
                    ['regency_name' => $regencyName, 'name' => $subdistrictName],
                    ['regency_name' => $regencyName, 'name' => $subdistrictName]
                );

                foreach ($villages as $villageName) {
                    $village = Village::firstOrCreate(
                        ['subdistrict_id' => $subdistrict->id, 'name' => $villageName],
                        ['subdistrict_id' => $subdistrict->id, 'name' => $villageName]
                    );

                    // Dummy votes
                    $votesCast = rand(200, 3500);

                    VillageVoteSummary::updateOrCreate(
                        ['election_year_id' => $year->id, 'village_id' => $village->id],
                        ['votes_cast' => $votesCast]
                    );
                }
            }
        }
    }
}
