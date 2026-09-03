<?php

namespace App\Services\Financial;

use App\Models\CbuAccount;
use App\Models\CbuTransaction;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CbuService
{
    public function createAccount(Member $member): CbuAccount
    {
        return CbuAccount::firstOrCreate(
            ['member_id' => $member->id],
            [
                'current_balance' => 0,
                'status' => 'active',
                'opened_at' => now()->toDateString(),
            ]
        );
    }

    public function contribute(
        Member $member,
        float $amount,
        ?string $referenceNo = null,
        ?string $description = null,
        ?int $createdBy = null
    ): CbuTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'CBU contribution amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $member,
            $amount,
            $referenceNo,
            $description,
            $createdBy,
        ) {
            $account = $this->createAccount($member);

            $transaction = $account->transactions()->create([
                'transaction_date' => now(),
                'transaction_type' => 'contribution',
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

    public function retainForLoan(
        Member $member,
        float $amount,
        ?string $referenceNo = null,
        ?string $description = null,
        ?int $createdBy = null
    ): CbuTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'CBU retention amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $member,
            $amount,
            $referenceNo,
            $description,
            $createdBy,
        ) {
            $account = $this->createAccount($member);

            $account = CbuAccount::whereKey($account->id)
                ->lockForUpdate()
                ->first();

            if ((float) $account->current_balance < $amount) {
                throw new InvalidArgumentException(
                    'Insufficient CBU balance for this retention.'
                );
            }

            $transaction = $account->transactions()->create([
                'transaction_date' => now(),
                'transaction_type' => 'loan_retention',
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
}
