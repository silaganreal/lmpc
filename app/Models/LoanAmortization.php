<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanAmortization extends Model
{
    protected $fillable = [
        'loan_id',
        'installment_no',
        'due_date',
        'beginning_balance',
        'principal_amount',
        'interest_amount',
        'penalty_amount',
        'total_due',
        'paid_amount',
        'paid_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'beginning_balance' => 'decimal:2',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
            'total_due' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
