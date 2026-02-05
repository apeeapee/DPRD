<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::view('/dashboard', 'dashboard.index')->name('dashboard');
