<?php

namespace App\Services\Financial;

use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SavingsService
{
    /**
     * Create a savings account for a member.
     */
    public function createAccount(Member $member): SavingsAccount
    {
        return SavingsAccount::firstOrCreate(
            ['member_id' => $member->id],
            [
                'account_number' => $this->generateAccountNumber(),
                'account_type' => 'regular',
                'current_balance' => 0,
                'status' => 'active',
                'opened_at' => now()->toDateString(),
            ]
        );
    }

    /**
     * Record a savings deposit.
     */
    public function deposit(
        Member $member,
        float $amount,
        string $transactionDate,
        ?string $referenceNo = null,
        ?string $description = null,
        ?int $createdBy = null,
    ): SavingsTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Savings deposit amount must be greater than zero.');
        }

        return DB::transaction(function () use (
            $member,
            $amount,
            $transactionDate,
            $referenceNo,
            $description,
            $createdBy,
        ) {
            $account = $member->savingsAccount()->lockForUpdate()->first();

            if (! $account) {
                $account = $this->createAccount($member);
            }

            if ($account->status !== 'active') {
                throw new InvalidArgumentException('Savings account is not active.');
            }

            $transaction = $account->transactions()->create([
                'transaction_date' => $transactionDate,
                'transaction_type' => 'deposit',
                'direction' => 'credit',
                'reference_no' => $referenceNo,
                'description' => $description,
                'amount' => $amount,
                'created_by' => $createdBy,
            ]);

            $account->increment('current_balance', $amount);

            return $transaction;
        });
    }

    /**
     * Record a savings withdrawal.
     */
    public function withdraw(
        Member $member,
        float $amount,
        string $transactionDate,
        ?string $referenceNo = null,
        ?string $description = null,
        ?int $createdBy = null,
    ): SavingsTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Savings withdrawal amount must be greater than zero.');
        }

        return DB::transaction(function () use (
            $member,
            $amount,
            $transactionDate,
            $referenceNo,
            $description,
            $createdBy,
        ) {
            $account = $member->savingsAccount()->lockForUpdate()->first();

            if (! $account) {
                throw new InvalidArgumentException('Savings account does not exist.');
            }

            if ($account->status !== 'active') {
                throw new InvalidArgumentException('Savings account is not active.');
            }

            if ((float) $account->current_balance < $amount) {
                throw new InvalidArgumentException('Insufficient savings balance.');
            }

            $transaction = $account->transactions()->create([
                'transaction_date' => $transactionDate,
                'transaction_type' => 'withdrawal',
                'direction' => 'debit',
                'reference_no' => $referenceNo,
                'description' => $description,
                'amount' => $amount,
                'created_by' => $createdBy,
            ]);

            $account->decrement('current_balance', $amount);

            return $transaction;
        });
    }

    /**
     * Generate a unique savings account number.
     */
    private function generateAccountNumber(): string
    {
        do {
            $accountNumber = 'SAV-'.now()->format('YmdHis').random_int(10, 99);
        } while (SavingsAccount::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }
}
