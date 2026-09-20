<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\Financial\SavingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavingsController extends Controller
{
    public function __construct(
        private SavingsService $savingsService,
    ) {}

    public function createDeposit(Member $member): Response
    {
        $member->load('savingsAccount');

        return Inertia::render('Savings/Deposit', [
            'member' => $member,
            'savingsAccount' => $member->savingsAccount,
        ]);
    }

    public function deposit(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->savingsService->deposit(
            member: $member,
            amount: (float) $validated['amount'],
            transactionDate: $validated['transaction_date'],
            referenceNo: $validated['reference_no'] ?? null,
            description: $validated['description'] ?? null,
            createdBy: $request->user()?->id,
        );

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'Savings deposit recorded successfully.');
    }

    public function createWithdrawal(Member $member): Response
    {
        $member->load('savingsAccount');

        return Inertia::render('Savings/Withdrawal', [
            'member' => $member,
            'savingsAccount' => $member->savingsAccount,
        ]);
    }

    public function withdraw(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->savingsService->withdraw(
            member: $member,
            amount: (float) $validated['amount'],
            transactionDate: $validated['transaction_date'],
            referenceNo: $validated['reference_no'] ?? null,
            description: $validated['description'] ?? null,
            createdBy: $request->user()?->id,
        );

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'Savings withdrawal recorded successfully.');
    }
}
