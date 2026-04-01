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

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['rosterId'] = $this->roster_id;
        $array['shiftId'] = $this->shift_id;
        $array['dayOrder'] = $this->day_order;
        
        unset($array['roster_id'], $array['shift_id'], $array['day_order'], $array['created_at'], $array['updated_at']);
        
        return $array;
    }
}