<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subdistrict;
use Illuminate\Http\Request;

class SubdistrictController extends Controller
{
    public function index(Request $request)
    {
        $regencies = $request->query('regencies');

        if (is_string($regencies)) {
            $regencies = array_filter(array_map('trim', explode(',', $regencies)));
        }

        if (!is_array($regencies) || count($regencies) === 0) {
            $regencies = [
                'Kabupaten Karanganyar',
                'Kabupaten Sragen',
                'Kabupaten Wonogiri',
            ];
        }

        $items = Subdistrict::query()
            ->whereIn('regency_name', $regencies)
            ->orderBy('regency_name')
            ->orderBy('name')
            ->get(['id', 'regency_name', 'name']);

        return response()->json([
            'count' => $items->count(),
            'data' => $items,
        ]);
    }
}
