<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:super_admin,division_admin,operator'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($actor->role !== 'super_admin') {
            if ($validated['role'] === 'super_admin' || ($validated['division_id'] ?? null) !== $actor->division_id) {
                abort(403);
            }
        }

        if ($validated['role'] !== 'super_admin' && empty($validated['division_id'])) {
            abort(422, 'division_id wajib diisi.');
        }

        $user = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'username' => Str::lower($validated['username']),
            'name' => trim($validated['name']),
            'email' => isset($validated['email']) ? Str::lower($validated['email']) : null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'division_id' => $validated['role'] === 'super_admin' ? null : $validated['division_id'],
            'is_active' => $validated['is_active'] ?? true,
            'must_reset_password' => false,
        ]);

        return response()->json([
            'data' => $user->only(['id', 'uuid', 'username', 'name', 'email', 'role', 'division_id', 'is_active']),
        ], 201);
    }
}
