<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'branch_id',
        'attendance_date',
        'status',
        'status_reason',
        'check_in_time',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_time',
        'check_out_latitude',
        'check_out_longitude',
        'work_duration_minutes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in_latitude' => 'decimal:8',
        'check_in_longitude' => 'decimal:8',
        'check_out_latitude' => 'decimal:8',
        'check_out_longitude' => 'decimal:8',
    ];

    public const STATUS_ON_TIME = 'On Time';
    public const STATUS_LATE = 'Late';
    public const STATUS_EARLY_DEPARTURE = 'Early Departure';
    public const STATUS_FRAUDULENT = 'Fraudulent';
    public const STATUS_VERIFIED = 'Verified';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scans()
    {
        return $this->hasMany(AttendanceScan::class);
    }

    /**
     * Calculate work duration when check-out is recorded
     */
    public function calculateWorkDuration(): void
    {
        if ($this->check_in_time && $this->check_out_time) {
            $checkIn = Carbon::parse($this->check_in_time);
            $checkOut = Carbon::parse($this->check_out_time);
            
            // Calculate duration in minutes and ensure it's an integer
            $duration = $checkOut->diffInMinutes($checkIn);
            
            // Ensure non-negative duration (check-out should be after check-in)
            $this->work_duration_minutes = max(0, (int) $duration);
        }
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['employeeId'] = $this->employee_id;
        $array['branchId'] = $this->branch_id;
        $array['attendanceDate'] = $this->attendance_date?->format('Y-m-d');
        $array['statusReason'] = $this->status_reason;
        $array['checkInTime'] = $this->check_in_time?->toISOString();
        $array['checkInLatitude'] = $this->check_in_latitude;
        $array['checkInLongitude'] = $this->check_in_longitude;
        $array['checkOutTime'] = $this->check_out_time?->toISOString();
        $array['checkOutLatitude'] = $this->check_out_latitude;
        $array['checkOutLongitude'] = $this->check_out_longitude;
        $array['workDurationMinutes'] = $this->work_duration_minutes;
        $array['createdAt'] = $this->created_at?->toISOString();
        $array['updatedAt'] = $this->updated_at?->toISOString();
        
        unset(
            $array['employee_id'],
            $array['branch_id'],
            $array['attendance_date'],
            $array['status_reason'],
            $array['check_in_time'],
            $array['check_in_latitude'],
            $array['check_in_longitude'],
            $array['check_out_time'],
            $array['check_out_latitude'],
            $array['check_out_longitude'],
            $array['work_duration_minutes'],
            $array['created_at'],
            $array['updated_at']
        );
        
        return $array;
    }
}