<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAddress extends Model
{
    protected $fillable = [
        'member_id',
        'unit_room',
        'floor_building',
        'lot_block_phase',
        'street_purok',
        'subdivision',
        'barangay',
        'municipality',
        'province',
        'zip_code',
        'address_type',
        'is_primary',
    ];

    protected function casts(): array {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function member(): BelongsTo {
        return $this->belongsTo(Member::class);
    }
}
