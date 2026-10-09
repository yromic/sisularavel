<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOutgoingLetterRequest;
use App\Models\Division;
use App\Models\OutgoingLetter;
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

        $letters = OutgoingLetter::query()
            ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
            ->latest('id')
            ->get(['id', 'uuid', 'division_id', 'letter_year', 'letter_number', 'letter_date', 'disseminated_date', 'subject']);

        return response()->json(['data' => $letters]);
    }

    public function show(Request $request, OutgoingLetter $outgoingLetter): JsonResponse
    {
        if (! $this->canView($request, $outgoingLetter)) {
            return $this->forbiddenDivisionResponse();
        }

        return response()->json(['data' => $this->resource($outgoingLetter)]);
    }

    public function store(StoreOutgoingLetterRequest $request, DivisionContext $divisionContext, AuditLogger $auditLogger): Response
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

        $letterYear = (int) $request->date('tanggal_surat')->format('Y');
        if ($this->numberExists($divisionId, $letterYear, $request->string('nomor_surat')->toString())) {
            return $this->duplicateNumberResponse();
        }

        $letter = DB::transaction(function () use ($request, $divisionId, $letterYear, $user, $auditLogger): OutgoingLetter {
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

            return $letter;
        });

        if (! $request->expectsJson()) {
            return redirect('/surat-keluar')->with('success', 'Surat keluar berhasil disimpan.');
        }

        return response()->json(['success' => true, 'data' => $this->resource($letter)], 201);
    }

    public function update(StoreOutgoingLetterRequest $request, OutgoingLetter $outgoingLetter, AuditLogger $auditLogger): JsonResponse
    {
        if (! $this->canManage($request, $outgoingLetter)) {
            return $this->forbiddenDivisionResponse();
        }

        $letterYear = (int) $request->date('tanggal_surat')->format('Y');
        $letterNumber = $request->string('nomor_surat')->toString();
        if ($this->numberExists($outgoingLetter->division_id, $letterYear, $letterNumber, $outgoingLetter->id)) {
            return $this->duplicateNumberResponse();
        }

        $letter = DB::transaction(function () use ($request, $outgoingLetter, $letterYear, $letterNumber, $auditLogger): OutgoingLetter {
            $outgoingLetter->forceFill([
                'letter_year' => $letterYear,
                'letter_number' => $letterNumber,
                'letter_date' => $request->date('tanggal_surat')->toDateString(),
                'disseminated_date' => $request->date('tanggal_share')->toDateString(),
                'subject' => $request->string('perihal')->toString(),
                'updated_by_user_id' => $request->user()->id,
            ])->save();

            $auditLogger->outgoingLetterEvent('LETTER_OUTGOING_UPDATED', $request->user(), $outgoingLetter, 'Outgoing letter metadata updated.', $this->auditValues($outgoingLetter));

            return $outgoingLetter;
        });

        return response()->json(['success' => true, 'data' => $this->resource($letter)]);
    }

    public function destroy(Request $request, OutgoingLetter $outgoingLetter, AuditLogger $auditLogger): Response
    {
        if (! $this->canManage($request, $outgoingLetter)) {
            return $this->forbiddenDivisionResponse();
        }

        DB::transaction(function () use ($request, $outgoingLetter, $auditLogger): void {
            $outgoingLetter->forceFill(['deleted_by_user_id' => $request->user()->id])->save();
            $outgoingLetter->delete();

            $auditLogger->outgoingLetterEvent('LETTER_OUTGOING_DELETED', $request->user(), $outgoingLetter, 'Outgoing letter soft deleted.');
        });

        if (! $request->expectsJson()) {
            return back()->with('success', 'Surat keluar berhasil dihapus.');
        }

        return response()->json(['success' => true, 'data' => ['id' => $outgoingLetter->id, 'deleted' => true]]);
    }

    public function restore(Request $request, int $outgoingLetter, AuditLogger $auditLogger): JsonResponse
    {
        $letter = OutgoingLetter::withTrashed()->findOrFail($outgoingLetter);

        if (! $this->canManage($request, $letter)) {
            return $this->forbiddenDivisionResponse();
        }

        DB::transaction(function () use ($request, $letter, $auditLogger): void {
            $letter->restore();
            $letter->forceFill(['deleted_by_user_id' => null])->save();

            $auditLogger->outgoingLetterEvent('LETTER_OUTGOING_RESTORED', $request->user(), $letter, 'Outgoing letter restored.');
        });

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

    private function duplicateNumberResponse(): JsonResponse
    {
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

    private function forbiddenDivisionResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'ERR_403_DIVISI',
            'message' => 'Anda tidak memiliki izin untuk mengakses data divisi ini.',
        ], 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(OutgoingLetter $letter): array
    {
        return [
            'id' => $letter->id,
            'uuid' => $letter->uuid,
            'division_id' => $letter->division_id,
            'letter_year' => $letter->letter_year,
            'nomor_surat' => $letter->letter_number,
            'tanggal_surat' => $letter->letter_date->toDateString(),
            'tanggal_share' => $letter->disseminated_date->toDateString(),
            'perihal' => $letter->subject,
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
