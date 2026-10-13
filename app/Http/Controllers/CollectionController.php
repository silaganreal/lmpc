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

        $unpaidInstallments = LoanAmortization::query()
            ->whereIn('status', ['pending', 'partial']);

        $installments = (clone $unpaidInstallments)
            ->with([
                'loan.member',
                'loan.loanProduct',
            ])
            ->orderBy('due_date')
            ->orderBy('installment_no')
            ->paginate(15)
            ->withQueryString();

        // dd($installments->first()?->loan?->loanProduct?->toArray());

        return Inertia::render('Collections/Index', [
            'stats' => [
                'unpaidInstallments' => (clone $unpaidInstallments)
                    ->count(),

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

            // 'debugProduct' => $installments->getCollection()
            //     ->first()
            //     ?->loan
            //     ?->loanProduct
            //     ?->only(['id', 'code', 'name']),
        ]);
    }
}
