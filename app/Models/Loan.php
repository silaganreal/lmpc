<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'member_id',
        'loan_product_id',
        'loan_no',
        'application_date',
        'approval_date',
        'release_date',
        'maturity_date',
        'principal_amount',
        'interest_rate_monthly',
        'term_months',
        'monthly_amortization',
        'outstanding_principal',
        'outstanding_interest',
        'outstanding_penalty',
        'service_fee',
        'cbu_retention',
        'insurance_premium',
        'notarial_fee',
        'net_proceeds',
        'status',
        'purpose',
        'remarks',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'approval_date' => 'date',
            'release_date' => 'date',
            'maturity_date' => 'date',
            'principal_amount' => 'decimal:2',
            'interest_rate_monthly' => 'decimal:2',
            'term_months' => 'integer',
            'monthly_amortization' => 'decimal:2',
            'outstanding_principal' => 'decimal:2',
            'outstanding_interest' => 'decimal:2',
            'outstanding_penalty' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'cbu_retention' => 'decimal:2',
            'insurance_premium' => 'decimal:2',
            'notarial_fee' => 'decimal:2',
            'net_process' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<LoanProduct, $this>
     */
    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
