<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use Illuminate\Support\Str;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        // Fokus hanya 3 kabupaten sesuai kebutuhan aplikasi.
        $coords = [
            'kabupaten karanganyar' => [-7.6000, 110.9500],
            'kabupaten wonogiri' => [-7.8100, 110.9200],
            'kabupaten sragen' => [-7.4300, 111.0200],
        ];

        $areas = [
            ['name' => 'Kabupaten Karanganyar', 'type' => 'KAB'],
            ['name' => 'Kabupaten Wonogiri', 'type' => 'KAB'],
            ['name' => 'Kabupaten Sragen', 'type' => 'KAB'],
        ];

        foreach ($areas as $a) {
             $prefix = $a['type'] === 'KOTA' ? 'KOT_' : 'KAB_';

            $base = Str::of($a['name'])
                ->lower()
                ->replace('kota ', '')
                ->replace('kabupaten ', '')
                ->slug('_');

            $base = Str::limit((string)$base, 16, '');
            $code = strtoupper($prefix . $base);

            // Dummy koordinat supaya marker bisa tampil.
            // Sebaran kira-kira area Jawa Tengah (lat: -8..-6, lng: 108.5..111.5)
            $hLat = (int) sprintf('%u', crc32($code . '|lat'));
            $hLng = (int) sprintf('%u', crc32($code . '|lng'));
            $latitude = -8.0 + (($hLat % 2000) / 2000) * 2.0;
            $longitude = 108.5 + (($hLng % 3000) / 3000) * 3.0;

            Area::updateOrCreate(
                ['code' => $code],
                [
                    'code' => $code,
                    'name' => $a['name'],
                    'type' => $a['type'],
                    'latitude' => $coords[strtolower($a['name'])][0] ?? $latitude,
                    'longitude' => $coords[strtolower($a['name'])][1] ?? $longitude,
                ]
            );
        }
    }
}
