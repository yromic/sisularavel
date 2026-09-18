<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicClaimPiagamRequest;
use App\Models\Attachment;
use App\Models\Piagam;
use App\Models\School;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicPiagamController extends Controller
{
    public function claim(PublicClaimPiagamRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $piagam = Piagam::query()->findOrFail($request->integer('piagam_id'));
        $school = School::query()->where('npsn', $request->string('npsn')->toString())->firstOrFail();

        if ($piagam->school_id !== $school->id) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['npsn' => ['NPSN sekolah harus 8 digit dan terdaftar.']],
            ], 422);
        }

        [$mime, $binary] = $this->decodeSignature($request->string('signature_canvas')->toString());
        $path = 'signatures/'.$piagam->division->code.'/'.Str::uuid().'.png';

        $attachment = DB::transaction(function () use ($request, $auditLogger, $piagam, $mime, $binary, $path): Attachment {
            Storage::disk('private_signatures')->put($path, $binary);

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
        if ($binary === false || $binary === '') {
            abort(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['signature_canvas' => ['Tanda tangan digital wajib dibubuhkan.']],
            ], 422));
        }

        return [$matches[1], $binary];
    }
}
