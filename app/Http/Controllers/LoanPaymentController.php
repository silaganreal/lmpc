<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanPaymentRequest;
use App\Models\Loan;
use App\Models\LoanRestructuring;
use App\Services\Financial\LoanPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class LoanPaymentController extends Controller
{
    public function __construct(
        private readonly LoanPaymentService $loanPaymentService,
    ) {}

    public function store(
        StoreLoanPaymentRequest $request,
        Loan $loan,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $hasUnpaidRestructuring = LoanRestructuring::query()
                ->where('loan_id', $loan->id)
                ->where('status', 'approved')
                ->whereHas('amortizations', function ($query): void {
                    $query->whereIn('status', ['pending', 'partial']);
                })
                ->exists();

            $paymentArguments = [
                'loan' => $loan,
                'amount' => (float) $validated['amount'],
                'paymentDate' => $validated['payment_date'],
                'referenceNo' => $validated['reference_no'] ?? null,
                'paymentMethod' => $validated['payment_method'],
                'remarks' => $validated['remarks'] ?? null,
                'createdBy' => $request->user()?->id,
            ];

            if ($hasUnpaidRestructuring) {
                $this->loanPaymentService->recordRestructuringPayment(
                    ...$paymentArguments,
                );
            } else {
                $this->loanPaymentService->recordPayment(
                    ...$paymentArguments,
                );
            }
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'amount' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan payment recorded successfully.');
    }
}
