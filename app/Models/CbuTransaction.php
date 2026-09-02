<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbuTransaction extends Model
{
    protected $fillable = [
        'cbu_account_id',
        'transaction_date',
        'transaction_type',
        'direction',
        'reference_no',
        'description',
        'amount',
        'created_by',
    ];

    protected function casts(): array {
        return [
            'transaction_date' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function cbuAccount(): BelongsTo {
        return $this->belongsTo(CbuAccount::class);
    }

    public function createdBy(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}
