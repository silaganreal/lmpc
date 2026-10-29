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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_it_rejects_non_finite_regular_payment_amounts(): void
    {
        $loan = $this->createReleasedLoan();

        foreach ([INF, NAN] as $amount) {
            try {
                $this->service->recordPayment(
                    loan: $loan,
                    amount: $amount,
                );

                $this->fail('A non-finite payment amount should be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Payment amount must be greater than zero.',
                    $exception->getMessage(),
                );
            }
        }

        $this->assertSame(0, $loan->payments()->count());
    }

    public function test_it_rejects_regular_payment_amounts_that_round_to_zero(): void
    {
        $loan = $this->createReleasedLoan();

        try {
            $this->service->recordPayment(
                loan: $loan,
                amount: 0.001,
            );

            $this->fail('An amount that rounds to zero should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Payment amount must be greater than zero.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(0, $loan->payments()->count());
        $this->assertSame(
            'pending',
            $loan->amortizations()->first()->fresh()->status,
        );
    }

    public function test_it_rejects_non_finite_restructuring_payment_amounts(): void
    {
        $loan = $this->createReleasedLoan();
        $this->createApprovedRestructuring($loan);

        foreach ([INF, NAN] as $amount) {
            try {
                $this->service->recordRestructuringPayment(
                    loan: $loan,
                    amount: $amount,
                    paymentDate: '2026-10-28',
                );

                $this->fail('A non-finite payment amount should be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Payment amount must be greater than zero.',
                    $exception->getMessage(),
                );
            }
        }

        $this->assertSame(0, $loan->payments()->count());
        $this->assertSame(
            'pending',
            $loan->restructurings()->first()
                ->amortizations()->first()->fresh()->status,
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

    public function test_restructuring_payment_does_not_change_original_loan_balances(): void
    {
        $loan = $this->createReleasedLoan();
        $this->createApprovedRestructuring($loan);

        $originalPrincipal = (float) $loan->outstanding_principal;
        $originalInterest = (float) $loan->outstanding_interest;
        $originalPenalty = (float) $loan->outstanding_penalty;
        $originalStatus = $loan->status;

        $this->service->recordRestructuringPayment(
            loan: $loan,
            amount: 20.00,
            paymentDate: '2026-10-26',
            referenceNo: 'RST-INTEGRITY-001',
        );

        $loan->refresh();

        $this->assertEqualsWithDelta(
            $originalPrincipal,
            (float) $loan->outstanding_principal,
            0.01,
        );

        $this->assertEqualsWithDelta(
            $originalInterest,
            (float) $loan->outstanding_interest,
            0.01,
        );

        $this->assertEqualsWithDelta(
            $originalPenalty,
            (float) $loan->outstanding_penalty,
            0.01,
        );

        $this->assertSame($originalStatus, $loan->status);
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

        $this->assertEqualsWithDelta(
            100.00,
            (float) $payments->sum('total_amount') + 100.00 - $totalDue,
            0.01,
        );

        $this->assertSame(
            12,
            $restructuring->amortizations()
                ->where('status', 'paid')
                ->count(),
        );

        $this->assertSame(
            0,
            $restructuring->amortizations()
                ->whereIn('status', ['pending', 'partial'])
                ->count(),
        );

        // The original loan's schedule and status remain unchanged.
        $loan->refresh();

        $this->assertSame('released', $loan->status);

        $this->assertSame(
            12,
            $loan->amortizations()->where('status', 'pending')->count(),
        );
    }

    public function test_it_rejects_restructuring_payment_when_no_installments_are_unpaid(): void
    {
        $loan = $this->createReleasedLoan();
        $restructuring = $this->createApprovedRestructuring($loan);

        $restructuring->amortizations()->update([
            'paid_amount' => DB::raw('total_due'),
            'paid_date' => '2026-10-26',
            'status' => 'paid',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'This loan has no approved restructuring with unpaid installments.'
        );

        $this->service->recordRestructuringPayment(
            loan: $loan,
            amount: 100.00,
            paymentDate: '2026-10-26',
            referenceNo: 'RST-ALL-PAID',
        );
    }

    public function test_it_rejects_a_duplicate_payment_reference_for_the_same_loan(): void
    {
        $loan = $this->createReleasedLoan();

        $this->service->recordPayment(
            loan: $loan,
            amount: 20.00,
            paymentDate: '2026-10-28',
            referenceNo: 'OR-DUPLICATE-001',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A payment with this reference number has already been recorded for this loan.'
        );

        $this->service->recordPayment(
            loan: $loan,
            amount: 20.00,
            paymentDate: '2026-10-28',
            referenceNo: 'OR-DUPLICATE-001',
        );
    }

    public function test_it_rejects_a_reference_already_used_for_a_regular_payment_when_restructuring(): void
    {
        $loan = $this->createReleasedLoan();
        $this->createApprovedRestructuring($loan);

        $this->service->recordPayment(
            loan: $loan,
            amount: 20.00,
            paymentDate: '2026-10-28',
            referenceNo: 'OR-CROSS-TYPE-001',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A payment with this reference number has already been recorded for this loan.'
        );

        $this->service->recordRestructuringPayment(
            loan: $loan,
            amount: 20.00,
            paymentDate: '2026-10-28',
            referenceNo: 'OR-CROSS-TYPE-001',
        );
    }

    public function test_it_allows_the_same_reference_for_different_loans(): void
    {
        $firstLoan = $this->createReleasedLoan();

        // Create a second member and loan using the existing helper's pattern.
        $secondMember = Member::create([
            'member_no' => 'MEM-TEST-002',
            'first_name' => 'Second',
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

        $secondLoan = Loan::create([
            'member_id' => $secondMember->id,
            'loan_product_id' => $firstLoan->loan_product_id,
            'loan_no' => 'LN-TEST-002',
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
            'remarks' => 'Second loan payment service test',
        ]);

        for ($installment = 1; $installment <= 12; $installment++) {
            $principal = $installment === 12 ? 83.37 : 83.33;

            LoanAmortization::create([
                'loan_id' => $secondLoan->id,
                'installment_no' => $installment,
                'due_date' => now()->addMonths($installment)->toDateString(),
                'beginning_balance' => max(
                    0,
                    1000 - (83.33 * ($installment - 1)),
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

        $reference = 'OR-DIFFERENT-LOANS-001';

        $this->service->recordPayment(
            loan: $firstLoan,
            amount: 20,
            paymentDate: '2026-10-28',
            referenceNo: $reference,
        );

        $payment = $this->service->recordPayment(
            loan: $secondLoan->fresh(),
            amount: 20,
            paymentDate: '2026-10-28',
            referenceNo: $reference,
        );

        $this->assertSame($reference, $payment->reference_no);

        $this->assertSame(
            1,
            LoanPayment::query()
                ->where('loan_id', $firstLoan->id)
                ->where('reference_no', $reference)
                ->count(),
        );

        $this->assertSame(
            1,
            LoanPayment::query()
                ->where('loan_id', $secondLoan->id)
                ->where('reference_no', $reference)
                ->count(),
        );
    }

    public function test_it_allows_blank_and_null_payment_references(): void
    {
        $loan = $this->createReleasedLoan();

        $blankReferencePayment = $this->service->recordPayment(
            loan: $loan,
            amount: 20,
            paymentDate: '2026-10-28',
            referenceNo: '',
        );

        $nullReferencePayment = $this->service->recordPayment(
            loan: $loan,
            amount: 20,
            paymentDate: '2026-10-28',
            referenceNo: null,
        );

        $this->assertSame('', $blankReferencePayment->reference_no);
        $this->assertNull($nullReferencePayment->reference_no);

        $this->assertSame(2, LoanPayment::query()
            ->where('loan_id', $loan->id)
            ->count());
    }

    public function test_member_cannot_be_deleted_when_member_has_a_loan(): void
    {
        $loan = $this->createReleasedLoan();
        $member = $loan->member;

        try {
            $member->delete();
        } catch (QueryException) {
            $this->assertDatabaseHas('members', [
                'id' => $member->id,
            ]);

            $this->assertDatabaseHas('loans', [
                'id' => $loan->id,
            ]);

            return;
        }

        $this->fail(
            'A member with an associated loan should not be deletable.'
        );
    }

    public function test_loan_cannot_be_deleted_when_loan_has_payment_records(): void
    {
        $loan = $this->createReleasedLoan();

        $payment = LoanPayment::create([
            'loan_id' => $loan->id,
            'payment_date' => '2026-10-28',
            'principal_amount' => 50.00,
            'interest_amount' => 10.00,
            'penalty_amount' => 0.00,
            'total_amount' => 60.00,
            'payment_method' => 'cash',
            'reference_no' => 'TEST-DELETE-001',
        ]);

        try {
            $loan->delete();
        } catch (QueryException) {
            $this->assertDatabaseHas('loans', [
                'id' => $loan->id,
            ]);

            $this->assertDatabaseHas('loan_payments', [
                'id' => $payment->id,
                'loan_id' => $loan->id,
            ]);

            return;
        }

        $this->fail(
            'A loan with payment records should not be deletable.'
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
