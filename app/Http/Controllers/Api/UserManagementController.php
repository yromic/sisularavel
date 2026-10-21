<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Models\Division;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Bulk\UserBulkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UserManagementController extends Controller
{
    public function store(Request $request, AuditLogger $auditLogger): Response
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $rawUsername = Str::lower(trim((string) $request->input('username')));
        $rawEmail = $request->filled('email') ? Str::lower(trim((string) $request->input('email'))) : null;

        // Custom Uniqueness & Informative Message Check for Username
        if ($rawUsername !== '') {
            $existingUser = User::withTrashed()->where('username', $rawUsername)->first();
            if ($existingUser) {
                if ($existingUser->trashed()) {
                    throw ValidationException::withMessages([
                        'username' => ['Akun dengan username atau email tersebut pernah terdaftar dan saat ini berada dalam arsip pengguna. Silakan hubungi administrator untuk memulihkan akun.'],
                    ]);
                } else {
                    throw ValidationException::withMessages([
                        'username' => ['Username atau email sudah digunakan oleh akun lain.'],
                    ]);
                }
            }
        }

        // Custom Uniqueness & Informative Message Check for Email
        if ($rawEmail !== null) {
            $existingEmailUser = User::withTrashed()->where('email', $rawEmail)->first();
            if ($existingEmailUser) {
                if ($existingEmailUser->trashed()) {
                    throw ValidationException::withMessages([
                        'email' => ['Akun dengan username atau email tersebut pernah terdaftar dan saat ini berada dalam arsip pengguna. Silakan hubungi administrator untuk memulihkan akun.'],
                    ]);
                } else {
                    throw ValidationException::withMessages([
                        'email' => ['Username atau email sudah digunakan oleh akun lain.'],
                    ]);
                }
            }
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
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

        $auditLogger->userEvent('USER_CREATED', $actor, $user, "User {$user->username} berhasil dibuat.", [
            'username' => $user->username,
            'role' => $user->role,
            'division_id' => $user->division_id,
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Pengguna {$user->username} berhasil ditambahkan.");
        }

        return response()->json([
            'data' => $user->only(['id', 'uuid', 'username', 'name', 'email', 'role', 'division_id', 'is_active']),
        ], 201);
    }

    public function update(Request $request, User $user, AuditLogger $auditLogger): Response
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        if ($actor->role !== 'super_admin') {
            if ($user->division_id !== $actor->division_id || $user->role !== 'operator') {
                abort(403);
            }
        }

        $rawEmail = $request->filled('email') ? Str::lower(trim((string) $request->input('email'))) : null;
        if ($rawEmail !== null) {
            $existingEmailUser = User::withTrashed()
                ->where('email', $rawEmail)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingEmailUser) {
                if ($existingEmailUser->trashed()) {
                    throw ValidationException::withMessages([
                        'email' => ['Akun dengan username atau email tersebut pernah terdaftar dan saat ini berada dalam arsip pengguna. Silakan hubungi administrator untuk memulihkan akun.'],
                    ]);
                } else {
                    throw ValidationException::withMessages([
                        'email' => ['Username atau email sudah digunakan oleh akun lain.'],
                    ]);
                }
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'in:super_admin,division_admin,operator'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updateData = [
            'name' => trim($validated['name']),
            'email' => isset($validated['email']) ? Str::lower($validated['email']) : null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if ($actor->role === 'super_admin' && isset($validated['role'])) {
            $updateData['role'] = $validated['role'];
            $updateData['division_id'] = $validated['role'] === 'super_admin' ? null : ($validated['division_id'] ?? $user->division_id);
        }

        $user->update($updateData);

        $auditLogger->userEvent('USER_UPDATED', $actor, $user, "User {$user->username} berhasil diperbarui.", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'division_id' => $user->division_id,
            'is_active' => $user->is_active,
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Data pengguna {$user->username} berhasil diperbarui.");
        }

        return response()->json([
            'data' => $user->only(['id', 'uuid', 'username', 'name', 'email', 'role', 'division_id', 'is_active']),
        ]);
    }

    public function destroy(Request $request, User $user, AuditLogger $auditLogger): Response
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        if ($actor->id === $user->id) {
            if (! $request->expectsJson()) {
                return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            }

            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }

        if ($actor->role !== 'super_admin') {
            if ($user->division_id !== $actor->division_id || $user->role !== 'operator') {
                abort(403);
            }
        }

        $user->delete();

        $auditLogger->userEvent('USER_DELETED', $actor, $user, "User {$user->username} berhasil dihapus (soft delete).");

        if (! $request->expectsJson()) {
            return redirect('/master/pengguna')->with('success', "Pengguna {$user->username} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
    }

    public function restore(Request $request, int $id, AuditLogger $auditLogger): Response
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403, 'Anda tidak memiliki wewenang untuk memulihkan pengguna.');

        if ($actor->id === $id) {
            abort(403, 'Tidak dapat memulihkan akun sendiri melalui endpoint ini.');
        }

        $user = User::onlyTrashed()->findOrFail($id);

        // RBAC check for division admin
        if ($actor->role !== 'super_admin') {
            if ($user->division_id !== $actor->division_id || $user->role !== 'operator') {
                abort(403, 'Admin Divisi hanya berwenang memulihkan akun operator pada divisi sendiri.');
            }
        }

        // Division Status Integrity Check
        if ($user->division_id !== null) {
            $division = Division::find($user->division_id);
            if (! $division || $division->status !== 'active') {
                if (! $request->expectsJson()) {
                    return back()->with('error', "Gagal memulihkan: Divisi asal pengguna ({$user->division_id}) sudah tidak aktif atau tidak ditemukan.");
                }

                return response()->json([
                    'success' => false,
                    'code' => 'ERR_422_INACTIVE_DIVISION',
                    'message' => 'Divisi asal pengguna sudah tidak aktif atau tidak ditemukan.',
                ], 422);
            }
        }

        // Collision Check with Active Accounts
        $conflict = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($query) use ($user) {
                $query->where('username', $user->username);
                if ($user->email !== null) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->exists();

        if ($conflict) {
            if (! $request->expectsJson()) {
                return back()->with('error', 'Gagal memulihkan: Terdapat akun aktif lain dengan username atau email yang sama.');
            }

            return response()->json([
                'success' => false,
                'code' => 'ERR_409_CONFLICT',
                'message' => 'Terdapat akun aktif lain dengan username atau email yang sama.',
            ], 409);
        }

        DB::transaction(function () use ($user, $actor, $auditLogger) {
            $user->restore();
            // Account status remains deactivated until reactivation
            $user->forceFill(['is_active' => false])->save();

            $auditLogger->userEvent(
                'USER_RESTORED',
                $actor,
                $user,
                "User {$user->username} berhasil dipulihkan dari arsip pengguna.",
                $user->only(['id', 'username', 'role', 'division_id', 'is_active'])
            );
        });

        if (! $request->expectsJson()) {
            return back()->with('success', "Akun pengguna {$user->username} berhasil dipulihkan.");
        }

        return response()->json([
            'success' => true,
            'message' => "Akun pengguna {$user->username} berhasil dipulihkan.",
            'data' => $user->only(['id', 'uuid', 'username', 'name', 'email', 'role', 'division_id', 'is_active']),
        ]);
    }

    public function bulkPreview(BulkDeleteRequest $request, UserBulkService $bulkService): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $isRestore = $request->isRestore();
        $result = $isRestore
            ? $bulkService->previewRestore($request, $actor)
            : $bulkService->previewDelete($request, $actor);

        return response()->json($result->toArray());
    }

    public function bulkDestroy(BulkDeleteRequest $request, UserBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $result = $bulkService->bulkDelete($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }

    public function bulkRestore(BulkDeleteRequest $request, UserBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $result = $bulkService->bulkRestore($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }
}
