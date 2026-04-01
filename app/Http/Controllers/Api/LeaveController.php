<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Leave::with(['user.branch', 'leaveCategory', 'approver']);

        // Role-based filtering
        if ($user->role === User::ROLE_EMPLOYEE) {
            // Employees can only see their own leaves
            $query->where('user_id', $user->id);
        } elseif ($user->role === User::ROLE_BRANCH_MANAGER) {
            // Branch Managers see leaves from their branch
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        } elseif ($user->role === User::ROLE_HR_MANAGER) {
            // HR Managers see employee leaves (not other HR/Branch Managers)
            $query->whereHas('user', function ($q) {
                $q->where('role', User::ROLE_EMPLOYEE);
            });
        }
        // Admin sees all leaves (no filter)

        // Optional branch filter for Admin and HR Manager
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
            'leaveCategoryId' => ['required', 'exists:leave_categories,id'],
            'durationType' => ['required', Rule::in([Leave::DURATION_FULL_DAY, Leave::DURATION_HALF_DAY])],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $user = User::findOrFail($validated['userId']);
        $category = LeaveCategory::findOrFail($validated['leaveCategoryId']);

        // Validate category is active
        if (!$category->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This leave category is not active',
            ], 400);
        }

        // Validate category is applicable for user's role
        if (!$category->isApplicableForRole($user->role)) {
            return response()->json([
                'success' => false,
                'message' => 'This leave category is not applicable for your role',
            ], 400);
        }

        // Validate category is applicable for user's branch
        if (!$category->isApplicableForBranch($user->branch_id)) {
            return response()->json([
                'success' => false,
                'message' => 'This leave category is not applicable for your branch',
            ], 400);
        }

        // Validate duration type
        if ($category->leave_duration_type !== LeaveCategory::DURATION_BOTH && 
            $category->leave_duration_type !== $validated['durationType']) {
            return response()->json([
                'success' => false,
                'message' => 'This leave category only allows ' . $category->leave_duration_type,
            ], 400);
        }

        // Calculate days count
        $startDate = new \DateTime($validated['startDate']);
        $endDate = new \DateTime($validated['endDate']);
        $daysDiff = $startDate->diff($endDate)->days + 1;
        $daysCount = $validated['durationType'] === Leave::DURATION_HALF_DAY ? $daysDiff * 0.5 : $daysDiff;

        // Check leave balance for paid leaves
        if ($category->is_paid && $user->leave_balance < $daysCount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient leave balance. Available: ' . $user->leave_balance . ' days, Requested: ' . $daysCount . ' days',
            ], 400);
        }

        // Check for overlapping leaves
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
            'leave_category_id' => $validated['leaveCategoryId'],
            'duration_type' => $validated['durationType'],
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
            'status' => Leave::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted successfully',
            'data' => $leave->load(['user.branch', 'leaveCategory', 'approver']),
        ], 201);
    }

    public function show(Leave $leave)
    {
        return response()->json([
            'success' => true,
            'message' => 'Leave request fetched successfully',
            'data' => $leave->load(['user.branch', 'leaveCategory', 'approver']),
        ]);
    }

    public function updateStatus(Request $request, Leave $leave)
    {
        $user = $request->user();
        
        // Role-based authorization
        $canApprove = false;
        if ($user->role === User::ROLE_ADMIN) {
            $canApprove = true;
        } elseif ($user->role === User::ROLE_HR_MANAGER) {
            // HR Manager can approve employee leaves
            $canApprove = $leave->user->role === User::ROLE_EMPLOYEE;
        } elseif ($user->role === User::ROLE_BRANCH_MANAGER) {
            // Branch Manager can approve leaves from their branch (employees and HR managers)
            $canApprove = $leave->user->branch_id === $user->branch_id && 
                         in_array($leave->user->role, [User::ROLE_EMPLOYEE, User::ROLE_HR_MANAGER]);
        }

        if (!$canApprove) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve this leave request',
            ], 403);
        }

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
                'approved_by' => $user->id,
                'rejection_reason' => $validated['rejectionReason'] ?? null,
            ]);

            // Deduct leave balance for approved paid leaves
            if ($validated['status'] === Leave::STATUS_APPROVED && $leave->leaveCategory->is_paid) {
                $leaveUser = $leave->user;
                $leaveUser->decrement('leave_balance', $leave->days_count);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request ' . strtolower($validated['status']) . ' successfully',
                'data' => $leave->fresh()->load(['user.branch', 'leaveCategory', 'approver']),
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

        $leaves = Leave::with(['user.branch', 'leaveCategory', 'approver'])
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
