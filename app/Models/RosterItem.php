<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterItem extends Model
{
    protected $fillable = [
        'roster_id',
        'shift_id',
        'day_order',
    ];

    public function roster(): BelongsTo
    {
        return $this->belongsTo(Roster::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}