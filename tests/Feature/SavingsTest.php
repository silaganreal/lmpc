<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createMember(): Member
    {
        return Member::create([
            'member_no' => 'M-2026-000001',
            'first_name' => 'Juan',
            'middle_name' => 'Miguel',
            'last_name' => 'Dela Cruz',
            'suffix' => null,
            'sex' => 'male',
            'civil_status' => 'single',
            'nationality' => 'Filipino',
            'membership_type' => 'regular',
            'date_joined' => '2026-09-01',
            'status' => 'active',
        ]);
    }

    public function test_authenticated_user_can_record_savings_deposit(): void
    {
        $user = User::factory()->create();
        $member = $this->createMember();

        $response = $this
            ->actingAs($user)
            ->post(route('savings.deposit', $member), [
                'amount' => 1000,
                'transaction_date' => '2026-09-20',
                'reference_no' => 'SAV-001',
                'description' => 'Initial savings deposit',
            ]);

        $response->assertRedirect(route('members.show', $member));

        $account = SavingsAccount::where('member_id', $member->id)->first();

        $this->assertNotNull($account);

        $this->assertEquals(1000, (float) $account->current_balance);

        $this->assertDatabaseHas('savings_transactions', [
            'savings_account_id' => $account->id,
            'transaction_type' => 'deposit',
            'direction' => 'credit',
            'reference_no' => 'SAV-001',
            'description' => 'Initial savings deposit',
            'amount' => 1000,
            'created_by' => $user->id,
        ]);
    }

    public function test_deposit_uses_entered_transaction_date(): void
    {
        $user = User::factory()->create();
        $member = $this->createMember();

        $this
            ->actingAs($user)
            ->post(route('savings.deposit', $member), [
                'amount' => 500,
                'transaction_date' => '2026-09-10',
            ]);

        $transaction = SavingsTransaction::first();

        $this->assertNotNull($transaction);
        $this->assertEquals(
            '2026-09-10',
            $transaction->transaction_date->format('Y-m-d')
        );
    }

    public function test_multiple_deposits_increase_savings_balance(): void
    {
        $user = User::factory()->create();
        $member = $this->createMember();

        $this
            ->actingAs($user)
            ->post(route('savings.deposit', $member), [
                'amount' => 1000,
                'transaction_date' => '2026-09-20',
            ]);

        $this
            ->actingAs($user)
            ->post(route('savings.deposit', $member), [
                'amount' => 500,
                'transaction_date' => '2026-09-20',
            ]);

        $account = SavingsAccount::where('member_id', $member->id)->first();

        $this->assertNotNull($account);
        $this->assertEquals(1500, (float) $account->current_balance);

        $this->assertDatabaseCount('savings_transactions', 2);
    }

    public function test_authenticated_user_can_record_savings_withdrawal(): void
    {
        $user = User::factory()->create();
        $member = $this->createMember();

        $this
            ->actingAs($user)
            ->post(route('savings.deposit', $member), [
                'amount' => 1000,
                'transaction_date' => '2026-09-20',
            ]);

        $response = $this
            ->actingAs($user)
            ->post(route('savings.withdrawal', $member), [
                'amount' => 400,
                'transaction_date' => '2026-09-20',
                'reference_no' => 'WD-001',
                'description' => 'Savings withdrawal',
            ]);

        $response->assertRedirect(route('members.show', $member));

        $account = SavingsAccount::where('member_id', $member->id)->first();

        $this->assertNotNull($account);

        $this->assertEquals(600, (float) $account->current_balance);

        $this->assertDatabaseHas('savings_transactions', [
            'savings_account_id' => $account->id,
            'transaction_type' => 'withdrawal',
            'direction' => 'debit',
            'reference_no' => 'WD-001',
            'description' => 'Savings withdrawal',
            'amount' => 400,
            'created_by' => $user->id,
        ]);
    }

    public function test_withdrawal_cannot_exceed_savings_balance(): void
    {
        $user = User::factory()->create();
        $member = $this->createMember();

        $this
            ->actingAs($user)
            ->post(route('savings.deposit', $member), [
                'amount' => 500,
                'transaction_date' => '2026-09-20',
            ]);

        $this
            ->actingAs($user)
            ->post(route('savings.withdrawal', $member), [
                'amount' => 600,
                'transaction_date' => '2026-09-20',
            ]);

        $account = SavingsAccount::where('member_id', $member->id)->first();

        $this->assertNotNull($account);

        $this->assertEquals(500, (float) $account->current_balance);

        $this->assertDatabaseCount('savings_transactions', 1);
    }

    public function test_deposit_requires_positive_amount(): void
    {
        $user = User::factory()->create();
        $member = $this->createMember();

        $response = $this
            ->actingAs($user)
            ->post(route('savings.deposit', $member), [
                'amount' => 0,
                'transaction_date' => '2026-09-20',
            ]);

        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('savings_accounts', 0);
        $this->assertDatabaseCount('savings_transactions', 0);
    }

    public function test_unauthenticated_user_cannot_record_savings_deposit(): void
    {
        $member = $this->createMember();

        $response = $this->post(route('savings.deposit', $member), [
            'amount' => 1000,
            'transaction_date' => '2026-09-20',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('savings_accounts', 0);
        $this->assertDatabaseCount('savings_transactions', 0);
    }
}
