<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'description',
        'is_active',
        'break_duration_minutes',
        // 'overtime_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'break_duration_minutes' => 'integer',
        // 'overtime_rate' => 'decimal:2',
    ];

    // public function users(): HasMany
    // {
    //     return $this->hasMany(User::class);
    // }

    public function rosterItems()
    {
        return $this->hasMany(RosterItem::class);
    }
}