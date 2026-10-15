<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompetitionCategory;
use App\Models\PickupCategory;
use App\Models\WorkUnit;
use App\Services\DivisionContext;
use Illuminate\Database\Eloquent\Model;
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

    public function destroyPickup(Request $request, PickupCategory $category): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);
        if ($user->role !== 'super_admin' && $category->division_id !== $user->division_id) {
            abort(403);
        }

        $category->delete();

        if (! $request->expectsJson()) {
            return redirect('/master/kategori')->with('success', "Kategori pengambilan {$category->name} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
    }

    public function destroyCompetition(Request $request, CompetitionCategory $category): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);
        if ($user->role !== 'super_admin' && $category->division_id !== $user->division_id) {
            abort(403);
        }

        $category->delete();

        if (! $request->expectsJson()) {
            return redirect('/master/kategori')->with('success', "Kategori lomba {$category->name} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
    }

    public function storeWorkUnit(Request $request): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);

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
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);

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

    public function destroyWorkUnit(Request $request, WorkUnit $workUnit): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403);

        $workUnit->delete();

        if (! $request->expectsJson()) {
            return redirect('/master/kategori')->with('success', "Unit kerja {$workUnit->name} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
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
