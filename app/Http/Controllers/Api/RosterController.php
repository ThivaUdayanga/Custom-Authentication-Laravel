<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Roster;
use App\Models\RosterItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RosterController extends Controller
{
    public function index()
    {
        $rosters = Roster::with(['branch', 'items.shift'])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Rosters fetched successfully',
            'data' => $rosters,
        ]);
    }

    public function show(Roster $roster)
    {
        $roster->load(['branch', 'items.shift']);

        return response()->json([
            'success' => true,
            'message' => 'Roster fetched successfully',
            'data' => $roster,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branchId' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.dayOrder' => ['required', 'integer', 'min:1'],
            'items.*.shiftId' => ['required', 'exists:shifts,id'],
        ]);

        $roster = DB::transaction(function () use ($validated) {
            $roster = Roster::create([
                'branch_id' => $validated['branchId'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['isActive'] ?? true,
            ]);

            foreach ($validated['items'] as $item) {
                RosterItem::create([
                    'roster_id' => $roster->id,
                    'shift_id' => $item['shiftId'],
                    'day_order' => $item['dayOrder'],
                ]);
            }

            return $roster->load(['branch', 'items.shift']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Roster created successfully',
            'data' => $roster,
        ], 201);
    }

    public function update(Request $request, Roster $roster)
    {
        $validated = $request->validate([
            'branchId' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.dayOrder' => ['required', 'integer', 'min:1'],
            'items.*.shiftId' => ['required', 'exists:shifts,id'],
        ]);

        DB::transaction(function () use ($validated, $roster) {
            $roster->update([
                'branch_id' => $validated['branchId'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['isActive'] ?? $roster->is_active,
            ]);

            $roster->items()->delete();

            foreach ($validated['items'] as $item) {
                RosterItem::create([
                    'roster_id' => $roster->id,
                    'shift_id' => $item['shiftId'],
                    'day_order' => $item['dayOrder'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Roster updated successfully',
            'data' => $roster->fresh()->load(['branch', 'items.shift']),
        ]);
    }

    public function destroy(Roster $roster)
    {
        $roster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Roster deleted successfully',
        ]);
    }
}