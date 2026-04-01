<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'user_id',
        'leave_category_id',
        'duration_type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'approved_by',
        'rejection_reason',
        'requested_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'requested_at' => 'datetime',
        'days_count' => 'decimal:1',
    ];

    public const STATUS_PENDING = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';

    public const DURATION_FULL_DAY = 'Full Day';
    public const DURATION_HALF_DAY = 'Half Day';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveCategory(): BelongsTo
    {
        return $this->belongsTo(LeaveCategory::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getDurationAttribute(): float
    {
        return (float) $this->days_count;
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['userId'] = $this->user_id;
        $array['leaveCategoryId'] = $this->leave_category_id;
        $array['durationType'] = $this->duration_type;
        $array['startDate'] = $this->start_date?->format('Y-m-d');
        $array['endDate'] = $this->end_date?->format('Y-m-d');
        $array['daysCount'] = (float) $this->days_count;
        $array['approvedBy'] = $this->approved_by;
        $array['rejectionReason'] = $this->rejection_reason;
        $array['requestedAt'] = $this->requested_at?->toISOString();
        $array['createdAt'] = $this->created_at?->toISOString();
        $array['updatedAt'] = $this->updated_at?->toISOString();
        
        // Transform nested relationships
        if (isset($array['leave_category'])) {
            $array['leaveCategory'] = $array['leave_category'];
            unset($array['leave_category']);
        }
        
        unset($array['user_id'], $array['leave_category_id'], $array['duration_type'], 
              $array['start_date'], $array['end_date'], $array['days_count'],
              $array['approved_by'], $array['rejection_reason'], $array['requested_at'],
              $array['created_at'], $array['updated_at']);
        
        return $array;
    }
}
