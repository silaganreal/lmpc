<?php

namespace App\Services\Financial;

use App\Models\Loan;
use App\Models\LoanAmortization;
use App\Models\LoanPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanPaymentService
{
    /**
     * Record a payment and allocate it against unpaid amortizations.
     */
    public function recordPayment(
        Loan $loan,
        float $amount,
        ?string $paymentDate = null,
        ?string $referenceNo = null,
        string $paymentMethod = 'cash',
        ?string $remarks = null,
        ?int $createdBy = null,
    ): LoanPayment {
        if (! in_array($loan->status, ['released', 'active', 'past_due'], true)) {
            throw new InvalidArgumentException(
                'Only released, active, or past due loans can receive payments.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Payment amount must be greater than zero.'
            );
        }

        $effectivePaymentDate = $paymentDate ?? Carbon::today()->toDateString();

        return DB::transaction(function () use (
            $loan,
            $amount,
            $paymentDate,
            $effectivePaymentDate,
            $referenceNo,
            $paymentMethod,
            $remarks,
            $createdBy,
        ): LoanPayment {
            $loan->refresh();

            $remainingPayment = round($amount, 2);
            $totalPrincipal = 0.00;
            $totalInterest = 0.00;
            $totalPenalty = 0.00;

            $amortizations = LoanAmortization::query()
                ->where('loan_id', $loan->id)
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('installment_no')
                ->lockForUpdate()
                ->get();

            if ($amortizations->isEmpty()) {
                throw new InvalidArgumentException(
                    'This loan has no unpaid amortization installments.'
                );
            }

            foreach ($amortizations as $amortization) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $result = $this->allocateToAmortization(
                    $amortization,
                    $remainingPayment,
                    $effectivePaymentDate,
                );

                $remainingPayment = round(
                    $remainingPayment - $result['applied'],
                    2
                );

                $totalPenalty += $result['penalty'];
                $totalInterest += $result['interest'];
                $totalPrincipal += $result['principal'];
            }

            if ($remainingPayment > 0) {
                throw new InvalidArgumentException(
                    'Payment amount exceeds the remaining amount due on this loan.'
                );
            }

            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'payment_date' => $paymentDate
                    ? Carbon::parse($paymentDate)->toDateString()
                    : Carbon::today()->toDateString(),
                'reference_no' => $referenceNo,
                'principal_amount' => $totalPrincipal,
                'interest_amount' => $totalInterest,
                'penalty_amount' => $totalPenalty,
                'total_amount' => $amount,
                'payment_method' => $paymentMethod,
                'remarks' => $remarks,
                'created_by' => $createdBy,
            ]);

            $this->updateLoanBalances($loan);

            return $payment;
        });
    }

    /**
     * Allocate a payment against a single amortization installment.
     *
     * Allocation order:
     * penalty -> interest -> principal.
     *
     * @return array{
     *     applied: float,
     *     penalty: float,
     *     interest: float,
     *     principal: float
     * }
     */
    private function allocateToAmortization(
        LoanAmortization $amortization,
        float $payment,
        string $paymentDate,
    ): array {
        $remainingDue = round(
            (float) $amortization->total_due
                + (float) $amortization->penalty_amount
                - (float) $amortization->paid_amount,
            2
        );

        if ($remainingDue <= 0) {
            return [
                'applied' => 0.00,
                'penalty' => 0.00,
                'interest' => 0.00,
                'principal' => 0.00,
            ];
        }

        $payment = min($payment, $remainingDue);

        $existingPaid = (float) $amortization->paid_amount;

        $penaltyDue = (float) $amortization->penalty_amount;
        $interestDue = (float) $amortization->interest_amount;
        $principalDue = (float) $amortization->principal_amount;

        /*
        * Existing payments are allocated in this order:
        * penalty -> interest -> principal.
        */
        $remainingPenalty = max(
            0,
            $penaltyDue - $existingPaid
        );

        $amountAfterPenalty = max(
            0,
            $existingPaid - $penaltyDue
        );

        $remainingInterest = max(
            0,
            $interestDue - $amountAfterPenalty
        );

        $amountAfterInterest = max(
            0,
            $amountAfterPenalty - $interestDue
        );

        $remainingPrincipal = max(
            0,
            $principalDue - $amountAfterInterest
        );

        $penaltyPayment = min(
            $payment,
            $remainingPenalty
        );

        $payment -= $penaltyPayment;

        $interestPayment = min(
            $payment,
            $remainingInterest
        );

        $payment -= $interestPayment;

        $principalPayment = min(
            $payment,
            $remainingPrincipal
        );

        $applied = round(
            $penaltyPayment
                + $interestPayment
                + $principalPayment,
            2
        );

        $newPaidAmount = round(
            $existingPaid + $applied,
            2
        );

        $totalDue = round(
            (float) $amortization->total_due
                + (float) $amortization->penalty_amount,
            2
        );

        $isFullyPaid = $newPaidAmount >= $totalDue;

        $amortization->update([
            'paid_amount' => $newPaidAmount,
            'paid_date' => $isFullyPaid
                ? Carbon::parse($paymentDate)->toDateString()
                : $amortization->paid_date,
            'status' => $isFullyPaid
                ? 'paid'
                : 'partial',
        ]);

        return [
            'applied' => $applied,
            'penalty' => round($penaltyPayment, 2),
            'interest' => round($interestPayment, 2),
            'principal' => round($principalPayment, 2),
        ];
    }

    /**
     * Recalculate loan outstanding balances from its amortization schedule.
     */
    private function updateLoanBalances(Loan $loan): void
    {
        $loan->load('amortizations');

        $outstandingPrincipal = 0.00;
        $outstandingInterest = 0.00;
        $outstandingPenalty = 0.00;

        foreach ($loan->amortizations as $amortization) {
            $paidAmount = (float) $amortization->paid_amount;

            $penalty = (float) $amortization->penalty_amount;
            $interest = (float) $amortization->interest_amount;
            $principal = (float) $amortization->principal_amount;

            $remaining = $paidAmount;

            // Payment allocation order:
            // penalty -> interest -> principal.

            $remainingPenalty = min(
                $penalty,
                $remaining
            );

            $remaining -= $remainingPenalty;

            $remainingInterest = min(
                $interest,
                $remaining
            );

            $remaining -= $remainingInterest;

            $remainingPrincipal = min(
                $principal,
                $remaining
            );

            $outstandingPenalty += $penalty - $remainingPenalty;
            $outstandingInterest += $interest - $remainingInterest;
            $outstandingPrincipal += $principal - $remainingPrincipal;
        }

        $newStatus = $loan->status;

        if ($this->isFullyPaid($loan)) {
            $newStatus = 'fully_paid';
        } elseif ($loan->status === 'released') {
            $newStatus = 'active';
        }

        $loan->update([
            'outstanding_principal' => round($outstandingPrincipal, 2),
            'outstanding_interest' => round($outstandingInterest, 2),
            'outstanding_penalty' => round($outstandingPenalty, 2),
            'status' => $newStatus,
        ]);
    }

    private function isFullyPaid(Loan $loan): bool
    {
        return ! $loan->amortizations()
            ->whereIn('status', ['pending', 'partial'])
            ->exists();
    }
}
