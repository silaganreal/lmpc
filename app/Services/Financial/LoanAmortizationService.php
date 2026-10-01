<?php

namespace App\Services\Financial;

use App\Models\Loan;
use App\Models\LoanAmortization;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanAmortizationService
{
    /**
     * Generate the amortization schedule for a release loan.
     */
    public function generate(Loan $loan): void
    {
        if ($loan->status !== 'released') {
            throw new InvalidArgumentException(
                'Only released loans can have an amortization schedule generated.'
            );
        }

        if ($loan->term_months <= 0) {
            throw new InvalidArgumentException(
                'Loan term must be greater than zero.'
            );
        }

        DB::transaction(function () use ($loan): void {
            $loan->amortizations()->delete();

            $principal = (float) $loan->principal_amount;
            $monthlyRate = (float) $loan->interest_rate_monthly;
            $termMonths = (int) $loan->term_months;

            $monthlyInterest = $principal * ($monthlyRate / 100);
            $totalInterest = $monthlyInterest * $termMonths;
            $totalPayable = $principal + $totalInterest;
            $monthlyAmortization = $totalPayable / $termMonths;

            $beginningBalance = $principal;

            $releaseDate = $loan->release_date
                ? Carbon::parse($loan->release_date)
                : Carbon::today();

            for ($installment = 1; $installment <= $termMonths; $installment++) {
                $principalPayment = $principal / $termMonths;

                $interestPayment = $monthlyInterest;

                $totalDue = $principalPayment + $interestPayment;

                $endingBalance = $beginningBalance - $principalPayment;

                // Prevent floating-point residue on the final installment.
                if ($installment === $termMonths) {
                    $principalPayment = $beginningBalance;
                    $totalDue = $principalPayment + $interestPayment;
                    $endingBalance = 0;
                }

                LoanAmortization::create([
                    'loan_id' => $loan->id,
                    'installment_no' => $installment,

                    'due_date' => $releaseDate->copy()->addMonths($installment),

                    'beginning_balance' => $beginningBalance,
                    'principal_amount' => $principalPayment,
                    'interest_amount' => $interestPayment,
                    'penalty_amount' => 0,
                    'total_due' => $totalDue,

                    'paid_amount' => 0,
                    'paid_date' => null,

                    'status' => 'pending',
                ]);

                $beginningBalance = $endingBalance;
            }
        });
    }
}
