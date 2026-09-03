<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\MembershipApplicationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('members', MemberController::class);
    Route::resource('membership-applications', MembershipApplicationController::class);
});

require __DIR__.'/settings.php';
