<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\LoanProductSeeder;
// use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApplicationTest extends TestCase
{
    use RefreshDatabase;
    // use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LoanProductSeeder::class);
    }

    public function test_authenticated_user_can_view_loan_application_page(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->get(route('loans.create'));

        $response->assertSuccessful();
    }

    public function test_authenticated_user_can_create_loan_application(): void
    {
        $user = $this->createUser();

        $member = $this->createMember();

        $member->cbuAccount()->create([
            'current_balance' => 50_000,
        ]);

        $loanProduct = LoanProduct::where('code', 'EMERGENCY')
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->post(route('loans.store'), [
                'member_id' => $member->id,
                'loan_product_id' => $loanProduct->id,
                'principal_amount' => 40_000,
                'term_months' => 12,
                'purpose' => 'Emergency expenses',
                'remarks' => 'Test loan application',
            ]);

        // $response->dump();
        // $response->dumpSession();

        $loan = Loan::query()
            ->where('member_id', $member->id)
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(
            route('loans.show', $loan)
        );

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'status' => 'draft',
        ]);

        $this->assertSame(
            40_000.00,
            (float) $loan->principal_amount
        );

        $this->assertSame(
            1_200.00,
            (float) $loan->cbu_retention
        );

        $this->assertSame(
            800.00,
            (float) $loan->service_fee
        );

        $this->assertSame(
            150.00,
            (float) $loan->notarial_fee
        );

        $this->assertSame(
            37_850.00,
            (float) $loan->net_process
        );

        $this->assertSame(
            $user->id,
            $loan->created_by
        );
    }

    public function test_loan_application_requires_member(): void
    {
        $user = $this->createUser();

        $loanProduct = LoanProduct::where('code', 'EMERGENCY')
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->post(route('loans.store'), [
                'loan_product_id' => $loanProduct->id,
                'principal_amount' => 40_000,
                'term_months' => 12,
            ]);

        $response->assertSessionHasErrors([
            'member_id',
        ]);
    }

    public function test_loan_application_requires_loan_product(): void
    {
        $user = $this->createUser();

        $member = $this->createMember();

        $response = $this
            ->actingAs($user)
            ->post(route('loans.store'), [
                'member_id' => $member->id,
                'principal_amount' => 40_000,
                'term_months' => 12,
            ]);

        $response->assertSessionHasErrors([
            'loan_product_id',
        ]);
    }

    public function test_loan_application_rejects_zero_principal(): void
    {
        $user = $this->createUser();

        $member = $this->createMember();

        $loanProduct = LoanProduct::where('code', 'EMERGENCY')
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->post(route('loans.store'), [
                'member_id' => $member->id,
                'loan_product_id' => $loanProduct->id,
                'principal_amount' => 0,
                'term_months' => 12,
            ]);

        $response->assertSessionHasErrors([
            'principal_amount',
        ]);
    }

    public function test_loan_application_rejects_zero_term(): void
    {
        $user = $this->createUser();

        $member = $this->createMember();

        $loanProduct = LoanProduct::where('code', 'EMERGENCY')
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->post(route('loans.store'), [
                'member_id' => $member->id,
                'loan_product_id' => $loanProduct->id,
                'principal_amount' => 40_000,
                'term_months' => 0,
            ]);

        $response->assertSessionHasErrors([
            'term_months',
        ]);
    }

    public function test_loan_application_rejects_amount_above_maximum(): void
    {
        $user = $this->createUser();

        $member = $this->createMember();

        $member->cbuAccount()->create([
            'current_balance' => 10_000,
        ]);

        $loanProduct = LoanProduct::where('code', 'EMERGENCY')
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->post(route('loans.store'), [
                'member_id' => $member->id,
                'loan_product_id' => $loanProduct->id,
                'principal_amount' => 100_001,
                'term_months' => 12,
            ]);

        $response->assertSessionHasErrors([
            'principal_amount',
        ]);
    }

    private function createUser()
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
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
