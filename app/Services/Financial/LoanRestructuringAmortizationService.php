<?php

namespace App\Services\Financial;

use App\Models\LoanRestructuring;
use App\Models\LoanRestructuringAmortization;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanRestructuringAmortizationService
{
    /**
     * Generate a separate diminishing-balance schedule for an approved
     * restructuring. The original loan schedule remains untouched.
     */
    public function generate(LoanRestructuring $restructuring): void
    {
        if ($restructuring->status !== 'approved') {
            throw new InvalidArgumentException(
                'Only approved restructurings can generate a schedule.'
            );
        }

        if ($restructuring->approved_term_years === null ||
            $restructuring->approved_term_years < 1 ||
            $restructuring->approved_term_years > 5) {
            throw new InvalidArgumentException(
                'The approved restructuring term must be between 1 and 5 years.'
            );
        }

        DB::transaction(function () use ($restructuring): void {
            $restructuring->loadMissing('loan');

            $loan = $restructuring->loan;

            $principal = round(
                (float) $restructuring->remaining_principal,
                2
            );

            if ($principal <= 0) {
                throw new InvalidArgumentException(
                    'The remaining principal must be greater than zero.'
                );
            }

            $termMonths = $restructuring->approved_term_years * 12;

            // Retain the existing loan's monthly interest rate.
            $annualRate = ((float) $loan->interest_rate_monthly * 12) / 100;

            $basePrincipalPayment = round(
                $principal / $termMonths,
                2
            );

            $remainingPrincipal = $principal;

            $startDate = $restructuring->restructured_at
                ? Carbon::parse($restructuring->restructured_at)->startOfDay()
                : Carbon::today();

            $previousDate = $startDate->copy();

            for ($installment = 1; $installment <= $termMonths; $installment++) {
                $dueDate = $startDate->copy()
                    ->addMonthsNoOverflow($installment);

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

                LoanRestructuringAmortization::create([
                    'loan_restructuring_id' => $restructuring->id,
                    'installment_no' => $installment,
                    'due_date' => $dueDate->toDateString(),
                    'beginning_balance' => $remainingPrincipal,
                    'principal_amount' => $principalPayment,
                    'interest_amount' => $interestPayment,
                    'total_due' => $totalDue,
                    'paid_amount' => 0,
                    'paid_date' => null,
                    'status' => 'pending',
                ]);

                $remainingPrincipal = round(
                    $remainingPrincipal - $principalPayment,
                    2
                );

                $previousDate = $dueDate;
            }
        });
    }
}
