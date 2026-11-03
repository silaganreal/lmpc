<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrialBalanceController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'as_of_date' => ['nullable', 'date'],
        ]);

        $asOfDate = $validated['as_of_date']
            ?? now()->toDateString();

        $accounts = DB::table('chart_of_accounts')
            ->leftJoin(
                'journal_entry_lines',
                'chart_of_accounts.id',
                '=',
                'journal_entry_lines.chart_of_account_id'
            )
            ->leftJoin(
                'journal_entries',
                'journal_entry_lines.journal_entry_id',
                '=',
                'journal_entries.id'
            )
            ->where('chart_of_accounts.is_active', true)
            ->where('chart_of_accounts.is_posting_allowed', true)
            ->where(function ($query) use ($asOfDate) {
                $query->whereNull('journal_entries.id')
                    ->orWhere(function ($query) use ($asOfDate) {
                        $query->whereIn('journal_entries.status', [
                            'posted',
                            'reversed',
                        ])
                            ->whereDate(
                                'journal_entries.entry_date',
                                '<=',
                                $asOfDate
                            );
                    });
            })
            ->select([
                'chart_of_accounts.id',
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance',
            ])
            ->selectRaw('
                COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit,
                COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit
            ')
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance'
            )
            ->orderBy('chart_of_accounts.account_code')
            ->get();

        $accounts = $accounts->map(function ($account) {
            $debit = (float) $account->total_debit;
            $credit = (float) $account->total_credit;

            $netBalance = $debit - $credit;

            $account->debit_balance = round(max($netBalance, 0), 2);
            $account->credit_balance = round(max(-$netBalance, 0), 2);

            return $account;
        });

        $totalDebits = round(
            (float) $accounts->sum('debit_balance'),
            2
        );

        $totalCredits = round(
            (float) $accounts->sum('credit_balance'),
            2
        );

        return Inertia::render('Accounting/Trial-Balance/Index', [
            'accounts' => $accounts,
            'summary' => [
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'difference' => round(
                    $totalDebits - $totalCredits,
                    2
                ),
                'is_balanced' => abs($totalDebits - $totalCredits) < 0.005,
            ],
            'filters' => [
                'as_of_date' => $asOfDate,
            ],
        ]);
    }
}
