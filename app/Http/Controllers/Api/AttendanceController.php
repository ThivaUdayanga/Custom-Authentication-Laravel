<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceScan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function markAttendance(Request $request)
    {
        $validated = $request->validate([
            'qrCode' => ['required', 'string'],
            'scanType' => ['required', 'in:Check-in,Check-out'],
        ]);

        $employee = User::where('email', $request->user()->email)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }

        $branchId = $validated['qrCode']; // Assuming the QR code contains the branch ID (example format: 'branch-id:1')
        $scanTime = Carbon::now();

        // Check if attendance record already exists
        $attendanceRecord = AttendanceRecord::where('employee_id', $employee->id)
            ->where('branch_id', $branchId)
            ->whereDate('scan_time', $scanTime->toDateString())
            ->first();

        // Create a new attendance record if not found
        if (!$attendanceRecord) {
            $attendanceRecord = AttendanceRecord::create([
                'employee_id' => $employee->id,
                'branch_id' => $branchId,
                'status' => 'On Time',
                'scan_type' => $validated['scanType'],
                'scan_time' => $scanTime,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        }

        // Store the scan result
        $attendanceScan = AttendanceScan::create([
            'attendance_record_id' => $attendanceRecord->id,
            'qr_code' => $validated['qrCode'],
            'scan_type' => $validated['scanType'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully.',
            'data' => [
                'attendance_record' => $attendanceRecord,
                'scan' => $attendanceScan,
            ],
        ]);
    }

    public function getAttendanceRecords(Request $request)
    {
        $employee = User::where('email', $request->user()->email)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }

        $attendanceRecords = AttendanceRecord::with(['scans', 'branch'])
            ->where('employee_id', $employee->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Attendance records fetched successfully.',
            'data' => $attendanceRecords,
        ]);
    }
}