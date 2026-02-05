<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AreaController;

Route::get('/areas', [AreaController::class, 'index']);
Route::get('/areas/{area}', [AreaController::class, 'show']);
