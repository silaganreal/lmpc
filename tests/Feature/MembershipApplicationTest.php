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

    public function test_submitted_membership_application_can_be_moved_to_review(): void
    {
        $user = User::factory()->create();

        $application = MembershipApplication::create([
            'application_no' => 'APP-2026-999997',
            'date_of_application' => '2026-09-11',
            'membership_type' => 'regular',
            'shares_subscribed' => 10,
            'amount_subscribed' => 1000,
            'initial_paid_up' => 500,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($user)->patch(
            route(
                'membership-applications.review',
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
            'status' => 'under_review',
            'reviewed_by' => $user->id,
        ]);

        $application->refresh();

        $this->assertNotNull($application->reviewed_at);
    }

    public function test_unauthenticated_user_cannot_start_membership_application_review(): void
    {
        $application = MembershipApplication::create([
            'application_no' => 'APP-2026-999996',
            'date_of_application' => '2026-09-11',
            'membership_type' => 'regular',
            'shares_subscribed' => 10,
            'amount_subscribed' => 1000,
            'initial_paid_up' => 500,
            'status' => 'submitted',
        ]);

        $response = $this->patch(
            route(
                'membership-applications.review',
                $application
            )
        );

        $response->assertRedirect(
            route('login')
        );

        $this->assertDatabaseHas('membership_applications', [
            'id' => $application->id,
            'status' => 'submitted',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    // public function test_only_submitted_membership_applications_can_be_moved_to_review(): void
    // {
    //     $user = User::factory()->create();

    //     $application = MembershipApplication::create([
    //         'application_no' => 'APP-2026-999995',
    //         'date_of_application' => '2026-09-11',
    //         'membership_type' => 'regular',
    //         'shares_subscribed' => 10,
    //         'amount_subscribed' => 1000,
    //         'initial_paid_up' => 500,
    //         'status' => 'draft',
    //     ]);

    //     $this->expectException(\LogicException::class);

    //     $this->actingAs($user)->patch(
    //         route(
    //             'membership-applications.review',
    //             $application
    //         )
    //     );
    // }

    public function test_membership_application_can_be_approved(): void
    {
        $user = User::factory()->create();

        $application = MembershipApplication::create([
            'application_no' => 'APP-2026-999995',
            'date_of_application' => '2026-09-11',
            'membership_type' => 'regular',
            'shares_subscribed' => 10,
            'amount_subscribed' => 1000,
            'initial_paid_up' => 500,
            'status' => 'under_review',
        ]);

        $this->actingAs($user)
            ->patch(
                route(
                    'membership-applications.approve',
                    $application
                )
            )
            ->assertRedirect(
                route(
                    'membership-applications.show',
                    $application
                )
            );

        $application->refresh();

        $this->assertSame('approved', $application->status);
        $this->assertSame($user->id, $application->approved_by);
        $this->assertNotNull($application->approved_at);
    }

    public function test_unauthenticated_user_cannot_approve_membership_application(): void
    {
        $application = MembershipApplication::create([
            'application_no' => 'APP-2026-999994',
            'date_of_application' => '2026-09-11',
            'membership_type' => 'regular',
            'shares_subscribed' => 10,
            'amount_subscribed' => 1000,
            'initial_paid_up' => 500,
            'status' => 'under_review',
        ]);

        $this->patch(
            route(
                'membership-applications.approve',
                $application
            )
        )->assertRedirect(route('login'));

        $application->refresh();

        $this->assertSame('under_review', $application->status);
        $this->assertNull($application->approved_by);
        $this->assertNull($application->approved_at);
    }
}
