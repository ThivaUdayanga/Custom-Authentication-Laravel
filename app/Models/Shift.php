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
    ];

    protected $casts = [
        'is_active' => 'boolean',
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