<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Services\Financial\LoanApplicationService;
use Database\Seeders\LoanProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LoanProductSeeder::class);
    }

    public function test_calculates_three_percent_cbu_retention(): void
    {
        $member = $this->createMember();
        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $result = app(LoanApplicationService::class)->calculateDeductions(
            member: $member,
            loanProduct: $product,
            principalAmount: 10_000,
            termMonths: 12,
        );

        $this->assertSame(300.0, $result['cbu_retention']);
    }

    public function test_calculates_two_percent_service_fee(): void
    {
        $member = $this->createMember();
        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $result = app(LoanApplicationService::class)->calculateDeductions(
            member: $member,
            loanProduct: $product,
            principalAmount: 10_000,
            termMonths: 12,
        );

        $this->assertSame(200.0, $result['service_fee']);
    }

    public function test_service_fee_is_capped_at_sixteen_hundred_pesos(): void
    {
        $member = $this->createMember();
        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $result = app(LoanApplicationService::class)->calculateDeductions(
            member: $member,
            loanProduct: $product,
            principalAmount: 100_000,
            termMonths: 12,
        );

        $this->assertEquals(1_600.0, $result['service_fee']);
    }

    public function test_notarial_fee_is_zero_for_loans_below_thirty_thousand(): void
    {
        $member = $this->createMember();
        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $result = app(LoanApplicationService::class)->calculateDeductions(
            member: $member,
            loanProduct: $product,
            principalAmount: 29_999,
            termMonths: 12,
        );

        $this->assertEquals(0.0, $result['notarial_fee']);
    }

    public function test_notarial_fee_is_one_hundred_fifty_for_loans_of_thirty_thousand_or_more(): void
    {
        $member = $this->createMember();
        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $result = app(LoanApplicationService::class)->calculateDeductions(
            member: $member,
            loanProduct: $product,
            principalAmount: 30_000,
            termMonths: 12,
        );

        $this->assertEquals(150.0, $result['notarial_fee']);
    }

    public function test_calculates_net_process_correctly(): void
    {
        $member = $this->createMember();
        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $result = app(LoanApplicationService::class)->calculateDeductions(
            member: $member,
            loanProduct: $product,
            principalAmount: 30_000,
            termMonths: 12,
        );

        /*
         * Principal:       30,000
         * CBU 3%:             900
         * Service fee 2%:     600
         * Insurance:            0
         * Notarial fee:       150
         * -----------------------
         * Net process:      28,350
         */
        $this->assertSame(28_350.0, $result['net_process']);
    }

    public function test_rejects_zero_principal_amount(): void
    {
        $member = $this->createMember();
        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $this->expectException(\InvalidArgumentException::class);

        app(LoanApplicationService::class)->calculateDeductions(
            member: $member,
            loanProduct: $product,
            principalAmount: 0,
            termMonths: 12,
        );
    }

    public function test_rejects_zero_term(): void
    {
        $member = $this->createMember();
        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $this->expectException(\InvalidArgumentException::class);

        app(LoanApplicationService::class)->calculateDeductions(
            member: $member,
            loanProduct: $product,
            principalAmount: 10_000,
            termMonths: 0,
        );
    }

    public function test_creates_loan_application_with_calculated_values(): void
    {
        $member = $this->createMember();

        $member->cbuAccount()->create([
            'current_balance' => 50_000,
        ]);

        $loanProduct = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $loan = app(LoanApplicationService::class)->createApplication(
            member: $member,
            loanProduct: $loanProduct,
            principalAmount: 40_000,
            termMonths: 12,
            purpose: 'Emergency expenses',
        );

        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertSame($member->id, $loan->member_id);
        $this->assertSame($loanProduct->id, $loan->loan_product_id);

        $this->assertSame(40_000.00, (float) $loan->principal_amount);
        $this->assertSame(1_200.00, (float) $loan->cbu_retention);
        $this->assertSame(800.00, (float) $loan->service_fee);
        $this->assertSame(150.00, (float) $loan->notarial_fee);
        $this->assertSame(37_850.00, (float) $loan->net_process);

        $this->assertSame('draft', $loan->status);
        $this->assertSame('Emergency expenses', $loan->purpose);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'status' => 'draft',
        ]);
    }

    public function test_rejects_loan_amount_above_maximum_loanable_amount(): void
    {
        $member = $this->createMember();

        $member->cbuAccount()->create([
            'current_balance' => 10_000,
        ]);

        $loanProduct = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $this->expectException(\InvalidArgumentException::class);

        app(LoanApplicationService::class)->createApplication(
            member: $member,
            loanProduct: $loanProduct,
            principalAmount: 100_001,
            termMonths: 12,
        );
    }

    public function test_creates_loan_with_generated_loan_number(): void
    {
        $member = $this->createMember();

        $member->cbuAccount()->create([
            'current_balance' => 50_000,
        ]);

        $loanProduct = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        $loan = app(LoanApplicationService::class)->createApplication(
            member: $member,
            loanProduct: $loanProduct,
            principalAmount: 20_000,
            termMonths: 12,
        );

        $this->assertMatchesRegularExpression(
            '/^LN-\d{4}-\d{5}$/',
            $loan->loan_no,
        );
    }

    private function createMember(): Member
    {
        return Member::create([
            'member_no' => 'MEM-'.uniqid(),
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'Member',
            'suffix' => null,
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'civil_status' => 'single',
            'nationality' => 'Filipino',
            'religion' => null,
            'place_of_birth' => 'Tacloban City',
            'tin' => null,
            'mobile' => '09170000000',
            'telephone' => null,
            'email' => null,
            'residence_type' => null,
            'membership_type' => 'regular',
            'date_joined' => now()->toDateString(),
            'status' => 'active',
        ]);
    }
}
