<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicClaimPiagamRequest;
use App\Models\Attachment;
use App\Models\Division;
use App\Models\Piagam;
use App\Models\School;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PublicPiagamController extends Controller
{
    public function claim(PublicClaimPiagamRequest $request, AuditLogger $auditLogger): Response
    {
        // 1. Decode & Validate Signature Canvas
        [$mime, $binary] = $this->decodeSignature($request->string('signature_canvas')->toString());

        // 2. Branch: Legacy pre-registered claim vs New Direct Handover Receipt
        if ($request->filled('piagam_id')) {
            return $this->handleLegacyClaim($request, $auditLogger, $mime, $binary);
        }

        return $this->handleDirectHandoverReceipt($request, $auditLogger, $mime, $binary);
    }

    private function handleDirectHandoverReceipt(
        PublicClaimPiagamRequest $request,
        AuditLogger $auditLogger,
        string $mime,
        string $binary
    ): Response {
        $division = Division::query()->where('status', 'active')->findOrFail($request->integer('division_id'));
        $schoolOrigin = trim($request->string('school_origin')->toString());
        $recipientUnit = trim((string) ($request->input('recipient_unit') ?: $schoolOrigin));
        $studentName = trim($request->string('student_name')->toString());
        $piagamDescription = trim($request->string('piagam_description')->toString());
        $recipientName = trim($request->string('recipient_name')->toString());
        $recipientPosition = trim($request->string('recipient_position')->toString());
        $competitionYear = (int) ($request->input('competition_year') ?: now()->year);
        $schoolId = $request->input('school_id');
        $workUnitId = $request->input('work_unit_id');
        $npsn = $request->input('npsn');
        $pickupCategoryId = $request->input('pickup_category_id');
        $pickupCategoryName = $request->input('pickup_category_name');
        $competitionCategoryId = $request->input('competition_category_id');
        $competitionCategoryName = $request->input('competition_category_name');

        $path = 'signatures/'.$division->code.'/'.Str::uuid().'.png';
        $stagedPath = null;

        try {
            /** @var array{piagam: Piagam, attachment: Attachment} $result */
            $result = DB::transaction(function () use (
                $auditLogger,
                $division,
                $schoolId,
                $workUnitId,
                $npsn,
                $schoolOrigin,
                $studentName,
                $piagamDescription,
                $competitionCategoryId,
                $competitionCategoryName,
                $competitionYear,
                $pickupCategoryId,
                $pickupCategoryName,
                $recipientName,
                $recipientPosition,
                $recipientUnit,
                $mime,
                $binary,
                $path,
                &$stagedPath
            ) {
                Storage::disk('private_signatures')->put($path, $binary);
                $stagedPath = $path;

                $piagam = Piagam::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $division->id,
                    'student_name' => $studentName,
                    'piagam_description' => $piagamDescription,
                    'school_id' => $schoolId,
                    'work_unit_id' => $workUnitId,
                    'school_origin' => $schoolOrigin,
                    'npsn' => $npsn,
                    'competition_category_id' => $competitionCategoryId,
                    'competition_category_name' => $competitionCategoryName,
                    'competition_year' => $competitionYear,
                    'pickup_category_id' => $pickupCategoryId,
                    'pickup_category_name' => $pickupCategoryName,
                    'recipient_name' => $recipientName,
                    'recipient_position' => $recipientPosition,
                    'recipient_unit' => $recipientUnit,
                    'created_by_user_id' => null, // Public guest handover receipt
                ]);

                $attachment = Attachment::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $division->id,
                    'attachable_type' => Piagam::class,
                    'attachable_id' => $piagam->id,
                    'file_path' => $path,
                    'original_name' => 'signature.png',
                    'file_size_bytes' => strlen($binary),
                    'mime_type' => $mime,
                    'file_hash' => hash('sha256', $binary),
                    'is_missing' => false,
                    'migration_status' => 'migrated',
                    'created_by_user_id' => null,
                ]);

                $auditLogger->publicPiagamEvent('PIAGAM_HANDOVER_RECORDED', $piagam, "Pengambilan piagam dicatat untuk {$recipientName} ({$schoolOrigin}).");

                return ['piagam' => $piagam, 'attachment' => $attachment];
            });
        } catch (\Throwable $e) {
            if ($stagedPath && Storage::disk('private_signatures')->exists($stagedPath)) {
                Storage::disk('private_signatures')->delete($stagedPath);
            }
            throw $e;
        }

        $piagam = $result['piagam'];
        $attachment = $result['attachment'];

        if (! $request->expectsJson()) {
            return redirect('/piagam/ambil')->with('success', 'Tanda terima pengambilan piagam berhasil dicatat.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Tanda terima pengambilan piagam berhasil dicatat.',
            'data' => [
                'id' => $piagam->id,
                'uuid' => $piagam->uuid,
                'piagam_description' => $piagam->piagam_description,
                'student_name' => $piagam->student_name,
                'school_origin' => $piagam->school_origin,
                'recipient_unit' => $piagam->recipient_unit,
                'npsn' => $piagam->npsn,
                'recipient_name' => $piagam->recipient_name,
                'recipient_position' => $piagam->recipient_position,
                'pickup_category_name' => $piagam->pickup_category_name,
                'competition_category_name' => $piagam->competition_category_name,
                'signature_uuid' => $attachment->uuid,
            ],
        ], 201);
    }

    private function handleLegacyClaim(
        PublicClaimPiagamRequest $request,
        AuditLogger $auditLogger,
        string $mime,
        string $binary
    ): JsonResponse {
        $piagam = Piagam::query()->findOrFail($request->integer('piagam_id'));
        $school = School::query()->where('npsn', $request->string('npsn')->toString())->firstOrFail();

        if ($piagam->school_id !== $school->id) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['npsn' => ['NPSN sekolah harus 8 digit dan terdaftar.']],
            ], 422);
        }

        $path = 'signatures/'.$piagam->division->code.'/'.Str::uuid().'.png';
        $stagedPath = null;

        try {
            $attachment = DB::transaction(function () use ($request, $auditLogger, $piagam, $mime, $binary, $path, &$stagedPath): Attachment {
                Storage::disk('private_signatures')->put($path, $binary);
                $stagedPath = $path;

                $piagam->forceFill([
                    'recipient_name' => $request->string('recipient_name')->toString(),
                    'recipient_position' => $request->string('recipient_position')->toString(),
                    'recipient_unit' => $request->string('recipient_unit')->toString(),
                ])->save();

                $attachment = Attachment::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $piagam->division_id,
                    'attachable_type' => Piagam::class,
                    'attachable_id' => $piagam->id,
                    'file_path' => $path,
                    'original_name' => 'signature.png',
                    'file_size_bytes' => strlen($binary),
                    'mime_type' => $mime,
                    'file_hash' => hash('sha256', $binary),
                    'is_missing' => false,
                    'migration_status' => 'migrated',
                    'created_by_user_id' => null,
                ]);

                $auditLogger->publicPiagamEvent('PIAGAM_PUBLIC_SUBMITTED', $piagam, 'Public piagam claim submitted.');

                return $attachment;
            });
        } catch (\Throwable $e) {
            if ($stagedPath && Storage::disk('private_signatures')->exists($stagedPath)) {
                Storage::disk('private_signatures')->delete($stagedPath);
            }
            throw $e;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $piagam->id,
                'recipient_name' => $piagam->recipient_name,
                'recipient_position' => $piagam->recipient_position,
                'recipient_unit' => $piagam->recipient_unit,
                'signature_uuid' => $attachment->uuid,
            ],
        ], 201);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function decodeSignature(string $dataUrl): array
    {
        if (! preg_match('/^data:(image\/(?:png|jpeg));base64,(.+)$/', $dataUrl, $matches)) {
            abort(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['signature_canvas' => ['Tanda tangan digital wajib dibubuhkan.']],
            ], 422));
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || strlen($binary) === 0) {
            abort(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['signature_canvas' => ['Tanda tangan digital wajib dibubuhkan.']],
            ], 422));
        }

        return [$matches[1], $binary];
    }
}
