<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanRestructuringAmortization extends Model
{
    protected $fillable = [
        'loan_restructuring_id',
        'installment_no',
        'due_date',
        'beginning_balance',
        'principal_amount',
        'interest_amount',
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
            'total_due' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_date' => 'date',
        ];
    }

    /** @return BelongsTo<LoanRestructuring, $this> */
    public function restructuring(): BelongsTo
    {
        return $this->belongsTo(LoanRestructuring::class, 'loan_restructuring_id');
    }

    /**
     * @return HasMany<LoanPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(
            LoanPayment::class,
            'loan_restructuring_amortization_id'
        );
    }
}
