<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberEmployment extends Model
{
    protected $fillable = [
        'member_id',
        'employer',
        'branch_center',
        'position',
        'date_hired',
        'is_cuurrent',
    ];

    protected function casts(): array
    {
        return [
            'date_hired' => 'date',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
