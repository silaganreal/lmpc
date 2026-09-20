<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsAccount extends Model
{
    protected $fillable = [
        'member_id',
        'account_number',
        'account_type',
        'current_balance',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'opened_at' => 'date',
            'closed_at' => 'date',
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
     * @return HasMany<SavingsTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }
}
