<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanRestructuring;
use App\Services\Financial\LoanRestructuringAmortizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LoanRestructuringController extends Controller
{
    public function store(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $validated = $request->validate([
            'request_date' => ['required', 'date'],
            'request_reason' => ['required', 'string', 'max:5000'],
            'requested_term_years' => [
                'required',
                'integer',
                Rule::in([1, 2, 3, 4, 5]),
            ],
        ]);

        if (! in_array($loan->status, ['released', 'active', 'past_due'], true)) {
            return back()->withErrors([
                'requested_term_years' => 'Only released or active loans can be submitted for restructuring.',
            ]);
        }

        $hasPendingRequest = LoanRestructuring::query()
            ->where('loan_id', $loan->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingRequest) {
            return back()->withErrors([
                'requested_term_years' => 'This loan already has a pending restructuring request.',
            ]);
        }

        LoanRestructuring::create([
            'loan_id' => $loan->id,
            'requested_by' => $request->user()->id,
            'request_date' => $validated['request_date'],
            'request_reason' => $validated['request_reason'],
            'requested_term_years' => $validated['requested_term_years'],
            'status' => 'pending',
        ]);

        return back()->with(
            'success',
            'Restructuring request submitted for board review.'
        );
    }

    public function index(): Response
    {
        abort_unless(
            request()->user()?->hasRole('admin', 'board'),
            403
        );

        $restructurings = LoanRestructuring::query()
            // ->with(['loan.member', 'requestedBy:id,name', 'reviewedBy:id,name'])
            ->with(['loan.member', 'requestedBy', 'reviewedBy'])
            ->latest()
            ->paginate(15);

        return Inertia::render(
            'Loan-Restructurings/Index',
            [
                'restructurings' => $restructurings,
                // 'debug' => $restructurings->getCollection()->map(fn ($item) => [
                //     'request_id' => $item->id,
                //     'requested_by' => $item->requested_by,
                //     'requester_exists' => $item->requestedBy !== null,
                //     'request_date' => $item->request_date,
                // ]),
            ]
        );
    }

    public function approve(
        Request $request,
        LoanRestructuring $loanRestructuring,
        LoanRestructuringAmortizationService $amortizationService
    ): RedirectResponse {
        abort_unless(
            $request->user()?->hasRole('admin', 'board'),
            403
        );

        if ($loanRestructuring->requested_by === $request->user()->id) {
            abort(403, 'You cannot approve your own restructuring request.');
        }

        if ($loanRestructuring->status !== 'pending') {
            return back()->withErrors([
                'status' => 'Only pending requests can be approved.',
            ]);
        }

        $validated = $request->validate([
            'approved_term_years' => [
                'required',
                'integer',
                Rule::in([1, 2, 3, 4, 5]),
            ],
            'board_remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use (
            $request,
            $loanRestructuring,
            $validated,
            $amortizationService
        ): void {
            $restructuring = LoanRestructuring::query()
                ->lockForUpdate()
                ->findOrFail($loanRestructuring->id);

            if ($restructuring->status !== 'pending') {
                abort(422, 'This request has already been reviewed.');
            }

            $loan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($restructuring->loan_id);

            $restructuredDate = now()->toDateString();

            $restructuring->update([
                'approved_term_years' => $validated['approved_term_years'],
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => $restructuredDate,
                'board_remarks' => $validated['board_remarks'] ?? null,
                'remaining_principal' => $loan->outstanding_principal,
                'deferred_interest' => $loan->outstanding_interest,
                'penalties' => $loan->outstanding_penalty,
                'restructured_at' => $restructuredDate,
            ]);

            // Generate the separate schedule for the approved restructuring.
            $amortizationService->generate($restructuring);
        });

        return back()->with(
            'success',
            'Restructuring approved and the new amortization schedule generated.'
        );
    }

    public function reject(
        Request $request,
        LoanRestructuring $loanRestructuring
    ): RedirectResponse {
        abort_unless(
            $request->user()?->hasRole('admin', 'board'),
            403
        );

        if ($loanRestructuring->requested_by === $request->user()->id) {
            abort(403, 'You cannot review your own restructuring request.');
        }

        if ($loanRestructuring->status !== 'pending') {
            return back()->withErrors([
                'status' => 'Only pending requests can be rejected.',
            ]);
        }

        $validated = $request->validate([
            'board_remarks' => ['required', 'string', 'max:5000'],
        ]);

        $loanRestructuring->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now()->toDateString(),
            'board_remarks' => $validated['board_remarks'],
        ]);

        return back()->with('success', 'Restructuring request rejected.');
    }
}
