<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScan extends Model
{
    protected $fillable = [
        'attendance_record_id',
        'qr_code',
        'scan_type',
    ];

    public const SCAN_TYPE_CHECK_IN = 'Check-in';
    public const SCAN_TYPE_CHECK_OUT = 'Check-out';

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['attendanceRecordId'] = $this->attendance_record_id;
        $array['qrCode'] = $this->qr_code;
        $array['scanType'] = $this->scan_type;
        $array['createdAt'] = $this->created_at?->toISOString();
        $array['updatedAt'] = $this->updated_at?->toISOString();
        
        unset(
            $array['attendance_record_id'],
            $array['qr_code'],
            $array['scan_type'],
            $array['created_at'],
            $array['updated_at']
        );
        
        return $array;
    }
}