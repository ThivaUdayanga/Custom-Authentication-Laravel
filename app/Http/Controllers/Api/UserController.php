<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('branch');

        if ($request->has('email')) {
            $query->where('email', $request->input('email'));
        }

        $users = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Users fetched successfully',
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4', 'max:255'],
            'role' => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_HR_MANAGER,
                User::ROLE_BRANCH_MANAGER,
                User::ROLE_EMPLOYEE,
            ])],
            'branchId' => ['required', 'exists:branches,id'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'employmentStatus' => ['required', Rule::in(['Active', 'On Leave', 'Resigned', 'Terminated'])],
            'dateOfJoining' => ['required', 'date'],
            'shiftId' => ['nullable'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'branch_id' => $validated['branchId'],
            'department' => $validated['department'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'employment_status' => $validated['employmentStatus'],
            'date_of_joining' => $validated['dateOfJoining'],
            'shift_id' => $validated['shiftId'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load('branch'),
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'User fetched successfully',
            'data' => $user->load('branch'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'role' => ['sometimes', 'required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_HR_MANAGER,
                User::ROLE_BRANCH_MANAGER,
                User::ROLE_EMPLOYEE,
            ])],
            'branchId' => ['sometimes', 'required', 'exists:branches,id'],
            // 'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            // 'designation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'employmentStatus' => ['sometimes', 'required', Rule::in(['Active', 'On Leave', 'Resigned', 'Terminated'])],
            'dateOfJoining' => ['sometimes', 'required', 'date'],
            // 'shiftId' => ['nullable'],
        ]);

        $data = [];

        if (array_key_exists('name', $validated)) $data['name'] = $validated['name'];
        if (array_key_exists('email', $validated)) $data['email'] = $validated['email'];
        if (!empty($validated['password'])) $data['password'] = $validated['password'];
        if (array_key_exists('role', $validated)) $data['role'] = $validated['role'];
        if (array_key_exists('branchId', $validated)) $data['branch_id'] = $validated['branchId'];
        // if (array_key_exists('department', $validated)) $data['department'] = $validated['department'];
        // if (array_key_exists('designation', $validated)) $data['designation'] = $validated['designation'];
        if (array_key_exists('employmentStatus', $validated)) $data['employment_status'] = $validated['employmentStatus'];
        if (array_key_exists('dateOfJoining', $validated)) $data['date_of_joining'] = $validated['dateOfJoining'];
        // if (array_key_exists('shiftId', $validated)) $data['shift_id'] = $validated['shiftId'];

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh()->load('branch'),
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}