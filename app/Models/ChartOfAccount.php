<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'normal_balance',
        'parent_id',
        'is_posting_allowed',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_posting_allowed' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent account.
     *
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    /**
     * Get the child accounts.
     *
     * @return HasMany<ChartOfAccount, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }
}
