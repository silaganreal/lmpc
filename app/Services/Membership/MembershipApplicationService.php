<?php

namespace App\Services\Membership;

use App\Models\Member;
use App\Models\MembershipApplication;
use Illuminate\Support\Facades\DB;

class MembershipApplicationService
{
    /**
     * Create a new membership application.
     *
     * @param  array<string, mixed>  $data
     */
    public function createApplication(array $data): MembershipApplication
    {
        $data['application_no'] = $this->generateApplicationNumber();
        $data['status'] = 'draft';

        return MembershipApplication::create($data);
    }

    /**
     * Submit a membership application for review.
     */
    public function submitApplication(
        MembershipApplication $application
    ): MembershipApplication {
        if ($application->status !== 'draft') {
            throw new \LogicException(
                'Only draft applications can be submitted.'
            );
        }

        $application->update([
            'status' => 'submitted',
            'processed_at' => now(),
        ]);

        return $application->fresh();
    }

    /**
     * Generate the next membership application number.
     */
    private function generateApplicationNumber(): string
    {
        $year = now()->year;

        $lastApplication = MembershipApplication::query()
            ->where('application_no', 'like', "APP-{$year}-%")
            ->latest('id')
            ->first();

        $nextNumber = $lastApplication
            ? ((int) substr($lastApplication->application_no, -6)) + 1
            : 1;

        return sprintf('APP-%d-%06d', $year, $nextNumber);
    }

    /**
     * Move a submitted membership application into review.
     */
    public function startReview(
        MembershipApplication $application,
        int $reviewedBy
    ): MembershipApplication {
        if ($application->status !== 'submitted') {
            throw new \LogicException(
                'Only submitted applications can be moved to review.'
            );
        }

        $application->update([
            'status' => 'under_review',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);

        return $application->fresh();
    }

    /**
     * Approve a membership application.
     */
    // public function approveApplication(
    //     MembershipApplication $application,
    //     int $approvedBy
    // ): MembershipApplication {
    //     if ($application->status !== 'under_review') {
    //         throw new \LogicException(
    //             'Only applications under review can be approved.'
    //         );
    //     }

    //     $application->update([
    //         'status' => 'approved',
    //         'approved_by' => $approvedBy,
    //         'approved_at' => now(),
    //     ]);

    //     return $application->fresh();
    // }
    public function approveApplication(
        MembershipApplication $application,
        int $approvedBy
    ): MembershipApplication {
        if ($application->status !== 'under_review') {
            throw new \LogicException(
                'Only applications under review can be approved.'
            );
        }

        return DB::transaction(function () use ($application, $approvedBy) {
            $member = Member::create([
                'member_no' => $this->generateMemberNumber(),
                'application_no' => $application->application_no,

                'first_name' => $application->first_name,
                'middle_name' => $application->middle_name,
                'last_name' => $application->last_name,
                'suffix' => $application->suffix,

                'date_of_birth' => $application->date_of_birth,
                'sex' => $application->sex,
                'civil_status' => $application->civil_status,
                'nationality' => $application->nationality,
                'religion' => $application->religion,
                'place_of_birth' => $application->place_of_birth,
                'tin' => $application->tin,

                'mobile_number' => $application->mobile_number,
                'telephone_number' => $application->telephone_number,
                'email' => $application->email,
                'residence_type' => $application->residence_type,

                'membership_type' => $application->membership_type,
                'date_joined' => now()->toDateString(),
                'status' => 'active',
            ]);

            $application->update([
                'member_id' => $member->id,
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $application->fresh();
        });
    }

    private function generateMemberNumber(): string
    {
        $year = now()->year;

        $lastMember = Member::query()
            ->where('member_no', 'like', "M-{$year}-%")
            ->latest('id')
            ->first();

        $nextNumber = $lastMember
            ? ((int) substr($lastMember->member_no, -6)) + 1
            : 1;

        return sprintf('M-%d-%06d', $year, $nextNumber);
    }
}
