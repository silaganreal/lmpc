<?php

namespace App\Services\Membership;

use App\Models\MembershipApplication;

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
    public function approveApplication(
        MembershipApplication $application,
        int $approvedBy
    ): MembershipApplication {
        if ($application->status !== 'under_review') {
            throw new \LogicException(
                'Only applications under review can be approved.'
            );
        }

        $application->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $application->fresh();
    }
}
