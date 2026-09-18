<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Services\AuditLogger;
use App\Services\DivisionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

            return response()->json([
                'success' => false,
                'code' => 'ERR_403_DIVISI',
                'message' => 'Anda tidak memiliki izin untuk mengakses data divisi ini.',
            ], 403);
        }

        return response()->json([
            'data' => $incomingLetter->only([
                'id',
                'uuid',
                'division_id',
                'letter_number',
                'origin_agency',
                'letter_date',
                'received_date',
                'subject',
            ]),
        ]);
    }

    private function canView(Request $request, IncomingLetter $incomingLetter): bool
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->division_id === $incomingLetter->division_id;
    }
}
