<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRestructuring;
use App\Models\Member;
use App\Models\User;
use App\Services\Financial\LoanPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class LoanPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_uses_restructured_schedule_when_unpaid_restructuring_exists(): void
    {
        [$user, $loan] = $this->createLoanWithUser();

        $restructuring = $this->createRestructuring($loan, $user);

        $restructuring->amortizations()->create([
            'installment_no' => 1,
            'due_date' => '2026-11-25',
            'beginning_balance' => 1000,
            'principal_amount' => 80,
            'interest_amount' => 20,
            'total_due' => 100,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        $service = $this->mock(LoanPaymentService::class, function (
            MockInterface $mock,
        ): void {
            $mock->shouldReceive('recordRestructuringPayment')
                ->once();

            $mock->shouldNotReceive('recordPayment');
        });

        $response = $this->actingAs($user)
            ->post(route('loans.payments.store', $loan), [
                'amount' => 100,
                'payment_date' => '2026-10-27',
                'payment_method' => 'cash',
                'reference_no' => 'TEST-RESTRUCTURED',
            ]);

        $response->assertRedirect(route('loans.show', $loan));

        $response->assertSessionHas(
            'success',
            'Loan payment recorded successfully.',
        );

        $this->assertDatabaseHas('loan_restructurings', [
            'id' => $restructuring->id,
            'loan_id' => $loan->id,
            'status' => 'approved',
        ]);
    }

    public function test_payment_uses_original_schedule_when_no_unpaid_restructuring_exists(): void
    {
        [$user, $loan] = $this->createLoanWithUser();

        $this->mock(LoanPaymentService::class, function (
            MockInterface $mock,
        ): void {
            $mock->shouldReceive('recordPayment')
                ->once();

            $mock->shouldNotReceive('recordRestructuringPayment');
        });

        $response = $this->actingAs($user)
            ->post(route('loans.payments.store', $loan), [
                'amount' => 100,
                'payment_date' => '2026-10-27',
                'payment_method' => 'cash',
                'reference_no' => 'TEST-ORIGINAL',
            ]);

        $response->assertRedirect(route('loans.show', $loan));

        $response->assertSessionHas(
            'success',
            'Loan payment recorded successfully.',
        );

        $this->assertDatabaseMissing('loan_restructurings', [
            'loan_id' => $loan->id,
            'status' => 'approved',
        ]);
    }

    public function test_payment_uses_original_schedule_when_restructuring_has_no_unpaid_installments(): void
    {
        [$user, $loan] = $this->createLoanWithUser();

        $restructuring = $this->createRestructuring($loan, $user);

        // Mark the restructuring installment as paid.
        $restructuring->amortizations()->create([
            'installment_no' => 1,
            'due_date' => '2026-10-25',
            'beginning_balance' => 100,
            'principal_amount' => 100,
            'interest_amount' => 0,
            'total_due' => 100,
            'paid_amount' => 100,
            'paid_date' => '2026-10-25',
            'status' => 'paid',
        ]);

        $this->mock(LoanPaymentService::class, function (
            MockInterface $mock,
        ): void {
            $mock->shouldReceive('recordPayment')
                ->once();

            $mock->shouldNotReceive('recordRestructuringPayment');
        });

        $response = $this->actingAs($user)
            ->post(route('loans.payments.store', $loan), [
                'amount' => 100,
                'payment_date' => '2026-10-27',
                'payment_method' => 'cash',
                'reference_no' => 'TEST-COMPLETED-RESTRUCTURE',
            ]);

        $response->assertRedirect(route('loans.show', $loan));

        $response->assertSessionHas(
            'success',
            'Loan payment recorded successfully.',
        );

        $this->assertDatabaseHas('loan_restructurings', [
            'id' => $restructuring->id,
            'status' => 'approved',
        ]);
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
            'name' => 'Controller Test Loan',
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
            'remarks' => 'Controller test',
            'created_by' => $user->id,
        ]);

        return [$user, $loan];
    }

    private function createRestructuring(
        Loan $loan,
        User $user,
    ): LoanRestructuring {
        return LoanRestructuring::create([
            'loan_id' => $loan->id,
            'requested_by' => $user->id,
            'request_date' => '2026-10-20',
            'request_reason' => 'Testing',
            'requested_term_years' => 1,
            'approved_term_years' => 1,
            'status' => 'approved',
            'reviewed_by' => $user->id,
            'reviewed_at' => '2026-10-25',
            'remaining_principal' => 1000,
            'deferred_interest' => 0,
            'penalties' => 0,
            'restructured_at' => '2026-10-25',
        ]);
    }
}
