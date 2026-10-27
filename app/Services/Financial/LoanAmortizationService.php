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
     * Generate the amortization schedule for a released loan.
     *
     * Interest is calculated using:
     * Outstanding Principal × Annual Interest Rate × Days / 360.
     *
     * The stored loan interest rate is monthly, so it is annualized
     * by multiplying it by 12.
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

            $principal = round((float) $loan->principal_amount, 2);
            $monthlyRate = (float) $loan->interest_rate_monthly;
            $annualRate = ($monthlyRate * 12) / 100;
            $termMonths = (int) $loan->term_months;

            $basePrincipalPayment = round(
                $principal / $termMonths,
                2
            );

            $remainingPrincipal = $principal;

            $releaseDate = $loan->release_date
                ? Carbon::parse($loan->release_date)->startOfDay()
                : Carbon::today();

            $previousDate = $releaseDate;

            for ($installment = 1; $installment <= $termMonths; $installment++) {
                $dueDate = $releaseDate->copy()->addMonthsNoOverflow($installment);

                // The final installment reconciles any principal rounding.
                $principalPayment = $installment === $termMonths
                    ? $remainingPrincipal
                    : min($basePrincipalPayment, $remainingPrincipal);

                $principalPayment = round($principalPayment, 2);

                $days = (int) $previousDate->diffInDays($dueDate);

                $interestPayment = round(
                    $remainingPrincipal * $annualRate * $days / 360,
                    2
                );

                $totalDue = round(
                    $principalPayment + $interestPayment,
                    2
                );

                $endingBalance = round(
                    $remainingPrincipal - $principalPayment,
                    2
                );

                LoanAmortization::create([
                    'loan_id' => $loan->id,
                    'installment_no' => $installment,
                    'due_date' => $dueDate,
                    'beginning_balance' => $remainingPrincipal,
                    'principal_amount' => $principalPayment,
                    'interest_amount' => $interestPayment,
                    'penalty_amount' => 0,
                    'total_due' => $totalDue,
                    'paid_amount' => 0,
                    'paid_date' => null,
                    'status' => 'pending',
                ]);

                $remainingPrincipal = $endingBalance;
                $previousDate = $dueDate;
            }
        });
    }
}
