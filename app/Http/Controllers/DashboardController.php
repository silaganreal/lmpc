<?php

namespace App\Http\Controllers;

use App\Models\CbuAccount;
use App\Models\Loan;
use App\Models\LoanAmortization;
use App\Models\LoanPayment;
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
        $today = today()->toDateString();

        $unpaidInstallments = LoanAmortization::query()
            ->whereIn('status', ['pending', 'partial']);

        $recentPayments = LoanPayment::query()
            ->with([
                'loan:id,member_id,loan_no',
                'loan.member:id,member_no,first_name,middle_name,last_name',
            ])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get([
                'id',
                'loan_id',
                'payment_date',
                'reference_no',
                'payment_method',
                'total_amount',
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'members' => [
                    'total' => Member::count(),
                    'active' => Member::where('status', 'active')->count(),
                ],

                'applications' => [
                    'total' => MembershipApplication::count(),
                    'draft' => MembershipApplication::where('status', 'draft')->count(),
                    'submitted' => MembershipApplication::where('status', 'submitted')->count(),
                    'underReview' => MembershipApplication::where('status', 'under_review')->count(),
                    'approved' => MembershipApplication::where('status', 'approved')->count(),
                    'awaitingAction' => MembershipApplication::whereIn('status', [
                        'submitted',
                        'under_review',
                    ])->count(),
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

                    'pendingApproval' => (clone $loanQuery)
                        ->where('status', 'pending')
                        ->count(),

                    'awaitingRelease' => (clone $loanQuery)
                        ->where('status', 'approved')
                        ->count(),

                    'active' => (clone $loanQuery)
                        ->where('status', 'active')
                        ->count(),

                    'pastDue' => (clone $loanQuery)
                        ->where('status', 'past_due')
                        ->count(),

                    'outstandingPrincipal' => (float) $loanQuery->sum('outstanding_principal'),
                ],

                'collections' => [
                    'overdueInstallments' => (clone $unpaidInstallments)
                        ->whereDate('due_date', '<', $today)
                        ->count(),

                    'thisMonth' => (float) LoanPayment::query()
                        ->whereYear('payment_date', now()->year)
                        ->whereMonth('payment_date', now()->month)
                        ->sum('total_amount'),
                ],
            ],

            'recentPayments' => $recentPayments,
        ]);
    }
}
