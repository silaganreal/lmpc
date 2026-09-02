<?php

namespace App\Services\Financial;

use App\Models\Member;
use App\Models\ShareAccount;
use App\Models\ShareTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShareCapitalService
{
    public function createAccount(Member $member): ShareAccount {
        return ShareAccount::firstOrCreate(
            ['member_id' => $member->id],
            [
                'shares_subscribed' => 0,
                'total_subscribed_amount' => 0,
                'paid_up_amount' => 0,
                'status' => 'active',
                'opened_at' => now()->toDateString(),
            ]
        );
    }

    public function recordPayment(
        Member $member,
        float $amount,
        ?string $referenceNo = null,
        ?string $description = null,
        ?int $createdBy = null
    ): ShareTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Share capital payment amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $member,
            $amount,
            $referenceNo,
            $description,
            $createdBy
        ) {
            $account = $this->createAccount($member);

            $account = ShareAccount::whereKey($account->id)
                ->lockForUpdate()
                ->first();
            
            $transaction = $account->transactions()->create([
                'transaction_date' => now(),
                'transaction_type' => 'payment',
                'direction' => 'credit',
                'reference_no' => $referenceNo,
                'description' => $description,
                'amount' => $amount,
                'created_by' => $createdBy
            ]);

            $account->increment('paid_up_amount', $amount);

            return $transaction;
        });
    }

    public function recordSubscription(
        Member $member,
        int $shares,
        float $amount,
        ?string $referenceNo = null,
        ?string $description = null,
        ?int $createdBy = null
    ): ShareTransaction {
        if ($shares <= 0) {
            throw new InvalidArgumentException(
                'Number of shares must be greater than zero.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Share subscription amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $member,
            $shares,
            $amount,
            $referenceNo,
            $description,
            $createdBy
        ) {
            $account = $this->createAccount($member);

            $account = ShareAccount::whereKey($account->id)
                ->lockForUpdate()
                ->first();
            
            $transaction = $account->transactions()->create([
                'transaction_date' => now(),
                'transaction_type' => 'subscription',
                'direction' => 'credit',
                'reference_no' => $referenceNo,
                'description' => $description,
                'amount' => $amount,
                'created_by' => $createdBy
            ]);

            $account->increment('shares_subscribed', $shares);
            $account->increment('total_subscribed_amount', $amount);

            return $transaction;
        });
    }
}