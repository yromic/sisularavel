<?php

namespace Tests\Feature\Backup;

use App\Models\BackupRun;
use App\Models\Division;
use App\Models\User;
use App\Services\Backup\BackupConsistencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupAdminDashboardContractTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Clean up test backups and staging temp files
        $backupsDir = storage_path('app/private/backups');
        if (File::isDirectory($backupsDir)) {
            File::deleteDirectory($backupsDir);
        }

        $tempDir = storage_path('app/private/backup-temp');
        if (File::isDirectory($tempDir)) {
            File::deleteDirectory($tempDir);
        }

        // Release any leftover consistency lock
        app(BackupConsistencyService::class)->releaseLock();

        parent::tearDown();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin/backups');
        $response->assertRedirect('/login');
    }

    public function test_division_admin_and_operator_forbidden(): void
    {
        $division = Division::factory()->create();
        $divAdmin = User::factory()->for($division)->create(['role' => 'division_admin']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        // 1. Division Admin is forbidden (HTTP 403)
        $this->actingAs($divAdmin)->get('/admin/backups')->assertStatus(403);
        $this->actingAs($divAdmin)->post('/admin/backups')->assertStatus(403);
        $this->actingAs($divAdmin)->post('/admin/backups/prune')->assertStatus(403);
        $this->actingAs($divAdmin)->get('/admin/backups/bkp-20261016-120000/download')->assertStatus(403);

        // 2. Operator is forbidden (HTTP 403)
        $this->actingAs($operator)->get('/admin/backups')->assertStatus(403);
        $this->actingAs($operator)->post('/admin/backups')->assertStatus(403);
        $this->actingAs($operator)->post('/admin/backups/prune')->assertStatus(403);
        $this->actingAs($operator)->get('/admin/backups/bkp-20261016-120000/download')->assertStatus(403);
    }

    public function test_super_admin_can_access_dashboard_html(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

        // Seed sample backup run
        BackupRun::query()->create([
            'backup_id' => 'bkp-20261016-100000',
            'type' => 'full',
            'trigger' => 'scheduled',
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addSeconds(5),
            'duration_seconds' => 5,
            'archive_size_bytes' => 1048576,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/backups');

        $response->assertStatus(200);
        $response->assertViewIs('admin.backups.index');
        $response->assertSee('Backup & Disaster Recovery');
        $response->assertSee('Kapasitas SSD Hosting');
        $response->assertSee('Replikasi Off-site Cloud');
        $response->assertSee('bkp-20261016-100000');
    }

    public function test_super_admin_can_access_dashboard_json(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

        $response = $this->actingAs($superAdmin)
            ->getJson('/admin/backups');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'storage' => ['total_bytes', 'used_bytes', 'free_bytes', 'used_percent', 'status'],
            'remote' => ['enabled', 'provider', 'folder_id'],
            'stats' => ['total_completed', 'total_failed'],
            'backups' => ['data', 'total', 'per_page'],
        ]);
    }

    public function test_super_admin_can_trigger_manual_backup_successfully(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

        $response = $this->actingAs($superAdmin)->post('/admin/backups', [
            'type' => 'database',
            'upload' => false,
        ]);

        $response->assertRedirect('/admin/backups');
        $response->assertSessionHas('success');

        // Check persisted backup run
        $this->assertDatabaseHas('backup_runs', [
            'type' => 'database',
            'trigger' => 'manual',
            'requested_by' => $superAdmin->id,
            'status' => 'completed',
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'event_code' => 'BACKUP_MANUAL_TRIGGERED',
        ]);
    }

    public function test_super_admin_can_trigger_manual_backup_json(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

        $response = $this->actingAs($superAdmin)->postJson('/admin/backups', [
            'type' => 'database',
            'upload' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'type' => 'database',
            'trigger_type' => 'manual',
        ]);
    }

    public function test_super_admin_can_download_encrypted_backup_archive(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);
        $backupId = 'bkp-20261016-150000';

        // Prepare local dummy encrypted archive
        $backupsDir = storage_path('app/private/backups');
        if (! File::isDirectory($backupsDir)) {
            File::makeDirectory($backupsDir, 0755, true);
        }
        $archivePath = "{$backupsDir}/{$backupId}.enc";
        File::put($archivePath, 'SISURAT_ENCRYPTED_DUMMY_PAYLOAD_TEST');

        BackupRun::query()->create([
            'backup_id' => $backupId,
            'type' => 'full',
            'trigger' => 'manual',
            'requested_by' => $superAdmin->id,
            'status' => 'completed',
            'started_at' => now(),
            'archive_size_bytes' => strlen('SISURAT_ENCRYPTED_DUMMY_PAYLOAD_TEST'),
        ]);

        $response = $this->actingAs($superAdmin)->get("/admin/backups/{$backupId}/download");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', "attachment; filename={$backupId}.enc");
        $this->assertEquals('SISURAT_ENCRYPTED_DUMMY_PAYLOAD_TEST', $response->streamedContent());

        // Audit log verified
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'event_code' => 'BACKUP_ARCHIVE_DOWNLOADED',
        ]);
    }

    public function test_download_validates_backup_id_format_and_rejects_path_traversal(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

        // Invalid format
        $this->actingAs($superAdmin)->get('/admin/backups/invalid-id/download')->assertStatus(400);

        // Path traversal attempt
        $this->actingAs($superAdmin)->get('/admin/backups/..%2F..%2Fetc%2Fpasswd/download')->assertStatus(400);
    }

    public function test_download_returns_404_if_local_file_missing(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);
        $backupId = 'bkp-20261016-160000';

        BackupRun::query()->create([
            'backup_id' => $backupId,
            'type' => 'full',
            'trigger' => 'scheduled',
            'status' => 'completed',
            'started_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get("/admin/backups/{$backupId}/download")
            ->assertStatus(404);
    }

    public function test_super_admin_can_trigger_prune_via_web(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

        $response = $this->actingAs($superAdmin)->post('/admin/backups/prune', [
            'dry_run' => true,
        ]);

        $response->assertRedirect('/admin/backups');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'event_code' => 'BACKUP_RETENTION_PRUNED',
        ]);
    }

    public function test_super_admin_can_trigger_prune_json(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

        $response = $this->actingAs($superAdmin)->postJson('/admin/backups/prune', [
            'dry_run' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'dry_run' => true,
        ]);
    }
}
