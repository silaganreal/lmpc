<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\LoanAmortization;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\LoanRestructuring;
use App\Models\LoanRestructuringAmortization;
use App\Models\Member;
use App\Models\User;
use App\Services\Financial\LoanPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LoanPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoanPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LoanPaymentService::class);
    }

    public function test_it_records_a_full_first_installment_payment(): void
    {
        $loan = $this->createReleasedLoan();

        $amortization = $loan->amortizations()->first();

        $payment = $this->service->recordPayment(
            loan: $loan,
            amount: (float) $amortization->total_due,
            paymentDate: '2026-10-25',
            referenceNo: 'OR-0001',
            paymentMethod: 'cash',
        );

        $this->assertInstanceOf(LoanPayment::class, $payment);

        $this->assertSame('98.33', $payment->total_amount);

        $this->assertSame('83.33', $payment->principal_amount);

        $this->assertSame('15.00', $payment->interest_amount);

        $this->assertSame('0.00', $payment->penalty_amount);

        $amortization->refresh();

        $this->assertSame('98.33', $amortization->paid_amount);

        $this->assertSame('paid', $amortization->status);
    }

    public function test_it_supports_partial_payment(): void
    {
        $loan = $this->createReleasedLoan();

        $this->service->recordPayment(
            loan: $loan,
            amount: 50.00,
            paymentDate: '2026-10-25',
            referenceNo: 'OR-0002',
        );

        $amortization = $loan->amortizations()->first();

        $this->assertSame('50.00', $amortization->paid_amount);

        $this->assertSame('partial', $amortization->status);

        $this->assertEqualsWithDelta(
            50.00,
            (float) $amortization->paid_amount,
            0.01,
        );
    }

    public function test_it_applies_payment_to_multiple_installments(): void
    {
        $loan = $this->createReleasedLoan();

        $this->service->recordPayment(
            loan: $loan,
            amount: 196.66,
            paymentDate: '2026-10-25',
            referenceNo: 'OR-0003',
        );

        $amortizations = $loan
            ->amortizations()
            ->orderBy('installment_no')
            ->get();

        $this->assertSame('98.33', $amortizations[0]->paid_amount);

        $this->assertSame('paid', $amortizations[0]->status);

        $this->assertSame('98.33', $amortizations[1]->paid_amount);

        $this->assertSame('paid', $amortizations[1]->status);

        $this->assertSame('0.00', $amortizations[2]->paid_amount);

        $this->assertSame('pending', $amortizations[2]->status);
    }

    public function test_it_allocates_interest_before_principal(): void
    {
        $loan = $this->createReleasedLoan();

        $this->service->recordPayment(
            loan: $loan,
            amount: 20.00,
            paymentDate: '2026-10-25',
            referenceNo: 'OR-0004',
        );

        $amortization = $loan->amortizations()->first();

        $this->assertSame('20.00', $amortization->paid_amount);

        $this->assertSame('partial', $amortization->status);

        $payment = $loan->payments()->first();

        $this->assertSame('15.00', $payment->interest_amount);

        $this->assertSame('5.00', $payment->principal_amount);

        $this->assertSame('0.00', $payment->penalty_amount);
    }

    public function test_excess_payment_settles_loan_and_transfers_excess_to_cbu(): void
    {
        $loan = $this->createReleasedLoan();

        $totalDue = (float) $loan->amortizations()->sum('total_due');
        $paymentAmount = $totalDue + 100.00;

        $payment = $this->service->recordPayment(
            loan: $loan,
            amount: $paymentAmount,
            paymentDate: '2027-09-25',
            referenceNo: 'OR-OVERPAY',
        );

        $loan->refresh();

        $this->assertSame('fully_paid', $loan->status);

        $this->assertEqualsWithDelta(
            $totalDue,
            (float) $payment->total_amount,
            0.01,
        );

        $this->assertDatabaseHas('cbu_transactions', [
            'transaction_type' => 'transfer',
            'direction' => 'credit',
            'reference_no' => 'OR-OVERPAY',
            'amount' => '100.00',
        ]);

        $this->assertDatabaseHas('cbu_accounts', [
            'member_id' => $loan->member_id,
            'current_balance' => '100.00',
        ]);
    }

    public function test_it_rejects_zero_payment(): void
    {
        $loan = $this->createReleasedLoan();

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Payment amount must be greater than zero.'
        );

        $this->service->recordPayment(
            loan: $loan,
            amount: 0,
        );
    }

    public function test_it_updates_outstanding_balances(): void
    {
        $loan = $this->createReleasedLoan();

        $this->service->recordPayment(
            loan: $loan,
            amount: 98.33,
            paymentDate: '2026-10-25',
        );

        $loan->refresh();

        $this->assertEqualsWithDelta(
            916.67,
            (float) $loan->outstanding_principal,
            0.01,
        );

        $this->assertEqualsWithDelta(
            165.00,
            (float) $loan->outstanding_interest,
            0.01,
        );

        $this->assertEqualsWithDelta(
            0.00,
            (float) $loan->outstanding_penalty,
            0.01,
        );

        $this->assertSame('active', $loan->status);
    }

    public function test_it_marks_the_loan_fully_paid_after_all_installments_are_paid(): void
    {
        $loan = $this->createReleasedLoan();

        $totalPayable = $loan->amortizations()
            ->sum('total_due');

        $this->service->recordPayment(
            loan: $loan,
            amount: (float) $totalPayable,
            paymentDate: '2027-09-25',
            referenceNo: 'OR-FINAL',
        );

        $loan->refresh();

        $this->assertSame('fully_paid', $loan->status);

        $this->assertEqualsWithDelta(
            0.00,
            (float) $loan->outstanding_principal,
            0.01,
        );

        $this->assertEqualsWithDelta(
            0.00,
            (float) $loan->outstanding_interest,
            0.01,
        );

        $this->assertEqualsWithDelta(
            0.00,
            (float) $loan->outstanding_penalty,
            0.01,
        );

        $this->assertSame(
            12,
            $loan->amortizations()
                ->where('status', 'paid')
                ->count(),
        );
    }

    public function test_it_records_a_partial_restructuring_payment(): void
    {
        $loan = $this->createReleasedLoan();
        $restructuring = $this->createApprovedRestructuring($loan);

        $payments = $this->service->recordRestructuringPayment(
            loan: $loan,
            amount: 20.00,
            paymentDate: '2026-10-26',
            referenceNo: 'RST-PARTIAL-001',
        );

        $this->assertCount(1, $payments);

        $payment = $payments->first();

        $this->assertSame('20.00', $payment->total_amount);
        $this->assertSame('15.00', $payment->interest_amount);
        $this->assertSame('5.00', $payment->principal_amount);
        $this->assertSame('0.00', $payment->penalty_amount);

        $this->assertNotNull($payment->loan_restructuring_amortization_id);

        $amortization = $restructuring->amortizations()
            ->where('installment_no', 1)
            ->firstOrFail();

        $this->assertSame('20.00', $amortization->fresh()->paid_amount);
        $this->assertSame('partial', $amortization->fresh()->status);

        // Regular amortization history must remain untouched.
        $this->assertSame(
            'pending',
            $loan->amortizations()->first()->fresh()->status,
        );
    }

    public function test_it_applies_restructuring_payment_to_multiple_installments(): void
    {
        $loan = $this->createReleasedLoan();
        $restructuring = $this->createApprovedRestructuring($loan);

        $payments = $this->service->recordRestructuringPayment(
            loan: $loan,
            amount: 196.66,
            paymentDate: '2026-10-26',
            referenceNo: 'RST-MULTI-001',
        );

        $this->assertCount(2, $payments);

        $amortizations = $restructuring->amortizations()
            ->orderBy('installment_no')
            ->get();

        $this->assertSame('paid', $amortizations[0]->fresh()->status);
        $this->assertSame('98.33', $amortizations[0]->fresh()->paid_amount);

        $this->assertSame('paid', $amortizations[1]->fresh()->status);
        $this->assertSame('98.33', $amortizations[1]->fresh()->paid_amount);

        $this->assertSame('pending', $amortizations[2]->fresh()->status);

        $this->assertSame(
            2,
            LoanPayment::query()
                ->where('loan_id', $loan->id)
                ->whereNotNull('loan_restructuring_amortization_id')
                ->count(),
        );
    }

    public function test_excess_restructuring_payment_is_credited_to_cbu(): void
    {
        $loan = $this->createReleasedLoan();
        $restructuring = $this->createApprovedRestructuring($loan);

        $totalDue = (float) $restructuring->amortizations()->sum('total_due');

        $payments = $this->service->recordRestructuringPayment(
            loan: $loan,
            amount: $totalDue + 100.00,
            paymentDate: '2027-10-26',
            referenceNo: 'RST-OVERPAY-001',
        );

        $this->assertEqualsWithDelta(
            $totalDue,
            (float) $payments->sum('total_amount'),
            0.01,
        );

        $this->assertDatabaseHas('cbu_transactions', [
            'transaction_type' => 'transfer',
            'direction' => 'credit',
            'reference_no' => 'RST-OVERPAY-001',
            'amount' => '100.00',
        ]);

        $this->assertDatabaseHas('cbu_accounts', [
            'member_id' => $loan->member_id,
            'current_balance' => '100.00',
        ]);

        // The original loan's schedule and status remain unchanged.
        $loan->refresh();

        $this->assertSame('released', $loan->status);

        $this->assertSame(
            12,
            $loan->amortizations()->where('status', 'pending')->count(),
        );
    }

    private function createReleasedLoan(): Loan
    {
        $member = Member::create([
            'member_no' => 'MEM-TEST-001',
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

        $loanProduct = LoanProduct::create([
            'code' => 'TEST_LOAN',
            'name' => 'Test Loan',
            'interest_rate_monthly' => 1.50,
            'maximum_amount' => 100000,
            'maximum_term_months' => 12,
            'term_type' => 'monthly',
            'eligibility_requirements' => 'Testing only',
            'is_active' => true,
        ]);

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'loan_no' => 'LN-TEST-001',
            'application_date' => '2026-09-25',
            'approval_date' => '2026-09-25',
            'release_date' => '2026-09-25',
            'principal_amount' => 1000,
            'interest_rate_monthly' => 1.50,
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
            'remarks' => 'Loan payment service test',
        ]);

        for ($installment = 1; $installment <= 12; $installment++) {
            $principal = $installment === 12
                ? 83.37
                : 83.33;

            LoanAmortization::create([
                'loan_id' => $loan->id,
                'installment_no' => $installment,
                'due_date' => now()->addMonths($installment)->toDateString(),
                'beginning_balance' => max(
                    0,
                    1000 - (83.33 * ($installment - 1))
                ),
                'principal_amount' => $principal,
                'interest_amount' => 15,
                'penalty_amount' => 0,
                'total_due' => $principal + 15,
                'paid_amount' => 0,
                'paid_date' => null,
                'status' => 'pending',
            ]);
        }

        return $loan->fresh(['amortizations']);
    }

    private function createApprovedRestructuring(
        Loan $loan,
    ): LoanRestructuring {
        $user = User::factory()->create();

        $restructuring = LoanRestructuring::create([
            'loan_id' => $loan->id,
            'requested_by' => $user->id,
            'request_date' => '2026-10-20',
            'request_reason' => 'Testing restructuring payments',
            'requested_term_years' => 1,
            'approved_term_years' => 1,
            'status' => 'approved',
            'reviewed_by' => $user->id,
            'reviewed_at' => '2026-10-25',
            'remaining_principal' => 1000.00,
            'deferred_interest' => 0,
            'penalties' => 0,
            'restructured_at' => '2026-10-25',
        ]);

        for ($installment = 1; $installment <= 12; $installment++) {
            $principal = $installment === 12 ? 83.37 : 83.33;

            LoanRestructuringAmortization::create([
                'loan_restructuring_id' => $restructuring->id,
                'installment_no' => $installment,
                'due_date' => now()->addMonths($installment)->toDateString(),
                'beginning_balance' => max(
                    0,
                    1000 - (83.33 * ($installment - 1)),
                ),
                'principal_amount' => $principal,
                'interest_amount' => 15.00,
                'total_due' => round($principal + 15.00, 2),
                'paid_amount' => 0,
                'paid_date' => null,
                'status' => 'pending',
            ]);
        }

        return $restructuring;
    }
}
