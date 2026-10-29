<?php

namespace App\Http\Controllers;

use App\Models\LoanAmortization;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        $today = today()->toDateString();

        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ]);

        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? $today;

        $unpaidInstallments = LoanAmortization::query()
            ->whereIn('status', ['pending', 'partial']);

        $installments = (clone $unpaidInstallments)
            ->with([
                'loan.member',
                'loan.loanProduct',
            ])
            ->orderBy('due_date')
            ->orderBy('installment_no')
            ->paginate(15, ['*'], 'installments_page')
            ->withQueryString();

        $paymentsQuery = LoanPayment::query()
            ->with([
                'loan.member',
                'createdBy:id,name',
            ])
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        $payments = (clone $paymentsQuery)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'payments_page')
            ->withQueryString();

        $collectionTotals = (clone $paymentsQuery)
            ->selectRaw('
                COUNT(*) as transaction_count,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COALESCE(SUM(principal_amount), 0) as principal_amount,
                COALESCE(SUM(interest_amount), 0) as interest_amount,
                COALESCE(SUM(penalty_amount), 0) as penalty_amount
            ')
            ->first();

        return Inertia::render('Collections/Index', [
            'stats' => [
                'unpaidInstallments' => (clone $unpaidInstallments)->count(),

                'partialInstallments' => (clone $unpaidInstallments)
                    ->where('status', 'partial')
                    ->count(),

                'overdueInstallments' => (clone $unpaidInstallments)
                    ->whereDate('due_date', '<', $today)
                    ->count(),

                'collectedThisMonth' => (float) LoanPayment::query()
                    ->whereYear('payment_date', now()->year)
                    ->whereMonth('payment_date', now()->month)
                    ->sum('total_amount'),
            ],

            'installments' => $installments,
            'payments' => $payments,

            'collectionTotals' => [
                'transactionCount' => (int) ($collectionTotals->transaction_count ?? 0),
                'totalAmount' => (float) ($collectionTotals->total_amount ?? 0),
                'principalAmount' => (float) ($collectionTotals->principal_amount ?? 0),
                'interestAmount' => (float) ($collectionTotals->interest_amount ?? 0),
                'penaltyAmount' => (float) ($collectionTotals->penalty_amount ?? 0),
            ],

            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
