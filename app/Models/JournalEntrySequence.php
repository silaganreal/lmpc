<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntrySequence extends Model
{
    protected $fillable = [
        'fiscal_year',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
