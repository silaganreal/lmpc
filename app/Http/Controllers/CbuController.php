<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\Financial\CbuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CbuController extends Controller
{
    public function create(Member $member): Response
    {
        $member->load('cbuAccount');

        return Inertia::render('Cbu/Create', [
            'member' => $member,
        ]);
    }

    public function store(
        Request $request,
        Member $member
    ): RedirectResponse {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        app(CbuService::class)->contribute(
            $member,
            (float) $validated['amount'],
            $validated['transaction_date'],
            $validated['reference_no'] ?? null,
            $validated['description'] ?? null,
            (int) auth()->id()
        );

        return redirect()
            ->route('members.show', $member)
            ->with(
                'success',
                'CBU contribution recorded successfully.'
            );
    }
}
