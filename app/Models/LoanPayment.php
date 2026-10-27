<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPayment extends Model
{
    protected $fillable = [
        'loan_id',
        'loan_restructuring_amortization_id',
        'payment_date',
        'reference_no',
        'principal_amount',
        'interest_amount',
        'penalty_amount',
        'total_amount',
        'payment_method',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * @return BelongsTo<LoanRestructuringAmortization, $this>
     */
    public function restructuringAmortization(): BelongsTo
    {
        return $this->belongsTo(
            LoanRestructuringAmortization::class,
            'loan_restructuring_amortization_id'
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
