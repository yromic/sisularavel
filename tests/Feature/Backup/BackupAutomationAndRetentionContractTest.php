<?php

namespace Tests\Feature\Backup;

use App\Models\BackupRun;
use App\Models\Division;
use App\Models\User;
use App\Services\Backup\BackupRetentionService;
use App\Services\Backup\GoogleDriveStorageService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class BackupAutomationAndRetentionContractTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $backupsDir = storage_path('app/private/backups');
        if (File::isDirectory($backupsDir)) {
            File::deleteDirectory($backupsDir);
        }

        parent::tearDown();
    }

    public function test_retention_service_enforces_golden_rule_when_single_backup(): void
    {
        $division = Division::factory()->create();
        $user = User::factory()->for($division)->create();

        BackupRun::query()->create([
            'backup_id' => 'bkp-solo-001',
            'type' => 'full',
            'trigger' => 'manual',
            'requested_by' => $user->id,
            'status' => 'completed',
            'started_at' => now()->subDays(20),
            'completed_at' => now()->subDays(20)->addMinutes(2),
            'archive_size_bytes' => 1048576,
        ]);

        $service = app(BackupRetentionService::class);
        $plan = $service->planRetention();

        $this->assertCount(1, $plan['to_keep']);
        $this->assertCount(0, $plan['to_prune']);
        $this->assertStringContainsString('Golden Rule', $plan['reason']);
    }

    public function test_retention_service_enforces_golden_rule_when_latest_run_failed(): void
    {
        $division = Division::factory()->create();
        $user = User::factory()->for($division)->create();

        BackupRun::query()->create([
            'backup_id' => 'bkp-good-001',
            'type' => 'full',
            'trigger' => 'scheduled',
            'status' => 'completed',
            'started_at' => now()->subDays(30),
            'completed_at' => now()->subDays(30)->addMinutes(1),
            'archive_size_bytes' => 1048576,
        ]);

        BackupRun::query()->create([
            'backup_id' => 'bkp-good-002',
            'type' => 'full',
            'trigger' => 'scheduled',
            'status' => 'completed',
            'started_at' => now()->subDays(20),
            'completed_at' => now()->subDays(20)->addMinutes(1),
            'archive_size_bytes' => 1048576,
        ]);

        // Failed run on top of history
        BackupRun::query()->create([
            'backup_id' => 'bkp-failed-003',
            'type' => 'full',
            'trigger' => 'scheduled',
            'status' => 'failed',
            'started_at' => now()->subMinutes(10),
            'error_message' => 'Simulated disk warning abort',
        ]);

        $service = app(BackupRetentionService::class);
        $plan = $service->planRetention();

        $this->assertCount(2, $plan['to_keep']);
        $this->assertCount(0, $plan['to_prune']);
        $this->assertStringContainsString('Golden Rule', $plan['reason']);
        $this->assertStringContainsString('failed', $plan['reason']);
    }

    public function test_retention_service_daily_weekly_monthly_gfs_rotation(): void
    {
        config([
            'backup.retention.keep_daily_days' => 7,
            'backup.retention.keep_weekly_weeks' => 4,
            'backup.retention.keep_monthly_months' => 2,
        ]);

        $now = Carbon::parse('2026-10-16 15:00:00');
        Carbon::setTestNow($now);

        // 1. Create backups for the last 7 days (days 0 to 6)
        for ($i = 0; $i < 7; $i++) {
            BackupRun::query()->create([
                'backup_id' => "bkp-day-{$i}",
                'type' => 'full',
                'status' => 'completed',
                'started_at' => $now->copy()->subDays($i),
                'completed_at' => $now->copy()->subDays($i)->addMinutes(1),
                'archive_size_bytes' => 1000000,
            ]);
        }

        // 2. Create an earlier backup on the exact same day (October 14th, 4 hours earlier)
        BackupRun::query()->create([
            'backup_id' => 'bkp-day-2-earlier',
            'type' => 'full',
            'status' => 'completed',
            'started_at' => $now->copy()->subDays(2)->subHours(4),
            'completed_at' => $now->copy()->subDays(2)->subHours(4)->addMinutes(1),
            'archive_size_bytes' => 1000000,
        ]);

        // 3. Create an old backup 40 days ago that qualifies for monthly
        BackupRun::query()->create([
            'backup_id' => 'bkp-month-1',
            'type' => 'full',
            'status' => 'completed',
            'started_at' => $now->copy()->subDays(40),
            'completed_at' => $now->copy()->subDays(40)->addMinutes(1),
            'archive_size_bytes' => 1000000,
        ]);

        // 4. Create a very ancient backup (90 days ago) that exceeds daily, weekly, and 2-month threshold
        BackupRun::query()->create([
            'backup_id' => 'bkp-ancient-prunable',
            'type' => 'full',
            'status' => 'completed',
            'started_at' => $now->copy()->subDays(90),
            'completed_at' => $now->copy()->subDays(90)->addMinutes(1),
            'archive_size_bytes' => 1000000,
        ]);

        $service = app(BackupRetentionService::class);
        $plan = $service->planRetention();

        $pruneIds = $plan['to_prune']->pluck('backup_id')->all();
        $keepIds = $plan['to_keep']->pluck('backup_id')->all();

        $this->assertContains('bkp-day-0', $keepIds);
        $this->assertContains('bkp-day-6', $keepIds);
        $this->assertContains('bkp-month-1', $keepIds);

        // Multiple backups on day 2: older one should be pruned
        $this->assertContains('bkp-day-2-earlier', $pruneIds);

        // Ancient backup exceeds retention window: must be pruned
        $this->assertContains('bkp-ancient-prunable', $pruneIds);

        Carbon::setTestNow();
    }

    public function test_retention_service_prune_deletes_local_and_calls_remote_storage(): void
    {
        $mockDrive = Mockery::mock(GoogleDriveStorageService::class);
        $mockDrive->shouldReceive('deleteRemoteFile')
            ->once()
            ->with('google-file-id-to-delete')
            ->andReturn(true);

        $service = new BackupRetentionService($mockDrive);

        config(['backup.retention.keep_monthly_months' => 2]);

        $now = Carbon::parse('2026-10-16 02:00:00');
        Carbon::setTestNow($now);

        // Recent run to keep
        BackupRun::query()->create([
            'backup_id' => 'bkp-recent',
            'type' => 'full',
            'status' => 'completed',
            'started_at' => $now,
            'completed_at' => $now->addMinutes(1),
            'archive_size_bytes' => 500000,
        ]);

        // Old run to prune (100 days ago exceeds 2 months)
        $oldRun = BackupRun::query()->create([
            'backup_id' => 'bkp-to-prune',
            'type' => 'full',
            'status' => 'completed',
            'started_at' => $now->copy()->subDays(100),
            'completed_at' => $now->copy()->subDays(100)->addMinutes(1),
            'archive_size_bytes' => 750000,
            'remote_storage_provider' => 'google_drive',
            'remote_file_id' => 'google-file-id-to-delete',
        ]);

        // Place a dummy file in private storage
        $localPath = storage_path('app/private/backups/bkp-to-prune.enc');
        File::ensureDirectoryExists(dirname($localPath));
        File::put($localPath, 'DUMMY ENCRYPTED ARCHIVE DATA');
        $this->assertFileExists($localPath);

        // Run pruning (not dry-run)
        $result = $service->prune(false);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['pruned_count']);
        $this->assertSame(1, $result['kept_count']);

        // Assert local file deleted
        $this->assertFileDoesNotExist($localPath);

        // Assert database record deleted
        $this->assertDatabaseMissing('backup_runs', [
            'backup_id' => 'bkp-to-prune',
        ]);

        // Recent record is still in database
        $this->assertDatabaseHas('backup_runs', [
            'backup_id' => 'bkp-recent',
        ]);

        Carbon::setTestNow();
    }

    public function test_retention_service_dry_run_preserves_files_and_records(): void
    {
        config(['backup.retention.keep_monthly_months' => 2]);

        $now = Carbon::parse('2026-10-16 02:00:00');
        Carbon::setTestNow($now);

        BackupRun::query()->create([
            'backup_id' => 'bkp-recent-2',
            'type' => 'full',
            'status' => 'completed',
            'started_at' => $now,
            'archive_size_bytes' => 500000,
        ]);

        BackupRun::query()->create([
            'backup_id' => 'bkp-old-dry-run',
            'type' => 'full',
            'status' => 'completed',
            'started_at' => $now->copy()->subDays(120),
            'archive_size_bytes' => 500000,
        ]);

        $localPath = storage_path('app/private/backups/bkp-old-dry-run.enc');
        File::ensureDirectoryExists(dirname($localPath));
        File::put($localPath, 'DRY RUN DATA');

        $service = app(BackupRetentionService::class);
        $result = $service->prune(true); // dry-run

        $this->assertTrue($result['dry_run']);
        $this->assertSame(1, $result['pruned_count']);

        // Assert local file still exists
        $this->assertFileExists($localPath);

        // Assert DB record still exists
        $this->assertDatabaseHas('backup_runs', [
            'backup_id' => 'bkp-old-dry-run',
        ]);

        File::delete($localPath);
        Carbon::setTestNow();
    }

    public function test_artisan_prune_command_renders_table(): void
    {
        $this->artisan('sisurat:backup:prune', ['--dry-run' => true])
            ->expectsOutputToContain('SISURAT BACKUP RETENTION ENGINE')
            ->expectsOutputToContain('Evaluated Archives')
            ->assertExitCode(0);
    }

    public function test_artisan_prune_command_json_output(): void
    {
        $this->artisan('sisurat:backup:prune', ['--dry-run' => true, '--json' => true])
            ->assertSuccessful();
    }

    public function test_scheduler_contains_backup_and_prune_commands(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $commands = $events->map(function ($event) {
            return $event->command;
        })->filter()->all();

        $hasBackup = false;
        $hasPrune = false;
        $hasIdempotency = false;

        foreach ($commands as $cmd) {
            if (str_contains($cmd, 'sisurat:backup --type=full --upload')) {
                $hasBackup = true;
            }
            if (str_contains($cmd, 'sisurat:backup:prune')) {
                $hasPrune = true;
            }
            if (str_contains($cmd, 'sisurat:prune-idempotency')) {
                $hasIdempotency = true;
            }
        }

        $this->assertTrue($hasBackup, 'Scheduler does not contain daily backup command.');
        $this->assertTrue($hasPrune, 'Scheduler does not contain daily prune command.');
        $this->assertTrue($hasIdempotency, 'Scheduler does not contain hourly prune idempotency command.');
    }
}
