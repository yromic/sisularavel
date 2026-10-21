<?php

namespace App\Services\Bulk;

use App\Http\Requests\BulkDeleteRequest;
use App\Models\Division;
use App\Models\User;
use App\Services\AuditLogger;

class UserBulkService extends BulkDeleteService
{
    public function previewDelete(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateDelete($request, $actor, false);
    }

    public function bulkDelete(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateDelete($request, $actor, true, $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'USER_BULK_DELETED',
                $actor,
                User::class,
                $result->deletedCount(),
                "Menghapus massal {$result->deletedCount()} pengguna.",
                ['deleted' => $result->deleted, 'skipped_count' => $result->skippedCount()]
            );
        }

        return $result;
    }

    public function previewRestore(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateRestore($request, $actor, false);
    }

    public function bulkRestore(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateRestore($request, $actor, true, $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'USER_BULK_RESTORED',
                $actor,
                User::class,
                $result->deletedCount(),
                "Memulihkan massal {$result->deletedCount()} pengguna.",
                ['restored' => $result->deleted, 'skipped_count' => $result->skippedCount()]
            );
        }

        return $result;
    }

    protected function evaluateDelete(BulkDeleteRequest $request, User $actor, bool $execute, ?AuditLogger $auditLogger = null): BulkDeleteResult
    {
        $baseQuery = User::query();

        if ($actor->role !== 'super_admin') {
            $baseQuery->where('division_id', $actor->division_id)
                ->where('role', 'operator');
        }

        $ids = $this->resolveTargetIds($request, $baseQuery);

        // Cross-division strict check: if actor is not super_admin, verify no foreign users are requested
        if ($actor->role !== 'super_admin') {
            $foreignExists = User::query()
                ->whereIn('id', $ids)
                ->where(function ($q) use ($actor) {
                    $q->where('division_id', '!=', $actor->division_id)
                        ->orWhere('role', '!=', 'operator');
                })
                ->exists();

            if ($foreignExists) {
                abort(403, 'Akses ditolak: Anda tidak memiliki izin mengelola akun pengguna divisi lain atau selain operator.');
            }
        }

        $users = User::query()->whereIn('id', $ids)->get();
        $totalSuperAdmins = User::query()->where('role', 'super_admin')->count();
        $result = new BulkDeleteResult;

        $deletableUsers = [];

        foreach ($users as $user) {
            if ($user->id === $actor->id) {
                $result->addSkipped($user->id, $user->username, 'Anda tidak dapat menghapus akun Anda sendiri.');

                continue;
            }

            if ($user->role === 'super_admin' && $totalSuperAdmins <= 1) {
                $result->addSkipped($user->id, $user->username, 'Tidak dapat menghapus Super Admin terakhir pada sistem.');

                continue;
            }

            $deletableUsers[] = $user;
        }

        if ($execute) {
            $deletableIds = array_map(fn ($u) => $u->id, $deletableUsers);
            $userMap = collect($deletableUsers)->keyBy('id');

            $this->processChunks($deletableIds, function (array $chunk) use ($userMap, $actor, $auditLogger, $result): void {
                foreach ($chunk as $id) {
                    $user = $userMap->get($id);
                    if ($user) {
                        $user->delete();
                        $auditLogger?->userEvent(
                            'USER_DELETED',
                            $actor,
                            $user,
                            "User {$user->username} berhasil dihapus (soft delete)."
                        );
                        $result->addDeleted($user->id);
                    }
                }
            });
        } else {
            foreach ($deletableUsers as $u) {
                $result->addDeleted($u->id);
            }
        }

        return $result;
    }

    protected function evaluateRestore(BulkDeleteRequest $request, User $actor, bool $execute, ?AuditLogger $auditLogger = null): BulkDeleteResult
    {
        $baseQuery = User::onlyTrashed();

        if ($actor->role !== 'super_admin') {
            $baseQuery->where('division_id', $actor->division_id)
                ->where('role', 'operator');
        }

        $ids = $this->resolveTargetIds($request, $baseQuery);

        if ($actor->role !== 'super_admin') {
            $foreignExists = User::onlyTrashed()
                ->whereIn('id', $ids)
                ->where(function ($q) use ($actor) {
                    $q->where('division_id', '!=', $actor->division_id)
                        ->orWhere('role', '!=', 'operator');
                })
                ->exists();

            if ($foreignExists) {
                abort(403, 'Akses ditolak: Admin Divisi hanya berwenang memulihkan akun operator pada divisi sendiri.');
            }
        }

        $trashedUsers = User::onlyTrashed()->whereIn('id', $ids)->get();
        $result = new BulkDeleteResult;
        $restorableUsers = [];

        foreach ($trashedUsers as $user) {
            if ($user->id === $actor->id) {
                $result->addSkipped($user->id, $user->username, 'Tidak dapat memulihkan akun sendiri melalui aksi massal.');

                continue;
            }

            if ($user->division_id !== null) {
                $division = Division::find($user->division_id);
                if (! $division || $division->status !== 'active') {
                    $result->addSkipped($user->id, $user->username, 'Divisi asal pengguna sudah tidak aktif atau tidak ditemukan.');

                    continue;
                }
            }

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
                $result->addSkipped($user->id, $user->username, 'Terdapat akun aktif lain dengan username atau email yang sama.');

                continue;
            }

            $restorableUsers[] = $user;
        }

        if ($execute) {
            $restorableIds = array_map(fn ($u) => $u->id, $restorableUsers);
            $userMap = collect($restorableUsers)->keyBy('id');

            $this->processChunks($restorableIds, function (array $chunk) use ($userMap, $actor, $auditLogger, $result): void {
                foreach ($chunk as $id) {
                    $user = $userMap->get($id);
                    if ($user) {
                        $user->restore();
                        $user->forceFill(['is_active' => false])->save();
                        $auditLogger?->userEvent(
                            'USER_RESTORED',
                            $actor,
                            $user,
                            "User {$user->username} berhasil dipulihkan dari arsip pengguna.",
                            $user->only(['id', 'username', 'role', 'division_id', 'is_active'])
                        );
                        $result->addDeleted($user->id);
                    }
                }
            });
        } else {
            foreach ($restorableUsers as $u) {
                $result->addDeleted($u->id);
            }
        }

        return $result;
    }
}
