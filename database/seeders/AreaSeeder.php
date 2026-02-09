<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use Illuminate\Support\Str;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        // Koordinat pusat (perkiraan) Kab/Kota Jawa Tengah.
        // Tujuan: marker tampil di lokasi yang sesuai wilayah (bukan random).
        // Sumber: titik pusat kota/kabupaten (approx), aman untuk visualisasi.
        $coords = [
            // KOTA
            'kota semarang' => [-6.9667, 110.4167],
            'kota surakarta' => [-7.5667, 110.8167],
            'kota salatiga' => [-7.3300, 110.5000],
            'kota pekalongan' => [-6.8880, 109.6750],
            'kota tegal' => [-6.8694, 109.1402],
            'kota magelang' => [-7.4698, 110.2177],

            // KABUPATEN
            'kabupaten semarang' => [-7.2000, 110.4400],
            'kabupaten kendal' => [-6.9200, 110.2100],
            'kabupaten demak' => [-6.8900, 110.6400],
            'kabupaten grobogan' => [-7.1000, 110.9100],
            'kabupaten kudus' => [-6.8057, 110.8405],
            'kabupaten jepara' => [-6.5886, 110.6673],
            'kabupaten pati' => [-6.7500, 111.0300],
            'kabupaten rembang' => [-6.7100, 111.3500],
            'kabupaten blora' => [-6.9700, 111.4200],

            'kabupaten boyolali' => [-7.5333, 110.6000],
            'kabupaten klaten' => [-7.7067, 110.6064],
            'kabupaten sukoharjo' => [-7.6800, 110.8300],
            'kabupaten karanganyar' => [-7.6000, 110.9500],
            'kabupaten wonogiri' => [-7.8100, 110.9200],
            'kabupaten sragen' => [-7.4300, 111.0200],

            'kabupaten magelang' => [-7.4800, 110.2200],
            'kabupaten temanggung' => [-7.3167, 110.1747],
            'kabupaten wonosobo' => [-7.3600, 109.9000],
            'kabupaten purworejo' => [-7.7156, 109.9739],
            'kabupaten kebumen' => [-7.6700, 109.6500],

            'kabupaten banyumas' => [-7.4200, 109.2300],
            'kabupaten cilacap' => [-7.7167, 109.0167],
            'kabupaten purbalingga' => [-7.3880, 109.3630],
            'kabupaten banjarnegara' => [-7.3900, 109.6800],

            'kabupaten pekalongan' => [-7.0200, 109.6200],
            'kabupaten batang' => [-6.9200, 109.7300],
            'kabupaten pemalang' => [-6.8900, 109.3800],
            'kabupaten tegal' => [-6.9800, 109.1300],
            'kabupaten brebes' => [-6.8700, 109.0400],
        ];

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
                    'latitude' => $coords[strtolower($a['name'])][0] ?? $latitude,
                    'longitude' => $coords[strtolower($a['name'])][1] ?? $longitude,
                ]
            );
        }
    }
}
