<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Candidate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PruneToThreeRegenciesSeeder extends Seeder
{
    public function run(): void
    {
        $allowedAreaNames = [
            'Kabupaten Sragen',
            'Kabupaten Karanganyar',
            'Kabupaten Wonogiri',
        ];

        DB::transaction(function () use ($allowedAreaNames) {
            $allowedAreaIds = Area::query()
                ->whereIn('name', $allowedAreaNames)
                ->pluck('id')
                ->values();

            // Hapus semua calon di luar 3 kabupaten (termasuk yang area_id NULL)
            Candidate::query()
                ->whereNull('area_id')
                ->orWhereNotIn('area_id', $allowedAreaIds)
                ->delete();

            // Hapus semua area selain 3 kabupaten (akan cascade ke hasil rekap area_*)
            Area::query()
                ->whereNotIn('id', $allowedAreaIds)
                ->delete();
        });
    }
}
