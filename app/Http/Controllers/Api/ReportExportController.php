<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Services\DivisionContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function csv(Request $request, DivisionContext $divisionContext): StreamedResponse
    {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());

        return response()->streamDownload(function () use ($divisionId): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['type', 'letter_number', 'subject']);

            IncomingLetter::query()
                ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
                ->orderBy('id')
                ->each(function (IncomingLetter $letter) use ($output): void {
                    fputcsv($output, [
                        'incoming_letter',
                        $this->sanitizeCell($letter->letter_number),
                        $this->sanitizeCell($letter->subject),
                    ]);
                });

            fclose($output);
        }, 'sisurat-export.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function sanitizeCell(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
