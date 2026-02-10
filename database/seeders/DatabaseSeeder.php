<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ElectionYearSeeder::class,
            AreaSeeder::class,
            PartySeeder::class,
            DummyResultsSeeder::class,
        ]);
    }
}
