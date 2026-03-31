<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Leave::with(['user.branch', 'approver']);

        if ($user->role === User::ROLE_EMPLOYEE) {
            $query->where('user_id', $user->id);
        } elseif ($user->role === User::ROLE_BRANCH_MANAGER) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        $branchId = $request->query('branchId');
        if ($branchId && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_HR_MANAGER])) {
            $query->whereHas('user', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $leaves = $query->latest('requested_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Leave requests fetched successfully',
            'data' => $leaves,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'userId' => ['required', 'exists:users,id'],
            'leaveType' => ['required', Rule::in([
                Leave::TYPE_SICK,
                Leave::TYPE_CASUAL,
                Leave::TYPE_ANNUAL,
                Leave::TYPE_MATERNITY,
                Leave::TYPE_PATERNITY,
                Leave::TYPE_UNPAID,
            ])],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $user = User::findOrFail($validated['userId']);
        $startDate = new \DateTime($validated['startDate']);
        $endDate = new \DateTime($validated['endDate']);
        $duration = $startDate->diff($endDate)->days + 1;

        if ($validated['leaveType'] !== Leave::TYPE_UNPAID && $user->leave_balance < $duration) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient leave balance. Available: ' . $user->leave_balance . ' days, Requested: ' . $duration . ' days',
            ], 400);
        }

        $overlapping = Leave::where('user_id', $validated['userId'])
            ->where('status', '!=', Leave::STATUS_REJECTED)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['startDate'], $validated['endDate']])
                    ->orWhereBetween('end_date', [$validated['startDate'], $validated['endDate']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_date', '<=', $validated['startDate'])
                          ->where('end_date', '>=', $validated['endDate']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request overlaps with an existing leave request',
            ], 400);
        }

        $leave = Leave::create([
            'user_id' => $validated['userId'],
            'leave_type' => $validated['leaveType'],
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'reason' => $validated['reason'],
            'status' => Leave::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted successfully',
            'data' => $leave->load(['user.branch', 'approver']),
        ], 201);
    }

    public function show(Leave $leave)
    {
        return response()->json([
            'success' => true,
            'message' => 'Leave request fetched successfully',
            'data' => $leave->load(['user.branch', 'approver']),
        ]);
    }

    public function updateStatus(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Leave::STATUS_APPROVED, Leave::STATUS_REJECTED])],
            'rejectionReason' => ['required_if:status,' . Leave::STATUS_REJECTED, 'nullable', 'string', 'max:500'],
        ]);

        if ($leave->status !== Leave::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request has already been processed',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $leave->update([
                'status' => $validated['status'],
                'approved_by' => $request->user()->id,
                'rejection_reason' => $validated['rejectionReason'] ?? null,
            ]);

            if ($validated['status'] === Leave::STATUS_APPROVED && $leave->leave_type !== Leave::TYPE_UNPAID) {
                $user = $leave->user;
                $duration = $leave->start_date->diffInDays($leave->end_date) + 1;
                $user->decrement('leave_balance', $duration);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request ' . strtolower($validated['status']) . ' successfully',
                'data' => $leave->fresh()->load(['user.branch', 'approver']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update leave request status',
            ], 500);
        }
    }

    public function destroy(Leave $leave)
    {
        if ($leave->status !== Leave::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending leave requests can be deleted',
            ], 400);
        }

        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave request deleted successfully',
        ]);
    }

    public function getEmployeeLeaves(Request $request, $userId)
    {
        $user = $request->user();

        if ($user->role === User::ROLE_EMPLOYEE && $user->id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $leaves = Leave::with(['user.branch', 'approver'])
            ->where('user_id', $userId)
            ->latest('requested_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Employee leave requests fetched successfully',
            'data' => $leaves,
        ]);
    }
}
