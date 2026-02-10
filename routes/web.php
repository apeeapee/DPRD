<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\VillageVotePageController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    // Halaman yang sudah ada dijadikan dashboard admin
    Route::view('/admin/dashboard', 'dashboard.index')->name('admin.dashboard');
});

Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
});

// Boleh diakses admin & user (login dulu)
Route::get('/suara-desa', [VillageVotePageController::class, 'index'])->middleware('auth')->name('village-votes');
