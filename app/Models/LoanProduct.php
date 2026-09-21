<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanProduct extends Model
{
    protected $fillable = [
        'code',
        'name',
        'interest_rate_monthly',
        'maximum_amount',
        'maximum_term_months',
        'term_type',
        'eligibility_requirements',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'interest_rate_monthly' => 'decimal:2',
            'maximum_amount' => 'decimal:2',
            'maximum_term_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
