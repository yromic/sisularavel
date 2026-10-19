<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Services\DivisionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());

        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $results = collect()
            ->merge($this->incoming($query, $divisionId))
            ->merge($this->outgoing($query, $divisionId))
            ->merge($this->piagams($query, $divisionId))
            ->values();

        return response()->json(['data' => $results]);
    }

    private function incoming(string $search, ?int $divisionId)
    {
        return IncomingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->where(fn ($query) => $query
                ->where('letter_number', 'like', "%{$search}%")
                ->orWhere('origin_agency', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%"))
            ->limit(50)
            ->get(['id', 'division_id', 'letter_number', 'subject'])
            ->map(fn (IncomingLetter $letter) => [
                'type' => 'incoming_letter',
                'id' => $letter->id,
                'division_id' => $letter->division_id,
                'letter_number' => $letter->letter_number,
                'subject' => $letter->subject,
            ]);
    }

    private function outgoing(string $search, ?int $divisionId)
    {
        return OutgoingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->where(fn ($query) => $query
                ->where('letter_number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%"))
            ->limit(50)
            ->get(['id', 'division_id', 'letter_number', 'subject'])
            ->map(fn (OutgoingLetter $letter) => [
                'type' => 'outgoing_letter',
                'id' => $letter->id,
                'division_id' => $letter->division_id,
                'letter_number' => $letter->letter_number,
                'subject' => $letter->subject,
            ]);
    }

    private function piagams(string $search, ?int $divisionId)
    {
        return Piagam::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->where(fn ($query) => $query
                ->where('student_name', 'like', "%{$search}%")
                ->orWhere('piagam_description', 'like', "%{$search}%")
                ->orWhere('recipient_name', 'like', "%{$search}%")
                ->orWhere('recipient_unit', 'like', "%{$search}%")
                ->orWhere('school_origin', 'like', "%{$search}%")
                ->orWhere('competition_category_name', 'like', "%{$search}%")
                ->orWhere('pickup_category_name', 'like', "%{$search}%")
                ->orWhereHas('school', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                ->orWhereHas('workUnit', fn ($w) => $w->where('name', 'like', "%{$search}%"))
                ->orWhereHas('competitionCategory', fn ($c) => $c->where('name', 'like', "%{$search}%")))
            ->limit(50)
            ->get(['id', 'division_id', 'student_name', 'piagam_description'])
            ->map(fn (Piagam $piagam) => [
                'type' => 'piagam',
                'id' => $piagam->id,
                'division_id' => $piagam->division_id,
                'student_name' => $piagam->piagam_description ?: $piagam->student_name,
            ]);
    }
}
