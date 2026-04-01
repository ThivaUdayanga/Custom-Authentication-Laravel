<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveCategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LeaveCategory::with('branch');

        // Filter by branch for branch managers
        if ($user->role === User::ROLE_BRANCH_MANAGER) {
            $query->where(function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                  ->orWhereNull('branch_id'); // Include global categories
            });
        }

        // Filter by active status
        if ($request->query('activeOnly') === 'true') {
            $query->where('is_active', true);
        }

        // Filter by role applicability
        if ($user->role !== User::ROLE_ADMIN) {
            $query->where(function ($q) use ($user) {
                $q->whereJsonContains('applicable_roles', $user->role)
                  ->orWhereNull('applicable_roles')
                  ->orWhereJsonLength('applicable_roles', 0);
            });
        }

        $categories = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Leave categories fetched successfully',
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'branchId' => ['nullable', 'exists:branches,id'],
            'leaveDurationType' => ['required', Rule::in([
                LeaveCategory::DURATION_FULL_DAY,
                LeaveCategory::DURATION_HALF_DAY,
                LeaveCategory::DURATION_BOTH,
            ])],
            'daysPerYear' => ['required', 'integer', 'min:0', 'max:365'],
            'applicableRoles' => ['nullable', 'array'],
            'applicableRoles.*' => [Rule::in([
                User::ROLE_EMPLOYEE,
                User::ROLE_BRANCH_MANAGER,
                User::ROLE_HR_MANAGER,
            ])],
            'isPaid' => ['boolean'],
            'isActive' => ['boolean'],
        ]);

        $category = LeaveCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'branch_id' => $validated['branchId'] ?? null,
            'leave_duration_type' => $validated['leaveDurationType'],
            'days_per_year' => $validated['daysPerYear'],
            'applicable_roles' => $validated['applicableRoles'] ?? null,
            'is_paid' => $validated['isPaid'] ?? true,
            'is_active' => $validated['isActive'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave category created successfully',
            'data' => $category->load('branch'),
        ], 201);
    }

    public function show(LeaveCategory $leaveCategory)
    {
        return response()->json([
            'success' => true,
            'message' => 'Leave category fetched successfully',
            'data' => $leaveCategory->load('branch'),
        ]);
    }

    public function update(Request $request, LeaveCategory $leaveCategory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'branchId' => ['nullable', 'exists:branches,id'],
            'leaveDurationType' => ['required', Rule::in([
                LeaveCategory::DURATION_FULL_DAY,
                LeaveCategory::DURATION_HALF_DAY,
                LeaveCategory::DURATION_BOTH,
            ])],
            'daysPerYear' => ['required', 'integer', 'min:0', 'max:365'],
            'applicableRoles' => ['nullable', 'array'],
            'applicableRoles.*' => [Rule::in([
                User::ROLE_EMPLOYEE,
                User::ROLE_BRANCH_MANAGER,
                User::ROLE_HR_MANAGER,
            ])],
            'isPaid' => ['boolean'],
            'isActive' => ['boolean'],
        ]);

        $leaveCategory->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'branch_id' => $validated['branchId'] ?? null,
            'leave_duration_type' => $validated['leaveDurationType'],
            'days_per_year' => $validated['daysPerYear'],
            'applicable_roles' => $validated['applicableRoles'] ?? null,
            'is_paid' => $validated['isPaid'] ?? $leaveCategory->is_paid,
            'is_active' => $validated['isActive'] ?? $leaveCategory->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave category updated successfully',
            'data' => $leaveCategory->fresh()->load('branch'),
        ]);
    }

    public function destroy(LeaveCategory $leaveCategory)
    {
        // Check if category has any leaves
        if ($leaveCategory->leaves()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing leave requests',
            ], 400);
        }

        $leaveCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave category deleted successfully',
        ]);
    }

    public function getApplicableCategories(Request $request)
    {
        $user = $request->user();

        $categories = LeaveCategory::where('is_active', true)
            ->where(function ($q) use ($user) {
                // Filter by branch
                $q->where('branch_id', $user->branch_id)
                  ->orWhereNull('branch_id');
            })
            ->where(function ($q) use ($user) {
                // Filter by role
                $q->whereJsonContains('applicable_roles', $user->role)
                  ->orWhereNull('applicable_roles')
                  ->orWhereJsonLength('applicable_roles', 0);
            })
            ->with('branch')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Applicable leave categories fetched successfully',
            'data' => $categories,
        ]);
    }
}
