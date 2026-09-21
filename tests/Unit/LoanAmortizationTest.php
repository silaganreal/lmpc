<?php

namespace Tests\Unit;

use App\Services\Financial\LoanApplicationService;
use Tests\TestCase;

class LoanAmortizationTest extends TestCase
{
    public function test_calculates_monthly_interest(): void
    {
        $result = app(LoanApplicationService::class)->calculateAmortization(
            principalAmount: 100_000,
            monthlyInterestRate: 2.00,
            termMonths: 12,
        );

        $this->assertEquals(2_000.0, $result['monthly_interest']);
    }

    public function test_calculates_total_interest(): void
    {
        $result = app(LoanApplicationService::class)->calculateAmortization(
            principalAmount: 100_000,
            monthlyInterestRate: 2.00,
            termMonths: 12,
        );

        $this->assertEquals(24_000.0, $result['total_interest']);
    }

    public function test_calculates_total_payable(): void
    {
        $result = app(LoanApplicationService::class)->calculateAmortization(
            principalAmount: 100_000,
            monthlyInterestRate: 2.00,
            termMonths: 12,
        );

        $this->assertEquals(124_000.0, $result['total_payable']);
    }

    public function test_calculates_monthly_amortization(): void
    {
        $result = app(LoanApplicationService::class)->calculateAmortization(
            principalAmount: 100_000,
            monthlyInterestRate: 2.00,
            termMonths: 12,
        );

        $this->assertEquals(
            10_333.333333333334,
            $result['monthly_amortization']
        );
    }

    public function test_calculates_providential_one_rate(): void
    {
        $result = app(LoanApplicationService::class)->calculateAmortization(
            principalAmount: 50_000,
            monthlyInterestRate: 1.50,
            termMonths: 12,
        );

        $this->assertEquals(750.0, $result['monthly_interest']);
        $this->assertEquals(9_000.0, $result['total_interest']);
        $this->assertEquals(59_000.0, $result['total_payable']);
    }

    public function test_rejects_zero_principal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(LoanApplicationService::class)->calculateAmortization(
            principalAmount: 0,
            monthlyInterestRate: 2.00,
            termMonths: 12,
        );
    }

    public function test_rejects_negative_interest_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(LoanApplicationService::class)->calculateAmortization(
            principalAmount: 100_000,
            monthlyInterestRate: -1.00,
            termMonths: 12,
        );
    }

    public function test_rejects_zero_term(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(LoanApplicationService::class)->calculateAmortization(
            principalAmount: 100_000,
            monthlyInterestRate: 2.00,
            termMonths: 0,
        );
    }
}
