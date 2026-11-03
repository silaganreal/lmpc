<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntrySequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param array<int, array{
     *     chart_of_account_id: int,
     *     debit?: int|float|string,
     *     credit?: int|float|string,
     *     description?: string|null
     * }> $lines
     * @return array{
     *     total_debit: float,
     *     total_credit: float,
     *     is_balanced: bool
     * }
     */
    public function validateLines(
        array $lines,
        bool $requireBalanced = false,
        bool $lockAccounts = false
    ): array {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'A journal entry must contain at least two lines.',
            ]);
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $index => $line) {
            $debit = $this->toCents($line['debit'] ?? 0);
            $credit = $this->toCents($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Negative amounts are not allowed.',
                ]);
            }

            if (($debit > 0 && $credit > 0) || ($debit === 0 && $credit === 0)) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Each line must have either a debit or a credit amount.',
                ]);
            }

            $accountQuery = ChartOfAccount::query()
                ->whereKey($line['chart_of_account_id']);

            if ($lockAccounts) {
                $accountQuery->lockForUpdate();
            }

            $account = $accountQuery->first();

            if (
                ! $account ||
                ! $account->is_active ||
                ! $account->is_posting_allowed
            ) {
                throw ValidationException::withMessages([
                    "lines.{$index}.chart_of_account_id" => 'The selected account is inactive or does not allow posting.',
                ]);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if ($requireBalanced && $totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'lines' => 'Total debit must equal total credit before posting.',
            ]);
        }

        return [
            'total_debit' => $totalDebit / 100,
            'total_credit' => $totalCredit / 100,
            'is_balanced' => $totalDebit === $totalCredit,
        ];
    }

    /**
     * Create a new journal entry as a draft.
     *
     * @param array{
     *     entry_date: string,
     *     reference_number?: string|null,
     *     description: string,
     *     source_type?: string|null,
     *     source_id?: string|null,
     *     lines: array<int, array{
     *         chart_of_account_id: int,
     *         debit?: int|float|string,
     *         credit?: int|float|string,
     *         description?: string|null
     *     }>
     * } $data
     */
    public function createDraft(array $data, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($data, $userId) {
            $this->validateLines($data['lines']);

            $entryYear = (int) substr($data['entry_date'], 0, 4);

            $entryNumber = $this->generateEntryNumber($entryYear);

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $data['entry_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'],
                'status' => 'draft',
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'created_by' => $userId,
            ]);

            $this->saveLines($entry, $data['lines']);

            return $entry->load('lines.account');
        });
    }

    /**
     * @param array{
     *     entry_date: string,
     *     reference_number?: string|null,
     *     description: string,
     *     lines: array<int, array{
     *         chart_of_account_id: int,
     *         debit?: int|float|string,
     *         credit?: int|float|string,
     *         description?: string|null
     *     }>
     * } $data
     */
    public function updateDraft(JournalEntry $entry, array $data): JournalEntry
    {
        return DB::transaction(function () use ($entry, $data) {
            $entry = JournalEntry::query()
                ->lockForUpdate()
                ->findOrFail($entry->id);

            if (! $entry->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft journal entries can be updated.',
                ]);
            }

            $this->validateLines($data['lines']);

            $entry->update([
                'entry_date' => $data['entry_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'],
            ]);

            $entry->lines()->delete();

            $this->saveLines($entry, $data['lines']);

            return $entry->load('lines.account');
        });
    }

    /**
     * Post a balanced draft journal entry.
     */
    public function post(JournalEntry $entry, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($entry, $userId) {
            $entry = JournalEntry::query()
                ->lockForUpdate()
                ->findOrFail($entry->id);

            if (! $entry->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft journal entries can be posted.',
                ]);
            }

            $lines = $entry->lines()
                ->lockForUpdate()
                ->get()
                ->map(fn ($line) => [
                    'chart_of_account_id' => (int) $line->chart_of_account_id,
                    'debit' => (string) $line->debit,
                    'credit' => (string) $line->credit,
                ])
                ->all();

            $this->validateLines($lines, requireBalanced: true, lockAccounts: true);

            $entry->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            return $entry->load('lines.account');
        });
    }

    /**
     * @param array<int, array{
     *     chart_of_account_id: int,
     *     debit?: int|float|string,
     *     credit?: int|float|string,
     *     description?: string|null
     * }> $lines
     */
    private function saveLines(JournalEntry $entry, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $entry->lines()->create([
                'chart_of_account_id' => $line['chart_of_account_id'],
                'line_number' => $index + 1,
                'description' => $line['description'] ?? null,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
            ]);
        }
    }

    /**
     * Convert a monetary amount to integer cents.
     */
    private function toCents(mixed $amount): int
    {
        if (! is_numeric($amount) || ! is_finite((float) $amount)) {
            throw ValidationException::withMessages([
                'lines' => 'Journal amounts must be valid finite numbers.',
            ]);
        }

        $value = (float) $amount;

        if ($value < 0 || round($value, 2) !== $value) {
            throw ValidationException::withMessages([
                'lines' => 'Amounts must be non-negative and have no more than two decimal places.',
            ]);
        }

        return (int) round($value * 100);
    }

    /**
     * Generate the next journal entry number for a fiscal year.
     */
    public function generateEntryNumber(?int $year = null): string
    {
        $year ??= now()->year;

        return DB::transaction(function () use ($year) {
            JournalEntrySequence::query()->insertOrIgnore([
                'fiscal_year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = JournalEntrySequence::query()
                ->where('fiscal_year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');

            return sprintf('GJ-%d-%04d', $year, $sequence->last_number);
        });
    }

    /**
     * Reverse a posted journal entry.
     */
    public function reverse(
        JournalEntry $entry,
        int $userId,
        string $reversalDate,
        string $description
    ): JournalEntry {
        try {
            return DB::transaction(function () use (
                $entry,
                $userId,
                $reversalDate,
                $description
            ) {
                $entry = JournalEntry::query()
                    ->lockForUpdate()
                    ->with('lines')
                    ->findOrFail($entry->id);

                if (! $entry->isPosted()) {
                    throw ValidationException::withMessages([
                        'status' => 'Only posted journal entries can be reversed.',
                    ]);
                }

                if ($entry->reversal_of_id !== null) {
                    throw ValidationException::withMessages([
                        'status' => 'A reversal entry cannot itself be reversed.',
                    ]);
                }

                if ($entry->reversalEntry()->exists()) {
                    throw ValidationException::withMessages([
                        'status' => 'This journal entry has already been reversed.',
                    ]);
                }

                if ($entry->lines->isEmpty()) {
                    throw ValidationException::withMessages([
                        'lines' => 'The journal entry has no lines to reverse.',
                    ]);
                }

                $reversalLines = $entry->lines->map(fn ($line) => [
                    'chart_of_account_id' => (int) $line->chart_of_account_id,
                    'description' => $line->description,
                    'debit' => (string) $line->credit,
                    'credit' => (string) $line->debit,
                ])->all();

                $this->validateLines(
                    $reversalLines,
                    requireBalanced: true,
                    lockAccounts: true
                );

                $entryYear = (int) substr($reversalDate, 0, 4);

                $reversal = JournalEntry::create([
                    'entry_number' => $this->generateEntryNumber($entryYear),
                    'entry_date' => $reversalDate,
                    'reference_number' => $entry->entry_number,
                    'description' => $description,
                    'status' => 'posted',
                    'reversal_of_id' => $entry->id,
                    'created_by' => $userId,
                    'posted_by' => $userId,
                    'posted_at' => now(),
                ]);

                foreach ($reversalLines as $index => $line) {
                    $reversal->lines()->create([
                        'chart_of_account_id' => $line['chart_of_account_id'],
                        'line_number' => $index + 1,
                        'description' => $line['description'],
                        'debit' => $line['debit'],
                        'credit' => $line['credit'],
                    ]);
                }

                $entry->update([
                    'status' => 'reversed',
                ]);

                return $reversal->load('lines.account', 'originalEntry');
            });
        } catch (QueryException $exception) {
            $isDuplicateReversal = str_contains(
                $exception->getMessage(),
                'journal_entries_reversal_of_id_unique'
            );

            if ($isDuplicateReversal) {
                throw ValidationException::withMessages([
                    'status' => 'This journal entry has already been reversed.',
                ]);
            }

            throw $exception;
        }
    }
}
