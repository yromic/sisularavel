<?php

namespace App\Services\Backup;

use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetBackupService
{
    /**
     * Generate complete SpreadsheetML (Excel XML) workbook content as string.
     */
    public function generateWorkbookXml(?int $divisionId = null): string
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<?mso-application progid="Excel.Sheet"?>';
        $xml[] = '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml[] = ' xmlns:o="urn:schemas-microsoft-com:office:office"';
        $xml[] = ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
        $xml[] = ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml[] = ' xmlns:html="http://www.w3.org/TR/REC-html40">';

        // Styles
        $xml[] = ' <Styles>';
        $xml[] = '  <Style ss:ID="Default" ss:Name="Normal">';
        $xml[] = '   <Alignment ss:Vertical="Center"/>';
        $xml[] = '   <Borders/>';
        $xml[] = '   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>';
        $xml[] = '   <Interior/>';
        $xml[] = '   <NumberFormat/>';
        $xml[] = '   <Protection/>';
        $xml[] = '  </Style>';
        $xml[] = '  <Style ss:ID="HeaderStyle">';
        $xml[] = '   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        $xml[] = '   <Borders>';
        $xml[] = '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/>';
        $xml[] = '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/>';
        $xml[] = '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/>';
        $xml[] = '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/>';
        $xml[] = '   </Borders>';
        $xml[] = '   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#1E3A8A" ss:Bold="1"/>';
        $xml[] = '   <Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/>';
        $xml[] = '  </Style>';
        $xml[] = '  <Style ss:ID="DataStyle">';
        $xml[] = '   <Alignment ss:Vertical="Center"/>';
        $xml[] = '   <Borders>';
        $xml[] = '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '   </Borders>';
        $xml[] = '   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="10" ss:Color="#111827"/>';
        $xml[] = '  </Style>';
        $xml[] = '  <Style ss:ID="CenterStyle">';
        $xml[] = '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        $xml[] = '   <Borders>';
        $xml[] = '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '   </Borders>';
        $xml[] = '   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="10" ss:Color="#111827"/>';
        $xml[] = '  </Style>';
        $xml[] = '  <Style ss:ID="LinkStyle">';
        $xml[] = '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        $xml[] = '   <Borders>';
        $xml[] = '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>';
        $xml[] = '   </Borders>';
        $xml[] = '   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="10" ss:Color="#1D4ED8" ss:Underline="Single" ss:Bold="1"/>';
        $xml[] = '  </Style>';
        $xml[] = ' </Styles>';

        // Sheet 1: Surat Masuk
        $xml[] = $this->buildIncomingLettersSheet($divisionId);

        // Sheet 2: Surat Keluar
        $xml[] = $this->buildOutgoingLettersSheet($divisionId);

        // Sheet 3: Ekspedisi Piagam
        $xml[] = $this->buildPiagamSheet($divisionId);

        $xml[] = '</Workbook>';

        return implode("\n", $xml);
    }

    /**
     * Save generated workbook XML to a destination file path.
     */
    public function saveWorkbookToFile(string $destinationPath, ?int $divisionId = null): string
    {
        $dir = dirname($destinationPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $xmlContent = $this->generateWorkbookXml($divisionId);
        File::put($destinationPath, $xmlContent);

        return $destinationPath;
    }

    /**
     * Create an HTTP StreamedResponse downloading the multi-sheet Excel file.
     */
    public function streamDownloadResponse(?int $divisionId = null, ?string $filename = null): StreamedResponse
    {
        $name = $filename ?: 'Rekap_Cadangan_SISURAT_'.now()->format('Y-m-d_His').'.xls';

        return response()->streamDownload(function () use ($divisionId): void {
            echo $this->generateWorkbookXml($divisionId);
        }, $name, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ]);
    }

    /**
     * Build Sheet 1: Buku Agenda Surat Masuk.
     */
    protected function buildIncomingLettersSheet(?int $divisionId = null): string
    {
        $out = [];
        $out[] = ' <Worksheet ss:Name="Surat Masuk">';
        $out[] = '  <Table ss:DefaultRowHeight="20">';
        $out[] = '   <Column ss:Width="40"/>';  // No
        $out[] = '   <Column ss:Width="130"/>'; // Divisi
        $out[] = '   <Column ss:Width="160"/>'; // Nomor Surat
        $out[] = '   <Column ss:Width="180"/>'; // Asal Pengirim
        $out[] = '   <Column ss:Width="85"/>';  // Tanggal Surat
        $out[] = '   <Column ss:Width="85"/>';  // Tanggal Diterima
        $out[] = '   <Column ss:Width="250"/>'; // Perihal
        $out[] = '   <Column ss:Width="110"/>'; // Petugas Input
        $out[] = '   <Column ss:Width="120"/>'; // Waktu Pencatatan
        $out[] = '   <Column ss:Width="220"/>'; // Link Google Drive

        // Header
        $out[] = '   <Row ss:Height="26">';
        $headers = [
            'No',
            'Divisi / Bidang',
            'Nomor Surat',
            'Asal Pengirim',
            'Tanggal Surat',
            'Tanggal Diterima',
            'Perihal',
            'Petugas Input',
            'Waktu Pencatatan',
            'Tautan Berkas Surat (Google Drive)',
        ];
        foreach ($headers as $h) {
            $out[] = '    <Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">'.$this->escapeXml($h).'</Data></Cell>';
        }
        $out[] = '   </Row>';

        // Data Rows
        $query = IncomingLetter::query()
            ->with(['division', 'createdBy', 'attachments'])
            ->when($divisionId !== null, fn ($b) => $b->where('division_id', $divisionId))
            ->orderBy('id');

        $rowNo = 1;
        $query->lazy(200)->each(function (IncomingLetter $letter) use (&$out, &$rowNo): void {
            $attachment = $letter->attachments->first();
            $driveUrl = $attachment?->drive_view_link;

            $out[] = '   <Row>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="Number">'.$rowNo++.'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->division?->name ?? '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->letter_number).'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->origin_agency).'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">'.($letter->letter_date ? $letter->letter_date->format('d/m/Y') : '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">'.($letter->received_date ? $letter->received_date->format('d/m/Y') : '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->subject).'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->createdBy?->name ?? 'Sistem').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">'.($letter->created_at ? $letter->created_at->format('d/m/Y H:i') : '-').'</Data></Cell>';

            if (! empty($driveUrl)) {
                $out[] = '    <Cell ss:StyleID="LinkStyle" ss:HRef="'.$this->escapeXml($driveUrl).'"><Data ss:Type="String">Buka Berkas di Google Drive</Data></Cell>';
            } elseif ($attachment && ! $attachment->is_missing) {
                $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">[Tersimpan Lokal - Belum Sinkron Cloud]</Data></Cell>';
            } else {
                $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">[Tanpa Lampiran]</Data></Cell>';
            }

            $out[] = '   </Row>';
        });

        $out[] = '  </Table>';
        $out[] = ' </Worksheet>';

        return implode("\n", $out);
    }

    /**
     * Build Sheet 2: Buku Agenda Surat Keluar.
     */
    protected function buildOutgoingLettersSheet(?int $divisionId = null): string
    {
        $out = [];
        $out[] = ' <Worksheet ss:Name="Surat Keluar">';
        $out[] = '  <Table ss:DefaultRowHeight="20">';
        $out[] = '   <Column ss:Width="40"/>';  // No
        $out[] = '   <Column ss:Width="130"/>'; // Divisi
        $out[] = '   <Column ss:Width="160"/>'; // Nomor Surat
        $out[] = '   <Column ss:Width="85"/>';  // Tanggal Surat
        $out[] = '   <Column ss:Width="250"/>'; // Perihal
        $out[] = '   <Column ss:Width="200"/>'; // Tujuan / Diseminasi
        $out[] = '   <Column ss:Width="110"/>'; // Petugas Input
        $out[] = '   <Column ss:Width="120"/>'; // Waktu Terbit
        $out[] = '   <Column ss:Width="220"/>'; // Link Google Drive

        // Header
        $out[] = '   <Row ss:Height="26">';
        $headers = [
            'No',
            'Divisi / Bidang',
            'Nomor Surat',
            'Tanggal Surat',
            'Perihal',
            'Tujuan / Diseminasi',
            'Petugas Input',
            'Waktu Terbit',
            'Tautan Berkas Surat (Google Drive)',
        ];
        foreach ($headers as $h) {
            $out[] = '    <Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">'.$this->escapeXml($h).'</Data></Cell>';
        }
        $out[] = '   </Row>';

        // Data Rows
        $query = OutgoingLetter::query()
            ->with(['division', 'createdBy', 'attachments'])
            ->when($divisionId !== null, fn ($b) => $b->where('division_id', $divisionId))
            ->orderBy('id');

        $rowNo = 1;
        $query->lazy(200)->each(function (OutgoingLetter $letter) use (&$out, &$rowNo): void {
            $attachment = $letter->attachments->first();
            $driveUrl = $attachment?->drive_view_link;

            $out[] = '   <Row>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="Number">'.$rowNo++.'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->division?->name ?? '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->letter_number).'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">'.($letter->letter_date ? $letter->letter_date->format('d/m/Y') : '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->subject).'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->recipient_text ?? '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($letter->createdBy?->name ?? 'Sistem').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">'.($letter->created_at ? $letter->created_at->format('d/m/Y H:i') : '-').'</Data></Cell>';

            if (! empty($driveUrl)) {
                $out[] = '    <Cell ss:StyleID="LinkStyle" ss:HRef="'.$this->escapeXml($driveUrl).'"><Data ss:Type="String">Buka Berkas di Google Drive</Data></Cell>';
            } elseif ($attachment && ! $attachment->is_missing) {
                $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">[Tersimpan Lokal - Belum Sinkron Cloud]</Data></Cell>';
            } else {
                $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">[Tanpa Lampiran]</Data></Cell>';
            }

            $out[] = '   </Row>';
        });

        $out[] = '  </Table>';
        $out[] = ' </Worksheet>';

        return implode("\n", $out);
    }

    /**
     * Build Sheet 3: Buku Ekspedisi Digital Piagam Penghargaan.
     */
    protected function buildPiagamSheet(?int $divisionId = null): string
    {
        $out = [];
        $out[] = ' <Worksheet ss:Name="Ekspedisi Piagam">';
        $out[] = '  <Table ss:DefaultRowHeight="20">';
        $out[] = '   <Column ss:Width="65"/>';  // No Resi
        $out[] = '   <Column ss:Width="120"/>'; // Tanggal Ambil
        $out[] = '   <Column ss:Width="130"/>'; // Divisi Penyerah
        $out[] = '   <Column ss:Width="180"/>'; // Asal Sekolah / Instansi
        $out[] = '   <Column ss:Width="200"/>'; // Nama Siswa / Kegiatan
        $out[] = '   <Column ss:Width="160"/>'; // Nama Penerima Tamu
        $out[] = '   <Column ss:Width="150"/>'; // Jabatan / Status
        $out[] = '   <Column ss:Width="110"/>'; // Nomor Telepon / WA
        $out[] = '   <Column ss:Width="220"/>'; // Link Google Drive

        // Header
        $out[] = '   <Row ss:Height="26">';
        $headers = [
            'No Resi',
            'Tanggal Ambil',
            'Divisi Penyerah',
            'Asal Sekolah / Instansi',
            'Nama Siswa / Kegiatan',
            'Nama Penerima Tamu',
            'Jabatan / Status',
            'Nomor Telepon / WA',
            'Tautan Tanda Tangan (Google Drive)',
        ];
        foreach ($headers as $h) {
            $out[] = '    <Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">'.$this->escapeXml($h).'</Data></Cell>';
        }
        $out[] = '   </Row>';

        // Data Rows
        $query = Piagam::query()
            ->with(['division', 'school', 'workUnit', 'attachments'])
            ->when($divisionId !== null, fn ($b) => $b->where('division_id', $divisionId))
            ->orderBy('id');

        $query->lazy(200)->each(function (Piagam $piagam) use (&$out): void {
            $attachment = $piagam->attachments->first();
            $driveUrl = $attachment?->drive_view_link;
            $receiptNo = '#'.str_pad((string) $piagam->id, 4, '0', STR_PAD_LEFT);

            $out[] = '   <Row>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">'.$receiptNo.'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">'.($piagam->updated_at ? $piagam->updated_at->format('d/m/Y H:i') : '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($piagam->division?->name ?? '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($piagam->school_display_name).'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($piagam->piagam_display_name).'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($piagam->recipient_name ?? '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="DataStyle"><Data ss:Type="String">'.$this->escapeXml($piagam->recipient_position ?? '-').'</Data></Cell>';
            $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">'.$this->escapeXml($piagam->recipient_unit ?? '-').'</Data></Cell>';

            if (! empty($driveUrl)) {
                $out[] = '    <Cell ss:StyleID="LinkStyle" ss:HRef="'.$this->escapeXml($driveUrl).'"><Data ss:Type="String">Lihat Tanda Tangan di Google Drive</Data></Cell>';
            } elseif ($attachment && ! $attachment->is_missing) {
                $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">[Tersimpan Lokal - Belum Sinkron Cloud]</Data></Cell>';
            } else {
                $out[] = '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">[Belum Ditandatangani]</Data></Cell>';
            }

            $out[] = '   </Row>';
        });

        $out[] = '  </Table>';
        $out[] = ' </Worksheet>';

        return implode("\n", $out);
    }

    /**
     * Escape XML string safely for SpreadsheetML.
     */
    protected function escapeXml(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_XML1, 'UTF-8');
    }
}
