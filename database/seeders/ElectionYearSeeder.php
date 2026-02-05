<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ElectionYear;

class ElectionYearSeeder extends Seeder
{
    public function run(): void
    {
        ElectionYear::updateOrCreate(
            ['year' => 2024],
            []
        );
    }
}
