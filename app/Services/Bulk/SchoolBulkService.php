<?php

namespace App\Services\Bulk;

use App\Http\Requests\BulkDeleteRequest;
use App\Models\Piagam;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogger;

class SchoolBulkService extends BulkDeleteService
{
    public function previewDelete(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        abort_unless($actor->role === 'super_admin', 403, 'Hanya Super Admin yang berwenang mengelola data sekolah.');

        return $this->evaluateDelete($request, $actor, false);
    }

    public function bulkDelete(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        abort_unless($actor->role === 'super_admin', 403, 'Hanya Super Admin yang berwenang mengelola data sekolah.');

        $result = $this->evaluateDelete($request, $actor, true, $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'SCHOOL_BULK_DELETED',
                $actor,
                School::class,
                $result->deletedCount(),
                "Menghapus massal {$result->deletedCount()} data sekolah (soft delete).",
                ['deleted' => $result->deleted, 'skipped_count' => $result->skippedCount()]
            );
        }

        return $result;
    }

    public function previewRestore(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        abort_unless($actor->role === 'super_admin', 403, 'Hanya Super Admin yang berwenang memulihkan data sekolah.');

        return $this->evaluateRestore($request, $actor, false);
    }

    public function bulkRestore(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        abort_unless($actor->role === 'super_admin', 403, 'Hanya Super Admin yang berwenang memulihkan data sekolah.');

        $result = $this->evaluateRestore($request, $actor, true, $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'SCHOOL_BULK_RESTORED',
                $actor,
                School::class,
                $result->deletedCount(),
                "Memulihkan massal {$result->deletedCount()} data sekolah dari arsip.",
                ['restored' => $result->deleted, 'skipped_count' => $result->skippedCount()]
            );
        }

        return $result;
    }

    protected function evaluateDelete(BulkDeleteRequest $request, User $actor, bool $execute, ?AuditLogger $auditLogger = null): BulkDeleteResult
    {
        $baseQuery = School::query();
        $ids = $this->resolveTargetIds($request, $baseQuery);
        $schools = School::query()->whereIn('id', $ids)->get();

        $result = new BulkDeleteResult;

        if ($execute) {
            $schoolMap = $schools->keyBy('id');

            $this->processChunks($ids, function (array $chunk) use ($schoolMap, $result): void {
                foreach ($chunk as $id) {
                    $school = $schoolMap->get($id);
                    if ($school) {
                        $school->delete();
                        $result->addDeleted($school->id);
                    }
                }
            });
        } else {
            foreach ($schools as $school) {
                $hasPiagam = Piagam::withTrashed()->where('school_id', $school->id)->exists();
                if ($hasPiagam) {
                    // Informative note in skipped/warning or deletable with piagam notice
                    // In soft delete, it IS deletable
                    $result->addDeleted($school->id);
                } else {
                    $result->addDeleted($school->id);
                }
            }
        }

        return $result;
    }

    protected function evaluateRestore(BulkDeleteRequest $request, User $actor, bool $execute, ?AuditLogger $auditLogger = null): BulkDeleteResult
    {
        $baseQuery = School::onlyTrashed();
        $ids = $this->resolveTargetIds($request, $baseQuery);
        $trashedSchools = School::onlyTrashed()->whereIn('id', $ids)->get();

        $result = new BulkDeleteResult;
        $restorable = [];

        foreach ($trashedSchools as $school) {
            // Check if active school with same NPSN exists
            $conflict = School::query()
                ->where('npsn', $school->npsn)
                ->where('id', '!=', $school->id)
                ->exists();

            if ($conflict) {
                $result->addSkipped($school->id, $school->name, "Terdapat sekolah aktif lain dengan NPSN {$school->npsn}.");

                continue;
            }

            $restorable[] = $school;
        }

        if ($execute) {
            $restorableIds = array_map(fn ($s) => $s->id, $restorable);
            $schoolMap = collect($restorable)->keyBy('id');

            $this->processChunks($restorableIds, function (array $chunk) use ($schoolMap, $result): void {
                foreach ($chunk as $id) {
                    $school = $schoolMap->get($id);
                    if ($school) {
                        $school->restore();
                        $result->addDeleted($school->id);
                    }
                }
            });
        } else {
            foreach ($restorable as $s) {
                $result->addDeleted($s->id);
            }
        }

        return $result;
    }
}
