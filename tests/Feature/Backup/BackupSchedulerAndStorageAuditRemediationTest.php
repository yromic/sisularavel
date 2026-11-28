<?php

namespace Tests\Feature\Backup;

use App\Models\Attachment;
use App\Models\BackupRun;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Services\Backup\AttachmentDriveSyncService;
use App\Services\Backup\BackupPreflightService;
use App\Services\Backup\GoogleDriveStorageService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class BackupSchedulerAndStorageAuditRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $testDirs = [
            storage_path('app/private/documents/test_remediation'),
            storage_path('app/private/backups'),
            storage_path('app/private/backup-temp'),
        ];

        foreach ($testDirs as $dir) {
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }

        Mockery::close();
        parent::tearDown();
    }

    public function test_backup_run_duration_seconds_accessor(): void
    {
        $run = new BackupRun([
            'started_at' => now()->subSeconds(45),
            'completed_at' => now(),
        ]);
        $this->assertEquals(45, $run->duration_seconds);

        $pendingRun = new BackupRun([
            'started_at' => now(),
            'completed_at' => null,
        ]);
        $this->assertNull($pendingRun->duration_seconds);
    }

    public function test_dashboard_renders_otomatis_for_scheduled_and_manual_for_manual_trigger(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

        BackupRun::query()->create([
            'backup_id' => 'bkp-sched-001',
            'type' => 'full',
            'trigger' => 'scheduled',
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(10)->addSeconds(12),
            'archive_size_bytes' => 2097152, // 2.0 MB
            'files_count' => 15,
        ]);

        BackupRun::query()->create([
            'backup_id' => 'bkp-man-002',
            'type' => 'database',
            'trigger' => 'manual',
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(5)->addSeconds(3),
            'archive_size_bytes' => 524288,
            'files_count' => 1,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/backups');

        $response->assertStatus(200);
        $response->assertSee('Otomatis');
        $response->assertSee('Manual');
        $response->assertSee('Lama: 12 detik');
        $response->assertSee('Lama: 3 detik');
        $response->assertSee('15 berkas terarsip');
        $response->assertDontSee('Data: 0 · Berkas: 0');
    }

    public function test_attachment_drive_sync_resolves_fallback_path_and_uploads(): void
    {
        $division = Division::factory()->create();
        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '500/TEST/2026',
            'agenda_number' => '001',
            'origin_agency' => 'Dinas Pendidikan',
            'subject' => 'Surat Pengujian Sync',
            'letter_date' => now()->toDateString(),
            'received_date' => now()->toDateString(),
            'created_by' => 1,
        ]);

        // Simpan file fisik di storage/app/private/documents/test_remediation/sample.pdf
        $relPathOnDisk = 'test_remediation/sample.pdf';
        Storage::disk('private_documents')->put($relPathOnDisk, 'fake-pdf-content');

        // Path di database sengaja dibuat memiliki prefiks 'documents/' yang redundant
        $redundantDbPath = 'documents/test_remediation/sample.pdf';

        $attachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => $redundantDbPath,
            'original_name' => 'surat_asli.pdf',
            'file_size_bytes' => 16,
            'mime_type' => 'application/pdf',
            'is_missing' => false,
        ]);

        $mockGoogleDrive = Mockery::mock(GoogleDriveStorageService::class);
        $mockGoogleDrive->shouldReceive('isConfigured')->andReturn(true);
        $mockGoogleDrive->shouldReceive('ensureFolder')->andReturn('folder_drive_123');
        $mockGoogleDrive->shouldReceive('uploadSingleFile')
            ->once()
            ->withArgs(function ($localPath, $filename, $folderId, $mime) {
                return File::exists($localPath) && str_ends_with($filename, '.pdf');
            })
            ->andReturn([
                'file_id' => 'drive_file_abc_123',
                'view_url' => 'https://drive.google.com/file/d/drive_file_abc_123/view',
            ]);

        $syncService = new AttachmentDriveSyncService($mockGoogleDrive);
        $result = $syncService->syncBatch(10);

        $this->assertEquals(1, $result['synced']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);

        $attachment->refresh();
        $this->assertEquals('drive_file_abc_123', $attachment->google_drive_file_id);
        $this->assertEquals('https://drive.google.com/file/d/drive_file_abc_123/view', $attachment->google_drive_view_url);
    }

    public function test_attachment_drive_sync_retry_missing_clears_is_missing_flag(): void
    {
        $division = Division::factory()->create();
        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '501/TEST/2026',
            'agenda_number' => '002',
            'origin_agency' => 'Dinas Pendidikan',
            'subject' => 'Surat Pengujian Retry Missing',
            'letter_date' => now()->toDateString(),
            'received_date' => now()->toDateString(),
            'created_by' => 1,
        ]);

        $relPathOnDisk = 'test_remediation/recovered.pdf';
        Storage::disk('private_documents')->put($relPathOnDisk, 'recovered-content');

        $attachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => 'test_remediation/recovered.pdf',
            'original_name' => 'recovered.pdf',
            'file_size_bytes' => 17,
            'mime_type' => 'application/pdf',
            'is_missing' => true,
            'migration_status' => 'missing_drive_file',
            'missing_reason' => 'Previously skipped',
        ]);

        $mockGoogleDrive = Mockery::mock(GoogleDriveStorageService::class);
        $mockGoogleDrive->shouldReceive('isConfigured')->andReturn(true);
        $mockGoogleDrive->shouldReceive('ensureFolder')->andReturn('folder_drive_123');
        $mockGoogleDrive->shouldReceive('uploadSingleFile')
            ->once()
            ->andReturn([
                'file_id' => 'drive_recovered_999',
                'view_url' => 'https://drive.google.com/file/d/drive_recovered_999/view',
            ]);

        $syncService = new AttachmentDriveSyncService($mockGoogleDrive);
        $result = $syncService->syncBatch(10, retryMissing: true);

        $this->assertEquals(1, $result['synced']);
        $attachment->refresh();
        $this->assertFalse($attachment->is_missing);
        $this->assertNull($attachment->missing_reason);
        $this->assertEquals('migrated', $attachment->migration_status);
        $this->assertEquals('drive_recovered_999', $attachment->google_drive_file_id);
    }

    public function test_hosting_storage_metrics_evaluates_against_configured_quota(): void
    {
        config(['backup.hosting_quota_mb' => 4096]); // 4 GB

        $preflight = app(BackupPreflightService::class);
        $metrics = $preflight->getHostingStorageMetrics();

        $this->assertEquals(4096, $metrics['quota_mb']);
        $this->assertEquals(4096 * 1024 * 1024, $metrics['total_bytes']);
        $this->assertStringContainsString('4 GB', $metrics['total_formatted']);
        $this->assertArrayHasKey('storage_bytes', $metrics);
        $this->assertArrayHasKey('database_bytes', $metrics);
        $this->assertArrayHasKey('used_percent', $metrics);
        $this->assertEquals('normal', $metrics['status']);

        // Check preflight checkDiskCapacity also utilizes these metrics
        $diskCheck = $preflight->checkDiskCapacity();
        $this->assertEquals('PASS', $diskCheck['check']['status']);
        $this->assertFalse($diskCheck['check']['critical']);
    }

    public function test_scheduler_registers_sync_drive_files_and_log_redirection(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        // 1. Verify sisurat:sync-drive-files is registered
        $syncEvent = $events->first(function (Event $event) {
            return str_contains($event->command, 'sisurat:sync-drive-files');
        });
        $this->assertNotNull($syncEvent, 'sisurat:sync-drive-files must be registered in scheduler.');
        $this->assertEquals('0 * * * *', $syncEvent->expression); // Hourly

        // 2. Verify backup command uses backup-last.log
        $backupEvent = $events->first(function (Event $event) {
            return str_contains($event->command, 'sisurat:backup');
        });
        $this->assertNotNull($backupEvent);
        $this->assertEquals('0 2 * * *', $backupEvent->expression);
        $this->assertStringContainsString('backup-last.log', $backupEvent->output);
    }

    public function test_create_backup_cli_logs_error_and_preserves_failed_state_on_failure(): void
    {
        Log::shouldReceive('error')->atLeast()->once();

        // Set impossible critical threshold to force preflight check failure
        config(['backup.thresholds.disk_critical_percent' => 0.0001]);

        $this->artisan('sisurat:backup', [
            '--type' => 'full',
            '--trigger' => 'scheduled',
        ])->assertExitCode(1);

        // Verify failed record exists in backup_runs
        $failedRun = BackupRun::query()->where('trigger', 'scheduled')->latest('id')->first();
        $this->assertNotNull($failedRun);
        $this->assertEquals('failed', $failedRun->status);
        $this->assertStringContainsString('Preflight checks failed', $failedRun->error_message);
    }
}
