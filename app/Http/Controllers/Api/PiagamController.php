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

class PiagamController extends Controller
{
    public function index(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        $activeDivisionId = $divisionContext->activeDivisionId($request, $request->user());

        $piagams = Piagam::query()
            ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
            ->latest('id')
            ->get();

        return response()->json(['data' => $piagams->map(fn (Piagam $piagam) => $this->resource($piagam))->values()]);
    }

    public function store(StorePiagamRequest $request, DivisionContext $divisionContext, AuditLogger $auditLogger): JsonResponse
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

        return response()->json(['success' => true, 'data' => $this->resource($piagam)], 201);
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
}
