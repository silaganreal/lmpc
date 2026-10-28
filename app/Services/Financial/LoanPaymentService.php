<?php

namespace App\Services\Financial;

use App\Models\Loan;
use App\Models\LoanAmortization;
use App\Models\LoanPayment;
use App\Models\LoanRestructuring;
use App\Models\LoanRestructuringAmortization;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanPaymentService
{
    public function __construct(
        private readonly CbuService $cbuService,
    ) {}

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

        // if ($amount <= 0) {
        //     throw new InvalidArgumentException(
        //         'Payment amount must be greater than zero.'
        //     );
        // }

        if (! is_finite($amount) || $amount <= 0) {
            throw new InvalidArgumentException(
                'Payment amount must be greater than zero.'
            );
        }

        $amount = round($amount, 2);

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
            $loan = Loan::query()
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Check the loan status again after acquiring the lock.
            if (! in_array($loan->status, ['released', 'active', 'past_due'], true)) {
                throw new InvalidArgumentException(
                    'Only released, active, or past due loans can receive payments.'
                );
            }

            $this->ensureUniquePaymentReference(
                (int) $loan->id,
                $referenceNo,
            );

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

            // if ($remainingPayment > 0) {
            //     throw new InvalidArgumentException(
            //         'Payment amount exceeds the remaining amount due on this loan.'
            //     );
            // }

            $appliedAmount = round($amount - $remainingPayment, 2);
            $excessAmount = round($remainingPayment, 2);

            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'payment_date' => $paymentDate
                    ? Carbon::parse($paymentDate)->toDateString()
                    : Carbon::today()->toDateString(),
                'reference_no' => $referenceNo,
                'principal_amount' => $totalPrincipal,
                'interest_amount' => $totalInterest,
                'penalty_amount' => $totalPenalty,
                'total_amount' => $appliedAmount,
                'payment_method' => $paymentMethod,
                'remarks' => $remarks,
                'created_by' => $createdBy,
            ]);

            if ($excessAmount > 0) {
                $loan->loadMissing('member');

                $this->cbuService->creditLoanPaymentExcess(
                    member: $loan->member,
                    amount: $excessAmount,
                    transactionDate: $effectivePaymentDate,
                    referenceNo: $referenceNo,
                    description: sprintf(
                        'Excess from loan payment for loan %s; payment ID %d.',
                        $loan->loan_no,
                        $payment->id,
                    ),
                    createdBy: $createdBy,
                );
            }

            $this->updateLoanBalances($loan);

            return $payment;
        });
    }

    /**
     * Record a payment against an approved restructuring schedule.
     *
     * @return Collection<int, LoanPayment>
     */
    // public function recordRestructuringPayment(
    //     Loan $loan,
    //     float $amount,
    //     string $paymentDate,
    //     ?string $referenceNo = null,
    //     string $paymentMethod = 'cash',
    //     ?string $remarks = null,
    //     ?int $createdBy = null,
    // ): Collection {
    //     if ($amount <= 0) {
    //         throw new InvalidArgumentException(
    //             'Payment amount must be greater than zero.'
    //         );
    //     }

    //     return DB::transaction(function () use (
    //         $loan,
    //         $amount,
    //         $paymentDate,
    //         $referenceNo,
    //         $paymentMethod,
    //         $remarks,
    //         $createdBy,
    //     ): Collection {
    //         $loan = Loan::query()
    //             ->whereKey($loan->id)
    //             ->lockForUpdate()
    //             ->firstOrFail();

    //         $restructuring = LoanRestructuring::query()
    //             ->where('loan_id', $loan->id)
    //             ->where('status', 'approved')
    //             ->whereHas('amortizations', function ($query): void {
    //                 $query->whereIn('status', ['pending', 'partial']);
    //             })
    //             ->latest('restructured_at')
    //             ->lockForUpdate()
    //             ->first();

    //         if (! $restructuring) {
    //             throw new InvalidArgumentException(
    //                 'This loan has no approved restructuring with unpaid installments.'
    //             );
    //         }

    //         $amortizations = LoanRestructuringAmortization::query()
    //             ->where('loan_restructuring_id', $restructuring->id)
    //             ->whereIn('status', ['pending', 'partial'])
    //             ->orderBy('installment_no')
    //             ->lockForUpdate()
    //             ->get();

    //         $remainingPayment = round($amount, 2);
    //         $payments = collect();

    //         foreach ($amortizations as $amortization) {
    //             if ($remainingPayment <= 0) {
    //                 break;
    //             }

    //             $result = $this->allocateToRestructuringAmortization(
    //                 $amortization,
    //                 $remainingPayment,
    //                 Carbon::parse($paymentDate)->toDateString(),
    //             );

    //             if ($result['applied'] <= 0) {
    //                 continue;
    //             }

    //             $payment = LoanPayment::create([
    //                 'loan_id' => $loan->id,
    //                 'loan_restructuring_amortization_id' => $amortization->id,
    //                 'payment_date' => Carbon::parse($paymentDate)->toDateString(),
    //                 'reference_no' => $referenceNo,
    //                 'principal_amount' => $result['principal'],
    //                 'interest_amount' => $result['interest'],
    //                 'penalty_amount' => 0,
    //                 'total_amount' => $result['applied'],
    //                 'payment_method' => $paymentMethod,
    //                 'remarks' => $remarks,
    //                 'created_by' => $createdBy,
    //             ]);

    //             $payments->push($payment);

    //             $remainingPayment = round(
    //                 $remainingPayment - $result['applied'],
    //                 2,
    //             );
    //         }

    //         if ($payments->isEmpty()) {
    //             throw new InvalidArgumentException(
    //                 'No unpaid restructuring installment could receive this payment.'
    //             );
    //         }

    //         if ($remainingPayment > 0) {
    //             $loan->loadMissing('member');

    //             $this->cbuService->creditLoanPaymentExcess(
    //                 member: $loan->member,
    //                 amount: $remainingPayment,
    //                 transactionDate: Carbon::parse($paymentDate)->toDateString(),
    //                 referenceNo: $referenceNo,
    //                 description: sprintf(
    //                     'Excess from restructured loan payment for loan %s.',
    //                     $loan->loan_no,
    //                 ),
    //                 createdBy: $createdBy,
    //             );
    //         }

    //         return $payments;
    //     });
    // }

    /**
     * Record a payment against an approved restructuring schedule.
     *
     * @return Collection<int, LoanPayment>
     */
    public function recordRestructuringPayment(
        Loan $loan,
        float $amount,
        string $paymentDate,
        ?string $referenceNo = null,
        string $paymentMethod = 'cash',
        ?string $remarks = null,
        ?int $createdBy = null,
    ): Collection {
        if (! is_finite($amount) || $amount <= 0) {
            throw new InvalidArgumentException(
                'Payment amount must be greater than zero.'
            );
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Payment amount must be greater than zero.'
            );
        }

        $effectivePaymentDate = Carbon::parse($paymentDate)->toDateString();

        return DB::transaction(function () use (
            $loan,
            $amount,
            $effectivePaymentDate,
            $referenceNo,
            $paymentMethod,
            $remarks,
            $createdBy,
        ): Collection {
            $loan = Loan::query()
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureUniquePaymentReference(
                (int) $loan->id,
                $referenceNo,
            );

            $restructuring = LoanRestructuring::query()
                ->where('loan_id', $loan->id)
                ->where('status', 'approved')
                ->whereHas('amortizations', function ($query): void {
                    $query->whereIn('status', ['pending', 'partial']);
                })
                ->latest('restructured_at')
                ->lockForUpdate()
                ->first();

            if (! $restructuring) {
                throw new InvalidArgumentException(
                    'This loan has no approved restructuring with unpaid installments.'
                );
            }

            $amortizations = LoanRestructuringAmortization::query()
                ->where('loan_restructuring_id', $restructuring->id)
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('installment_no')
                ->lockForUpdate()
                ->get();

            $remainingPayment = $amount;
            $payments = collect();

            foreach ($amortizations as $amortization) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $result = $this->allocateToRestructuringAmortization(
                    $amortization,
                    $remainingPayment,
                    $effectivePaymentDate,
                );

                if ($result['applied'] <= 0) {
                    continue;
                }

                $payment = LoanPayment::create([
                    'loan_id' => $loan->id,
                    'loan_restructuring_amortization_id' => $amortization->id,
                    'payment_date' => $effectivePaymentDate,
                    'reference_no' => $referenceNo,
                    'principal_amount' => $result['principal'],
                    'interest_amount' => $result['interest'],
                    'penalty_amount' => 0,
                    'total_amount' => $result['applied'],
                    'payment_method' => $paymentMethod,
                    'remarks' => $remarks,
                    'created_by' => $createdBy,
                ]);

                $payments->push($payment);

                $remainingPayment = round(
                    $remainingPayment - $result['applied'],
                    2,
                );
            }

            if ($payments->isEmpty()) {
                throw new InvalidArgumentException(
                    'No unpaid restructuring installment could receive this payment.'
                );
            }

            // Only the amount not applied to restructuring installments
            // is transferred to the member's CBU.
            if ($remainingPayment > 0) {
                $loan->loadMissing('member');

                $this->cbuService->creditLoanPaymentExcess(
                    member: $loan->member,
                    amount: $remainingPayment,
                    transactionDate: $effectivePaymentDate,
                    referenceNo: $referenceNo,
                    description: sprintf(
                        'Excess from restructured loan payment for loan %s.',
                        $loan->loan_no,
                    ),
                    createdBy: $createdBy,
                );
            }

            return $payments;
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

    /**
     * @return array{
     *     applied: float,
     *     interest: float,
     *     principal: float
     * }
     */
    private function allocateToRestructuringAmortization(
        LoanRestructuringAmortization $amortization,
        float $payment,
        string $paymentDate,
    ): array {
        $interestDue = (float) $amortization->interest_amount;
        $principalDue = (float) $amortization->principal_amount;
        $existingPaid = (float) $amortization->paid_amount;

        // Previously received payments are applied to interest first.
        $remainingInterest = max(0, $interestDue - $existingPaid);
        $amountAfterInterest = max(0, $existingPaid - $interestDue);
        $remainingPrincipal = max(0, $principalDue - $amountAfterInterest);

        $interestPayment = min($payment, $remainingInterest);
        $paymentAfterInterest = round($payment - $interestPayment, 2);

        $principalPayment = min(
            $paymentAfterInterest,
            $remainingPrincipal,
        );

        $applied = round($interestPayment + $principalPayment, 2);

        if ($applied <= 0) {
            return [
                'applied' => 0.00,
                'interest' => 0.00,
                'principal' => 0.00,
            ];
        }

        $newPaidAmount = round($existingPaid + $applied, 2);
        $totalDue = round($interestDue + $principalDue, 2);
        $isFullyPaid = $newPaidAmount >= $totalDue;

        $amortization->update([
            'paid_amount' => $newPaidAmount,
            'paid_date' => $isFullyPaid ? $paymentDate : null,
            'status' => $isFullyPaid ? 'paid' : 'partial',
        ]);

        return [
            'applied' => $applied,
            'interest' => round($interestPayment, 2),
            'principal' => round($principalPayment, 2),
        ];
    }

    /**
     * Reject a payment reference already used for this loan.
     */
    private function ensureUniquePaymentReference(
        int $loanId,
        ?string $referenceNo,
    ): void {
        if ($referenceNo === null || trim($referenceNo) === '') {
            return;
        }

        $exists = LoanPayment::query()
            ->where('loan_id', $loanId)
            ->where('reference_no', trim($referenceNo))
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException(
                'A payment with this reference number has already been recorded for this loan.'
            );
        }
    }
}
