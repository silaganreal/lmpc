<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GeneralLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_debit_increases_a_debit_normal_account_balance(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            'TEST-GL-1001',
            'GL Test Cash',
            'asset',
            'debit'
        );

        $payable = $this->createAccount(
            'TEST-GL-2001',
            'GL Test Payable',
            'liability',
            'credit'
        );

        $this->createPostedEntry(
            $user->id,
            '2026-11-01',
            $cash,
            $payable,
            1000
        );

        $response = $this->actingAs($user)->get(
            route('accounting.general-ledger.show', [
                'chartOfAccount' => $cash->id,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])
        );

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/General-Ledger/Show')
            ->where('openingBalance', 0)
            ->where('summary.total_debit', 1000)
            ->where('summary.total_credit', 0)
            ->where('summary.closing_balance', 1000)
            ->has('transactions', 1)
        );
    }

    public function test_credit_decreases_a_debit_normal_account_balance(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            'TEST-GL-1002',
            'GL Test Cash Credit',
            'asset',
            'debit'
        );

        $otherAsset = $this->createAccount(
            'TEST-GL-1003',
            'GL Test Other Asset',
            'asset',
            'debit'
        );

        $this->createPostedEntry(
            $user->id,
            '2026-11-01',
            $otherAsset,
            $cash,
            250
        );

        $response = $this->actingAs($user)->get(
            route('accounting.general-ledger.show', [
                'chartOfAccount' => $cash->id,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])
        );

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/General-Ledger/Show')
            ->where('summary.total_debit', 0)
            ->where('summary.total_credit', 250)
            ->where('summary.closing_balance', -250)
            ->has('transactions', 1)
        );
    }

    public function test_credit_increases_a_credit_normal_account_balance(): void
    {
        $user = User::factory()->create();

        $payable = $this->createAccount(
            'TEST-GL-2002',
            'GL Test Credit Normal Payable',
            'liability',
            'credit'
        );

        $cash = $this->createAccount(
            'TEST-GL-1004',
            'GL Test Credit Normal Cash',
            'asset',
            'debit'
        );

        $this->createPostedEntry(
            $user->id,
            '2026-11-01',
            $cash,
            $payable,
            750
        );

        $response = $this->actingAs($user)->get(
            route('accounting.general-ledger.show', [
                'chartOfAccount' => $payable->id,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])
        );

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/General-Ledger/Show')
            ->where('summary.total_debit', 0)
            ->where('summary.total_credit', 750)
            ->where('summary.closing_balance', 750)
            ->has('transactions', 1)
        );
    }

    public function test_previous_month_transactions_are_included_in_opening_balance(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            'TEST-GL-1005',
            'GL Test Opening Balance Cash',
            'asset',
            'debit'
        );

        $payable = $this->createAccount(
            'TEST-GL-2003',
            'GL Test Opening Balance Payable',
            'liability',
            'credit'
        );

        // Previous-month transaction.
        $this->createPostedEntry(
            $user->id,
            '2026-10-20',
            $cash,
            $payable,
            1000
        );

        // Transaction within the selected period.
        $this->createPostedEntry(
            $user->id,
            '2026-11-05',
            $cash,
            $payable,
            500
        );

        $response = $this->actingAs($user)->get(
            route('accounting.general-ledger.show', [
                'chartOfAccount' => $cash->id,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])
        );

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/General-Ledger/Show')
            ->where('openingBalance', 1000)
            ->where('summary.total_debit', 500)
            ->where('summary.total_credit', 0)
            ->where('summary.closing_balance', 1500)
            ->has('transactions', 1)
        );
    }

    public function test_original_and_reversal_entries_cancel_each_other_in_the_ledger(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            'TEST-GL-1006',
            'GL Test Reversal Cash',
            'asset',
            'debit'
        );

        $payable = $this->createAccount(
            'TEST-GL-2004',
            'GL Test Reversal Payable',
            'liability',
            'credit'
        );

        $service = app(JournalEntryService::class);

        $original = $service->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'General Ledger reversal test',
            'lines' => [
                [
                    'chart_of_account_id' => $cash->id,
                    'debit' => 5000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $payable->id,
                    'debit' => 0,
                    'credit' => 5000,
                ],
            ],
        ], $user->id);

        $postedOriginal = $service->post($original, $user->id);

        $service->reverse(
            $postedOriginal,
            $user->id,
            '2026-11-02',
            'Reversal of General Ledger test entry'
        );

        $response = $this->actingAs($user)->get(
            route('accounting.general-ledger.show', [
                'chartOfAccount' => $cash->id,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])
        );

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/General-Ledger/Show')
            ->where('summary.total_debit', 5000)
            ->where('summary.total_credit', 5000)
            ->where('summary.closing_balance', 0)
            ->has('transactions', 2)
            ->where('transactions.0.status', 'reversed')
            ->where('transactions.1.status', 'posted')
        );
    }

    public function test_draft_journal_entries_are_excluded_from_the_ledger(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            'TEST-GL-1007',
            'GL Test Draft Cash',
            'asset',
            'debit'
        );

        $payable = $this->createAccount(
            'TEST-GL-2005',
            'GL Test Draft Payable',
            'liability',
            'credit'
        );

        // Create a draft but do not post it.
        app(JournalEntryService::class)->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Draft exclusion test',
            'lines' => [
                [
                    'chart_of_account_id' => $cash->id,
                    'debit' => 3000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $payable->id,
                    'debit' => 0,
                    'credit' => 3000,
                ],
            ],
        ], $user->id);

        $response = $this->actingAs($user)->get(
            route('accounting.general-ledger.show', [
                'chartOfAccount' => $cash->id,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])
        );

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/General-Ledger/Show')
            ->where('openingBalance', 0)
            ->where('summary.total_debit', 0)
            ->where('summary.total_credit', 0)
            ->where('summary.closing_balance', 0)
            ->has('transactions', 0)
        );
    }

    private function createAccount(
        string $code,
        string $name,
        string $type,
        string $normalBalance
    ): ChartOfAccount {
        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);
    }

    private function createPostedEntry(
        int $userId,
        string $date,
        ChartOfAccount $debitAccount,
        ChartOfAccount $creditAccount,
        float $amount
    ): void {
        $service = app(JournalEntryService::class);

        $entry = $service->createDraft([
            'entry_date' => $date,
            'description' => 'General Ledger scenario test',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ], $userId);

        $service->post($entry, $userId);
    }
}
