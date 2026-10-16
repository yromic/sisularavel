<?php

namespace App\Console\Commands;

use App\Services\Backup\AttachmentDriveSyncService;
use Illuminate\Console\Command;

class SyncDriveFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sisurat:sync-drive-files 
                            {--chunk=50 : Number of files to sync per batch}
                            {--max-chunks=10 : Maximum number of batches to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize un-synced attachments and signatures to Google Drive instansi folders';

    /**
     * Execute the console command.
     */
    public function handle(AttachmentDriveSyncService $syncService): int
    {
        $this->info('Memulai sinkronisasi berkas lampiran ke Google Drive...');

        $stats = $syncService->getSyncStats();
        $this->line("Total berkas: {$stats['total_files']} | Tersinkron: {$stats['synced_files']} | Tertunda: {$stats['pending_files']}");

        if ($stats['is_fully_synced']) {
            $this->info('Seluruh berkas sudah tersinkron ke Google Drive.');

            return self::SUCCESS;
        }

        $chunk = (int) $this->option('chunk');
        $maxChunks = (int) $this->option('max-chunks');

        $result = $syncService->syncAll($chunk, $maxChunks);

        if ($result['status'] === 'skipped') {
            $this->warn("Sinkronisasi dilewati: {$result['reason']}");

            return self::FAILURE;
        }

        $this->info("Sinkronisasi selesai! Berhasil: {$result['synced']}, Gagal: {$result['failed']}, Dilewati: {$result['skipped']}");

        if (! empty($result['errors'])) {
            $this->warn('Beberapa galat ditemukan saat proses:');
            foreach ($result['errors'] as $err) {
                $this->error(" - {$err}");
            }
        }

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
