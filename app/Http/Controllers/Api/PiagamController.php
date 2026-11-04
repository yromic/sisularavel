<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePiagamRequest;
use App\Models\CompetitionCategory;
use App\Models\Piagam;
use App\Models\School;
use App\Services\AuditLogger;
use App\Services\DivisionContext;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PiagamController extends Controller
{
    public function index(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        $activeDivisionId = $divisionContext->activeDivisionId($request, $request->user());
        $perPage = max(1, min(100, $request->integer('per_page', 25)));

        $paginated = Piagam::query()
            ->with(['school', 'competitionCategory', 'pickupCategory', 'division', 'workUnit'])
            ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
            ->latest('id')
            ->paginate($perPage);

        $paginated->getCollection()->transform(fn (Piagam $piagam) => $this->resource($piagam));

        return response()->json($paginated);
    }

    public function store(StorePiagamRequest $request, DivisionContext $divisionContext, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        $divisionId = $divisionContext->activeDivisionId($request, $user);

        if ($divisionId === null) {
            throw new HttpResponseException(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['division_id' => ['Super Admin harus memilih konteks divisi aktif.']],
            ], 422));
        }

        $category = CompetitionCategory::query()
            ->where('id', $request->integer('competition_category_id'))
            ->where('division_id', $divisionId)
            ->firstOrFail();
        $school = School::query()->where('npsn', $request->string('npsn')->toString())->firstOrFail();

        $piagam = DB::transaction(function () use ($request, $divisionId, $user, $school, $category, $auditLogger): Piagam {
            $piagam = Piagam::query()->create([
                'uuid' => (string) Str::uuid(),
                'division_id' => $divisionId,
                'student_name' => $request->string('student_name')->toString(),
                'school_id' => $school->id,
                'competition_category_id' => $category->id,
                'competition_year' => $request->integer('competition_year'),
                'created_by_user_id' => $user->id,
            ]);

            $auditLogger->piagamEvent('PIAGAM_CREATED', $user, $piagam, 'Piagam created.');

            return $piagam;
        });

        if (! $request->expectsJson()) {
            return redirect('/piagam')->with('success', 'Piagam berhasil didaftarkan.');
        }

        return response()->json(['success' => true, 'data' => $this->resource($piagam)], 201);
    }

    public function show(Request $request, Piagam $piagam, AuditLogger $auditLogger): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $piagam->division_id !== $user->division_id) {
            $auditLogger->crossDivisionBlocked($user, $piagam, 'Unauthorized piagam view attempt.');

            return response()->json([
                'success' => false,
                'code' => 'ERR_403_DIVISI',
                'message' => 'Anda tidak memiliki izin untuk mengakses data divisi ini.',
            ], 403);
        }

        $piagam->loadMissing(['division', 'school', 'workUnit', 'competitionCategory', 'pickupCategory', 'createdBy', 'attachments']);

        return response()->json([
            'success' => true,
            'data' => $this->resourceDetail($piagam, $request),
        ]);
    }

    public function destroy(Request $request, Piagam $piagam, AuditLogger $auditLogger): Response
    {
        $user = $request->user();

        if ($user->role === 'operator') {
            if (! $request->expectsJson()) {
                abort(403, 'Operator tidak memiliki wewenang untuk menghapus piagam.');
            }

            return response()->json([
                'success' => false,
                'code' => 'ERR_403_ROLE',
                'message' => 'Operator tidak memiliki wewenang untuk menghapus piagam.',
            ], 403);
        }

        if ($user->role !== 'super_admin' && $user->division_id !== $piagam->division_id) {
            $auditLogger->crossDivisionBlocked($user, $piagam, 'Unauthorized piagam deletion attempt.');

            if (! $request->expectsJson()) {
                abort(403, 'Anda tidak memiliki izin untuk menghapus data divisi ini.');
            }

            return response()->json([
                'success' => false,
                'code' => 'ERR_403_DIVISI',
                'message' => 'Anda tidak memiliki izin untuk menghapus data divisi ini.',
            ], 403);
        }

        DB::transaction(function () use ($user, $piagam, $auditLogger): void {
            $piagam->delete();

            $auditLogger->piagamEvent(
                'PIAGAM_DELETED',
                $user,
                $piagam,
                'Piagam soft deleted.'
            );
        });

        if (! $request->expectsJson()) {
            return redirect('/persuratan?tab=piagam')->with('success', 'Piagam berhasil dihapus.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Piagam berhasil dihapus.',
            'data' => [
                'id' => $piagam->id,
                'deleted' => true,
            ],
        ]);
    }

    public function restore(Request $request, int $piagam, AuditLogger $auditLogger): Response
    {
        $user = $request->user();
        $record = Piagam::withTrashed()->findOrFail($piagam);

        if ($user->role === 'operator') {
            if (! $request->expectsJson()) {
                abort(403, 'Operator tidak memiliki wewenang untuk memulihkan piagam.');
            }

            return response()->json([
                'success' => false,
                'code' => 'ERR_403_ROLE',
                'message' => 'Operator tidak memiliki wewenang untuk memulihkan piagam.',
            ], 403);
        }

        if ($user->role !== 'super_admin' && $user->division_id !== $record->division_id) {
            $auditLogger->crossDivisionBlocked($user, $record, 'Unauthorized piagam restoration attempt.');

            if (! $request->expectsJson()) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
            }

            return response()->json([
                'success' => false,
                'code' => 'ERR_403_DIVISI',
                'message' => 'Anda tidak memiliki izin untuk mengakses data divisi ini.',
            ], 403);
        }

        DB::transaction(function () use ($user, $record, $auditLogger): void {
            $record->restore();

            $auditLogger->piagamEvent(
                'PIAGAM_RESTORED',
                $user,
                $record,
                'Piagam restored.'
            );
        });

        if (! $request->expectsJson()) {
            return redirect('/persuratan?tab=piagam')->with('success', 'Piagam berhasil dipulihkan.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Piagam berhasil dipulihkan.',
            'data' => [
                'id' => $record->id,
                'restored' => true,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Piagam $piagam): array
    {
        return [
            'id' => $piagam->id,
            'uuid' => $piagam->uuid,
            'division_id' => $piagam->division_id,
            'student_name' => $piagam->student_name,
            'school_id' => $piagam->school_id,
            'competition_category_id' => $piagam->competition_category_id,
            'competition_year' => $piagam->competition_year,
            'recipient_name' => $piagam->recipient_name,
            'recipient_position' => $piagam->recipient_position,
            'recipient_unit' => $piagam->recipient_unit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceDetail(Piagam $piagam, Request $request): array
    {
        $signature = $piagam->attachments->first();

        return [
            'id' => $piagam->id,
            'uuid' => $piagam->uuid,
            'division_id' => $piagam->division_id,
            'division_name' => $piagam->division?->name,
            'division_code' => $piagam->division?->code,
            'student_name' => $piagam->student_name,
            'piagam_description' => $piagam->piagam_description ?: $piagam->student_name,
            'school_origin' => $piagam->school_origin,
            'recipient_unit' => $piagam->recipient_unit,
            'unit_display_name' => $piagam->unit_display_name,
            'npsn' => $piagam->npsn ?? $piagam->school?->npsn ?? '-',
            'competition_category_name' => $piagam->competition_display_name,
            'competition_year' => $piagam->competition_year,
            'recipient_name' => $piagam->recipient_name ?? '-',
            'recipient_position' => $piagam->recipient_position ?? '-',
            'pickup_category_name' => $piagam->pickup_display_name,
            'created_at_formatted' => $piagam->created_at->format('d/m/Y H:i').' WIB',
            'created_at_human' => $piagam->created_at->translatedFormat('d F Y, H:i').' WIB',
            'created_by' => $piagam->createdBy?->name ?? 'Sistem / Publik Mandiri',
            'signature' => $signature ? [
                'uuid' => $signature->uuid,
                'original_name' => $signature->original_name,
                'file_size_bytes' => $signature->file_size_bytes,
                'mime_type' => $signature->mime_type,
                'is_missing' => (bool) $signature->is_missing,
                'legacy_drive_url' => $signature->legacy_drive_url,
                'preview_url' => '/api/v1/attachments/'.$signature->uuid.'/preview',
                'download_url' => '/api/v1/attachments/'.$signature->uuid.'/download',
            ] : null,
        ];
    }
}
