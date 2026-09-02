<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    protected $fillable = [
        'member_no',
        'application_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'date_of_birth',
        'sex',
        'civil_status',
        'nationality',
        'religion',
        'place_of_birth',
        'tin',
        'mobile_number',
        'telephone_number',
        'email',
        'residence_type',
        'membership_type',
        'date_joined',
        'status'
    ];

    protected function casts(): array {
        return [
            'date_of_birth' => 'date',
            'date_joined' => 'date',
        ];
    }

    protected function addresses(): HasMany {
        return $this->hasMany(MemberAddress::class);
    }

    public function familyMembers(): HasMany {
        return $this->hasMany(MemberFamilyMember::class);
    }

    public function educations(): HasMany {
        return $this->hasMany(MemberEducation::class);
    }

    public function employments(): HasMany {
        return $this->hasMany(MemberEmployment::class);
    }

    public function shareAccount(): HasOne {
        return $this->hasOne(ShareAccount::class);
    }

    public function cbuAccount(): HasOne {
        return $this->hasOne(CbuAccount::class);
    }
}
