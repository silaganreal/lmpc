<?php

namespace Database\Seeders;

use App\Models\CbuAccount;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\ShareAccount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed development/test data.
     */
    public function run(): void
    {
        /*
         * Development user
         */
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Development Admin',
                'password' => Hash::make('password'),
            ]
        );

        /*
         * Development member
         */
        $member = Member::updateOrCreate(
            ['member_no' => 'MEM-2026-00001'],
            [
                'application_no' => null,
                'first_name' => 'Juan',
                'middle_name' => 'Dela',
                'last_name' => 'Cruz',
                'suffix' => null,
                'date_of_birth' => '1990-01-15',
                'sex' => 'male',
                'civil_status' => 'married',
                'nationality' => 'Filipino',
                'religion' => null,
                'place_of_birth' => 'Tacloban City',
                'tin' => null,
                'mobile_number' => '09171234567',
                'telephone_number' => null,
                'email' => 'juan.cruz@example.com',
                'residence_type' => 'owned',
                'membership_type' => 'regular',
                'date_joined' => '2026-01-15',
                'status' => 'active',
            ]
        );

        /*
         * CBU account
         */
        CbuAccount::updateOrCreate(
            ['member_id' => $member->id],
            [
                'current_balance' => 20000,
                'status' => 'active',
                'opened_at' => '2026-01-15',
            ]
        );

        /*
         * Share account
         */
        ShareAccount::updateOrCreate(
            ['member_id' => $member->id],
            [
                'shares_subscribed' => 200,
                'total_subscribed_amount' => 20000,
                'paid_up_amount' => 20000,
                'status' => 'active',
                'opened_at' => '2026-01-15',
            ]
        );

        /*
         * Savings account
         */
        SavingsAccount::updateOrCreate(
            ['member_id' => $member->id],
            [
                'account_number' => 'SAV-2026-00001',
                'account_type' => 'regular',
                'current_balance' => 10000,
                'status' => 'active',
                'opened_at' => '2026-01-15',
                'closed_at' => null,
            ]
        );
    }
}
