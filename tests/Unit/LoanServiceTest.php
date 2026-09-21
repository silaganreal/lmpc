<?php

namespace Tests\Unit;

use App\Models\CbuAccount;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Services\Financial\LoanService;
use Database\Seeders\LoanProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LoanProductSeeder::class);
    }

    public function test_providential_one_maximum_is_two_times_cbu(): void
    {
        $member = $this->createMember();

        CbuAccount::create([
            'member_id' => $member->id,
            'current_balance' => 20_000,
            'status' => 'active',
            'opened_at' => now()->toDateString(),
        ]);

        $product = LoanProduct::where('code', 'PROVIDENTIAL_1')->firstOrFail();

        $maximum = app(LoanService::class)->maximumLoanableAmount(
            $member->fresh('cbuAccount'),
            $product,
        );

        $this->assertSame(40_000.0, $maximum);
    }

    public function test_providential_two_maximum_is_three_times_cbu(): void
    {
        $member = $this->createMember();

        CbuAccount::create([
            'member_id' => $member->id,
            'current_balance' => 20_000,
            'status' => 'active',
            'opened_at' => now()->toDateString(),
        ]);

        $product = LoanProduct::where('code', 'PROVIDENTIAL_2')->firstOrFail();

        $maximum = app(LoanService::class)->maximumLoanableAmount(
            $member->fresh('cbuAccount'),
            $product,
        );

        $this->assertSame(60_000.0, $maximum);
    }

    public function test_fixed_limit_products_return_their_maximum_amount(): void
    {
        $member = $this->createMember();

        CbuAccount::create([
            'member_id' => $member->id,
            'current_balance' => 20_000,
            'status' => 'active',
            'opened_at' => now()->toDateString(),
        ]);

        $service = app(LoanService::class);

        $limits = [
            'EMERGENCY' => 100_000,
            'EDUCATIONAL' => 50_000,
            'APPLIANCE' => 80_000,
            'MEDICAL' => 50_000,
            'HMO' => 50_000,
        ];

        foreach ($limits as $code => $expectedMaximum) {
            $product = LoanProduct::where('code', $code)->firstOrFail();

            $maximum = $service->maximumLoanableAmount(
                $member->fresh('cbuAccount'),
                $product,
            );

            $this->assertSame((float) $expectedMaximum, $maximum);
        }
    }

    public function test_single_borrower_limit_is_four_times_cbu(): void
    {
        $member = $this->createMember();

        CbuAccount::create([
            'member_id' => $member->id,
            'current_balance' => 20_000,
            'status' => 'active',
            'opened_at' => now()->toDateString(),
        ]);

        $limit = app(LoanService::class)->singleBorrowerLimit(
            $member->fresh('cbuAccount'),
        );

        $this->assertSame(80_000.0, $limit);
    }

    public function test_providential_two_requires_at_least_sixteen_thousand_cbu(): void
    {
        $member = $this->createMember();

        CbuAccount::create([
            'member_id' => $member->id,
            'current_balance' => 15_999,
            'status' => 'active',
            'opened_at' => now()->toDateString(),
        ]);

        $this->assertFalse(
            app(LoanService::class)->canAvailProvidentialTwo(
                $member->fresh('cbuAccount', 'loans'),
            ),
        );
    }

    public function test_providential_two_requires_fully_paid_providential_one(): void
    {
        $member = $this->createMember();

        CbuAccount::create([
            'member_id' => $member->id,
            'current_balance' => 20_000,
            'status' => 'active',
            'opened_at' => now()->toDateString(),
        ]);

        $product = LoanProduct::where('code', 'PROVIDENTIAL_1')->firstOrFail();

        Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'loan_no' => 'LN-TEST-0001',
            'application_date' => now()->toDateString(),
            'principal_amount' => 20_000,
            'interest_rate_monthly' => 1.50,
            'term_months' => 12,
            'outstanding_principal' => 20_000,
            'outstanding_interest' => 0,
            'outstanding_penalty' => 0,
            'service_fee' => 0,
            'cbu_retention' => 0,
            'insurance_premium' => 0,
            'notarial_fee' => 0,
            'net_process' => 20_000,
            'status' => 'active',
        ]);

        $this->assertFalse(
            app(LoanService::class)->canAvailProvidentialTwo(
                $member->fresh('cbuAccount', 'loans'),
            ),
        );
    }

    public function test_providential_two_is_allowed_when_requirements_are_met(): void
    {
        $member = $this->createMember();

        CbuAccount::create([
            'member_id' => $member->id,
            'current_balance' => 20_000,
            'status' => 'active',
            'opened_at' => now()->toDateString(),
        ]);

        $product = LoanProduct::where('code', 'PROVIDENTIAL_1')->firstOrFail();

        Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'loan_no' => 'LN-TEST-0002',
            'application_date' => now()->toDateString(),
            'principal_amount' => 20_000,
            'interest_rate_monthly' => 1.50,
            'term_months' => 12,
            'outstanding_principal' => 0,
            'outstanding_interest' => 0,
            'outstanding_penalty' => 0,
            'service_fee' => 0,
            'cbu_retention' => 0,
            'insurance_premium' => 0,
            'notarial_fee' => 0,
            'net_process' => 20_000,
            'status' => 'fully_paid',
        ]);

        $this->assertTrue(
            app(LoanService::class)->canAvailProvidentialTwo(
                $member->fresh('cbuAccount', 'loans'),
            ),
        );
    }

    public function test_providential_two_is_not_allowed_when_member_has_past_due_loan(): void
    {
        $member = $this->createMember();

        CbuAccount::create([
            'member_id' => $member->id,
            'current_balance' => 20_000,
            'status' => 'active',
            'opened_at' => now()->toDateString(),
        ]);

        $product = LoanProduct::where('code', 'EMERGENCY')->firstOrFail();

        Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'loan_no' => 'LN-TEST-0003',
            'application_date' => now()->toDateString(),
            'principal_amount' => 10_000,
            'interest_rate_monthly' => 2.00,
            'term_months' => 12,
            'outstanding_principal' => 10_000,
            'outstanding_interest' => 0,
            'outstanding_penalty' => 0,
            'service_fee' => 0,
            'cbu_retention' => 0,
            'insurance_premium' => 0,
            'notarial_fee' => 0,
            'net_process' => 10_000,
            'status' => 'past_due',
        ]);

        $this->assertFalse(
            app(LoanService::class)->canAvailProvidentialTwo(
                $member->fresh('cbuAccount', 'loans'),
            ),
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
