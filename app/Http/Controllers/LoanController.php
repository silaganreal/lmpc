<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanApplicationRequest;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Services\Financial\LoanAmortizationService;
use App\Services\Financial\LoanApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanApplicationService $loanApplicationService,
        private readonly LoanAmortizationService $loanAmortizationService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Loans/Create', [
            'members' => Member::query()
                ->where('status', 'active')
                ->orderBy('last_name')
                ->get([
                    'id',
                    'member_no',
                    'first_name',
                    'middle_name',
                    'last_name',
                ]),

            'loanProducts' => LoanProduct::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function createForMember(Member $member): Response
    {
        return Inertia::render('Loans/Create', [
            'members' => Member::query()
                ->where('status', 'active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get([
                    'id',
                    'member_no',
                    'first_name',
                    'middle_name',
                    'last_name',
                ]),

            'loanProducts' => LoanProduct::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'code',
                    'name',
                    'eligibility_requirements',
                ]),

            'selectedMember' => $member,
        ]);
    }

    public function store(
        StoreLoanApplicationRequest $request,
    ): RedirectResponse {
        $validated = $request->validated();

        /** @var Member $member */
        $member = Member::query()->findOrFail($validated['member_id']);

        /** @var LoanProduct $loanProduct */
        $loanProduct = LoanProduct::query()->findOrFail(
            $validated['loan_product_id']
        );

        try {
            $loan = $this->loanApplicationService->createApplication(
                member: $member,
                loanProduct: $loanProduct,
                principalAmount: (float) $validated['principal_amount'],
                termMonths: (int) $validated['term_months'],
                purpose: $validated['purpose'] ?? null,
                remarks: $validated['remarks'] ?? null,
                createdBy: $request->user()?->id,
            );
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'principal_amount' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan application created successfully.');
    }

    public function submit(Loan $loan): RedirectResponse
    {
        if ($loan->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Only draft loan applications can be submitted.',
            ]);
        }

        $loan->update([
            'status' => 'pending',
        ]);

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan application submitted for review.');
    }

    public function show(Loan $loan): Response
    {
        $loan->load([
            'member',
            'loanProduct',
            'amortizations',
            'payments',
        ]);

        // dd($loan->toArray());

        return Inertia::render('Loans/Show', [
            'loan' => $loan,
        ]);
    }

    public function approve(Loan $loan): RedirectResponse
    {
        if (! in_array($loan->status, ['draft', 'pending'], true)) {
            return back()->with(
                'error',
                'Only draft or pending loans can be approved.'
            );
        }

        $loan->update([
            'status' => 'approved',
            'approval_date' => now(),
            'approved_by' => auth()->id(),
        ]);

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan application approved successfully.');
    }

    public function reject(Loan $loan): RedirectResponse
    {
        if (! in_array($loan->status, ['draft', 'pending'], true)) {
            return back()->with(
                'error',
                'Only draft or pending loans can be rejected.'
            );
        }

        $loan->update([
            'status' => 'rejected',
        ]);

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan application rejected.');
    }

    public function release(Loan $loan): RedirectResponse
    {
        if ($loan->status !== 'approved') {
            return back()->with(
                'error',
                'Only approved loans can be released.'
            );
        }

        $loan->update([
            'status' => 'released',
            'release_date' => now(),
        ]);

        $this->loanAmortizationService->generate($loan->fresh());

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan released and amortization schedule generated successfully.');
    }
}
