<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceScan;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Manual Check-in endpoint
     * Records employee check-in with location and optional QR code
     */
    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'branchId' => ['required', 'exists:branches,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'qrCode' => ['nullable', 'string'], // Optional QR code for verification
        ]);

        $employee = $request->user();
        $today = Carbon::today();

        // Check if already checked in today
        $existingRecord = AttendanceRecord::where('employee_id', $employee->id)
            ->where('branch_id', $validated['branchId'])
            ->where('attendance_date', $today)
            ->first();

        if ($existingRecord && $existingRecord->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in today at ' . $existingRecord->check_in_time->format('h:i A'),
                'data' => $existingRecord->load(['employee', 'branch', 'scans']),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $checkInTime = Carbon::now();
            
            // Determine status based on shift timing (simplified - you can enhance this)
            $status = $this->determineCheckInStatus($employee, $checkInTime);

            // Create or update attendance record
            $attendanceRecord = AttendanceRecord::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'branch_id' => $validated['branchId'],
                    'attendance_date' => $today,
                ],
                [
                    'check_in_time' => $checkInTime,
                    'check_in_latitude' => $validated['latitude'],
                    'check_in_longitude' => $validated['longitude'],
                    'status' => $status,
                ]
            );

            // Create scan record if QR code is provided
            if (isset($validated['qrCode'])) {
                AttendanceScan::create([
                    'attendance_record_id' => $attendanceRecord->id,
                    'qr_code' => $validated['qrCode'],
                    'scan_type' => AttendanceScan::SCAN_TYPE_CHECK_IN,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful at ' . $checkInTime->format('h:i A'),
                'data' => $attendanceRecord->fresh()->load(['employee', 'branch', 'scans']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record check-in',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manual Check-out endpoint
     * Records employee check-out with location and calculates work duration
     */
    public function checkOut(Request $request)
    {
        $validated = $request->validate([
            'branchId' => ['required', 'exists:branches,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'qrCode' => ['nullable', 'string'], // Optional QR code for verification
        ]);

        $employee = $request->user();
        $today = Carbon::today();

        // Find today's attendance record
        $attendanceRecord = AttendanceRecord::where('employee_id', $employee->id)
            ->where('branch_id', $validated['branchId'])
            ->where('attendance_date', $today)
            ->first();

        if (!$attendanceRecord || !$attendanceRecord->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'No check-in record found for today. Please check in first.',
            ], 400);
        }

        if ($attendanceRecord->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked out today at ' . $attendanceRecord->check_out_time->format('h:i A'),
                'data' => $attendanceRecord->load(['employee', 'branch', 'scans']),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $checkOutTime = Carbon::now();

            // Validate check-out time is after check-in time
            if ($checkOutTime->lte($attendanceRecord->check_in_time)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-out time must be after check-in time.',
                ], 400);
            }

            // Update attendance record with check-out details
            $attendanceRecord->update([
                'check_out_time' => $checkOutTime,
                'check_out_latitude' => $validated['latitude'],
                'check_out_longitude' => $validated['longitude'],
            ]);

            // Calculate work duration
            $attendanceRecord->calculateWorkDuration();
            $attendanceRecord->save();

            // Update status if early departure
            $status = $this->determineCheckOutStatus($employee, $checkOutTime, $attendanceRecord->status);
            $attendanceRecord->update(['status' => $status]);

            // Create scan record if QR code is provided
            if (isset($validated['qrCode'])) {
                AttendanceScan::create([
                    'attendance_record_id' => $attendanceRecord->id,
                    'qr_code' => $validated['qrCode'],
                    'scan_type' => AttendanceScan::SCAN_TYPE_CHECK_OUT,
                ]);
            }

            DB::commit();

            $hours = floor($attendanceRecord->work_duration_minutes / 60);
            $minutes = $attendanceRecord->work_duration_minutes % 60;

            return response()->json([
                'success' => true,
                'message' => "Check-out successful at {$checkOutTime->format('h:i A')}. Total work time: {$hours}h {$minutes}m",
                'data' => $attendanceRecord->fresh()->load(['employee', 'branch', 'scans']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record check-out',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance records for the authenticated user
     */
    public function getAttendanceRecords(Request $request)
    {
        $employee = $request->user();
        
        $query = AttendanceRecord::with(['employee', 'branch', 'scans'])
            ->where('employee_id', $employee->id);

        // Filter by date range if provided
        if ($request->has('startDate')) {
            $query->where('attendance_date', '>=', $request->startDate);
        }
        if ($request->has('endDate')) {
            $query->where('attendance_date', '<=', $request->endDate);
        }

        // Filter by branch if provided
        if ($request->has('branchId')) {
            $query->where('branch_id', $request->branchId);
        }

        $attendanceRecords = $query->latest('attendance_date')->get();

        return response()->json([
            'success' => true,
            'message' => 'Attendance records fetched successfully.',
            'data' => $attendanceRecords,
        ]);
    }

    /**
     * Get all attendance records (for managers/admins)
     */
    public function getAllAttendanceRecords(Request $request)
    {
        $authUser = $request->user();

        $query = AttendanceRecord::with(['employee', 'branch', 'scans']);

        // Branch managers can only see their branch
        if ($authUser->role === User::ROLE_BRANCH_MANAGER) {
            $query->where('branch_id', $authUser->branch_id);
        }

        // Filter by branch if provided
        if ($request->has('branchId') && in_array($authUser->role, [User::ROLE_ADMIN, User::ROLE_HR_MANAGER])) {
            $query->where('branch_id', $request->branchId);
        }

        // Filter by employee if provided
        if ($request->has('employeeId')) {
            $query->where('employee_id', $request->employeeId);
        }

        // Filter by date range
        if ($request->has('startDate')) {
            $query->where('attendance_date', '>=', $request->startDate);
        }
        if ($request->has('endDate')) {
            $query->where('attendance_date', '<=', $request->endDate);
        }

        $attendanceRecords = $query->latest('attendance_date')->get();

        return response()->json([
            'success' => true,
            'message' => 'Attendance records fetched successfully.',
            'data' => $attendanceRecords,
        ]);
    }

    /**
     * Get today's attendance status
     */
    public function getTodayStatus(Request $request)
    {
        $employee = $request->user();
        $today = Carbon::today();

        $attendanceRecord = AttendanceRecord::with(['branch', 'scans'])
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        $status = [
            'hasCheckedIn' => false,
            'hasCheckedOut' => false,
            'checkInTime' => null,
            'checkOutTime' => null,
            'workDuration' => null,
            'attendanceRecord' => null,
        ];

        if ($attendanceRecord) {
            $status['hasCheckedIn'] = !is_null($attendanceRecord->check_in_time);
            $status['hasCheckedOut'] = !is_null($attendanceRecord->check_out_time);
            $status['checkInTime'] = $attendanceRecord->check_in_time?->toISOString();
            $status['checkOutTime'] = $attendanceRecord->check_out_time?->toISOString();
            $status['workDuration'] = $attendanceRecord->work_duration_minutes;
            $status['attendanceRecord'] = $attendanceRecord;
        }

        return response()->json([
            'success' => true,
            'message' => 'Today\'s attendance status fetched successfully.',
            'data' => $status,
        ]);
    }

    /**
     * Determine check-in status based on shift timing
     */
    private function determineCheckInStatus(User $employee, Carbon $checkInTime): string
    {
        // Simplified logic - you can enhance this based on shift timings
        // For now, assume work starts at 9:00 AM
        $expectedStartTime = Carbon::today()->setTime(9, 0);
        $lateThreshold = $expectedStartTime->copy()->addMinutes(15);

        if ($checkInTime->lte($expectedStartTime)) {
            return AttendanceRecord::STATUS_ON_TIME;
        } elseif ($checkInTime->lte($lateThreshold)) {
            return AttendanceRecord::STATUS_ON_TIME;
        } else {
            return AttendanceRecord::STATUS_LATE;
        }
    }

    /**
     * Determine check-out status
     */
    private function determineCheckOutStatus(User $employee, Carbon $checkOutTime, string $currentStatus): string
    {
        // Simplified logic - assume work ends at 5:00 PM
        $expectedEndTime = Carbon::today()->setTime(17, 0);

        if ($checkOutTime->lt($expectedEndTime->copy()->subMinutes(30))) {
            return AttendanceRecord::STATUS_EARLY_DEPARTURE;
        }

        return $currentStatus; // Keep existing status
    }
}