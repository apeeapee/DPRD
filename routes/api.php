<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\SubdistrictController;
use App\Http\Controllers\Api\VillageController;
use App\Http\Controllers\Api\VillageVoteController;

Route::get('/areas', [AreaController::class, 'index']);
Route::get('/areas/{area}', [AreaController::class, 'show']);

Route::get('/subdistricts', [SubdistrictController::class, 'index']);
Route::get('/villages', [VillageController::class, 'index']);
Route::get('/village-votes', [VillageVoteController::class, 'index']);
