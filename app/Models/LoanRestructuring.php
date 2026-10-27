<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanRestructuring extends Model
{
    protected $fillable = [
        'loan_id',
        'requested_by',
        'request_date',
        'request_reason',
        'requested_term_years',
        'approved_term_years',
        'status',
        'reviewed_by',
        'reviewed_at',
        'board_remarks',
        'remaining_principal',
        'deferred_interest',
        'penalties',
        'restructured_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'requested_term_years' => 'integer',
            'approved_term_years' => 'integer',
            'reviewed_at' => 'date',
            'remaining_principal' => 'decimal:2',
            'deferred_interest' => 'decimal:2',
            'penalties' => 'decimal:2',
            'restructured_at' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<LoanRestructuringAmortization, $this> */
    public function amortizations(): HasMany
    {
        return $this->hasMany(
            LoanRestructuringAmortization::class,
            'loan_restructuring_id'
        );
    }
}
