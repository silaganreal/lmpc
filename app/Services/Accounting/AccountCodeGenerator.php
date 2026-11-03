<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountCodeGenerator
{
    private const RANGES = [
        'asset' => ['start' => 1000, 'end' => 1999],
        'liability' => ['start' => 2000, 'end' => 2999],
        'equity' => ['start' => 3000, 'end' => 3999],
        'income' => ['start' => 4000, 'end' => 4999],
        'expense' => ['start' => 5000, 'end' => 5999],
    ];

    public function generate(string $accountType): string
    {
        if (! isset(self::RANGES[$accountType])) {
            throw ValidationException::withMessages([
                'account_type' => 'Invalid account type.',
            ]);
        }

        return DB::transaction(function () use ($accountType): string {
            $range = self::RANGES[$accountType];

            // Ensure that a sequence record exists for this account type.
            DB::table('account_code_sequences')->insertOrIgnore([
                'account_type' => $accountType,
                'last_number' => $range['start'] - 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Lock this account type's sequence to prevent duplicate
            // automatic codes when multiple users create accounts.
            $sequence = DB::table('account_code_sequences')
                ->where('account_type', $accountType)
                ->lockForUpdate()
                ->first();

            $nextNumber = max(
                $range['start'],
                $sequence->last_number + 1
            );

            // Skip codes that have already been assigned manually.
            while ($nextNumber <= $range['end']) {
                $code = (string) $nextNumber;

                $exists = ChartOfAccount::query()
                    ->where('account_code', $code)
                    ->exists();

                if (! $exists) {
                    DB::table('account_code_sequences')
                        ->where('account_type', $accountType)
                        ->update([
                            'last_number' => $nextNumber,
                            'updated_at' => now(),
                        ]);

                    return $code;
                }

                $nextNumber++;
            }

            throw ValidationException::withMessages([
                'account_code' => 'No available account codes remain for this account type.',
            ]);
        });
    }
}
