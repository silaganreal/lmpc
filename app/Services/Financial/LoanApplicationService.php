<?php

namespace App\Services\Financial;

use App\Models\LoanProduct;
use App\Models\Member;
use InvalidArgumentException;

class LoanApplicationService
{
    /**
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
}
