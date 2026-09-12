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

    Route::patch(
        'members/{member}/reactivate',
        [MemberController::class, 'reactivate']
    )->name('members.reactivate');

    Route::resource('membership-applications', MembershipApplicationController::class);

    Route::patch(
        'membership-applications/{membershipApplication}/submit',
        [MembershipApplicationController::class, 'submit']
    )->name('membership-applications.submit');

    Route::patch(
        'membership-applications/{membershipApplication}/review',
        [MembershipApplicationController::class, 'review']
    )->name('membership-applications.review');

    Route::patch(
        'membership-applications/{membershipApplication}/approve',
        [MembershipApplicationController::class, 'approve']
    )->name('membership-applications.approve');
});

require __DIR__.'/settings.php';
