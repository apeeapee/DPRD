<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\VillageVotePageController;
use App\Http\Controllers\Admin\VillageVoteSummaryController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    // Halaman yang sudah ada dijadikan dashboard admin
    Route::view('/admin/dashboard', 'dashboard.index')->name('admin.dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/suara-desa', [VillageVoteSummaryController::class, 'index'])->name('village-votes.index');
        Route::get('/suara-desa/create', [VillageVoteSummaryController::class, 'create'])->name('village-votes.create');
        Route::post('/suara-desa', [VillageVoteSummaryController::class, 'store'])->name('village-votes.store');
        Route::get('/suara-desa/{villageVote}/edit', [VillageVoteSummaryController::class, 'edit'])->name('village-votes.edit');
        Route::put('/suara-desa/{villageVote}', [VillageVoteSummaryController::class, 'update'])->name('village-votes.update');
        Route::delete('/suara-desa/{villageVote}', [VillageVoteSummaryController::class, 'destroy'])->name('village-votes.destroy');
    });
});

Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');

    // Halaman baca (list + filter) khusus user
    Route::get('/suara-desa', [VillageVotePageController::class, 'index'])->name('village-votes');
});
