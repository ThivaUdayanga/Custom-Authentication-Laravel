<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeRosterAssignment;
use App\Models\Roster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeRosterAssignmentController extends Controller
{
    public function index()
    {
        $assignments = EmployeeRosterAssignment::with([
            'employee.branch',
            'roster.branch',
            'roster.items.shift',
            'assignedBy',
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Employee roster assignments fetched successfully',
            'data' => $assignments,
        ]);
    }

    public function show(EmployeeRosterAssignment $employeeRosterAssignment)
    {
        $employeeRosterAssignment->load([
            'employee.branch',
            'roster.branch',
            'roster.items.shift',
            'assignedBy',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee roster assignment fetched successfully',
            'data' => $employeeRosterAssignment,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'userId' => ['required', 'exists:users,id'],
            'rosterId' => ['required', 'exists:rosters,id'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'isActive' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $employee = User::findOrFail($validated['userId']);
        $roster = Roster::with('branch')->findOrFail($validated['rosterId']);

        if (!in_array($employee->role, [
            User::ROLE_EMPLOYEE,
            User::ROLE_BRANCH_MANAGER,
            User::ROLE_HR_MANAGER
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Roster can only be assigned to users with Employee, Branch Manager, or HR Manager role',
            ], 422);
        }

        if ((int) $employee->branch_id !== (int) $roster->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Employee and roster must belong to the same branch',
            ], 422);
        }

        $hasOverlap = EmployeeRosterAssignment::where('user_id', $employee->id)
            ->where(function ($query) use ($validated) {
                $startDate = $validated['startDate'];
                $endDate = $validated['endDate'] ?? null;

                if ($endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereDate('start_date', '<=', $endDate)
                          ->where(function ($inner) use ($startDate) {
                              $inner->whereNull('end_date')
                                    ->orWhereDate('end_date', '>=', $startDate);
                          });
                    });
                } else {
                    $query->where(function ($q) use ($startDate) {
                        $q->whereNull('end_date')
                          ->orWhereDate('end_date', '>=', $startDate);
                    });
                }
            })
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'success' => false,
                'message' => 'This employee already has a roster assignment overlapping the selected date range',
            ], 422);
        }

        $assignment = DB::transaction(function () use ($validated, $request) {
            return EmployeeRosterAssignment::create([
                'user_id' => $validated['userId'],
                'roster_id' => $validated['rosterId'],
                'assigned_by' => $request->user()->id,
                'start_date' => $validated['startDate'],
                'end_date' => $validated['endDate'] ?? null,
                'is_active' => $validated['isActive'] ?? true,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $assignment->load([
            'employee.branch',
            'roster.branch',
            'roster.items.shift',
            'assignedBy',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Roster assigned to employee successfully',
            'data' => $assignment,
        ], 201);
    }

    public function update(Request $request, EmployeeRosterAssignment $employeeRosterAssignment)
    {
        $validated = $request->validate([
            'rosterId' => ['required', 'exists:rosters,id'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'isActive' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $employee = $employeeRosterAssignment->employee;
        $roster = Roster::findOrFail($validated['rosterId']);

        if ((int) $employee->branch_id !== (int) $roster->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Employee and roster must belong to the same branch',
            ], 422);
        }

        $hasOverlap = EmployeeRosterAssignment::where('user_id', $employee->id)
            ->where('id', '!=', $employeeRosterAssignment->id)
            ->where(function ($query) use ($validated) {
                $startDate = $validated['startDate'];
                $endDate = $validated['endDate'] ?? null;

                if ($endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereDate('start_date', '<=', $endDate)
                          ->where(function ($inner) use ($startDate) {
                              $inner->whereNull('end_date')
                                    ->orWhereDate('end_date', '>=', $startDate);
                          });
                    });
                } else {
                    $query->where(function ($q) use ($startDate) {
                        $q->whereNull('end_date')
                          ->orWhereDate('end_date', '>=', $startDate);
                    });
                }
            })
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'success' => false,
                'message' => 'This employee already has another overlapping roster assignment',
            ], 422);
        }

        $employeeRosterAssignment->update([
            'roster_id' => $validated['rosterId'],
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'] ?? null,
            'is_active' => $validated['isActive'] ?? $employeeRosterAssignment->is_active,
            'notes' => $validated['notes'] ?? null,
            'assigned_by' => $request->user()->id,
        ]);

        $employeeRosterAssignment->load([
            'employee.branch',
            'roster.branch',
            'roster.items.shift',
            'assignedBy',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee roster assignment updated successfully',
            'data' => $employeeRosterAssignment,
        ]);
    }

    public function destroy(EmployeeRosterAssignment $employeeRosterAssignment)
    {
        $employeeRosterAssignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee roster assignment deleted successfully',
        ]);
    }
}