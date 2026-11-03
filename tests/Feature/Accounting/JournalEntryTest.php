<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_journal_entry_draft_with_automatically_generated_entry_number(): void
    {
        $user = User::factory()->create();

        $debitAccount = ChartOfAccount::create([
            'account_code' => 'TEST-1001',
            'account_name' => 'Test Cash Account',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-2001',
            'account_name' => 'Test Payable Account',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $service = app(JournalEntryService::class);

        $entry = $service->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Test journal entry',
            'reference_number' => 'TEST-001',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'description' => 'Cash received',
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'description' => 'Payable recorded',
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ], $user->id);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertSame('GJ-2026-0001', $entry->entry_number);
        $this->assertSame('draft', $entry->status);
        $this->assertSame($user->id, $entry->created_by);
        $this->assertSame('TEST-001', $entry->reference_number);
        $this->assertCount(2, $entry->lines);
    }

    public function test_it_generates_sequential_numbers_per_fiscal_year(): void
    {
        $user = User::factory()->create();

        $debitAccount = ChartOfAccount::create([
            'account_code' => 'TEST-3001',
            'account_name' => 'Test Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-3002',
            'account_name' => 'Test Revenue',
            'account_type' => 'income',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $service = app(JournalEntryService::class);

        $createEntry = function (string $date) use (
            $service,
            $user,
            $debitAccount,
            $creditAccount
        ): JournalEntry {
            return $service->createDraft([
                'entry_date' => $date,
                'description' => 'Numbering test',
                'lines' => [
                    [
                        'chart_of_account_id' => $debitAccount->id,
                        'debit' => 500,
                        'credit' => 0,
                    ],
                    [
                        'chart_of_account_id' => $creditAccount->id,
                        'debit' => 0,
                        'credit' => 500,
                    ],
                ],
            ], $user->id);
        };

        $first = $createEntry('2026-11-01');
        $second = $createEntry('2026-11-02');
        $third = $createEntry('2027-01-01');

        $this->assertSame('GJ-2026-0001', $first->entry_number);
        $this->assertSame('GJ-2026-0002', $second->entry_number);
        $this->assertSame('GJ-2027-0001', $third->entry_number);
    }

    public function test_it_posts_a_balanced_journal_entry(): void
    {
        $user = User::factory()->create();

        $debitAccount = ChartOfAccount::create([
            'account_code' => 'TEST-4001',
            'account_name' => 'Posting Test Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-4002',
            'account_name' => 'Posting Test Payable',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $service = app(JournalEntryService::class);

        $entry = $service->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Balanced posting test',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'debit' => 1500,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 1500,
                ],
            ],
        ], $user->id);

        $postedEntry = $service->post($entry, $user->id);

        $this->assertSame('posted', $postedEntry->status);
        $this->assertSame($user->id, $postedEntry->posted_by);
        $this->assertNotNull($postedEntry->posted_at);
    }

    public function test_it_rejects_an_unbalanced_journal_entry(): void
    {
        $user = User::factory()->create();

        $debitAccount = ChartOfAccount::create([
            'account_code' => 'TEST-5001',
            'account_name' => 'Unbalanced Test Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-5002',
            'account_name' => 'Unbalanced Test Payable',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $service = app(JournalEntryService::class);

        $entry = $service->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Unbalanced posting test',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'debit' => 1500,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ], $user->id);

        try {
            $service->post($entry, $user->id);

            $this->fail('An unbalanced journal entry should not be posted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lines', $exception->errors());
        }

        $this->assertSame(
            'draft',
            $entry->fresh()->status
        );
    }

    public function test_it_rejects_inactive_accounts(): void
    {
        $user = User::factory()->create();

        $inactiveAccount = ChartOfAccount::create([
            'account_code' => 'TEST-6001',
            'account_name' => 'Inactive Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => false,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-6002',
            'account_name' => 'Test Liability',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $this->expectException(
            ValidationException::class
        );

        app(JournalEntryService::class)->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Inactive account test',
            'lines' => [
                [
                    'chart_of_account_id' => $inactiveAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ], $user->id);
    }

    public function test_it_rejects_accounts_that_do_not_allow_posting(): void
    {
        $user = User::factory()->create();

        $nonPostingAccount = ChartOfAccount::create([
            'account_code' => 'TEST-7001',
            'account_name' => 'Non Posting Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => false,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-7002',
            'account_name' => 'Test Revenue',
            'account_type' => 'income',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $this->expectException(
            ValidationException::class
        );

        app(JournalEntryService::class)->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Non-posting account test',
            'lines' => [
                [
                    'chart_of_account_id' => $nonPostingAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ], $user->id);
    }

    public function test_it_allows_updating_drafts_but_prevents_updating_posted_entries(): void
    {
        $user = User::factory()->create();

        $debitAccount = ChartOfAccount::create([
            'account_code' => 'TEST-8001',
            'account_name' => 'Draft Test Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-8002',
            'account_name' => 'Draft Test Payable',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $service = app(JournalEntryService::class);

        $entry = $service->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Original description',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ], $user->id);

        // A draft can be updated.
        $updatedEntry = $service->updateDraft($entry, [
            'entry_date' => '2026-11-02',
            'description' => 'Updated description',
            'reference_number' => 'UPDATED-001',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'debit' => 2000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 2000,
                ],
            ],
        ]);

        $this->assertSame('Updated description', $updatedEntry->description);
        $this->assertSame('2026-11-02', $updatedEntry->entry_date->toDateString());
        $this->assertSame('UPDATED-001', $updatedEntry->reference_number);

        // Once posted, the entry cannot be updated.
        $postedEntry = $service->post($updatedEntry, $user->id);

        try {
            $service->updateDraft($postedEntry, [
                'entry_date' => '2026-11-03',
                'description' => 'Should not be saved',
                'lines' => [
                    [
                        'chart_of_account_id' => $debitAccount->id,
                        'debit' => 3000,
                        'credit' => 0,
                    ],
                    [
                        'chart_of_account_id' => $creditAccount->id,
                        'debit' => 0,
                        'credit' => 3000,
                    ],
                ],
            ]);

            $this->fail('A posted journal entry should not be editable.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame('posted', $postedEntry->fresh()->status);
        $this->assertSame('Updated description', $postedEntry->fresh()->description);
    }

    public function test_it_reverses_a_posted_journal_entry(): void
    {
        $user = User::factory()->create();

        $debitAccount = ChartOfAccount::create([
            'account_code' => 'TEST-9001',
            'account_name' => 'Reversal Test Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-9002',
            'account_name' => 'Reversal Test Payable',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $service = app(JournalEntryService::class);

        $entry = $service->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Original journal entry',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'description' => 'Cash debit',
                    'debit' => 5000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'description' => 'Payable credit',
                    'debit' => 0,
                    'credit' => 5000,
                ],
            ],
        ], $user->id);

        $postedEntry = $service->post($entry, $user->id);

        $reversal = $service->reverse(
            $postedEntry,
            $user->id,
            '2026-11-02',
            'Reversal of incorrect journal entry'
        );

        $this->assertSame('reversed', $postedEntry->fresh()->status);
        $this->assertSame('posted', $reversal->status);
        $this->assertSame($postedEntry->id, $reversal->reversal_of_id);
        $this->assertSame($postedEntry->entry_number, $reversal->reference_number);
        $this->assertSame($user->id, $reversal->posted_by);

        $this->assertCount(2, $reversal->lines);

        $this->assertSame('0.00', $reversal->lines[0]->debit);
        $this->assertSame('5000.00', $reversal->lines[0]->credit);

        $this->assertSame('5000.00', $reversal->lines[1]->debit);
        $this->assertSame('0.00', $reversal->lines[1]->credit);
    }

    public function test_it_prevents_duplicate_and_nested_reversals(): void
    {
        $user = User::factory()->create();

        $debitAccount = ChartOfAccount::create([
            'account_code' => 'TEST-9101',
            'account_name' => 'Reversal Protection Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-9102',
            'account_name' => 'Reversal Protection Payable',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $service = app(JournalEntryService::class);

        $entry = $service->createDraft([
            'entry_date' => '2026-11-01',
            'description' => 'Reversal protection test',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'debit' => 3000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 3000,
                ],
            ],
        ], $user->id);

        $postedEntry = $service->post($entry, $user->id);

        $reversal = $service->reverse(
            $postedEntry,
            $user->id,
            '2026-11-02',
            'First reversal'
        );

        // The original entry cannot be reversed a second time.
        try {
            $service->reverse(
                $postedEntry,
                $user->id,
                '2026-11-03',
                'Duplicate reversal'
            );

            $this->fail('Duplicate reversal should not be allowed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        // A reversal entry cannot itself be reversed.
        try {
            $service->reverse(
                $reversal,
                $user->id,
                '2026-11-04',
                'Nested reversal'
            );

            $this->fail('A reversal entry should not be reversed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame('reversed', $postedEntry->fresh()->status);
        $this->assertSame('posted', $reversal->fresh()->status);

        $this->assertSame(
            2,
            JournalEntry::query()->count()
        );
    }

    public function test_it_prevents_duplicate_source_journal_entries(): void
    {
        $user = User::factory()->create();

        $debitAccount = ChartOfAccount::create([
            'account_code' => 'TEST-9201',
            'account_name' => 'Source Test Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $creditAccount = ChartOfAccount::create([
            'account_code' => 'TEST-9202',
            'account_name' => 'Source Test Revenue',
            'account_type' => 'income',
            'normal_balance' => 'credit',
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        $service = app(JournalEntryService::class);

        $data = [
            'entry_date' => '2026-11-01',
            'description' => 'Source transaction test',
            'source_type' => 'loan_payment',
            'source_id' => 'PAYMENT-001',
            'lines' => [
                [
                    'chart_of_account_id' => $debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ];

        $service->createDraft($data, $user->id);

        try {
            $service->createDraft($data, $user->id);

            $this->fail('Duplicate source journal entry should not be allowed.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString(
                'journal_entries_source_unique',
                $exception->getMessage()
            );
        }

        $this->assertSame(1, JournalEntry::query()->count());
    }

    public function test_journal_entry_permissions_follow_user_roles(): void
    {
        $roles = [
            'staff' => false,
            'admin' => false,
            'board' => false,
            'accounting' => true,
            'superadmin' => true,
        ];

        $entry = new JournalEntry([
            'status' => 'draft',
        ]);

        foreach ($roles as $role => $canManageAccounting) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $this->assertTrue(
                $user->can('viewAny', JournalEntry::class),
                "{$role} should be able to view journal entries."
            );

            $this->assertTrue(
                $user->can('create', JournalEntry::class),
                "{$role} should be able to create drafts."
            );

            $this->assertTrue(
                $user->can('update', $entry),
                "{$role} should be able to update drafts."
            );

            $this->assertSame(
                $canManageAccounting,
                $user->can('post', $entry),
                "{$role} posting permission is incorrect."
            );

            $postedEntry = new JournalEntry([
                'status' => 'posted',
            ]);

            $this->assertSame(
                $canManageAccounting,
                $user->can('reverse', $postedEntry),
                "{$role} reversal permission is incorrect."
            );
        }
    }
}
