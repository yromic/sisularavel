<?php

namespace App\Console\Commands;

use App\Services\Backup\GoogleDriveStorageService;
use App\Services\Backup\SpreadsheetBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupSpreadsheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sisurat:backup-spreadsheet
                            {--upload : Upload the generated Excel spreadsheet to Google Drive}
                            {--output= : Custom local output path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate human-readable 3-sheet Excel backup spreadsheet with Google Drive links';

    /**
     * Execute the console command.
     */
    public function handle(
        SpreadsheetBackupService $spreadsheetService,
        GoogleDriveStorageService $googleDrive
    ): int {
        $this->info('Mengompilasi berkas rekapitulasi Excel SISURAT (Surat Masuk, Surat Keluar, Piagam)...');

        $filename = 'Rekap_Cadangan_SISURAT_'.now()->format('Y-m-d_His').'.xls';
        $outputPath = $this->option('output') ?: storage_path('app/private/backups/'.$filename);

        $spreadsheetService->saveWorkbookToFile($outputPath);
        $sizeBytes = File::size($outputPath);
        $sizeMb = round($sizeBytes / (1024 * 1024), 2);

        $this->info("Berkas Excel berhasil dibuat: {$outputPath} ({$sizeMb} MB)");

        if ($this->option('upload')) {
            if (! $googleDrive->isConfigured()) {
                $this->warn('Google Drive tidak dikonfigurasi. Pengunggahan dilewati.');

                return self::SUCCESS;
            }

            $this->line('Mengunggah berkas Excel ke folder Google Drive instansi...');
            $result = $googleDrive->uploadSingleFile(
                $outputPath,
                'Rekap_Cadangan_SISURAT_Terbaru.xls',
                null,
                'application/vnd.ms-excel',
                true
            );

            $this->info("Berhasil diunggah ke Google Drive! Tautan akses: {$result['view_url']}");
        }

        return self::SUCCESS;
    }
}
