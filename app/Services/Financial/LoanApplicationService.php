<?php

namespace App\Services\Financial;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanApplicationService
{
    public function __construct(
        private readonly LoanService $loanService,
    ) {}

    /**
     * Calculate loan deductions.
     *
     * @return array<string, float>
     */
    public function calculateDeductions(
        Member $member,
        LoanProduct $loanProduct,
        float $principalAmount,
        int $termMonths,
    ): array {
        if ($principalAmount <= 0) {
            throw new InvalidArgumentException(
                'Loan principal amount must be greater than zero.'
            );
        }

        if ($termMonths <= 0) {
            throw new InvalidArgumentException(
                'Loan term must be greater than zero.'
            );
        }

        $cbuRetention = $principalAmount * 0.03;

        $serviceFee = min(
            $principalAmount * 0.02,
            1_600
        );

        $notarialFee = $principalAmount >= 30_000
            ? 150
            : 0;

        $insurancePremium = 0;

        $totalDeductions = $cbuRetention
            + $serviceFee
            + $insurancePremium
            + $notarialFee;

        $netProcess = $principalAmount - $totalDeductions;

        return [
            'principal_amount' => $principalAmount,
            'cbu_retention' => $cbuRetention,
            'service_fee' => $serviceFee,
            'insurance_premium' => $insurancePremium,
            'notarial_fee' => $notarialFee,
            'total_deductions' => $totalDeductions,
            'net_process' => $netProcess,
        ];
    }

    /**
     * Calculate loan amortization.
     *
     * @return array<string, float>
     */
    public function calculateAmortization(
        float $principalAmount,
        float $monthlyInterestRate,
        int $termMonths,
    ): array {
        if ($principalAmount <= 0) {
            throw new InvalidArgumentException(
                'Loan principal amount must be greater than zero.'
            );
        }

        if ($monthlyInterestRate < 0) {
            throw new InvalidArgumentException(
                'Monthly interest rate cannot be negative.'
            );
        }

        if ($termMonths <= 0) {
            throw new InvalidArgumentException(
                'Loan term must be greater than zero.'
            );
        }

        $monthlyInterest = $principalAmount * ($monthlyInterestRate / 100);

        $totalInterest = $monthlyInterest * $termMonths;

        $totalPayable = $principalAmount + $totalInterest;

        $monthlyAmortization = $totalPayable / $termMonths;

        return [
            'principal_amount' => $principalAmount,
            'monthly_interest' => $monthlyInterest,
            'total_interest' => $totalInterest,
            'total_payable' => $totalPayable,
            'monthly_amortization' => $monthlyAmortization,
        ];
    }

    /**
     * Create a new loan application.
     */
    public function createApplication(
        Member $member,
        LoanProduct $loanProduct,
        float $principalAmount,
        int $termMonths,
        ?string $purpose = null,
        ?string $remarks = null,
        ?int $createdBy = null,
    ): Loan {
        if ($principalAmount <= 0) {
            throw new InvalidArgumentException(
                'Loan principal amount must be greater than zero.'
            );
        }

        if ($termMonths <= 0) {
            throw new InvalidArgumentException(
                'Loan term must be greater than zero.'
            );
        }

        $maximumLoanable = $this->loanService->maximumLoanableAmount(
            $member,
            $loanProduct,
        );

        if ($principalAmount > $maximumLoanable) {
            throw new InvalidArgumentException(
                'Loan amount exceeds the member\'s maximum loanable amount.'
            );
        }

        $singleBorrowerLimit = $this->loanService->singleBorrowerLimit($member);

        if ($principalAmount > $singleBorrowerLimit) {
            throw new InvalidArgumentException(
                'Loan amount exceeds the single borrower limit.'
            );
        }

        if (
            $loanProduct->code === 'PROVIDENTIAL_2'
            && ! $this->loanService->canAvailProvidentialTwo($member)
        ) {
            throw new InvalidArgumentException(
                'Member does not meet the requirements for Providential 2.'
            );
        }

        $deductions = $this->calculateDeductions(
            $member,
            $loanProduct,
            $principalAmount,
            $termMonths,
        );

        $amortization = $this->calculateAmortization(
            $principalAmount,
            (float) $loanProduct->interest_rate_monthly,
            $termMonths,
        );

        return DB::transaction(function () use (
            $member,
            $loanProduct,
            $principalAmount,
            $termMonths,
            $purpose,
            $remarks,
            $createdBy,
            $deductions,
            $amortization,
        ): Loan {
            return Loan::create([
                'member_id' => $member->id,
                'loan_product_id' => $loanProduct->id,
                'loan_no' => $this->generateLoanNumber(),
                'application_date' => Carbon::today(),

                'principal_amount' => $principalAmount,
                'interest_rate_monthly' => $loanProduct->interest_rate_monthly,
                'term_months' => $termMonths,
                'monthly_amortization' => $amortization['monthly_amortization'],

                'outstanding_principal' => $principalAmount,
                'outstanding_interest' => 0,
                'outstanding_penalty' => 0,

                'service_fee' => $deductions['service_fee'],
                'cbu_retention' => $deductions['cbu_retention'],
                'insurance_premium' => $deductions['insurance_premium'],
                'notarial_fee' => $deductions['notarial_fee'],
                'net_process' => $deductions['net_process'],

                'status' => 'draft',
                'purpose' => $purpose,
                'remarks' => $remarks,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Generate the next loan number.
     */
    private function generateLoanNumber(): string
    {
        $year = Carbon::now()->year;

        $lastLoan = Loan::query()
            ->whereYear('created_at', $year)
            ->latest('id')
            ->first();

        $nextNumber = $lastLoan
            ? ((int) substr($lastLoan->loan_no, -5)) + 1
            : 1;

        return sprintf(
            'LN-%d-%05d',
            $year,
            $nextNumber,
        );
    }
}
