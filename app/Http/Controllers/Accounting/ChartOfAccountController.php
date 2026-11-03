<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Http\Requests\UpdateChartOfAccountRequest;
use App\Http\Requests\UpdateChartOfAccountStatusRequest;
use App\Models\ChartOfAccount;
use App\Services\Accounting\AccountCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'account_type' => [
                'nullable',
                'in:asset,liability,equity,income,expense',
            ],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $accounts = ChartOfAccount::query()
            ->with('parent:id,account_name')
            ->when(
                $validated['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('account_name', 'like', "%{$search}%")
                            ->orWhere('account_code', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $validated['account_type'] ?? null,
                fn ($query, $type) => $query->where('account_type', $type)
            )
            ->when(
                $validated['status'] ?? null,
                fn ($query, $status) => $query->where(
                    'is_active',
                    $status === 'active'
                )
            )
            ->orderBy('account_type')
            ->orderBy('account_name')
            ->paginate(15)
            ->withQueryString();

        $parentAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get(['id', 'account_name']);

        $allAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get([
                'id',
                'account_name',
                'account_type',
                'parent_id',
            ]);

        return Inertia::render('Accounting/Chart-Of-Accounts/Index', [
            'accounts' => $accounts,
            'parentAccounts' => $parentAccounts,
            'allAccounts' => $allAccounts,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'account_type' => $validated['account_type'] ?? '',
                'status' => $validated['status'] ?? 'active',
            ],
        ]);
    }

    public function store(
        StoreChartOfAccountRequest $request,
        AccountCodeGenerator $accountCodeGenerator
    ): RedirectResponse {
        $validated = $request->validated();

        // Automatically generate an account code if none was provided.
        if (blank($validated['account_code'] ?? null)) {
            $validated['account_code'] = $accountCodeGenerator->generate(
                $validated['account_type']
            );
        }

        ChartOfAccount::create($validated);

        return redirect()
            ->route('accounting.chart-of-accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function update(
        UpdateChartOfAccountRequest $request,
        ChartOfAccount $chartOfAccount
    ): RedirectResponse {
        $validated = $request->validated();

        // Preserve the existing account code when the field is left blank.
        if (blank($validated['account_code'] ?? null)) {
            $validated['account_code'] = $chartOfAccount->account_code;
        }

        if ($validated['parent_id'] !== null) {
            $descendantIds = collect([$chartOfAccount->id]);

            $currentParentIds = collect([$chartOfAccount->id]);

            while ($currentParentIds->isNotEmpty()) {
                $childIds = ChartOfAccount::query()
                    ->whereIn('parent_id', $currentParentIds)
                    ->pluck('id');

                $descendantIds = $descendantIds->merge($childIds);
                $currentParentIds = $childIds;
            }

            if ($descendantIds->contains($validated['parent_id'])) {
                return back()
                    ->withErrors([
                        'parent_id' => 'An account cannot be assigned to itself or one of its descendants.',
                    ])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($chartOfAccount, $validated) {
            $chartOfAccount->update($validated);
        });

        return redirect()
            ->route('accounting.chart-of-accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function updateStatus(
        UpdateChartOfAccountStatusRequest $request,
        ChartOfAccount $chartOfAccount
    ): RedirectResponse {
        $isActive = $request->boolean('is_active');

        if (! $isActive) {
            $hasActiveChildren = $chartOfAccount->children()
                ->where('is_active', true)
                ->exists();

            if ($hasActiveChildren) {
                return back()
                    ->withErrors([
                        'status' => 'This account cannot be deactivated because it has active child accounts. Deactivate its child accounts first.',
                    ]);
            }
        }

        $chartOfAccount->update([
            'is_active' => $isActive,
        ]);

        return redirect()
            ->route('accounting.chart-of-accounts.index')
            ->with(
                'success',
                $isActive
                    ? 'Account activated successfully.'
                    : 'Account deactivated successfully.'
            );
    }
}
