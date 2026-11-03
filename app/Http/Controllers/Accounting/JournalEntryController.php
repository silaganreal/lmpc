<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', JournalEntry::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in(['draft', 'posted', 'reversed']),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $entries = JournalEntry::query()
            ->with([
                'creator:id,name',
                'poster:id,name',
                'originalEntry:id,entry_number',
                'reversalEntry:id,entry_number,reversal_of_id',
            ])
            ->withSum('lines as total_debit', 'debit')
            ->withSum('lines as total_credit', 'credit')
            ->when(
                $validated['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('entry_number', 'like', "%{$search}%")
                            ->orWhere('reference_number', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $validated['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $validated['date_from'] ?? null,
                fn ($query, $date) => $query->whereDate('entry_date', '>=', $date)
            )
            ->when(
                $validated['date_to'] ?? null,
                fn ($query, $date) => $query->whereDate('entry_date', '<=', $date)
            )
            ->latest('entry_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $chartOfAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_posting_allowed', true)
            ->orderBy('account_code')
            ->orderBy('account_name')
            ->get([
                'id',
                'account_code',
                'account_name',
                'account_type',
                'normal_balance',
            ]);

        return Inertia::render('Accounting/Journal-Entries/Index', [
            'entries' => $entries,
            'chartOfAccounts' => $chartOfAccounts,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'status' => $validated['status'] ?? '',
                'date_from' => $validated['date_from'] ?? '',
                'date_to' => $validated['date_to'] ?? '',
            ],
            'permissions' => [
                'canCreate' => $request->user()->can('create', JournalEntry::class),
            ],
        ]);
    }

    public function store(
        Request $request,
        JournalEntryService $journalEntryService
    ): RedirectResponse {
        Gate::authorize('create', JournalEntry::class);

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => [
                'required',
                'integer',
                'exists:chart_of_accounts,id',
            ],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        $entry = $journalEntryService->createDraft(
            $validated,
            $request->user()->id
        );

        return redirect()
            ->route('accounting.journal-entries.index')
            ->with('success', "Journal entry {$entry->entry_number} saved as draft.");
    }

    public function show(JournalEntry $journalEntry): Response
    {
        Gate::authorize('view', $journalEntry);

        $journalEntry->load([
            'lines.account',
            'creator:id,name',
            'poster:id,name',
            'originalEntry:id,entry_number,entry_date',
            'reversalEntry:id,entry_number,entry_date',
        ]);

        return Inertia::render('Accounting/Journal-Entries/Show', [
            'entry' => $journalEntry,
            'permissions' => [
                'canPost' => request()->user()->can('post', $journalEntry),
                'canReverse' => request()->user()->can('reverse', $journalEntry),
            ],
        ]);
    }

    public function edit(JournalEntry $journalEntry): Response
    {
        Gate::authorize('update', $journalEntry);

        $journalEntry->load('lines.account');

        $chartOfAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_posting_allowed', true)
            ->orderBy('account_code')
            ->orderBy('account_name')
            ->get([
                'id',
                'account_code',
                'account_name',
                'account_type',
                'normal_balance',
            ]);

        return Inertia::render('Accounting/Journal-Entries/Edit', [
            'entry' => $journalEntry,
            'chartOfAccounts' => $chartOfAccounts,
        ]);
    }

    public function update(
        Request $request,
        JournalEntry $journalEntry,
        JournalEntryService $journalEntryService
    ): RedirectResponse {
        Gate::authorize('update', $journalEntry);

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => [
                'required',
                'integer',
                'exists:chart_of_accounts,id',
            ],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        $entry = $journalEntryService->updateDraft(
            $journalEntry,
            $validated
        );

        return redirect()
            ->route('accounting.journal-entries.show', $entry)
            ->with('success', "Journal entry {$entry->entry_number} updated successfully.");
    }

    public function post(
        JournalEntry $journalEntry,
        JournalEntryService $journalEntryService
    ): RedirectResponse {
        Gate::authorize('post', $journalEntry);

        $entry = $journalEntryService->post(
            $journalEntry,
            request()->user()->id
        );

        return redirect()
            ->route('accounting.journal-entries.show', $entry)
            ->with(
                'success',
                "Journal entry {$entry->entry_number} posted successfully."
            );
    }

    public function reverse(
        Request $request,
        JournalEntry $journalEntry,
        JournalEntryService $journalEntryService
    ): RedirectResponse {
        Gate::authorize('reverse', $journalEntry);

        $validated = $request->validate([
            'reversal_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $reversalEntry = $journalEntryService->reverse(
            $journalEntry,
            $request->user()->id,
            $validated['reversal_date'],
            $validated['description']
        );

        return redirect()
            ->route('accounting.journal-entries.show', $reversalEntry)
            ->with(
                'success',
                "Journal entry {$journalEntry->entry_number} reversed successfully. Reversal entry: {$reversalEntry->entry_number}."
            );
    }
}
