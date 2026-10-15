<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomingLetterRequest;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Services\AttachmentStorageService;
use App\Services\AuditLogger;
use App\Services\DivisionContext;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IncomingLetterController extends Controller
{
    public function index(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        $user = $request->user();
        $activeDivisionId = $divisionContext->activeDivisionId($request, $user);

        $letters = IncomingLetter::query()
            ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
            ->latest('id')
            ->get(['id', 'uuid', 'division_id', 'letter_number', 'origin_agency', 'letter_date', 'received_date', 'subject']);

        return response()->json(['data' => $letters]);
    }

    public function show(Request $request, IncomingLetter $incomingLetter, AuditLogger $auditLogger): JsonResponse
    {
        if (! $this->canView($request, $incomingLetter)) {
            $auditLogger->crossDivisionBlocked($request->user(), $incomingLetter, 'Unauthorized incoming letter view attempt.');

            if (! $request->expectsJson()) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
            }

            return response()->json([
                'success' => false,
                'code' => 'ERR_403_DIVISI',
                'message' => 'Anda tidak memiliki izin untuk mengakses data divisi ini.',
            ], 403);
        }

        $incomingLetter->loadMissing(['division', 'createdBy', 'attachments']);

        return response()->json([
            'success' => true,
            'data' => $this->resource($incomingLetter, $request),
        ]);
    }

    public function store(StoreIncomingLetterRequest $request, DivisionContext $divisionContext, AuditLogger $auditLogger, AttachmentStorageService $attachments): Response
    {
        $user = $request->user();
        $divisionId = $divisionContext->activeDivisionId($request, $user);

        if ($divisionId === null && $user->role === 'super_admin' && $request->filled('division_id')) {
            $divisionId = (int) $request->input('division_id');
            $request->session()->put('active_division_id', $divisionId);
        }

        if ($divisionId === null && $user->role === 'super_admin') {
            $firstDiv = Division::where('status', 'active')->first();
            if ($firstDiv) {
                $divisionId = $firstDiv->id;
                $request->session()->put('active_division_id', $divisionId);
            }
        }

        if ($divisionId === null) {
            throw new HttpResponseException(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['division_id' => ['Super Admin harus memilih konteks divisi aktif.']],
            ], 422));
        }

        $duplicateExists = IncomingLetter::query()
            ->where('division_id', $divisionId)
            ->where('letter_number', $request->string('nomor_surat')->toString())
            ->where('origin_agency', $request->string('asal_surat')->toString())
            ->whereDate('letter_date', $request->date('tanggal_surat')->toDateString())
            ->exists();

        if ($duplicateExists && ! $request->boolean('confirm_duplicate')) {
            return response()->json([
                'success' => true,
                'code' => 'DUPLICATE_ADVISORY',
                'duplicate_advisory' => true,
                'message' => 'Peringatan: Surat dari instansi, nomor, dan tanggal yang sama sudah terdaftar. Apakah Anda yakin ini bukan duplikat?',
            ]);
        }

        $storedPath = null;

        try {
            $letter = DB::transaction(function () use ($request, $divisionId, $user, $duplicateExists, $auditLogger, $attachments, &$storedPath): IncomingLetter {
                $letter = IncomingLetter::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $divisionId,
                    'letter_number' => $request->string('nomor_surat')->toString(),
                    'origin_agency' => $request->string('asal_surat')->toString(),
                    'letter_date' => $request->date('tanggal_surat')->toDateString(),
                    'received_date' => $request->date('tanggal_diterima')->toDateString(),
                    'subject' => $request->string('perihal')->toString(),
                    'created_by_user_id' => $user->id,
                ]);

                $auditLogger->incomingLetterEvent(
                    $duplicateExists ? 'LETTER_INCOMING_DUPLICATE_CONFIRMED' : 'LETTER_INCOMING_CREATED',
                    $user,
                    $letter,
                    $duplicateExists ? 'Incoming letter created after duplicate advisory confirmation.' : 'Incoming letter created.',
                    $letter->only(['division_id', 'letter_number', 'origin_agency', 'letter_date', 'received_date', 'subject'])
                );

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
            return redirect('/surat-masuk')->with('success', 'Surat masuk berhasil disimpan.');
        }

        return response()->json([
            'success' => true,
            'duplicate_advisory' => $duplicateExists,
            'data' => $this->resource($letter),
        ], 201);
    }

    public function update(StoreIncomingLetterRequest $request, IncomingLetter $incomingLetter, AuditLogger $auditLogger): Response
    {
        if (! $this->canManage($request, $incomingLetter)) {
            return $this->forbiddenDivisionResponse($request);
        }

        $letter = DB::transaction(function () use ($request, $incomingLetter, $auditLogger): IncomingLetter {
            $incomingLetter->forceFill([
                'letter_number' => $request->string('nomor_surat')->toString(),
                'origin_agency' => $request->string('asal_surat')->toString(),
                'letter_date' => $request->date('tanggal_surat')->toDateString(),
                'received_date' => $request->date('tanggal_diterima')->toDateString(),
                'subject' => $request->string('perihal')->toString(),
                'updated_by_user_id' => $request->user()->id,
            ])->save();

            $auditLogger->incomingLetterEvent(
                'LETTER_INCOMING_UPDATED',
                $request->user(),
                $incomingLetter,
                'Incoming letter metadata updated.',
                $incomingLetter->only(['division_id', 'letter_number', 'origin_agency', 'letter_date', 'received_date', 'subject'])
            );

            return $incomingLetter;
        });

        if (! $request->expectsJson()) {
            return redirect('/surat-masuk/'.$incomingLetter->id)->with('success', 'Surat masuk berhasil diperbarui.');
        }

        return response()->json(['success' => true, 'data' => $this->resource($letter)]);
    }

    public function destroy(Request $request, IncomingLetter $incomingLetter, AuditLogger $auditLogger): Response
    {
        if (! $this->canManage($request, $incomingLetter)) {
            return $this->forbiddenDivisionResponse($request);
        }

        DB::transaction(function () use ($request, $incomingLetter, $auditLogger): void {
            $incomingLetter->forceFill(['deleted_by_user_id' => $request->user()->id])->save();
            $incomingLetter->delete();

            $auditLogger->incomingLetterEvent(
                'LETTER_INCOMING_DELETED',
                $request->user(),
                $incomingLetter,
                'Incoming letter soft deleted.'
            );
        });

        if (! $request->expectsJson()) {
            return redirect('/surat-masuk')->with('success', 'Surat masuk berhasil dihapus.');
        }

        return response()->json(['success' => true, 'data' => ['id' => $incomingLetter->id, 'deleted' => true]]);
    }

    public function restore(Request $request, int $incomingLetter, AuditLogger $auditLogger): JsonResponse
    {
        $letter = IncomingLetter::withTrashed()->findOrFail($incomingLetter);

        if (! $this->canManage($request, $letter)) {
            return $this->forbiddenDivisionResponse($request);
        }

        DB::transaction(function () use ($request, $letter, $auditLogger): void {
            $letter->restore();
            $letter->forceFill(['deleted_by_user_id' => null])->save();

            $auditLogger->incomingLetterEvent(
                'LETTER_INCOMING_RESTORED',
                $request->user(),
                $letter,
                'Incoming letter restored.'
            );
        });

        return response()->json(['success' => true, 'data' => ['id' => $letter->id, 'deleted' => false]]);
    }

    public function remediateAttachment(Request $request, IncomingLetter $incomingLetter, AttachmentStorageService $attachments, AuditLogger $auditLogger): Response
    {
        if (! $this->canManage($request, $incomingLetter)) {
            return $this->forbiddenDivisionResponse($request);
        }

        $validated = $request->validate([
            'attachment' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png',
            ],
        ]);

        $attachment = $incomingLetter->attachments()->firstOrFail();
        $attachment = $attachments->remediate($attachment, $validated['attachment'], $incomingLetter->division, $request->user());

        $auditLogger->attachmentEvent('ATTACHMENT_UPLOADED', $request->user(), $attachment, 'Missing incoming letter attachment remediated.', [
            'file_path' => $attachment->file_path,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
        ]);

        if (! $request->expectsJson()) {
            return redirect('/surat-masuk/'.$incomingLetter->id)->with('success', 'Lampiran pengganti berhasil diunggah.');
        }

        return response()->json(['success' => true, 'data' => $this->attachmentResource($attachment)]);
    }

    private function canView(Request $request, IncomingLetter $incomingLetter): bool
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->division_id === $incomingLetter->division_id;
    }

    private function canManage(Request $request, IncomingLetter $incomingLetter): bool
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'division_admin' && $user->division_id === $incomingLetter->division_id;
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
    private function resource(IncomingLetter $letter, ?Request $request = null): array
    {
        $canManage = $request ? $this->canManage($request, $letter) : false;

        return [
            'id' => $letter->id,
            'uuid' => $letter->uuid,
            'division_id' => $letter->division_id,
            'division_name' => $letter->division?->name ?? '-',
            'division_code' => $letter->division?->code ?? '-',
            'letter_number' => $letter->letter_number,
            'nomor_surat' => $letter->letter_number,
            'origin_agency' => $letter->origin_agency,
            'asal_surat' => $letter->origin_agency,
            'letter_date' => $letter->letter_date->toDateString(),
            'tanggal_surat' => $letter->letter_date->toDateString(),
            'tanggal_surat_formatted' => $letter->letter_date->format('d F Y'),
            'received_date' => $letter->received_date->toDateString(),
            'tanggal_diterima' => $letter->received_date->toDateString(),
            'tanggal_diterima_formatted' => $letter->received_date->format('d F Y'),
            'subject' => $letter->subject,
            'perihal' => $letter->subject,
            'created_by' => $letter->createdBy ? ($letter->createdBy->name.' ('.$letter->createdBy->username.')') : 'Sistem',
            'created_at_formatted' => $letter->created_at ? $letter->created_at->format('d/m/Y H:i').' WIB' : '-',
            'can_edit' => $canManage,
            'can_delete' => $canManage,
            'attachment' => $letter->relationLoaded('attachments') && $letter->attachments->isNotEmpty()
                ? $this->attachmentResource($letter->attachments->first())
                : ($letter->attachments()->exists() ? $this->attachmentResource($letter->attachments()->first()) : null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attachmentResource($attachment): array
    {
        return [
            'uuid' => $attachment->uuid,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'file_size_bytes' => $attachment->file_size_bytes,
            'file_size_formatted' => number_format($attachment->file_size_bytes / 1024, 1).' KB',
            'is_missing' => (bool) $attachment->is_missing,
            'migration_status' => $attachment->migration_status,
            'legacy_drive_url' => $attachment->legacy_drive_url,
            'preview_url' => "/api/v1/attachments/{$attachment->uuid}/preview",
            'download_url' => "/api/v1/attachments/{$attachment->uuid}/download",
        ];
    }
}
