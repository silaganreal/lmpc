<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CbuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            PreventRequestForgery::class
        );
    }

    public function test_authenticated_user_can_record_cbu_contribution(): void
    {
        $user = User::factory()->create();

        $member = Member::create([
            'member_no' => 'MEM-000001',
            'first_name' => 'Juan',
            'middle_name' => null,
            'last_name' => 'Dela Cruz',
            'suffix' => null,
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'civil_status' => 'single',
            'nationality' => 'Filipino',
            'religion' => null,
            'place_of_birth' => null,
            'tin' => null,
            'mobile_number' => '09171234567',
            'telephone_number' => null,
            'email' => 'juan@example.com',
            'residence_type' => null,
            'membership_type' => 'regular',
            'date_joined' => '2026-09-01',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->post("/members/{$member->id}/cbu", [
                'amount' => 500,
                'transaction_date' => '2026-09-19',
                'reference_no' => 'CBU-TEST-001',
                'description' => 'Test CBU contribution',
            ]);

        $response->assertRedirect(
            route('members.show', $member)
        );

        $this->assertDatabaseHas('cbu_accounts', [
            'member_id' => $member->id,
            'current_balance' => 500,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('cbu_transactions', [
            'transaction_type' => 'contribution',
            'direction' => 'credit',
            'reference_no' => 'CBU-TEST-001',
            'description' => 'Test CBU contribution',
            'amount' => 500,
        ]);
    }

    public function test_cbu_contribution_uses_entered_transaction_date(): void
    {
        $user = User::factory()->create();

        $member = Member::create([
            'member_no' => 'MEM-000002',
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Santos',
            'suffix' => null,
            'date_of_birth' => '1992-05-10',
            'sex' => 'female',
            'civil_status' => 'single',
            'nationality' => 'Filipino',
            'religion' => null,
            'place_of_birth' => null,
            'tin' => null,
            'mobile_number' => null,
            'telephone_number' => null,
            'email' => 'maria@example.com',
            'residence_type' => null,
            'membership_type' => 'regular',
            'date_joined' => '2026-09-01',
            'status' => 'active',
        ]);

        $this
            ->actingAs($user)
            ->post("/members/{$member->id}/cbu", [
                'amount' => 750,
                'transaction_date' => '2026-09-10',
                'reference_no' => 'CBU-TEST-002',
                'description' => 'Backdated contribution',
            ]);

        $this->assertDatabaseHas('cbu_transactions', [
            'transaction_date' => '2026-09-10 00:00:00',
            'amount' => 750,
            'reference_no' => 'CBU-TEST-002',
        ]);
    }

    public function test_cbu_contribution_requires_positive_amount(): void
    {
        $user = User::factory()->create();

        $member = Member::create([
            'member_no' => 'MEM-000003',
            'first_name' => 'Pedro',
            'middle_name' => null,
            'last_name' => 'Reyes',
            'suffix' => null,
            'date_of_birth' => '1988-03-15',
            'sex' => 'male',
            'civil_status' => 'married',
            'nationality' => 'Filipino',
            'religion' => null,
            'place_of_birth' => null,
            'tin' => null,
            'mobile_number' => null,
            'telephone_number' => null,
            'email' => null,
            'residence_type' => null,
            'membership_type' => 'regular',
            'date_joined' => '2026-09-01',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->post("/members/{$member->id}/cbu", [
                'amount' => 0,
                'transaction_date' => '2026-09-19',
            ]);

        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('cbu_transactions', 0);
    }

    public function test_unauthenticated_user_cannot_record_cbu_contribution(): void
    {
        $member = Member::create([
            'member_no' => 'MEM-000004',
            'first_name' => 'Ana',
            'middle_name' => null,
            'last_name' => 'Garcia',
            'suffix' => null,
            'date_of_birth' => '1995-07-20',
            'sex' => 'female',
            'civil_status' => 'single',
            'nationality' => 'Filipino',
            'religion' => null,
            'place_of_birth' => null,
            'tin' => null,
            'mobile_number' => null,
            'telephone_number' => null,
            'email' => null,
            'residence_type' => null,
            'membership_type' => 'regular',
            'date_joined' => '2026-09-01',
            'status' => 'active',
        ]);

        $response = $this->post("/members/{$member->id}/cbu", [
            'amount' => 500,
            'transaction_date' => '2026-09-19',
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('cbu_transactions', 0);
    }

    public function test_cbu_account_balance_increases_with_multiple_contributions(): void
    {
        $user = User::factory()->create();

        $member = Member::create([
            'member_no' => 'MEM-000005',
            'first_name' => 'Carlos',
            'middle_name' => null,
            'last_name' => 'Cruz',
            'suffix' => null,
            'date_of_birth' => '1991-02-10',
            'sex' => 'male',
            'civil_status' => 'single',
            'nationality' => 'Filipino',
            'religion' => null,
            'place_of_birth' => null,
            'tin' => null,
            'mobile_number' => null,
            'telephone_number' => null,
            'email' => null,
            'residence_type' => null,
            'membership_type' => 'regular',
            'date_joined' => '2026-09-01',
            'status' => 'active',
        ]);

        $this
            ->actingAs($user)
            ->post("/members/{$member->id}/cbu", [
                'amount' => 500,
                'transaction_date' => '2026-09-19',
            ]);

        $this
            ->actingAs($user)
            ->post("/members/{$member->id}/cbu", [
                'amount' => 750,
                'transaction_date' => '2026-09-19',
            ]);

        $this->assertDatabaseHas('cbu_accounts', [
            'member_id' => $member->id,
            'current_balance' => 1250,
        ]);

        $this->assertDatabaseCount('cbu_transactions', 2);
    }
}
