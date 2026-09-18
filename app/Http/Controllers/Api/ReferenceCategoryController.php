<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompetitionCategory;
use App\Models\PickupCategory;
use App\Services\DivisionContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceCategoryController extends Controller
{
    public function storePickup(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        return $this->storeCategory($request, $divisionContext, new PickupCategory);
    }

    public function storeCompetition(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        return $this->storeCategory($request, $divisionContext, new CompetitionCategory);
    }

    private function storeCategory(Request $request, DivisionContext $divisionContext, Model $model): JsonResponse
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

        return response()->json(['data' => $category], 201);
    }
}
