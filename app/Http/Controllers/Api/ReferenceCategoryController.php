<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Models\CompetitionCategory;
use App\Models\PickupCategory;
use App\Models\WorkUnit;
use App\Services\AuditLogger;
use App\Services\Bulk\CategoryBulkService;
use App\Services\DivisionContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReferenceCategoryController extends Controller
{
    public function storePickup(Request $request, DivisionContext $divisionContext): Response
    {
        return $this->storeCategory($request, $divisionContext, new PickupCategory);
    }

    public function storeCompetition(Request $request, DivisionContext $divisionContext): Response
    {
        return $this->storeCategory($request, $divisionContext, new CompetitionCategory);
    }

    public function updatePickup(Request $request, PickupCategory $category): Response
    {
        return $this->updateCategory($request, $category);
    }

    public function updateCompetition(Request $request, CompetitionCategory $category): Response
    {
        return $this->updateCategory($request, $category);
    }

    public function destroyPickup(Request $request, PickupCategory $category, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);
        if ($user->role !== 'super_admin' && $category->division_id !== $user->division_id) {
            abort(403);
        }

        $category->delete();

        $auditLogger->log(
            'PICKUP_CATEGORY_DELETED',
            $user,
            "Menghapus kategori pengambilan {$category->name} (soft delete).",
            $category
        );

        if (! $request->expectsJson()) {
            return redirect('/master/kategori')->with('success', "Kategori pengambilan {$category->name} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
    }

    public function restorePickup(Request $request, int $id, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);

        $category = PickupCategory::onlyTrashed()->findOrFail($id);
        if ($user->role !== 'super_admin' && $category->division_id !== $user->division_id) {
            abort(403);
        }

        $category->restore();

        $auditLogger->log(
            'PICKUP_CATEGORY_RESTORED',
            $user,
            "Memulihkan kategori pengambilan {$category->name} dari arsip.",
            $category
        );

        if (! $request->expectsJson()) {
            return back()->with('success', "Kategori pengambilan {$category->name} berhasil dipulihkan.");
        }

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function destroyCompetition(Request $request, CompetitionCategory $category, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);
        if ($user->role !== 'super_admin' && $category->division_id !== $user->division_id) {
            abort(403);
        }

        $category->delete();

        $auditLogger->log(
            'COMPETITION_CATEGORY_DELETED',
            $user,
            "Menghapus kategori lomba {$category->name} (soft delete).",
            $category
        );

        if (! $request->expectsJson()) {
            return redirect('/master/kategori')->with('success', "Kategori lomba {$category->name} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
    }

    public function restoreCompetition(Request $request, int $id, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);

        $category = CompetitionCategory::onlyTrashed()->findOrFail($id);
        if ($user->role !== 'super_admin' && $category->division_id !== $user->division_id) {
            abort(403);
        }

        $category->restore();

        $auditLogger->log(
            'COMPETITION_CATEGORY_RESTORED',
            $user,
            "Memulihkan kategori lomba {$category->name} dari arsip.",
            $category
        );

        if (! $request->expectsJson()) {
            return back()->with('success', "Kategori lomba {$category->name} berhasil dipulihkan.");
        }

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function storeWorkUnit(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->role === 'super_admin', 403, 'Pengelolaan Unit Kerja Dinas hanya diizinkan untuk Super Admin.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $workUnit = WorkUnit::query()->create([
            'name' => trim($validated['name']),
            'code' => isset($validated['code']) ? trim($validated['code']) : null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Unit kerja {$workUnit->name} berhasil ditambahkan.");
        }

        return response()->json(['data' => $workUnit], 201);
    }

    public function updateWorkUnit(Request $request, WorkUnit $workUnit): Response
    {
        $user = $request->user();
        abort_unless($user?->role === 'super_admin', 403, 'Pengelolaan Unit Kerja Dinas hanya diizinkan untuk Super Admin.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $workUnit->update([
            'name' => trim($validated['name']),
            'code' => isset($validated['code']) ? trim($validated['code']) : $workUnit->code,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Unit kerja {$workUnit->name} berhasil diperbarui.");
        }

        return response()->json(['data' => $workUnit]);
    }

    public function destroyWorkUnit(Request $request, WorkUnit $workUnit, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        abort_unless($user?->role === 'super_admin', 403, 'Pengelolaan Unit Kerja Dinas hanya diizinkan untuk Super Admin.');

        $workUnit->delete();

        $auditLogger->log(
            'WORK_UNIT_DELETED',
            $user,
            "Menghapus unit kerja {$workUnit->name} (soft delete).",
            $workUnit
        );

        if (! $request->expectsJson()) {
            return redirect('/master/kategori')->with('success', "Unit kerja {$workUnit->name} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
    }

    public function restoreWorkUnit(Request $request, int $id, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        abort_unless($user?->role === 'super_admin', 403, 'Pengelolaan Unit Kerja Dinas hanya diizinkan untuk Super Admin.');

        $workUnit = WorkUnit::onlyTrashed()->findOrFail($id);
        $workUnit->restore();

        $auditLogger->log(
            'WORK_UNIT_RESTORED',
            $user,
            "Memulihkan unit kerja {$workUnit->name} dari arsip.",
            $workUnit
        );

        if (! $request->expectsJson()) {
            return back()->with('success', "Unit kerja {$workUnit->name} berhasil dipulihkan.");
        }

        return response()->json(['success' => true, 'data' => $workUnit]);
    }

    // Bulk operations for Competition Categories
    public function bulkPreviewCompetition(BulkDeleteRequest $request, CategoryBulkService $bulkService): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $isRestore = $request->isRestore();
        $result = $isRestore
            ? $bulkService->previewRestoreCompetition($request, $actor)
            : $bulkService->previewDeleteCompetition($request, $actor);

        return response()->json($result->toArray());
    }

    public function bulkDestroyCompetition(BulkDeleteRequest $request, CategoryBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $result = $bulkService->bulkDeleteCompetition($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }

    public function bulkRestoreCompetition(BulkDeleteRequest $request, CategoryBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $result = $bulkService->bulkRestoreCompetition($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }

    // Bulk operations for Pickup Categories
    public function bulkPreviewPickup(BulkDeleteRequest $request, CategoryBulkService $bulkService): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $isRestore = $request->isRestore();
        $result = $isRestore
            ? $bulkService->previewRestorePickup($request, $actor)
            : $bulkService->previewDeletePickup($request, $actor);

        return response()->json($result->toArray());
    }

    public function bulkDestroyPickup(BulkDeleteRequest $request, CategoryBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $result = $bulkService->bulkDeletePickup($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }

    public function bulkRestorePickup(BulkDeleteRequest $request, CategoryBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor?->role, ['super_admin', 'division_admin'], true), 403);

        $result = $bulkService->bulkRestorePickup($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }

    // Bulk operations for Work Units
    public function bulkPreviewWorkUnit(BulkDeleteRequest $request, CategoryBulkService $bulkService): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor?->role === 'super_admin', 403);

        $isRestore = $request->isRestore();
        $result = $isRestore
            ? $bulkService->previewRestoreWorkUnit($request, $actor)
            : $bulkService->previewDeleteWorkUnit($request, $actor);

        return response()->json($result->toArray());
    }

    public function bulkDestroyWorkUnit(BulkDeleteRequest $request, CategoryBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor?->role === 'super_admin', 403);

        $result = $bulkService->bulkDeleteWorkUnit($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }

    public function bulkRestoreWorkUnit(BulkDeleteRequest $request, CategoryBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor?->role === 'super_admin', 403);

        $result = $bulkService->bulkRestoreWorkUnit($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }

    private function storeCategory(Request $request, DivisionContext $divisionContext, Model $model): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $divisionId = $validated['division_id'] ?? $divisionContext->activeDivisionId($request, $user);

        if ($user->role !== 'super_admin' && $divisionId !== $user->division_id) {
            abort(403);
        }

        abort_if($divisionId === null, 422, 'division_id wajib diisi.');

        $category = $model->newQuery()->create([
            'division_id' => $divisionId,
            'name' => trim($validated['name']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Kategori {$category->name} berhasil ditambahkan.");
        }

        return response()->json(['data' => $category], 201);
    }

    private function updateCategory(Request $request, Model $category): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);
        if ($user->role !== 'super_admin' && $category->division_id !== $user->division_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update([
            'name' => trim($validated['name']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Kategori {$category->name} berhasil diperbarui.");
        }

        return response()->json(['data' => $category]);
    }
}
