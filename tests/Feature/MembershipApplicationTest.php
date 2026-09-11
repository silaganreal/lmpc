<?php

namespace Tests\Feature;

use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_application_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('membership-applications.store'),
            [
                'date_of_application' => '2026-09-11',
                'membership_type' => 'regular',
                'shares_subscribed' => 10,
                'amount_subscribed' => 1000,
                'initial_paid_up' => 500,
                'recruiter_name' => 'Juan Dela Cruz',
                'recruiter_mobile' => '09171234567',
                'remarks' => 'Test membership application.',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('membership_applications', [
            'membership_type' => 'regular',
            'shares_subscribed' => 10,
            'amount_subscribed' => '1000.00',
            'initial_paid_up' => '500.00',
            'status' => 'draft',
        ]);
    }

    public function test_draft_membership_application_can_be_submitted(): void
    {
        $user = User::factory()->create();

        $application = MembershipApplication::create([
            'application_no' => 'APP-2026-999999',
            'date_of_application' => '2026-09-11',
            'membership_type' => 'regular',
            'shares_subscribed' => 10,
            'amount_subscribed' => 1000,
            'initial_paid_up' => 500,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->patch(
            route(
                'membership-applications.submit',
                $application
            )
        );

        $response->assertRedirect(
            route(
                'membership-applications.show',
                $application
            )
        );

        $this->assertDatabaseHas('membership_applications', [
            'id' => $application->id,
            'status' => 'submitted',
        ]);

        $application->refresh();

        $this->assertNotNull($application->processed_at);
    }

    public function test_unauthenticated_user_cannot_submit_membership_application(): void
    {
        $application = MembershipApplication::create([
            'application_no' => 'APP-2026-999998',
            'date_of_application' => '2026-09-11',
            'membership_type' => 'regular',
            'shares_subscribed' => 10,
            'amount_subscribed' => 1000,
            'initial_paid_up' => 500,
            'status' => 'draft',
        ]);

        $response = $this->patch(
            route(
                'membership-applications.submit',
                $application
            )
        );

        $response->assertRedirect(
            route('login')
        );

        $this->assertDatabaseHas('membership_applications', [
            'id' => $application->id,
            'status' => 'draft',
        ]);
    }
}
