<?php

namespace App\Services\Financial;

use App\Models\LoanProduct;
use App\Models\Member;
use InvalidArgumentException;

class LoanService
{
    /**
     * Calculate the maximum amount a member may apply for
     * under the selected loan product.
     */
    public function maximumLoanableAmount(
        Member $member,
        LoanProduct $loanProduct,
    ): float {
        $cbuBalance = (float) ($member->cbuAccount->current_balance ?? 0);

        return match ($loanProduct->code) {
            'PROVIDENTIAL_1' => $cbuBalance * 2,
            'PROVIDENTIAL_2' => $cbuBalance * 3,
            'EMERGENCY' => 100_000,
            'EDUCATIONAL' => 50_000,
            'APPLIANCE' => 80_000,
            'MEDICAL' => 50_000,
            'HMO' => 50_000,
            'LAB' => $this->labMaximum($member),

            default => throw new InvalidArgumentException(
                "Unsupported loan product: {$loanProduct->code}"
            ),
        };
    }

    /**
     * Determine the Single Borrowers Limit.
     */
    public function singleBorrowerLimit(Member $member): float
    {
        $cbuBalance = (float) ($member->cbuAccount->current_balance ?? 0);

        return $cbuBalance * 4;
    }

    public function canAvailProvidentialTwo(Member $member): bool
    {
        $cbuBalance = (float) ($member->cbuAccount->current_balance ?? 0);

        return $cbuBalance >= 16_000
            && $this->hasFullyPaidProvidentialOne($member)
            && ! $this->hasPastDueLoan($member);
    }

    private function hasFullyPaidProvidentialOne(Member $member): bool
    {
        return ! $member->loans()
            ->whereHas('loanProduct', function ($query) {
                $query->where('code', 'PROVIDENTIAL_1');
            })
            ->whereNotIn('status', ['fully_paid', 'cancelled', 'rejected'])
            ->exists();
    }

    private function hasPastDueLoan(Member $member): bool
    {
        return $member->loans()
            ->where('status', 'past_due')
            ->exists();
    }

    /**
     * Determine LAB loan maximum.
     *
     * LAB requires expected employee benefits, which are not yet
     * represented in the current member data model.
     */
    private function labMaximum(Member $member): float
    {
        throw new InvalidArgumentException(
            'LAB loan eligibility requires expected employee benefits data.'
        );
    }
}
