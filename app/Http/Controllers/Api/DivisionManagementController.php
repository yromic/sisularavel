<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DivisionManagementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:divisions,code'],
            'name' => ['required', 'string', 'max:150'],
        ]);

        $division = Division::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => Str::upper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'status' => 'active',
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $division], 201);
    }

    public function updateStatus(Request $request, Division $division): JsonResponse
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $division->update(['status' => $validated['status']]);

        return response()->json(['data' => $division->refresh()]);
    }
}
