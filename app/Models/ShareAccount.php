<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareAccount extends Model
{
    protected $fillable = [
        'member_id',
        'shares_subscribed',
        'total_subscribed_amount',
        'paid_up_amount',
        'status',
        'opened_at',
    ];

    protected function casts(): array {
        return [
            'total_subscribed_amount' => 'decimal:2',
            'paid_up_amount' => 'decimal:2',
            'opened_at' => 'date',
        ];
    }

    public function member(): BelongsTo {
        return $this->belongsTo(Member::class);
    }

    public function transactions(): HasMany {
        return $this->hasMany(ShareTransaction::class);
    }
}
