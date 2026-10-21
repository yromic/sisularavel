<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Models\Piagam;
use App\Models\School;
use App\Services\AuditLogger;
use App\Services\Bulk\SchoolBulkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

        $rawNpsn = preg_replace('/\D+/', '', (string) $request->input('npsn'));
        if ($rawNpsn !== '') {
            $existingTrashed = School::onlyTrashed()->where('npsn', $rawNpsn)->first();
            if ($existingTrashed) {
                throw ValidationException::withMessages([
                    'npsn' => ["Sekolah dengan NPSN {$existingTrashed->npsn} ({$existingTrashed->name}) sudah ada di Tempat Sampah. Silakan pulihkan data dari Arsip Terhapus."],
                ]);
            }
        }

        $validated = $request->validate([
            'npsn' => ['required', 'digits:8', 'unique:schools,npsn'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $school = School::query()->create([
            'npsn' => $rawNpsn,
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

        $rawNpsn = preg_replace('/\D+/', '', (string) $request->input('npsn'));
        if ($rawNpsn !== '' && $rawNpsn !== $school->npsn) {
            $existingTrashed = School::onlyTrashed()->where('npsn', $rawNpsn)->first();
            if ($existingTrashed) {
                throw ValidationException::withMessages([
                    'npsn' => ["Sekolah dengan NPSN {$existingTrashed->npsn} ({$existingTrashed->name}) sudah ada di Tempat Sampah. Silakan pulihkan data dari Arsip Terhapus."],
                ]);
            }
        }

        $validated = $request->validate([
            'npsn' => ['required', 'digits:8', 'unique:schools,npsn,'.$school->id],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $school->update([
            'npsn' => $rawNpsn,
            'name' => trim($validated['name']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Data sekolah {$school->name} berhasil diperbarui.");
        }

        return response()->json(['data' => $school]);
    }

    public function destroy(Request $request, School $school, AuditLogger $auditLogger): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $hasPiagam = Piagam::withTrashed()->where('school_id', $school->id)->exists();
        if ($hasPiagam) {
            $school->update(['is_active' => false]);
            if (! $request->expectsJson()) {
                return back()->with('error', "Sekolah {$school->name} memiliki data piagam terkait, status dialihkan menjadi Nonaktif.");
            }

            return response()->json(['success' => false, 'message' => 'Sekolah memiliki data piagam terkait.'], 422);
        }

        $school->delete();

        $auditLogger->log(
            'SCHOOL_DELETED',
            $request->user(),
            "Menghapus data sekolah {$school->name} (soft delete).",
            $school
        );

        if (! $request->expectsJson()) {
            return redirect('/master/sekolah')->with('success', "Sekolah {$school->name} berhasil dihapus.");
        }

        return response()->json(['success' => true]);
    }

    public function restore(Request $request, int $id, AuditLogger $auditLogger): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $school = School::onlyTrashed()->findOrFail($id);

        $conflict = School::query()
            ->where('npsn', $school->npsn)
            ->where('id', '!=', $school->id)
            ->exists();

        if ($conflict) {
            if (! $request->expectsJson()) {
                return back()->with('error', "Gagal memulihkan: NPSN {$school->npsn} sudah digunakan oleh sekolah aktif lain.");
            }

            return response()->json([
                'success' => false,
                'message' => "Gagal memulihkan: NPSN {$school->npsn} sudah digunakan oleh sekolah aktif lain.",
            ], 422);
        }

        $school->restore();

        $auditLogger->log(
            'SCHOOL_RESTORED',
            $request->user(),
            "Memulihkan data sekolah {$school->name} dari Tempat Sampah.",
            $school
        );

        if (! $request->expectsJson()) {
            return back()->with('success', "Sekolah {$school->name} berhasil dipulihkan.");
        }

        return response()->json(['success' => true, 'data' => $school]);
    }

    public function bulkPreview(BulkDeleteRequest $request, SchoolBulkService $bulkService): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor?->role === 'super_admin', 403);

        $isRestore = $request->isRestore();
        $result = $isRestore
            ? $bulkService->previewRestore($request, $actor)
            : $bulkService->previewDelete($request, $actor);

        return response()->json($result->toArray());
    }

    public function bulkDestroy(BulkDeleteRequest $request, SchoolBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor?->role === 'super_admin', 403);

        $result = $bulkService->bulkDelete($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }

    public function bulkRestore(BulkDeleteRequest $request, SchoolBulkService $bulkService, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor?->role === 'super_admin', 403);

        $result = $bulkService->bulkRestore($request, $actor, $auditLogger);

        return response()->json($result->toArray());
    }
}
