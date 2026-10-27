<?php

use App\Http\Controllers\CbuController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\LoanRestructuringController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\ShareCapitalController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('dashboard', DashboardController::class)
        ->name('dashboard');
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

    Route::get(
        'members/{member}/share-capital/create',
        [ShareCapitalController::class, 'create']
    )->name('share-capital.create');

    Route::post(
        'members/{member}/share-capital',
        [ShareCapitalController::class, 'store']
    )->name('share-capital.store');

    Route::get(
        'members/{member}/cbu/create',
        [CbuController::class, 'create']
    )->name('cbu.create');

    Route::post(
        'members/{member}/cbu',
        [CbuController::class, 'store']
    )->name('cbu.store');

    Route::get('members/{member}/savings/deposit', [SavingsController::class, 'createDeposit'])
        ->name('savings.deposit.create');

    Route::post('members/{member}/savings/deposit', [SavingsController::class, 'deposit'])
        ->name('savings.deposit');

    Route::get('members/{member}/savings/withdrawal', [SavingsController::class, 'createWithdrawal'])
        ->name('savings.withdrawal.create');

    Route::post('members/{member}/savings/withdrawal', [SavingsController::class, 'withdraw'])
        ->name('savings.withdrawal');

    Route::get('/members/{member}/loans/create', [LoanController::class, 'createForMember'])
        ->name('members.loans.create');

    Route::get('/loans/create', [LoanController::class, 'create'])
        ->name('loans.create');

    Route::post('/loans', [LoanController::class, 'store'])
        ->name('loans.store');

    Route::patch('/loans/{loan}/submit', [LoanController::class, 'submit'])
        ->name('loans.submit');

    Route::get('/loans/{loan}', [LoanController::class, 'show'])
        ->name('loans.show');

    Route::patch('/loans/{loan}/approve', [LoanController::class, 'approve'])
        ->name('loans.approve');

    Route::patch('/loans/{loan}/reject', [LoanController::class, 'reject'])
        ->name('loans.reject');

    Route::patch('/loans/{loan}/release', [LoanController::class, 'release'])
        ->name('loans.release');

    Route::post('/loans/{loan}/payments', [LoanPaymentController::class, 'store'])
        ->name('loans.payments.store');

    Route::get('/collections', [CollectionController::class, 'index'])
        ->name('collections.index');

    Route::get('/loan-restructurings', [LoanRestructuringController::class, 'index'])
        ->name('loan-restructurings.index');

    Route::patch('/loan-restructurings/{loanRestructuring}/approve', [LoanRestructuringController::class, 'approve'])
        ->name('loan-restructurings.approve');

    Route::patch('/loan-restructurings/{loanRestructuring}/reject', [LoanRestructuringController::class, 'reject'])
        ->name('loan-restructurings.reject');

    Route::post('/loans/{loan}/restructurings', [LoanRestructuringController::class, 'store'])
        ->name('loans.restructurings.store');
});

require __DIR__.'/settings.php';
