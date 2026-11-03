<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GeneralLedgerController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $today = now()->toDateString();

        $startDate = $validated['start_date']
            ?? now()->startOfMonth()->toDateString();

        $endDate = $validated['end_date'] ?? $today;

        $accounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_posting_allowed', true)
            ->when(
                $validated['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('account_code', 'like', "%{$search}%")
                            ->orWhere('account_name', 'like', "%{$search}%");
                    });
                }
            )
            ->with('parent:id,account_code,account_name')
            ->orderBy('account_code')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Accounting/General-Ledger/Index', [
            'accounts' => $accounts,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function show(
        Request $request,
        ChartOfAccount $chartOfAccount
    ): Response {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        abort_unless(
            $chartOfAccount->is_active && $chartOfAccount->is_posting_allowed,
            404
        );

        $startDate = $validated['start_date']
            ?? now()->startOfMonth()->toDateString();

        $endDate = $validated['end_date']
            ?? now()->toDateString();

        $baseQuery = DB::table('journal_entry_lines')
            ->join(
                'journal_entries',
                'journal_entry_lines.journal_entry_id',
                '=',
                'journal_entries.id'
            )
            ->where(
                'journal_entry_lines.chart_of_account_id',
                $chartOfAccount->id
            )
            ->whereIn('journal_entries.status', ['posted', 'reversed']);

        $openingTotals = (clone $baseQuery)
            ->where('journal_entries.entry_date', '<', $startDate)
            ->selectRaw('
                COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit,
                COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit
            ')
            ->first();

        $openingDebit = (float) $openingTotals->total_debit;
        $openingCredit = (float) $openingTotals->total_credit;

        $openingBalance = $chartOfAccount->normal_balance === 'debit'
            ? $openingDebit - $openingCredit
            : $openingCredit - $openingDebit;

        $transactions = (clone $baseQuery)
            ->whereBetween('journal_entries.entry_date', [
                $startDate,
                $endDate,
            ])
            ->select([
                'journal_entry_lines.id',
                'journal_entry_lines.journal_entry_id',
                'journal_entry_lines.line_number',
                'journal_entry_lines.description as line_description',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit',
                'journal_entries.entry_number',
                'journal_entries.entry_date',
                'journal_entries.reference_number',
                'journal_entries.description as entry_description',
                'journal_entries.status',
            ])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.journal_entry_id')
            ->orderBy('journal_entry_lines.line_number')
            ->get();

        $runningBalance = $openingBalance;

        $transactions = $transactions->map(function ($transaction) use (
            &$runningBalance,
            $chartOfAccount
        ) {
            $debit = (float) $transaction->debit;
            $credit = (float) $transaction->credit;

            $movement = $chartOfAccount->normal_balance === 'debit'
                ? $debit - $credit
                : $credit - $debit;

            $runningBalance += $movement;

            $transaction->running_balance = round($runningBalance, 2);

            return $transaction;
        });

        $totalDebit = round(
            (float) $transactions->sum('debit'),
            2
        );

        $totalCredit = round(
            (float) $transactions->sum('credit'),
            2
        );

        return Inertia::render('Accounting/General-Ledger/Show', [
            'account' => $chartOfAccount,
            'openingBalance' => round($openingBalance, 2),
            'transactions' => $transactions,
            'summary' => [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => round($runningBalance, 2),
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
