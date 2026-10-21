<?php

namespace App\Services\Bulk;

use App\Http\Requests\BulkDeleteRequest;
use App\Models\CompetitionCategory;
use App\Models\PickupCategory;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;

class CategoryBulkService extends BulkDeleteService
{
    public function previewDeleteCompetition(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateCategoryAction($request, $actor, CompetitionCategory::query(), false, 'delete', 'lomba');
    }

    public function bulkDeleteCompetition(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateCategoryAction($request, $actor, CompetitionCategory::query(), true, 'delete', 'lomba', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'COMPETITION_CATEGORY_BULK_DELETED',
                $actor,
                CompetitionCategory::class,
                $result->deletedCount(),
                "Menghapus massal {$result->deletedCount()} kategori lomba (soft delete).",
                ['deleted' => $result->deleted]
            );
        }

        return $result;
    }

    public function previewRestoreCompetition(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateCategoryAction($request, $actor, CompetitionCategory::onlyTrashed(), false, 'restore', 'lomba');
    }

    public function bulkRestoreCompetition(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateCategoryAction($request, $actor, CompetitionCategory::onlyTrashed(), true, 'restore', 'lomba', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'COMPETITION_CATEGORY_BULK_RESTORED',
                $actor,
                CompetitionCategory::class,
                $result->deletedCount(),
                "Memulihkan massal {$result->deletedCount()} kategori lomba dari arsip.",
                ['restored' => $result->deleted]
            );
        }

        return $result;
    }

    public function previewDeletePickup(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateCategoryAction($request, $actor, PickupCategory::query(), false, 'delete', 'pengambilan');
    }

    public function bulkDeletePickup(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateCategoryAction($request, $actor, PickupCategory::query(), true, 'delete', 'pengambilan', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'PICKUP_CATEGORY_BULK_DELETED',
                $actor,
                PickupCategory::class,
                $result->deletedCount(),
                "Menghapus massal {$result->deletedCount()} kategori pengambilan (soft delete).",
                ['deleted' => $result->deleted]
            );
        }

        return $result;
    }

    public function previewRestorePickup(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateCategoryAction($request, $actor, PickupCategory::onlyTrashed(), false, 'restore', 'pengambilan');
    }

    public function bulkRestorePickup(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateCategoryAction($request, $actor, PickupCategory::onlyTrashed(), true, 'restore', 'pengambilan', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'PICKUP_CATEGORY_BULK_RESTORED',
                $actor,
                PickupCategory::class,
                $result->deletedCount(),
                "Memulihkan massal {$result->deletedCount()} kategori pengambilan dari arsip.",
                ['restored' => $result->deleted]
            );
        }

        return $result;
    }

    public function previewDeleteWorkUnit(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        abort_unless($actor->role === 'super_admin', 403, 'Pengelolaan Unit Kerja Dinas hanya diizinkan untuk Super Admin.');

        return $this->evaluateWorkUnitAction($request, $actor, WorkUnit::query(), false, 'delete');
    }

    public function bulkDeleteWorkUnit(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        abort_unless($actor->role === 'super_admin', 403, 'Pengelolaan Unit Kerja Dinas hanya diizinkan untuk Super Admin.');

        $result = $this->evaluateWorkUnitAction($request, $actor, WorkUnit::query(), true, 'delete', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'WORK_UNIT_BULK_DELETED',
                $actor,
                WorkUnit::class,
                $result->deletedCount(),
                "Menghapus massal {$result->deletedCount()} unit kerja (soft delete).",
                ['deleted' => $result->deleted]
            );
        }

        return $result;
    }

    public function previewRestoreWorkUnit(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        abort_unless($actor->role === 'super_admin', 403, 'Pengelolaan Unit Kerja Dinas hanya diizinkan untuk Super Admin.');

        return $this->evaluateWorkUnitAction($request, $actor, WorkUnit::onlyTrashed(), false, 'restore');
    }

    public function bulkRestoreWorkUnit(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        abort_unless($actor->role === 'super_admin', 403, 'Pengelolaan Unit Kerja Dinas hanya diizinkan untuk Super Admin.');

        $result = $this->evaluateWorkUnitAction($request, $actor, WorkUnit::onlyTrashed(), true, 'restore', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'WORK_UNIT_BULK_RESTORED',
                $actor,
                WorkUnit::class,
                $result->deletedCount(),
                "Memulihkan massal {$result->deletedCount()} unit kerja dari arsip.",
                ['restored' => $result->deleted]
            );
        }

        return $result;
    }

    protected function evaluateCategoryAction(
        BulkDeleteRequest $request,
        User $actor,
        Builder $baseQuery,
        bool $execute,
        string $action,
        string $typeLabel,
        ?AuditLogger $auditLogger = null
    ): BulkDeleteResult {
        abort_unless(in_array($actor->role, ['super_admin', 'division_admin'], true), 403);

        if ($actor->role !== 'super_admin') {
            $baseQuery->where('division_id', $actor->division_id);
        }

        $ids = $this->resolveTargetIds($request, $baseQuery);

        if ($actor->role !== 'super_admin') {
            $modelClass = $baseQuery->getModel()::class;
            $foreignExists = $modelClass::withTrashed()
                ->whereIn('id', $ids)
                ->where('division_id', '!=', $actor->division_id)
                ->exists();

            if ($foreignExists) {
                abort(403, 'Anda tidak memiliki izin untuk mengelola kategori divisi lain.');
            }
        }

        $categories = $baseQuery->whereIn('id', $ids)->get();
        $result = new BulkDeleteResult;

        if ($execute) {
            $catMap = $categories->keyBy('id');

            $this->processChunks($ids, function (array $chunk) use ($catMap, $action, $result): void {
                foreach ($chunk as $id) {
                    $cat = $catMap->get($id);
                    if ($cat) {
                        if ($action === 'delete') {
                            $cat->delete();
                        } else {
                            $cat->restore();
                        }
                        $result->addDeleted($cat->id);
                    }
                }
            });
        } else {
            foreach ($categories as $cat) {
                $result->addDeleted($cat->id);
            }
        }

        return $result;
    }

    protected function evaluateWorkUnitAction(
        BulkDeleteRequest $request,
        User $actor,
        Builder $baseQuery,
        bool $execute,
        string $action,
        ?AuditLogger $auditLogger = null
    ): BulkDeleteResult {
        $ids = $this->resolveTargetIds($request, $baseQuery);
        $units = $baseQuery->whereIn('id', $ids)->get();
        $result = new BulkDeleteResult;

        if ($execute) {
            $unitMap = $units->keyBy('id');

            $this->processChunks($ids, function (array $chunk) use ($unitMap, $action, $result): void {
                foreach ($chunk as $id) {
                    $unit = $unitMap->get($id);
                    if ($unit) {
                        if ($action === 'delete') {
                            $unit->delete();
                        } else {
                            $unit->restore();
                        }
                        $result->addDeleted($unit->id);
                    }
                }
            });
        } else {
            foreach ($units as $u) {
                $result->addDeleted($u->id);
            }
        }

        return $result;
    }
}
