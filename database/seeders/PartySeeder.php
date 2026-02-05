<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Party;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $parties = [
            'PDI Perjuangan',
            'Gerindra',
            'Golkar',
            'PKB',
            'NasDem',
            'PKS',
            'PAN',
        ];

        foreach ($parties as $i => $name) {
            $code = 'P' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT); // P01, P02, ...

            Party::updateOrCreate(
                ['code' => $code],
                ['code' => $code, 'name' => $name]
            );
        }
    }
}
