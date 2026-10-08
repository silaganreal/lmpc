<?php

namespace App\Http\Controllers;

use App\Models\CbuAccount;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\SavingsAccount;
use App\Models\ShareAccount;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $loanQuery = Loan::query();

        return Inertia::render('Dashboard', [
            'stats' => [
                'members' => [
                    'total' => Member::count(),
                    'active' => Member::where('status', 'active')->count(),
                ],

                'applications' => [
                    'total' => MembershipApplication::count(),
                    'pending' => MembershipApplication::where('status', 'pending')->count(),
                ],

                'shareCapital' => [
                    'paidUp' => (float) ShareAccount::sum('paid_up_amount'),
                ],

                'cbu' => [
                    'balance' => (float) CbuAccount::sum('current_balance'),
                ],

                'savings' => [
                    'balance' => (float) SavingsAccount::sum('current_balance'),
                ],

                'loans' => [
                    'total' => $loanQuery->count(),
                    'active' => (clone $loanQuery)
                        ->where('status', 'active')
                        ->count(),
                    'pastDue' => (clone $loanQuery)
                        ->where('status', 'past_due')
                        ->count(),
                    'outstandingPrincipal' => (float) $loanQuery->sum('outstanding_principal'),
                ],
            ],
        ]);
    }
}
