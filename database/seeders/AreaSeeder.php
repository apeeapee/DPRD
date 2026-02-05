<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use Illuminate\Support\Str;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Kota Semarang', 'type' => 'KOTA'],
            ['name' => 'Kota Surakarta', 'type' => 'KOTA'],
            ['name' => 'Kota Salatiga', 'type' => 'KOTA'],
            ['name' => 'Kota Pekalongan', 'type' => 'KOTA'],
            ['name' => 'Kota Tegal', 'type' => 'KOTA'],
            ['name' => 'Kota Magelang', 'type' => 'KOTA'],

            ['name' => 'Kabupaten Semarang', 'type' => 'KAB'],
            ['name' => 'Kabupaten Kendal', 'type' => 'KAB'],
            ['name' => 'Kabupaten Demak', 'type' => 'KAB'],
            ['name' => 'Kabupaten Grobogan', 'type' => 'KAB'],
            ['name' => 'Kabupaten Kudus', 'type' => 'KAB'],
            ['name' => 'Kabupaten Jepara', 'type' => 'KAB'],
            ['name' => 'Kabupaten Pati', 'type' => 'KAB'],
            ['name' => 'Kabupaten Rembang', 'type' => 'KAB'],
            ['name' => 'Kabupaten Blora', 'type' => 'KAB'],

            ['name' => 'Kabupaten Boyolali', 'type' => 'KAB'],
            ['name' => 'Kabupaten Klaten', 'type' => 'KAB'],
            ['name' => 'Kabupaten Sukoharjo', 'type' => 'KAB'],
            ['name' => 'Kabupaten Karanganyar', 'type' => 'KAB'],
            ['name' => 'Kabupaten Wonogiri', 'type' => 'KAB'],
            ['name' => 'Kabupaten Sragen', 'type' => 'KAB'],

            ['name' => 'Kabupaten Magelang', 'type' => 'KAB'],
            ['name' => 'Kabupaten Temanggung', 'type' => 'KAB'],
            ['name' => 'Kabupaten Wonosobo', 'type' => 'KAB'],
            ['name' => 'Kabupaten Purworejo', 'type' => 'KAB'],
            ['name' => 'Kabupaten Kebumen', 'type' => 'KAB'],

            ['name' => 'Kabupaten Banyumas', 'type' => 'KAB'],
            ['name' => 'Kabupaten Cilacap', 'type' => 'KAB'],
            ['name' => 'Kabupaten Purbalingga', 'type' => 'KAB'],
            ['name' => 'Kabupaten Banjarnegara', 'type' => 'KAB'],

            ['name' => 'Kabupaten Pekalongan', 'type' => 'KAB'],
            ['name' => 'Kabupaten Batang', 'type' => 'KAB'],
            ['name' => 'Kabupaten Pemalang', 'type' => 'KAB'],
            ['name' => 'Kabupaten Tegal', 'type' => 'KAB'],
            ['name' => 'Kabupaten Brebes', 'type' => 'KAB'],
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
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]
            );
        }
    }
}
