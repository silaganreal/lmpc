<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberFamilyMember extends Model
{
    protected $fillable = [
        'member_id',
        'relationship',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'sex',
        'contact_number',
        'is_lbmpc_member',
        'is_beneficiary',
    ];

    protected function casts(): array {
        return [
            'date_of_birth' => 'date',
            'is_lbpmc_member' => 'boolean',
            'is_beneficiary' => 'boolean',
        ];
    }

    public function member(): BelongsTo {
        return $this->belongsTo(Member::class);
    }
}
