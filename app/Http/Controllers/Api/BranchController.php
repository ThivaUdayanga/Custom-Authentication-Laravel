<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Branches fetched successfully',
            'data' => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255', 'unique:branches,name'],
            'location' => ['required', 'string', 'min:3', 'max:255'],
            'workingHours' => ['nullable', 'string', 'max:255'],
            'timeZone' => ['nullable', 'string', 'max:255'],
        ]);

        $branch = Branch::create([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'working_hours' => $validated['workingHours'] ?? null,
            'time_zone' => $validated['timeZone'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully',
            'data' => $branch,
        ], 201);
    }

    public function show(Branch $branch)
    {
        return response()->json([
            'success' => true,
            'message' => 'Branch fetched successfully',
            'data' => $branch,
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255', Rule::unique('branches', 'name')->ignore($branch->id)],
            'location' => ['required', 'string', 'min:3', 'max:255'],
            'workingHours' => ['nullable', 'string', 'max:255'],
            'timeZone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branch->update([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'working_hours' => $validated['workingHours'] ?? null,
            'time_zone' => $validated['timeZone'] ?? null,
            'is_active' => $validated['is_active'] ?? $branch->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully',
            'data' => $branch->fresh(),
        ]);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully',
        ]);
    }
}