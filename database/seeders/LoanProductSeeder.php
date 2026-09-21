<?php

namespace Database\Seeders;

use App\Models\LoanProduct;
use Illuminate\Database\Seeder;

class LoanProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'code' => 'PROVIDENTIAL_1',
                'name' => 'Providential 1',
                'interest_rate_monthly' => 1.50,
                'maximum_amount' => null,
                'maximum_term_months' => 36,
                'term_type' => 'monthly',
                'eligibility_requirements' => 'Maximum loanable amount is 2x CBU. Availment is 1 month after payment of the minimum CBU. Re-availment or renewal requires no past due loan in all loan windows within the term of the loan.',
                'is_active' => true,
            ],
            [
                'code' => 'PROVIDENTIAL_2',
                'name' => 'Providential 2',
                'interest_rate_monthly' => 2.00,
                'maximum_amount' => null,
                'maximum_term_months' => 36,
                'term_type' => 'monthly',
                'eligibility_requirements' => 'Maximum loanable amount is 3x CBU. Requires good track record, CBU of at least P16,000.00, no past due loan in any loan window, and fully paid Providential 1.',
                'is_active' => true,
            ],
            [
                'code' => 'EMERGENCY',
                'name' => 'Emergency Loan',
                'interest_rate_monthly' => 2.00,
                'maximum_amount' => 100000.00,
                'maximum_term_months' => 12,
                'term_type' => 'monthly',
                'eligibility_requirements' => 'Maximum loanable amount is P100,000.00. Availment is 1 month after payment of CBU.',
                'is_active' => true,
            ],
            [
                'code' => 'EDUCATIONAL',
                'name' => 'Educational Loan',
                'interest_rate_monthly' => 1.50,
                'maximum_amount' => 50000.00,
                'maximum_term_months' => 12,
                'term_type' => 'monthly',
                'eligibility_requirements' => 'Maximum loanable amount is P50,000.00. Availment is 1 month after payment of CBU. No past due in any loan window. Must present tuition assessment or billing statement from the school.',
                'is_active' => true,
            ],
            [
                'code' => 'APPLIANCE',
                'name' => 'Appliance Loan',
                'interest_rate_monthly' => 2.00,
                'maximum_amount' => 80000.00,
                'maximum_term_months' => 36,
                'term_type' => 'monthly',
                'eligibility_requirements' => 'Maximum loanable amount is P80,000.00. For purchase of laptops, gadgets and home appliances. Requires good track record and CBU of P16,000.00.',
                'is_active' => true,
            ],
            [
                'code' => 'MEDICAL',
                'name' => 'Medical Loan',
                'interest_rate_monthly' => 2.00,
                'maximum_amount' => 50000.00,
                'maximum_term_months' => 12,
                'term_type' => 'monthly',
                'eligibility_requirements' => 'Maximum loanable amount is P50,000.00. Requires good track record.',
                'is_active' => true,
            ],
            [
                'code' => 'HMO',
                'name' => 'HMO Loan',
                'interest_rate_monthly' => 2.00,
                'maximum_amount' => 50000.00,
                'maximum_term_months' => 12,
                'term_type' => 'monthly',
                'eligibility_requirements' => 'Maximum loanable amount is P50,000.00. Renewable every 6 months, provided 50% of the outstanding balance has been paid.',
                'is_active' => true,
            ],
            [
                'code' => 'LAB',
                'name' => 'LAB Loan',
                'interest_rate_monthly' => 2.00,
                'maximum_amount' => null,
                'maximum_term_months' => null,
                'term_type' => 'lump_sum',
                'eligibility_requirements' => 'Loanable amount is 70% of expected employee benefits. Requires LandBank employee status, receipt of regular benefits, and proof of documents as basis to determine the loanable amount. Mode of payment is lump sum.',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            LoanProduct::updateOrCreate(
                ['code' => $product['code']],
                $product,
            );
        }
    }
}
