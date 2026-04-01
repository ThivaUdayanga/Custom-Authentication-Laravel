<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRosterAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'roster_id',
        'assigned_by',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function roster(): BelongsTo
    {
        return $this->belongsTo(Roster::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['userId'] = $this->user_id;
        $array['rosterId'] = $this->roster_id;
        $array['assignedBy'] = $this->assigned_by;
        $array['startDate'] = $this->start_date?->format('Y-m-d');
        $array['endDate'] = $this->end_date?->format('Y-m-d');
        $array['isActive'] = (bool) $this->is_active;
        $array['createdAt'] = $this->created_at?->toISOString();
        $array['updatedAt'] = $this->updated_at?->toISOString();
        
        if (isset($array['assigned_by_user'])) {
            $array['assignedByUser'] = $array['assigned_by_user'];
            unset($array['assigned_by_user']);
        }
        
        unset($array['user_id'], $array['roster_id'], $array['assigned_by'], $array['start_date'], $array['end_date'], $array['is_active'], $array['created_at'], $array['updated_at']);
        
        return $array;
    }
}