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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function downloadTemplate(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $filename = 'template_import_sekolah_sisurat.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['npsn', 'nama_sekolah', 'status_aktif']);
            fputcsv($output, ['20320340', 'SMP Negeri 1 Ungaran', '1']);
            fputcsv($output, ['20320341', 'SMP Negeri 2 Ungaran', '1']);
            fputcsv($output, ['20320678', 'SD Negeri Bandungan 01', '1']);
            fputcsv($output, ['20320700', 'SD Negeri Ungaran 01', '1']);

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importCsv(Request $request, AuditLogger $auditLogger): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Berkas CSV kosong.'], 422);
            }

            return back()->with('error', 'Berkas CSV kosong.');
        }

        // Detect delimiter: semicolon vs comma
        $firstLine = strtok($content, "\r\n");
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal membaca berkas CSV.'], 422);
            }

            return back()->with('error', 'Gagal membaca berkas CSV.');
        }

        // Read header
        $header = fgetcsv($handle, 0, $delimiter);
        if (! $header) {
            fclose($handle);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Header berkas CSV tidak valid.'], 422);
            }

            return back()->with('error', 'Header berkas CSV tidak valid.');
        }

        // Clean BOM from first header column if present
        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', (string) $header[0]);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $npsnIdx = array_search('npsn', $header, true);
        $nameIdx = array_search('nama_sekolah', $header, true);
        if ($nameIdx === false) {
            $nameIdx = array_search('nama', $header, true);
        }
        $statusIdx = array_search('status_aktif', $header, true);
        if ($statusIdx === false) {
            $statusIdx = array_search('status', $header, true);
        }

        if ($npsnIdx === false || $nameIdx === false) {
            fclose($handle);
            $msg = 'Format header CSV harus memuat kolom "npsn" dan "nama_sekolah". Silakan gunakan template resmi.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $createdCount = 0;
        $updatedCount = 0;
        $restoredCount = 0;
        $skippedCount = 0;
        $rowNumber = 1;
        $errors = [];

        DB::transaction(function () use ($handle, $delimiter, $npsnIdx, $nameIdx, $statusIdx, &$createdCount, &$updatedCount, &$restoredCount, &$skippedCount, &$rowNumber, &$errors): void {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                // Skip completely empty rows
                if (! array_filter($row)) {
                    continue;
                }

                $rawNpsn = isset($row[$npsnIdx]) ? trim((string) $row[$npsnIdx]) : '';
                $rawName = isset($row[$nameIdx]) ? trim((string) $row[$nameIdx]) : '';
                $rawStatus = isset($row[$statusIdx]) ? trim((string) $row[$statusIdx]) : '1';

                $cleanNpsn = preg_replace('/\D+/', '', $rawNpsn);
                if (strlen($cleanNpsn) === 7) {
                    $cleanNpsn = str_pad($cleanNpsn, 8, '0', STR_PAD_LEFT);
                }

                if (strlen($cleanNpsn) !== 8) {
                    $errors[] = "Baris {$rowNumber}: NPSN '{$rawNpsn}' tidak valid (harus 8 digit angka).";
                    $skippedCount++;

                    continue;
                }

                if ($rawName === '') {
                    $errors[] = "Baris {$rowNumber}: Nama sekolah pada NPSN '{$cleanNpsn}' tidak boleh kosong.";
                    $skippedCount++;

                    continue;
                }

                $isActive = in_array(strtolower($rawStatus), ['1', 'true', 'aktif', 'active', 'ya'], true);

                $existingTrashed = School::onlyTrashed()->where('npsn', $cleanNpsn)->first();
                if ($existingTrashed) {
                    $existingTrashed->restore();
                    $existingTrashed->update([
                        'name' => $rawName,
                        'is_active' => $isActive,
                    ]);
                    $restoredCount++;

                    continue;
                }

                $existing = School::where('npsn', $cleanNpsn)->first();
                if ($existing) {
                    $existing->update([
                        'name' => $rawName,
                        'is_active' => $isActive,
                    ]);
                    $updatedCount++;
                } else {
                    School::create([
                        'npsn' => $cleanNpsn,
                        'name' => $rawName,
                        'is_active' => $isActive,
                    ]);
                    $createdCount++;
                }
            }
        });

        fclose($handle);

        $totalProcessed = $createdCount + $updatedCount + $restoredCount;

        $actor = $request->user();
        if ($actor) {
            $auditLogger->log(
                'SCHOOLS_CSV_IMPORTED',
                $actor,
                'Super Administrator mengimpor data sekolah via CSV.',
                null,
                [
                    'total_processed' => $totalProcessed,
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                    'restored' => $restoredCount,
                    'skipped' => $skippedCount,
                    'error_count' => count($errors),
                ]
            );
        }

        $summaryMsg = "Import CSV selesai: {$createdCount} sekolah baru ditambahkan, {$updatedCount} diperbarui, {$restoredCount} dipulihkan dari sampah.";
        if ($skippedCount > 0) {
            $summaryMsg .= " ({$skippedCount} baris dilewati karena format tidak sesuai).";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $summaryMsg,
                'data' => [
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                    'restored' => $restoredCount,
                    'skipped' => $skippedCount,
                    'errors' => array_slice($errors, 0, 10),
                ],
            ]);
        }

        if ($errors !== [] && $totalProcessed === 0) {
            return back()->with('error', $summaryMsg.' Rincian: '.implode(' ', array_slice($errors, 0, 5)));
        }

        return back()->with('success', $summaryMsg);
    }
}
