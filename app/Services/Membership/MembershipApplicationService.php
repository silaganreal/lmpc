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
}
