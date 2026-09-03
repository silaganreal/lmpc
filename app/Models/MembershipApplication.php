<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipApplication extends Model
{
    protected $fillable = [
        'application_no',
        'member_id',
        'date_of_application',
        'membership_type',
        'shares_subscribed',
        'amount_subscribed',
        'initial_paid_up',
        'recruiter_name',
        'recruiter_mobile',
        'board_resolution_no',
        'board_approval_date',
        'status',
        'processed_by',
        'approved_by',
        'processed_at',
        'approved_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_of_application' => 'date',
            'board_approval_date' => 'date',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
            'amount_subscribed' => 'decimal:2',
            'initial_paid_up' => 'decimal:2',
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
     * @return BelongsTo<User, $this>
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<MembershipApplicationDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(MembershipApplicationDocument::class);
    }
}
