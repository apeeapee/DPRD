<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Village;
use Illuminate\Http\Request;

class VillageController extends Controller
{
    public function index(Request $request)
    {
        $subdistrictId = $request->query('subdistrict_id');

        if (!$subdistrictId) {
            return response()->json([
                'count' => 0,
                'data' => [],
                'message' => 'subdistrict_id is required',
            ], 422);
        }

        $items = Village::query()
            ->where('subdistrict_id', $subdistrictId)
            ->orderBy('name')
            ->get(['id', 'subdistrict_id', 'name']);

        return response()->json([
            'count' => $items->count(),
            'data' => $items,
        ]);
    }
}
