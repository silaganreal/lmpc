<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbuAccount extends Model
{
    protected $fillable = [
        'member_id',
        'current_balance',
        'status',
        'opened_at',
    ];

    protected function casts(): array {
        return [
            'current_balance' => 'decimal:2',
            'opened_at' => 'date'
        ];
    }

    public function member(): BelongsTo {
        return $this->belongsTo(Member::class);
    }

    public function transactions(): HasMany {
        return $this->hasMany(CbuTransaction::class);
    }
}
