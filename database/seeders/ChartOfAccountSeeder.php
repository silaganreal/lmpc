<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Services\Accounting\AccountCodeGenerator;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(AccountCodeGenerator $accountCodeGenerator): void
    {
        $accounts = [
            // ASSETS
            ['Cash', 'asset', 'debit'],
            ['Cash in Bank', 'asset', 'debit'],
            ['Loans Receivable - Current', 'asset', 'debit'],
            ['Cash in Bank II', 'asset', 'debit'],
            ['Loans Receivable - Past Due', 'asset', 'debit'],
            ['Other Funds and Deposits', 'asset', 'debit'],
            ['Allowance for Probable Losses', 'asset', 'credit'],
            ['Trade Receivables', 'asset', 'debit'],
            ['Other Receivables - Officer', 'asset', 'debit'],
            ['Unused Office Supplies', 'asset', 'debit'],
            ['Office Equipment', 'asset', 'debit'],
            ['Accumulated Depreciation - Office Equipment', 'asset', 'credit'],
            ['Inventory - Load', 'asset', 'debit'],

            // LIABILITIES
            ['Members Deposits', 'liability', 'credit'],
            ['Notarial Payable', 'liability', 'credit'],
            ['Accrued Expense Payable - General Assembly', 'liability', 'credit'],
            ['Accrued Expense Payable - Professional Fee', 'liability', 'credit'],
            ['Accrued Interest Expense Payable - Members Deposit', 'liability', 'credit'],
            ['Accrued Expense Payable - Officers/Members Benefits', 'liability', 'credit'],
            ['Insurance Premium Payable', 'liability', 'credit'],
            ['Accounts Payable', 'liability', 'credit'],
            ['SSS/Pag-IBIG/PhilHealth Premium Payable', 'liability', 'credit'],
            ['Retirement Fund Payable', 'liability', 'credit'],
            ['Mutual Aid Fund Payable', 'liability', 'credit'],

            // EQUITY / FUNDS
            ['Paid Up Share Capital', 'equity', 'credit'],
            ['Interest on Share Capital and Patronage Refund', 'equity', 'credit'],
            ['General Reserve Fund', 'equity', 'credit'],
            ['Cooperative Education and Training Fund', 'equity', 'credit'],
            ['Optional Fund - Community Development (3%)', 'equity', 'credit'],
            ['Optional Fund - Land and Building (7%)', 'equity', 'credit'],
        ];

        foreach ($accounts as [$name, $type, $normalBalance]) {
            $this->seedAccount(
                $name,
                $type,
                $normalBalance,
                $accountCodeGenerator
            );
        }

        // INCOME AND EXPENSE ACCOUNTS FROM LMPC INCOME STATEMENT WORKBOOK
        $incomeAndExpenseAccounts = [
            // INCOME
            ['INTEREST INCOME FROM LOANS', 'income', 'credit'],
            ['SERVICE FEE', 'income', 'credit'],
            ['FINES/PENALTIES', 'income', 'credit'],
            ['MEMBERSHIP FEE', 'income', 'credit'],
            ['MISC. INCOME', 'income', 'credit'],
            ['SALES(LOAD)', 'income', 'credit'],

            // EXPENSES
            ['INTEREST EXPENSE ON MEMBERS DEPOSIT', 'expense', 'debit'],
            ['SALARIES &WAGES', 'expense', 'debit'],
            ['OVERTIME', 'expense', 'debit'],
            ['EMPLOYEES BENEFIT (MEAL & LOAD ALLOWANCE/BONUS)', 'expense', 'debit'],
            ['MEMBERS BENEFITS', 'expense', 'debit'],
            ['OFFICERS BENEFIT/MEMBERS BENEFIT', 'expense', 'debit'],
            ['EMPLOYEES BENEFITS (SSS/PAG.IBIG/PHILHEALTH)-CONTRIBUTION', 'expense', 'debit'],
            ['MUTUAL AID BENEFITS', 'expense', 'debit'],
            ['RETIREMENT BENEFITS', 'expense', 'debit'],
            ['OFFICE SUPPLIES', 'expense', 'debit'],
            ['TAXES & LICENSES', 'expense', 'debit'],
            ['TRAVEL & TRANSPORTATION', 'expense', 'debit'],
            ['GENERAL ASSEMBLY EXPENSE', 'expense', 'debit'],
            ['MEETING & CONFERENCES', 'expense', 'debit'],
            ['RERESENTATION EXPENSE', 'expense', 'debit'],
            ['PROFESSIONAL FEE EXPENSE', 'expense', 'debit'],
            ['AFFILIATION FEE', 'expense', 'debit'],
            ['REPAIR & MAINTENANCE', 'expense', 'debit'],
            ['PROVISION FOR LOAN LOSS', 'expense', 'debit'],
            ['DEPRECIATION EXPENSE (Office Equipment)', 'expense', 'debit'],
            ['COMMUNICATION EXPENSE', 'expense', 'debit'],
            ['MISCELLANEOUS EXPENSE', 'expense', 'debit'],
            ['PURCHASES(LOAD)', 'expense', 'debit'],
        ];

        foreach ($incomeAndExpenseAccounts as [$name, $type, $normalBalance]) {
            $this->seedAccount(
                $name,
                $type,
                $normalBalance,
                $accountCodeGenerator
            );
        }
    }

    private function seedAccount(
        string $name,
        string $type,
        string $normalBalance,
        AccountCodeGenerator $accountCodeGenerator
    ): void {
        $account = ChartOfAccount::firstOrNew([
            'account_name' => $name,
        ]);

        $account->fill([
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'parent_id' => null,
            'is_posting_allowed' => true,
            'is_active' => true,
        ]);

        // Generate a code only when the account has no existing code.
        if (blank($account->account_code)) {
            $account->account_code = $accountCodeGenerator->generate($type);
        }

        $account->save();
    }
}
