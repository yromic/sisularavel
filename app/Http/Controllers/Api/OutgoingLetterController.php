<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOutgoingLetterRequest;
use App\Models\OutgoingLetter;
use App\Services\AttachmentStorageService;
use App\Services\AuditLogger;
use App\Services\DivisionContext;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OutgoingLetterController extends Controller
{
    public function index(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        $activeDivisionId = $divisionContext->activeDivisionId($request, $request->user());
        $perPage = max(1, min(100, $request->integer('per_page', 25)));

        $letters = OutgoingLetter::query()
            ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
            ->latest('id')
            ->paginate($perPage, ['id', 'uuid', 'division_id', 'letter_year', 'letter_number', 'letter_date', 'disseminated_date', 'subject']);

        return response()->json($letters);
    }

    public function show(Request $request, OutgoingLetter $outgoingLetter): JsonResponse
    {
        if (! $this->canView($request, $outgoingLetter)) {
            return $this->forbiddenDivisionResponse($request);
        }

        $outgoingLetter->loadMissing(['division', 'createdBy', 'attachments']);

        return response()->json([
            'success' => true,
            'data' => $this->resource($outgoingLetter, $request),
        ]);
    }

    public function store(StoreOutgoingLetterRequest $request, DivisionContext $divisionContext, AuditLogger $auditLogger, AttachmentStorageService $attachments): Response
    {
        $user = $request->user();
        $divisionId = $divisionContext->activeDivisionId($request, $user);

        if ($divisionId === null && $user->role === 'super_admin' && $request->filled('division_id')) {
            $divisionId = (int) $request->input('division_id');
        }

        if ($divisionId === null) {
            throw new HttpResponseException(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['division_id' => ['Super Admin wajib memilih divisi pengarsip saat dalam mode Semua Divisi.']],
            ], 422));
        }

        $letterYear = (int) $request->date('tanggal_surat')->format('Y');
        if ($this->numberExists($divisionId, $letterYear, $request->string('nomor_surat')->toString())) {
            return $this->duplicateNumberResponse($request);
        }

        $storedPath = null;
        try {
            $letter = DB::transaction(function () use ($request, $divisionId, $letterYear, $user, $auditLogger, $attachments, &$storedPath): OutgoingLetter {
                $letter = OutgoingLetter::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $divisionId,
                    'letter_year' => $letterYear,
                    'letter_number' => $request->string('nomor_surat')->toString(),
                    'letter_date' => $request->date('tanggal_surat')->toDateString(),
                    'disseminated_date' => $request->date('tanggal_share')->toDateString(),
                    'subject' => $request->string('perihal')->toString(),
                    'created_by_user_id' => $user->id,
                ]);

                $auditLogger->outgoingLetterEvent('LETTER_OUTGOING_CREATED', $user, $letter, 'Outgoing letter created.', $this->auditValues($letter));

                if ($request->hasFile('photos') && count($request->file('photos')) > 0) {
                    $attachment = $attachments->storeMergedPhotosAsPdf($request->file('photos'), $letter, $letter->division, $user, $letter->letter_number);
                    $storedPath = $attachment->file_path;
                    $letter->setRelation('attachments', collect([$attachment]));
                } elseif ($request->hasFile('attachment')) {
                    $attachment = $attachments->storeForModel($request->file('attachment'), $letter, $letter->division, $user);
                    $storedPath = $attachment->file_path;
                    $letter->setRelation('attachments', collect([$attachment]));
                }

                return $letter;
            });
        } catch (\Throwable $exception) {
            $attachments->deleteStored($storedPath);

            throw $exception;
        }

        if (! $request->expectsJson()) {
            return redirect('/surat-keluar')->with('success', 'Surat keluar berhasil disimpan.');
        }

        return response()->json(['success' => true, 'data' => $this->resource($letter)], 201);
    }

    public function update(StoreOutgoingLetterRequest $request, OutgoingLetter $outgoingLetter, AuditLogger $auditLogger, AttachmentStorageService $attachments): Response
    {
        $user = $request->user();
        if (! in_array($user->role, ['super_admin', 'division_admin'], true)) {
            return $this->forbiddenRoleResponse($request);
        }

        if (! $this->canManage($request, $outgoingLetter)) {
            return $this->forbiddenDivisionResponse($request);
        }

        $letterYear = (int) $request->date('tanggal_surat')->format('Y');
        $letterNumber = $request->string('nomor_surat')->toString();
        if ($this->numberExists($outgoingLetter->division_id, $letterYear, $letterNumber, $outgoingLetter->id)) {
            return $this->duplicateNumberResponse($request);
        }

        $storedPath = null;
        try {
            $letter = DB::transaction(function () use ($request, $outgoingLetter, $letterYear, $letterNumber, $auditLogger, $attachments, &$storedPath): OutgoingLetter {
                $outgoingLetter->forceFill([
                    'letter_year' => $letterYear,
                    'letter_number' => $letterNumber,
                    'letter_date' => $request->date('tanggal_surat')->toDateString(),
                    'disseminated_date' => $request->date('tanggal_share')->toDateString(),
                    'subject' => $request->string('perihal')->toString(),
                    'updated_by_user_id' => $request->user()->id,
                ])->save();

                $auditLogger->outgoingLetterEvent('LETTER_OUTGOING_UPDATED', $request->user(), $outgoingLetter, 'Outgoing letter metadata updated.', $this->auditValues($outgoingLetter));

                if ($request->hasFile('attachment')) {
                    $attachment = $attachments->storeForModel($request->file('attachment'), $outgoingLetter, $outgoingLetter->division, $request->user());
                    $storedPath = $attachment->file_path;
                    $outgoingLetter->setRelation('attachments', collect([$attachment]));
                }

                return $outgoingLetter;
            });
        } catch (\Throwable $exception) {
            $attachments->deleteStored($storedPath);

            throw $exception;
        }

        if (! $request->expectsJson()) {
            return redirect('/surat-keluar/'.$outgoingLetter->id)->with('success', 'Surat keluar berhasil diperbarui.');
        }

        return response()->json(['success' => true, 'data' => $this->resource($letter)]);
    }

    public function destroy(Request $request, OutgoingLetter $outgoingLetter, AuditLogger $auditLogger): Response
    {
        if (! $this->canManage($request, $outgoingLetter)) {
            return $this->forbiddenDivisionResponse($request);
        }

        DB::transaction(function () use ($request, $outgoingLetter, $auditLogger): void {
            $outgoingLetter->forceFill(['deleted_by_user_id' => $request->user()->id])->save();
            $outgoingLetter->delete();

            $auditLogger->outgoingLetterEvent('LETTER_OUTGOING_DELETED', $request->user(), $outgoingLetter, 'Outgoing letter soft deleted.');
        });

        if (! $request->expectsJson()) {
            return redirect('/surat-keluar')->with('success', 'Surat keluar berhasil dihapus.');
        }

        return response()->json(['success' => true, 'data' => ['id' => $outgoingLetter->id, 'deleted' => true]]);
    }

    public function restore(Request $request, int $outgoingLetter, AuditLogger $auditLogger): Response
    {
        $letter = OutgoingLetter::withTrashed()->findOrFail($outgoingLetter);

        if (! $this->canManage($request, $letter)) {
            return $this->forbiddenDivisionResponse($request);
        }

        DB::transaction(function () use ($request, $letter, $auditLogger): void {
            $letter->restore();
            $letter->forceFill(['deleted_by_user_id' => null])->save();

            $auditLogger->outgoingLetterEvent('LETTER_OUTGOING_RESTORED', $request->user(), $letter, 'Outgoing letter restored.');
        });

        if (! $request->expectsJson()) {
            return redirect()->back()->with('success', 'Arsip surat keluar berhasil dipulihkan.');
        }

        return response()->json(['success' => true, 'data' => ['id' => $letter->id, 'deleted' => false]]);
    }

    private function numberExists(int $divisionId, int $letterYear, string $letterNumber, ?int $exceptId = null): bool
    {
        return OutgoingLetter::query()
            ->where('division_id', $divisionId)
            ->where('letter_year', $letterYear)
            ->where('letter_number', $letterNumber)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    private function duplicateNumberResponse(?Request $request = null): Response
    {
        if ($request && ! $request->expectsJson()) {
            return back()->withInput()->withErrors([
                'nomor_surat' => 'Nomor surat keluar sudah terdaftar pada divisi dan tahun ini.',
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => 'ERR_409_DUPLICATE_NUMBER',
            'message' => 'Nomor surat keluar sudah terdaftar pada divisi dan tahun ini',
        ], 409);
    }

    private function canView(Request $request, OutgoingLetter $outgoingLetter): bool
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->division_id === $outgoingLetter->division_id;
    }

    private function canManage(Request $request, OutgoingLetter $outgoingLetter): bool
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'division_admin' && $user->division_id === $outgoingLetter->division_id;
    }

    private function forbiddenRoleResponse(?Request $request = null): Response
    {
        if ($request && ! $request->expectsJson()) {
            abort(403, 'Operator tidak memiliki wewenang untuk mengubah arsip surat.');
        }

        return response()->json([
            'success' => false,
            'code' => 'ERR_403_ROLE',
            'message' => 'Operator tidak memiliki wewenang untuk mengubah arsip surat.',
        ], 403);
    }

    private function forbiddenDivisionResponse(?Request $request = null): Response
    {
        if ($request && ! $request->expectsJson()) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
        }

        return response()->json([
            'success' => false,
            'code' => 'ERR_403_DIVISI',
            'message' => 'Anda tidak memiliki izin untuk mengakses data divisi ini.',
        ], 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(OutgoingLetter $letter, ?Request $request = null): array
    {
        $canManage = $request ? $this->canManage($request, $letter) : false;

        return [
            'id' => $letter->id,
            'uuid' => $letter->uuid,
            'division_id' => $letter->division_id,
            'division_name' => $letter->division?->name ?? '-',
            'division_code' => $letter->division?->code ?? '-',
            'letter_year' => $letter->letter_year,
            'letter_number' => $letter->letter_number,
            'nomor_surat' => $letter->letter_number,
            'letter_date' => $letter->letter_date->toDateString(),
            'tanggal_surat' => $letter->letter_date->toDateString(),
            'tanggal_surat_formatted' => $letter->letter_date->format('d F Y'),
            'disseminated_date' => $letter->disseminated_date->toDateString(),
            'tanggal_share' => $letter->disseminated_date->toDateString(),
            'tanggal_share_formatted' => $letter->disseminated_date->format('d F Y'),
            'subject' => $letter->subject,
            'perihal' => $letter->subject,
            'created_by' => $letter->createdBy ? ($letter->createdBy->name.' ('.$letter->createdBy->username.')') : 'Sistem',
            'created_at_formatted' => $letter->created_at ? $letter->created_at->format('d/m/Y H:i').' WIB' : '-',
            'can_edit' => $canManage,
            'can_delete' => $canManage,
            'attachment' => $letter->attachments->first() ? [
                'uuid' => $letter->attachments->first()->uuid,
                'original_name' => $letter->attachments->first()->original_name,
                'file_size_bytes' => $letter->attachments->first()->file_size_bytes,
                'file_size_formatted' => number_format($letter->attachments->first()->file_size_bytes / 1024, 1).' KB',
                'mime_type' => $letter->attachments->first()->mime_type,
                'is_missing' => (bool) $letter->attachments->first()->is_missing,
                'legacy_drive_url' => $letter->attachments->first()->legacy_drive_url,
                'preview_url' => "/api/v1/attachments/{$letter->attachments->first()->uuid}/preview",
                'download_url' => "/api/v1/attachments/{$letter->attachments->first()->uuid}/download",
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(OutgoingLetter $letter): array
    {
        return $letter->only(['division_id', 'letter_year', 'letter_number', 'letter_date', 'disseminated_date', 'subject']);
    }
}
