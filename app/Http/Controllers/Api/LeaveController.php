<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $query = Leave::with(['user.branch', 'leaveCategory', 'approver']);

        if ($authUser->role === User::ROLE_EMPLOYEE) {
            $query->where('user_id', $authUser->id);
        } elseif ($authUser->role === User::ROLE_BRANCH_MANAGER) {
            $query->where(function ($q) use ($authUser) {
                $q->where('user_id', $authUser->id)
                    ->orWhereHas('user', function ($subQuery) use ($authUser) {
                        $subQuery->where('branch_id', $authUser->branch_id)
                            ->where('role', User::ROLE_EMPLOYEE);
                    });
            });
        } elseif ($authUser->role === User::ROLE_HR_MANAGER) {
            $query->where(function ($q) use ($authUser) {
                $q->where('user_id', $authUser->id)
                    ->orWhereHas('user', function ($subQuery) {
                        $subQuery->where('role', User::ROLE_EMPLOYEE);
                    });
            });
        }

        $branchId = $request->query('branchId');
        if ($branchId && in_array($authUser->role, [User::ROLE_ADMIN, User::ROLE_HR_MANAGER], true)) {
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
        $authUser = $request->user();

        $validated = $request->validate([
            'leaveCategoryId' => ['required', 'exists:leave_categories,id'],
            'durationType' => ['required', Rule::in([Leave::DURATION_FULL_DAY, Leave::DURATION_HALF_DAY])],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $category = LeaveCategory::findOrFail($validated['leaveCategoryId']);

        if (!$category->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This leave category is not active',
            ], 400);
        }

        if (!$category->isApplicableForRole($authUser->role)) {
            return response()->json([
                'success' => false,
                'message' => 'This leave category is not applicable for your role',
            ], 400);
        }

        if (!$category->isApplicableForBranch($authUser->branch_id)) {
            return response()->json([
                'success' => false,
                'message' => 'This leave category is not applicable for your branch',
            ], 400);
        }

        if ($category->leave_duration_type !== LeaveCategory::DURATION_BOTH && $category->leave_duration_type !== $validated['durationType']) {
            return response()->json([
                'success' => false,
                'message' => 'This leave category only allows ' . $category->leave_duration_type,
            ], 400);
        }

        $startDate = new \DateTime($validated['startDate']);
        $endDate = new \DateTime($validated['endDate']);
        $daysDiff = $startDate->diff($endDate)->days + 1;
        $daysCount = $validated['durationType'] === Leave::DURATION_HALF_DAY ? $daysDiff * 0.5 : $daysDiff;

        if ($category->is_paid && $authUser->leave_balance < $daysCount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient leave balance. Available: ' . $authUser->leave_balance . ' days, Requested: ' . $daysCount . ' days',
            ], 400);
        }

        $overlapping = Leave::where('user_id', $authUser->id)
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
            'user_id' => $authUser->id,
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

    public function show(Request $request, Leave $leave)
    {
        $authUser = $request->user();

        if (!$this->canViewLeave($authUser, $leave)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave request fetched successfully',
            'data' => $leave->load(['user.branch', 'leaveCategory', 'approver']),
        ]);
    }

    public function updateStatus(Request $request, Leave $leave)
    {
        $authUser = $request->user();
        $leave->loadMissing('user', 'leaveCategory');

        if (!$this->canApproveLeave($authUser, $leave)) {
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
            $lockedUser = User::whereKey($leave->user_id)->lockForUpdate()->firstOrFail();

            if ($validated['status'] === Leave::STATUS_APPROVED && $leave->leaveCategory->is_paid && $lockedUser->leave_balance < $leave->days_count) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient leave balance at approval time. Available: ' . $lockedUser->leave_balance . ' days, Requested: ' . $leave->days_count . ' days',
                ], 400);
            }

            $leave->update([
                'status' => $validated['status'],
                'approved_by' => $authUser->id,
                'rejection_reason' => $validated['rejectionReason'] ?? null,
            ]);

            if ($validated['status'] === Leave::STATUS_APPROVED && $leave->leaveCategory->is_paid) {
                $lockedUser->decrement('leave_balance', $leave->days_count);
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
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Leave $leave)
    {
        $authUser = $request->user();

        if ($leave->status !== Leave::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending leave requests can be deleted',
            ], 400);
        }

        if ((int) $authUser->id !== (int) $leave->user_id && $authUser->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own pending leave requests',
            ], 403);
        }

        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave request deleted successfully',
        ]);
    }

    public function getEmployeeLeaves(Request $request, $userId)
    {
        $authUser = $request->user();
        $targetUser = User::findOrFail($userId);

        if (!$this->canViewUserLeaves($authUser, $targetUser)) {
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

    private function canViewLeave(User $authUser, Leave $leave): bool
    {
        $leave->loadMissing('user');
        return $this->canViewUserLeaves($authUser, $leave->user);
    }

    private function canViewUserLeaves(User $authUser, User $targetUser): bool
    {
        if ($authUser->role === User::ROLE_ADMIN) {
            return true;
        }

        if ((int) $authUser->id === (int) $targetUser->id) {
            return true;
        }

        if ($authUser->role === User::ROLE_HR_MANAGER) {
            return $targetUser->role === User::ROLE_EMPLOYEE;
        }

        if ($authUser->role === User::ROLE_BRANCH_MANAGER) {
            return $targetUser->role === User::ROLE_EMPLOYEE && (int) $targetUser->branch_id === (int) $authUser->branch_id;
        }

        return false;
    }

    private function canApproveLeave(User $authUser, Leave $leave): bool
    {
        if ((int) $authUser->id === (int) $leave->user_id) {
            return false;
        }

        if ($authUser->role === User::ROLE_ADMIN) {
            return true;
        }

        if ($authUser->role === User::ROLE_HR_MANAGER) {
            return $leave->user->role === User::ROLE_EMPLOYEE;
        }

        if ($authUser->role === User::ROLE_BRANCH_MANAGER) {
            return $leave->user->role === User::ROLE_EMPLOYEE
                && (int) $leave->user->branch_id === (int) $authUser->branch_id;
        }

        return false;
    }
}
