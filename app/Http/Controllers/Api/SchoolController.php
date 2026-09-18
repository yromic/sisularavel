<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $schools = School::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('npsn', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'npsn', 'name', 'is_active']);

        return response()->json(['data' => $schools]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $validated = $request->validate([
            'npsn' => ['required', 'digits:8', 'unique:schools,npsn'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $school = School::query()->create([
            'npsn' => preg_replace('/\D+/', '', $validated['npsn']),
            'name' => trim($validated['name']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $school], 201);
    }
}
