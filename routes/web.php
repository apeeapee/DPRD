<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\Admin\VillageVoteSummaryController;
use App\Http\Controllers\Admin\TpsVoteController;
use App\Http\Controllers\Admin\PartyController;
use App\Http\Controllers\Admin\CandidateController;

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

        // Input suara per TPS
        Route::get('/suara-tps', [TpsVoteController::class, 'index'])->name('tps-votes.index');
        Route::get('/suara-tps/create', [TpsVoteController::class, 'create'])->name('tps-votes.create');
        Route::post('/suara-tps', [TpsVoteController::class, 'store'])->name('tps-votes.store');
        Route::get('/suara-tps/{pollingStation}/edit', [TpsVoteController::class, 'edit'])->name('tps-votes.edit');
        Route::put('/suara-tps/{pollingStation}', [TpsVoteController::class, 'update'])->name('tps-votes.update');
        Route::delete('/suara-tps/{pollingStation}', [TpsVoteController::class, 'destroy'])->name('tps-votes.destroy');

        // Data Partai
        Route::get('/partai', [PartyController::class, 'index'])->name('parties.index');
        Route::get('/partai/create', [PartyController::class, 'create'])->name('parties.create');
        Route::post('/partai', [PartyController::class, 'store'])->name('parties.store');
        Route::get('/partai/{party}/edit', [PartyController::class, 'edit'])->name('parties.edit');
        Route::put('/partai/{party}', [PartyController::class, 'update'])->name('parties.update');
        Route::delete('/partai/{party}', [PartyController::class, 'destroy'])->name('parties.destroy');

        // Data Calon
        Route::get('/calon', [CandidateController::class, 'index'])->name('candidates.index');
        Route::get('/calon/create', [CandidateController::class, 'create'])->name('candidates.create');
        Route::post('/calon', [CandidateController::class, 'store'])->name('candidates.store');
        Route::get('/calon/{candidate}/edit', [CandidateController::class, 'edit'])->name('candidates.edit');
        Route::put('/calon/{candidate}', [CandidateController::class, 'update'])->name('candidates.update');
        Route::delete('/calon/{candidate}', [CandidateController::class, 'destroy'])->name('candidates.destroy');
    });
});

Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');

    // Backward-compat: URL lama diarahkan ke dashboard
    Route::redirect('/suara-desa', '/user/dashboard');
});
