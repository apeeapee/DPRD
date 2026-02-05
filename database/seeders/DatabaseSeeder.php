<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ElectionYearSeeder::class,
            AreaSeeder::class,
            PartySeeder::class,
            DummyResultsSeeder::class,
        ]);
    }
}
