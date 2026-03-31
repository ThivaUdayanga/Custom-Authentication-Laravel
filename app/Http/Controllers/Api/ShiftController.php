<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::latest()->get()->map(function ($shift) {
            return [
                'id' => (string) $shift->id,
                'name' => $shift->name,
                'startTime' => \Carbon\Carbon::parse($shift->start_time)->format('H:i'),
                'endTime' => \Carbon\Carbon::parse($shift->end_time)->format('H:i'),
                'description' => $shift->description,
                'isActive' => $shift->is_active,
                'breakDurationMinutes' => $shift->break_duration_minutes,
                // 'overtimeRate' => $shift->overtime_rate,
                'createdAt' => $shift->created_at,
                'updatedAt' => $shift->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Shifts fetched successfully',
            'data' => $shifts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
            'breakDurationMinutes' => ['nullable', 'integer', 'min:0'],
            // 'overtimeRate' => ['nullable', 'numeric', 'min:1'],
        ]);

        $shift = Shift::create([
            'name' => $validated['name'],
            'start_time' => $validated['startTime'],
            'end_time' => $validated['endTime'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
            'break_duration_minutes' => $validated['breakDurationMinutes'] ?? null,
            // 'overtime_rate' => $validated['overtimeRate'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift created successfully',
            'data' => [
                'id' => (string) $shift->id,
                'name' => $shift->name,
                'startTime' => \Carbon\Carbon::parse($shift->start_time)->format('H:i'),
                'endTime' => \Carbon\Carbon::parse($shift->end_time)->format('H:i'),
                'description' => $shift->description,
                'isActive' => $shift->is_active,
                'breakDurationMinutes' => $validated['breakDurationMinutes'] ?? null,
                // 'overtimeRate' => $validated['overtimeRate'] ?? null,
            ],
        ], 201);
    }

    public function show(Shift $shift)
    {
        return response()->json([
            'success' => true,
            'message' => 'Shift fetched successfully',
            'data' => [
                'id' => (string) $shift->id,
                'name' => $shift->name,
                'startTime' => \Carbon\Carbon::parse($shift->start_time)->format('H:i'),
                'endTime' => \Carbon\Carbon::parse($shift->end_time)->format('H:i'),
                'description' => $shift->description,
                'isActive' => $shift->is_active,
                'breakDurationMinutes' => $shift->break_duration_minutes,
                // 'overtimeRate' => $shift->overtime_rate,
            ],
        ]);
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
            'breakDurationMinutes' => ['nullable', 'integer', 'min:0'],
            // 'overtimeRate' => ['nullable', 'numeric', 'min:1'],
        ]);

        $shift->update([
            'name' => $validated['name'],
            'start_time' => $validated['startTime'],
            'end_time' => $validated['endTime'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['isActive'] ?? $shift->is_active,
            'break_duration_minutes' => $validated['breakDurationMinutes'] ?? null,
            // 'overtime_rate' => $validated['overtimeRate'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift updated successfully',
            'data' => [
                'id' => (string) $shift->id,
                'name' => $shift->name,
                'startTime' => \Carbon\Carbon::parse($shift->start_time)->format('H:i'),
                'endTime' => \Carbon\Carbon::parse($shift->end_time)->format('H:i'),
                'description' => $shift->description,
                'isActive' => $shift->is_active,
                'breakDurationMinutes' => $validated['breakDurationMinutes'] ?? null,
                // 'overtimeRate' => $validated['overtimeRate'] ?? null,
            ],
        ]);
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shift deleted successfully',
        ]);
    }
}