<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_debit_and_credit_normal_accounts_show_correct_balances(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            '1000',
            'Cash',
            'asset',
            'debit'
        );

        $payable = $this->createAccount(
            '2000',
            'Accounts Payable',
            'liability',
            'credit'
        );

        $this->createPostedEntry(
            $user->id,
            '2026-11-01',
            [
                ['account' => $cash, 'debit' => 1000, 'credit' => 0],
                ['account' => $payable, 'debit' => 0, 'credit' => 1000],
            ]
        );

        $this->actingAs($user)
            ->get(route('accounting.trial-balance.index', [
                'as_of_date' => '2026-11-03',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Trial-Balance/Index')
                ->has('accounts', 2)
                ->where('accounts.0.account_code', '1000')
                ->where('accounts.0.debit_balance', 1000)
                ->where('accounts.0.credit_balance', 0)
                ->where('accounts.1.account_code', '2000')
                ->where('accounts.1.debit_balance', 0)
                ->where('accounts.1.credit_balance', 1000)
                ->where('summary.total_debits', 1000)
                ->where('summary.total_credits', 1000)
                ->where('summary.difference', 0)
                ->where('summary.is_balanced', true)
            );
    }

    public function test_only_transactions_on_or_before_the_as_of_date_are_included(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            '1000',
            'Cash',
            'asset',
            'debit'
        );

        $payable = $this->createAccount(
            '2000',
            'Accounts Payable',
            'liability',
            'credit'
        );

        $this->createPostedEntry(
            $user->id,
            '2026-11-01',
            [
                ['account' => $cash, 'debit' => 100, 'credit' => 0],
                ['account' => $payable, 'debit' => 0, 'credit' => 100],
            ]
        );

        $this->createPostedEntry(
            $user->id,
            '2026-11-03',
            [
                ['account' => $cash, 'debit' => 200, 'credit' => 0],
                ['account' => $payable, 'debit' => 0, 'credit' => 200],
            ]
        );

        $this->createPostedEntry(
            $user->id,
            '2026-11-04',
            [
                ['account' => $cash, 'debit' => 400, 'credit' => 0],
                ['account' => $payable, 'debit' => 0, 'credit' => 400],
            ]
        );

        $this->actingAs($user)
            ->get(route('accounting.trial-balance.index', [
                'as_of_date' => '2026-11-03',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Trial-Balance/Index')
                ->where('accounts.0.debit_balance', 300)
                ->where('accounts.1.credit_balance', 300)
                ->where('summary.total_debits', 300)
                ->where('summary.total_credits', 300)
                ->where('filters.as_of_date', '2026-11-03')
            );
    }

    public function test_original_and_reversal_entries_cancel_each_other(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            '1000',
            'Cash',
            'asset',
            'debit'
        );

        $payable = $this->createAccount(
            '2000',
            'Accounts Payable',
            'liability',
            'credit'
        );

        $original = $this->createPostedEntry(
            $user->id,
            '2026-11-01',
            [
                ['account' => $cash, 'debit' => 1500, 'credit' => 0],
                ['account' => $payable, 'debit' => 0, 'credit' => 1500],
            ]
        );

        app(JournalEntryService::class)->reverse(
            $original,
            $user->id,
            '2026-11-02',
            'Test reversal'
        );

        $this->actingAs($user)
            ->get(route('accounting.trial-balance.index', [
                'as_of_date' => '2026-11-02',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Trial-Balance/Index')
                ->where('accounts.0.debit_balance', 0)
                ->where('accounts.0.credit_balance', 0)
                ->where('accounts.1.debit_balance', 0)
                ->where('accounts.1.credit_balance', 0)
                ->where('summary.total_debits', 0)
                ->where('summary.total_credits', 0)
                ->where('summary.is_balanced', true)
            );
    }

    public function test_draft_journal_entries_are_excluded(): void
    {
        $user = User::factory()->create();

        $cash = $this->createAccount(
            '1000',
            'Cash',
            'asset',
            'debit'
        );

        $payable = $this->createAccount(
            '2000',
            'Accounts Payable',
            'liability',
            'credit'
        );

        app(JournalEntryService::class)->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Unposted transaction',
            'lines' => [
                [
                    'chart_of_account_id' => $cash->id,
                    'debit' => 500,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $payable->id,
                    'debit' => 0,
                    'credit' => 500,
                ],
            ],
        ], $user->id);

        $this->actingAs($user)
            ->get(route('accounting.trial-balance.index', [
                'as_of_date' => '2026-11-03',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Trial-Balance/Index')
                ->where('summary.total_debits', 0)
                ->where('summary.total_credits', 0)
                ->where('summary.is_balanced', true)
            );
    }

    public function test_inactive_and_non_posting_accounts_are_excluded(): void
    {
        $user = User::factory()->create();

        $this->createAccount(
            '1000',
            'Active Cash',
            'asset',
            'debit'
        );

        $this->createAccount(
            '1001',
            'Inactive Cash',
            'asset',
            'debit',
            isActive: false
        );

        $this->createAccount(
            '1002',
            'Non Posting Cash',
            'asset',
            'debit',
            isPostingAllowed: false
        );

        $this->actingAs($user)
            ->get(route('accounting.trial-balance.index', [
                'as_of_date' => '2026-11-03',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Trial-Balance/Index')
                ->has('accounts', 1)
                ->where('accounts.0.account_code', '1000')
            );
    }

    public function test_unauthenticated_users_cannot_view_trial_balance(): void
    {
        $this->get(route('accounting.trial-balance.index'))
            ->assertRedirect(route('login'));
    }

    private function createAccount(
        string $code,
        string $name,
        string $type,
        string $normalBalance,
        bool $isActive = true,
        bool $isPostingAllowed = true
    ): ChartOfAccount {
        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'is_posting_allowed' => $isPostingAllowed,
            'is_active' => $isActive,
        ]);
    }

    /**
     * @param array<int, array{
     *     account: ChartOfAccount,
     *     debit: int|float,
     *     credit: int|float
     * }> $lines
     */
    private function createPostedEntry(
        int $userId,
        string $date,
        array $lines
    ) {
        $service = app(JournalEntryService::class);

        $entryLines = array_map(
            fn (array $line) => [
                'chart_of_account_id' => $line['account']->id,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ],
            $lines
        );

        $entry = $service->createDraft([
            'entry_date' => $date,
            'description' => 'Trial balance test transaction',
            'lines' => $entryLines,
        ], $userId);

        return $service->post($entry, $userId);
    }
}
