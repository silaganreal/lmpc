<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_collections_page_only_displays_payments_within_selected_date_range(): void
    {
        [$user, $loan] = $this->createLoanWithUser();

        $this->createPayment($loan, $user, [
            'payment_date' => '2026-10-05',
            'reference_no' => 'COL-001',
            'principal_amount' => 500,
            'interest_amount' => 50,
            'penalty_amount' => 0,
            'total_amount' => 550,
        ]);

        $this->createPayment($loan, $user, [
            'payment_date' => '2026-10-15',
            'reference_no' => 'COL-002',
            'principal_amount' => 300,
            'interest_amount' => 30,
            'penalty_amount' => 10,
            'total_amount' => 340,
        ]);

        $this->createPayment($loan, $user, [
            'payment_date' => '2026-10-25',
            'reference_no' => 'COL-003',
            'principal_amount' => 200,
            'interest_amount' => 20,
            'penalty_amount' => 0,
            'total_amount' => 220,
        ]);

        $this->actingAs($user)
            ->get(route('collections.index', [
                'date_from' => '2026-10-10',
                'date_to' => '2026-10-20',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Collections/Index')
                ->has('payments.data', 1)
                ->where('payments.data.0.reference_no', 'COL-002')
                ->where('filters.date_from', '2026-10-10')
                ->where('filters.date_to', '2026-10-20')
            );
    }

    public function test_collections_page_calculates_totals_for_all_payments_in_selected_period(): void
    {
        [$user, $loan] = $this->createLoanWithUser();

        $this->createPayment($loan, $user, [
            'payment_date' => '2026-10-05',
            'reference_no' => 'TOTAL-001',
            'principal_amount' => 500,
            'interest_amount' => 50,
            'penalty_amount' => 10,
            'total_amount' => 560,
        ]);

        $this->createPayment($loan, $user, [
            'payment_date' => '2026-10-15',
            'reference_no' => 'TOTAL-002',
            'principal_amount' => 300,
            'interest_amount' => 30,
            'penalty_amount' => 20,
            'total_amount' => 350,
        ]);

        $this->createPayment($loan, $user, [
            'payment_date' => '2026-09-25',
            'reference_no' => 'TOTAL-003',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'penalty_amount' => 50,
            'total_amount' => 1150,
        ]);

        $this->actingAs($user)
            ->get(route('collections.index', [
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Collections/Index')
                ->where('collectionTotals.transactionCount', 2)
                ->where('collectionTotals.principalAmount', 800)
                ->where('collectionTotals.interestAmount', 80)
                ->where('collectionTotals.penaltyAmount', 30)
                ->where('collectionTotals.totalAmount', 910)
            );
    }

    public function test_collection_totals_include_payments_from_all_pages(): void
    {
        [$user, $loan] = $this->createLoanWithUser();

        for ($i = 1; $i <= 20; $i++) {
            $this->createPayment($loan, $user, [
                'payment_date' => '2026-10-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'reference_no' => 'PAGE-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'principal_amount' => 100,
                'interest_amount' => 10,
                'penalty_amount' => 5,
                'total_amount' => 115,
            ]);
        }

        $this->actingAs($user)
            ->get(route('collections.index', [
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Collections/Index')
                ->has('payments.data', 15)
                ->where('payments.total', 20)
                ->where('collectionTotals.transactionCount', 20)
                ->where('collectionTotals.totalAmount', 2300)
            );
    }

    public function test_guest_cannot_access_collections_page(): void
    {
        $this->get(route('collections.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * @return array{0: User, 1: Loan}
     */
    private function createLoanWithUser(): array
    {
        $user = User::factory()->create();

        $member = Member::create([
            'member_no' => 'MEM-'.uniqid(),
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'Member',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'civil_status' => 'single',
            'nationality' => 'Filipino',
            'membership_type' => 'regular',
            'date_joined' => '2026-01-01',
            'status' => 'active',
        ]);

        $product = LoanProduct::create([
            'code' => 'TEST-'.uniqid(),
            'name' => 'Collection Test Loan',
            'interest_rate_monthly' => 1.5,
            'maximum_amount' => 100000,
            'maximum_term_months' => 12,
            'term_type' => 'monthly',
            'eligibility_requirements' => 'Testing only',
            'is_active' => true,
        ]);

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'loan_no' => 'LN-'.uniqid(),
            'application_date' => '2026-09-25',
            'approval_date' => '2026-09-25',
            'release_date' => '2026-09-25',
            'principal_amount' => 1000,
            'interest_rate_monthly' => 1.5,
            'term_months' => 12,
            'monthly_amortization' => 98.33,
            'outstanding_principal' => 1000,
            'outstanding_interest' => 180,
            'outstanding_penalty' => 0,
            'service_fee' => 20,
            'cbu_retention' => 30,
            'insurance_premium' => 0,
            'notarial_fee' => 0,
            'net_process' => 950,
            'status' => 'released',
            'purpose' => 'Testing',
            'remarks' => 'Collection test',
            'created_by' => $user->id,
        ]);

        return [$user, $loan];
    }

    private function createPayment(
        Loan $loan,
        User $user,
        array $attributes = [],
    ): LoanPayment {
        return LoanPayment::create(array_merge([
            'loan_id' => $loan->id,
            'payment_date' => '2026-10-01',
            'reference_no' => 'COL-'.uniqid(),
            'principal_amount' => 100,
            'interest_amount' => 10,
            'penalty_amount' => 0,
            'total_amount' => 110,
            'payment_method' => 'cash',
            'remarks' => 'Collection feature test',
            'created_by' => $user->id,
        ], $attributes));
    }
}
