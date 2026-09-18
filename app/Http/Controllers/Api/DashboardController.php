<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Services\DivisionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'total_incoming_letters' => $this->scoped(IncomingLetter::query(), $divisionId)->count(),
                'total_outgoing_letters' => $this->scoped(OutgoingLetter::query(), $divisionId)->count(),
                'total_piagams' => $this->scoped(Piagam::query(), $divisionId)->count(),
                'monthly' => [
                    'incoming' => $this->monthlyIncoming($divisionId),
                    'outgoing' => $this->monthlyOutgoing($divisionId),
                ],
            ],
        ]);
    }

    private function scoped($query, ?int $divisionId)
    {
        return $query->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId));
    }

    /**
     * @return array<string, int>
     */
    private function monthlyIncoming(?int $divisionId): array
    {
        return $this->scoped(IncomingLetter::query(), $divisionId)
            ->get()
            ->groupBy(fn (IncomingLetter $letter) => $letter->letter_date->format('Y-m'))
            ->map->count()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function monthlyOutgoing(?int $divisionId): array
    {
        return $this->scoped(OutgoingLetter::query(), $divisionId)
            ->get()
            ->groupBy(fn (OutgoingLetter $letter) => $letter->letter_date->format('Y-m'))
            ->map->count()
            ->all();
    }
}
