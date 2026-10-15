<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Services\DivisionContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function csv(Request $request, DivisionContext $divisionContext): StreamedResponse
    {
        $user = $request->user();
        $divisionId = $divisionContext->activeDivisionId($request, $user);

        if ($user->role === 'super_admin' && $request->filled('division_id')) {
            $divisionId = (int) $request->input('division_id');
        }

        $type = $request->input('type', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $month = $request->input('month');
        $year = $request->input('year');
        $q = $request->input('q');

        $filename = 'sisurat-rekap-'.($type ?: 'all').'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($divisionId, $type, $dateFrom, $dateTo, $month, $year, $q): void {
            $output = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fwrite($output, "\xEF\xBB\xBF");

            $rowNumber = 1;

            if ($type === 'incoming') {
                fputcsv($output, [
                    'No',
                    'Nomor Surat',
                    'Asal Surat',
                    'Perihal',
                    'Tanggal Surat',
                    'Tanggal Diterima',
                    'Nama Pengunggah',
                    'Divisi',
                    'Status Lampiran',
                ]);

                $query = IncomingLetter::query()
                    ->with(['division', 'createdBy', 'attachments'])
                    ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                    ->when($dateFrom, fn ($builder) => $builder->whereDate('letter_date', '>=', $dateFrom))
                    ->when($dateTo, fn ($builder) => $builder->whereDate('letter_date', '<=', $dateTo))
                    ->when($year, fn ($builder) => $builder->whereYear('letter_date', $year))
                    ->when($month, fn ($builder) => $builder->whereMonth('letter_date', $month))
                    ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                        $sub->where('letter_number', 'like', "%{$q}%")
                            ->orWhere('subject', 'like', "%{$q}%")
                            ->orWhere('origin_agency', 'like', "%{$q}%");
                    }))
                    ->orderBy('id');

                $query->each(function (IncomingLetter $letter) use ($output, &$rowNumber): void {
                    $hasAttachment = $letter->attachments->isNotEmpty() ? 'Ada Lampiran' : 'Tanpa Lampiran';
                    fputcsv($output, [
                        $rowNumber++,
                        $this->sanitizeCell($letter->letter_number),
                        $this->sanitizeCell($letter->origin_agency),
                        $this->sanitizeCell($letter->subject),
                        $letter->letter_date ? $letter->letter_date->format('Y-m-d') : '',
                        $letter->received_date ? $letter->received_date->format('Y-m-d') : '',
                        $this->sanitizeCell($letter->createdBy?->name ?? 'Sistem'),
                        $this->sanitizeCell($letter->division?->name ?? '-'),
                        $hasAttachment,
                    ]);
                });
            } elseif ($type === 'outgoing') {
                fputcsv($output, [
                    'No',
                    'Nomor Surat',
                    'Perihal',
                    'Tanggal Surat',
                    'Tanggal Shared',
                    'Tahun Surat',
                    'Nama Pengunggah',
                    'Divisi',
                    'Status Lampiran',
                ]);

                $query = OutgoingLetter::query()
                    ->with(['division', 'createdBy', 'attachments'])
                    ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                    ->when($dateFrom, fn ($builder) => $builder->whereDate('letter_date', '>=', $dateFrom))
                    ->when($dateTo, fn ($builder) => $builder->whereDate('letter_date', '<=', $dateTo))
                    ->when($year, fn ($builder) => $builder->where('letter_year', $year))
                    ->when($month, fn ($builder) => $builder->whereMonth('letter_date', $month))
                    ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                        $sub->where('letter_number', 'like', "%{$q}%")
                            ->orWhere('subject', 'like', "%{$q}%");
                    }))
                    ->orderBy('id');

                $query->each(function (OutgoingLetter $letter) use ($output, &$rowNumber): void {
                    $hasAttachment = $letter->attachments->isNotEmpty() ? 'Ada Lampiran' : 'Tanpa Lampiran';
                    fputcsv($output, [
                        $rowNumber++,
                        $this->sanitizeCell($letter->letter_number),
                        $this->sanitizeCell($letter->subject),
                        $letter->letter_date ? $letter->letter_date->format('Y-m-d') : '',
                        $letter->disseminated_date ? $letter->disseminated_date->format('Y-m-d') : '',
                        $letter->letter_year,
                        $this->sanitizeCell($letter->createdBy?->name ?? 'Sistem'),
                        $this->sanitizeCell($letter->division?->name ?? '-'),
                        $hasAttachment,
                    ]);
                });
            } elseif ($type === 'piagam') {
                fputcsv($output, [
                    'No',
                    'Nama Siswa',
                    'Sekolah',
                    'NPSN',
                    'Kategori Lomba',
                    'Tahun Lomba',
                    'Status Pengambilan',
                    'Nama Penerima',
                    'Jabatan Penerima',
                    'Instansi Penerima',
                    'Jenis Pengambilan',
                    'Divisi',
                    'Petugas Registrasi',
                ]);

                $query = Piagam::query()
                    ->with(['division', 'school', 'competitionCategory', 'pickupCategory', 'createdBy'])
                    ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                    ->when($dateFrom, fn ($builder) => $builder->whereDate('created_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($builder) => $builder->whereDate('created_at', '<=', $dateTo))
                    ->when($year, fn ($builder) => $builder->where('competition_year', $year))
                    ->when($month, fn ($builder) => $builder->whereMonth('created_at', $month))
                    ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                        $sub->where('student_name', 'like', "%{$q}%")
                            ->orWhere('piagam_description', 'like', "%{$q}%")
                            ->orWhere('recipient_name', 'like', "%{$q}%")
                            ->orWhere('school_origin', 'like', "%{$q}%")
                            ->orWhereHas('school', fn ($s) => $s->where('name', 'like', "%{$q}%"));
                    }))
                    ->orderBy('id');

                $query->each(function (Piagam $piagam) use ($output, &$rowNumber): void {
                    fputcsv($output, [
                        $rowNumber++,
                        $this->sanitizeCell($piagam->piagam_description ?: $piagam->student_name),
                        $this->sanitizeCell($piagam->school?->name ?? $piagam->school_origin ?? '-'),
                        $this->sanitizeCell($piagam->npsn ?? $piagam->school?->npsn ?? '-'),
                        $this->sanitizeCell($piagam->competition_category_name ?: ($piagam->competitionCategory?->name ?? '-')),
                        $piagam->competition_year ?? '-',
                        'Tercatat',
                        $this->sanitizeCell($piagam->recipient_name ?? '-'),
                        $this->sanitizeCell($piagam->recipient_position ?? '-'),
                        $this->sanitizeCell($piagam->recipient_unit ?? $piagam->school_origin ?? '-'),
                        $this->sanitizeCell($piagam->pickup_category_name ?: ($piagam->pickupCategory?->name ?? 'Diambil Langsung')),
                        $this->sanitizeCell($piagam->division?->name ?? '-'),
                        $this->sanitizeCell($piagam->createdBy?->name ?? 'Tamu Loket'),
                    ]);
                });
            } else {
                // Unified export (All Administration)
                fputcsv($output, [
                    'No',
                    'Jenis Administrasi',
                    'Nomor Surat / Nama Siswa',
                    'Asal Instansi / Sekolah',
                    'Perihal / Kategori',
                    'Tanggal Dokumen',
                    'Tanggal Diterima / Shared',
                    'Status / Penerima',
                    'Nama Pengunggah / Petugas',
                    'Divisi',
                ]);

                // 1. Incoming Letters
                IncomingLetter::query()
                    ->with(['division', 'createdBy', 'attachments'])
                    ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                    ->when($dateFrom, fn ($builder) => $builder->whereDate('letter_date', '>=', $dateFrom))
                    ->when($dateTo, fn ($builder) => $builder->whereDate('letter_date', '<=', $dateTo))
                    ->when($year, fn ($builder) => $builder->whereYear('letter_date', $year))
                    ->when($month, fn ($builder) => $builder->whereMonth('letter_date', $month))
                    ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                        $sub->where('letter_number', 'like', "%{$q}%")
                            ->orWhere('subject', 'like', "%{$q}%")
                            ->orWhere('origin_agency', 'like', "%{$q}%");
                    }))
                    ->orderBy('id')
                    ->each(function (IncomingLetter $letter) use ($output, &$rowNumber): void {
                        fputcsv($output, [
                            $rowNumber++,
                            'Surat Masuk',
                            $this->sanitizeCell($letter->letter_number),
                            $this->sanitizeCell($letter->origin_agency),
                            $this->sanitizeCell($letter->subject),
                            $letter->letter_date ? $letter->letter_date->format('Y-m-d') : '',
                            $letter->received_date ? $letter->received_date->format('Y-m-d') : '',
                            $letter->attachments->isNotEmpty() ? 'Ada Lampiran' : 'Tanpa Lampiran',
                            $this->sanitizeCell($letter->createdBy?->name ?? 'Sistem'),
                            $this->sanitizeCell($letter->division?->name ?? '-'),
                        ]);
                    });

                // 2. Outgoing Letters
                OutgoingLetter::query()
                    ->with(['division', 'createdBy', 'attachments'])
                    ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                    ->when($dateFrom, fn ($builder) => $builder->whereDate('letter_date', '>=', $dateFrom))
                    ->when($dateTo, fn ($builder) => $builder->whereDate('letter_date', '<=', $dateTo))
                    ->when($year, fn ($builder) => $builder->where('letter_year', $year))
                    ->when($month, fn ($builder) => $builder->whereMonth('letter_date', $month))
                    ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                        $sub->where('letter_number', 'like', "%{$q}%")
                            ->orWhere('subject', 'like', "%{$q}%");
                    }))
                    ->orderBy('id')
                    ->each(function (OutgoingLetter $letter) use ($output, &$rowNumber): void {
                        fputcsv($output, [
                            $rowNumber++,
                            'Surat Keluar',
                            $this->sanitizeCell($letter->letter_number),
                            '-',
                            $this->sanitizeCell($letter->subject),
                            $letter->letter_date ? $letter->letter_date->format('Y-m-d') : '',
                            $letter->disseminated_date ? $letter->disseminated_date->format('Y-m-d') : '',
                            $letter->attachments->isNotEmpty() ? 'Ada Lampiran' : 'Tanpa Lampiran',
                            $this->sanitizeCell($letter->createdBy?->name ?? 'Sistem'),
                            $this->sanitizeCell($letter->division?->name ?? '-'),
                        ]);
                    });

                // 3. Piagam
                Piagam::query()
                    ->with(['division', 'school', 'competitionCategory', 'pickupCategory', 'createdBy'])
                    ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                    ->when($dateFrom, fn ($builder) => $builder->whereDate('created_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($builder) => $builder->whereDate('created_at', '<=', $dateTo))
                    ->when($year, fn ($builder) => $builder->where('competition_year', $year))
                    ->when($month, fn ($builder) => $builder->whereMonth('created_at', $month))
                    ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                        $sub->where('student_name', 'like', "%{$q}%")
                            ->orWhere('recipient_name', 'like', "%{$q}%");
                    }))
                    ->orderBy('id')
                    ->each(function (Piagam $piagam) use ($output, &$rowNumber): void {
                        $status = $piagam->recipient_name ? 'Diambil: '.$piagam->recipient_name : 'Belum Diambil';
                        fputcsv($output, [
                            $rowNumber++,
                            'Piagam',
                            $this->sanitizeCell($piagam->student_name),
                            $this->sanitizeCell($piagam->school?->name ?? '-'),
                            $this->sanitizeCell($piagam->competitionCategory?->name ?? '-'),
                            (string) $piagam->competition_year,
                            $piagam->created_at ? $piagam->created_at->format('Y-m-d') : '',
                            $status,
                            $this->sanitizeCell($piagam->createdBy?->name ?? 'Sistem'),
                            $this->sanitizeCell($piagam->division?->name ?? '-'),
                        ]);
                    });
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function sanitizeCell(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'{$value}" : $value;
    }

    public function pdf(Request $request, DivisionContext $divisionContext): Response
    {
        $user = $request->user();
        $divisionId = $divisionContext->activeDivisionId($request, $user);

        if ($user->role === 'super_admin' && $request->filled('division_id')) {
            $divisionId = (int) $request->input('division_id');
        }

        $type = $request->input('type', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $month = $request->input('month');
        $year = $request->input('year');
        $q = $request->input('q');

        $activeDivision = $divisionId ? Division::find($divisionId) : null;

        $incomingLetters = collect();
        $outgoingLetters = collect();
        $piagams = collect();

        if ($type === 'incoming' || $type === 'all') {
            $incomingLetters = IncomingLetter::query()
                ->with(['division', 'createdBy', 'attachments'])
                ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                ->when($dateFrom, fn ($builder) => $builder->whereDate('letter_date', '>=', $dateFrom))
                ->when($dateTo, fn ($builder) => $builder->whereDate('letter_date', '<=', $dateTo))
                ->when($year, fn ($builder) => $builder->whereYear('letter_date', $year))
                ->when($month, fn ($builder) => $builder->whereMonth('letter_date', $month))
                ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                    $sub->where('letter_number', 'like', "%{$q}%")
                        ->orWhere('subject', 'like', "%{$q}%")
                        ->orWhere('origin_agency', 'like', "%{$q}%");
                }))
                ->orderBy('id')
                ->get();
        }

        if ($type === 'outgoing' || $type === 'all') {
            $outgoingLetters = OutgoingLetter::query()
                ->with(['division', 'createdBy', 'attachments'])
                ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                ->when($dateFrom, fn ($builder) => $builder->whereDate('letter_date', '>=', $dateFrom))
                ->when($dateTo, fn ($builder) => $builder->whereDate('letter_date', '<=', $dateTo))
                ->when($year, fn ($builder) => $builder->where('letter_year', $year))
                ->when($month, fn ($builder) => $builder->whereMonth('letter_date', $month))
                ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                    $sub->where('letter_number', 'like', "%{$q}%")
                        ->orWhere('subject', 'like', "%{$q}%");
                }))
                ->orderBy('id')
                ->get();
        }

        if ($type === 'piagam' || $type === 'all') {
            $piagams = Piagam::query()
                ->with(['division', 'school', 'competitionCategory', 'pickupCategory', 'createdBy'])
                ->when($divisionId !== null, fn ($builder) => $builder->where('division_id', $divisionId))
                ->when($dateFrom, fn ($builder) => $builder->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn ($builder) => $builder->whereDate('created_at', '<=', $dateTo))
                ->when($year, fn ($builder) => $builder->where('competition_year', $year))
                ->when($month, fn ($builder) => $builder->whereMonth('created_at', $month))
                ->when($q, fn ($builder) => $builder->where(function ($sub) use ($q) {
                    $sub->where('student_name', 'like', "%{$q}%")
                        ->orWhere('recipient_name', 'like', "%{$q}%");
                }))
                ->orderBy('id')
                ->get();
        }

        $filename = 'sisurat-laporan-'.($type ?: 'all').'-'.now()->format('Ymd-His').'.pdf';

        $pdf = Pdf::loadView('reports.pdf', [
            'type' => $type,
            'activeDivision' => $activeDivision,
            'printedBy' => $user,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'year' => $year,
            'month' => $month,
            'incomingLetters' => $incomingLetters,
            'outgoingLetters' => $outgoingLetters,
            'piagams' => $piagams,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
