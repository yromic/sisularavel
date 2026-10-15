<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Piagam;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function store(Request $request): Response
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

        if (! $request->expectsJson()) {
            return back()->with('success', "Sekolah {$school->name} berhasil ditambahkan.");
        }

        return response()->json(['data' => $school], 201);
    }

    public function update(Request $request, School $school): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $validated = $request->validate([
            'npsn' => ['required', 'digits:8', 'unique:schools,npsn,'.$school->id],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $school->update([
            'npsn' => preg_replace('/\D+/', '', $validated['npsn']),
            'name' => trim($validated['name']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Data sekolah {$school->name} berhasil diperbarui.");
        }

        return response()->json(['data' => $school]);
    }

    public function destroy(Request $request, School $school): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $hasPiagam = Piagam::where('school_id', $school->id)->exists();
        if ($hasPiagam) {
            $school->update(['is_active' => false]);
            if (! $request->expectsJson()) {
                return back()->with('error', "Sekolah {$school->name} memiliki data piagam terkait, status dialihkan menjadi Nonaktif.");
            }

            return response()->json(['success' => false, 'message' => 'Sekolah memiliki data piagam terkait.'], 422);
        }

        $school->delete();

        if (! $request->expectsJson()) {
            return redirect('/master/sekolah')->with('success', "Sekolah {$school->name} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
    }
}
