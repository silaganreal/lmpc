<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Services\Financial\LoanAmortizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanAmortizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_installment_reconciles_principal_exactly(): void
    {
        $member = Member::create([
            'member_no' => 'M-TEST-0001',
            'first_name' => 'Test',
            'last_name' => 'Member',
            'membership_type' => 'regular',
            'status' => 'active',
        ]);

        $loanProduct = LoanProduct::create([
            'code' => 'TEST_LOAN',
            'name' => 'Test Loan',
            'interest_rate_monthly' => 1.50,
            'maximum_amount' => 100_000,
            'maximum_term_months' => 12,
            'term_type' => 'monthly',
            'is_active' => true,
        ]);

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'loan_no' => 'LN-TEST-00001',
            'application_date' => '2026-10-06',
            'release_date' => '2026-10-06',
            'principal_amount' => 1_000.00,
            'interest_rate_monthly' => 1.50,
            'term_months' => 12,
            'monthly_amortization' => 98.33,
            'outstanding_principal' => 1_000.00,
            'outstanding_interest' => 180.00,
            'outstanding_penalty' => 0.00,
            'service_fee' => 20.00,
            'cbu_retention' => 30.00,
            'insurance_premium' => 0.00,
            'notarial_fee' => 0.00,
            'net_process' => 950.00,
            'status' => 'released',
        ]);

        app(LoanAmortizationService::class)->generate($loan);

        $amortizations = $loan->fresh()->amortizations;

        $this->assertCount(12, $amortizations);

        $totalPrincipal = round(
            $amortizations->sum(
                fn ($amortization) => (float) $amortization->principal_amount
            ),
            2
        );

        $this->assertEquals(1_000.00, $totalPrincipal);

        $this->assertEquals(
            83.37,
            (float) $amortizations->last()->principal_amount
        );

        $this->assertEquals(
            83.37,
            (float) $amortizations->last()->beginning_balance
        );

        $this->assertEquals(
            0.00,
            round(
                (float) $amortizations->last()->beginning_balance
                    - (float) $amortizations->last()->principal_amount,
                2
            )
        );
    }

    public function test_principal_payments_are_equal_and_interest_declines(): void
    {
        $member = Member::create([
            'member_no' => 'M-TEST-0002',
            'first_name' => 'Test',
            'last_name' => 'Borrower',
            'membership_type' => 'regular',
            'status' => 'active',
        ]);

        $loanProduct = LoanProduct::create([
            'code' => 'TEST_LOAN_2',
            'name' => 'Test Loan 2',
            'interest_rate_monthly' => 1.50,
            'maximum_amount' => 100_000,
            'maximum_term_months' => 12,
            'term_type' => 'monthly',
            'is_active' => true,
        ]);

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'loan_no' => 'LN-TEST-00002',
            'application_date' => '2026-10-15',
            'release_date' => '2026-10-15',
            'principal_amount' => 12_000.00,
            'interest_rate_monthly' => 2.00,
            'term_months' => 12,
            'monthly_amortization' => 1_240.00,
            'outstanding_principal' => 12_000.00,
            'outstanding_interest' => 2_880.00,
            'outstanding_penalty' => 0.00,
            'service_fee' => 240.00,
            'cbu_retention' => 360.00,
            'insurance_premium' => 0.00,
            'notarial_fee' => 0.00,
            'net_process' => 11_400.00,
            'status' => 'released',
        ]);

        app(LoanAmortizationService::class)->generate($loan);

        $amortizations = $loan->fresh()->amortizations()
            ->orderBy('installment_no')
            ->get();

        $this->assertCount(12, $amortizations);

        // Equal principal payments, with final-installment rounding handled.
        foreach ($amortizations->take(11) as $amortization) {
            $this->assertEquals(
                1_000.00,
                (float) $amortization->principal_amount
            );
        }

        $this->assertEquals(
            1_000.00,
            (float) $amortizations->last()->principal_amount
        );

        // Interest must decline as the outstanding principal decreases.
        $this->assertGreaterThan(
            (float) $amortizations[1]->interest_amount,
            (float) $amortizations[0]->interest_amount
        );

        $this->assertGreaterThan(
            (float) $amortizations[10]->interest_amount,
            (float) $amortizations[9]->interest_amount
        );

        // First installment: Oct 15 to Nov 15 = 31 days.
        $this->assertEquals(
            248.00,
            (float) $amortizations[0]->interest_amount
        );

        // Principal must reconcile to the original loan amount.
        $this->assertEquals(
            12_000.00,
            round(
                $amortizations->sum(
                    fn ($item) => (float) $item->principal_amount
                ),
                2
            )
        );
    }
}
