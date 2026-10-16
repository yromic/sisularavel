<?php

namespace Tests\Feature\Backup;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\Piagam;
use App\Models\User;
use App\Services\Backup\AttachmentDriveSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GoogleDriveIndividualSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $superAdmin;

    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'backup.remote.enabled' => true,
            'backup.remote.google_drive.client_id' => 'fake-client-id.apps.googleusercontent.com',
            'backup.remote.google_drive.client_secret' => 'fake-client-secret-xyz',
            'backup.remote.google_drive.refresh_token' => 'fake-refresh-token-123',
            'backup.remote.google_drive.folder_id' => 'ROOT_BACKUP_FOLDER_ID',
        ]);

        $this->division = Division::factory()->create(['name' => 'Bidang Pembinaan SD', 'code' => 'DIKDAS']);
        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->operator = User::factory()->for($this->division)->create(['role' => 'operator']);
    }

    public function test_drive_sync_service_uploads_un_synced_attachments_and_populates_view_url(): void
    {
        Storage::fake('private_documents');
        Storage::fake('private_signatures');

        // Create sample physical files on disk
        $incomingFilePath = 'documents/DIKDAS/2026/01/sample_incoming.pdf';
        Storage::disk('private_documents')->put($incomingFilePath, '%PDF-1.4 Fake PDF Content');

        $sigFilePath = 'signatures/DIKDAS/sample_sig.png';
        Storage::disk('private_signatures')->put($sigFilePath, 'Fake PNG Binary Signature');

        // Seed domain models
        $incoming = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'letter_number' => '421/01/2026',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-01-15',
            'received_date' => '2026-01-16',
            'subject' => 'Surat Masuk Berkas',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $incomingAttachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $incoming->id,
            'file_path' => $incomingFilePath,
            'original_name' => 'Surat_Undangan.pdf',
            'file_size_bytes' => strlen('%PDF-1.4 Fake PDF Content'),
            'mime_type' => 'application/pdf',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'student_name' => 'Budi Santoso',
            'school_origin' => 'SD Negeri 1 Ungaran',
            'recipient_name' => 'Budi Santoso',
            'recipient_unit' => '081234567890',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $sigAttachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'attachable_type' => Piagam::class,
            'attachable_id' => $piagam->id,
            'file_path' => $sigFilePath,
            'original_name' => 'ttd.png',
            'file_size_bytes' => strlen('Fake PNG Binary Signature'),
            'mime_type' => 'image/png',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        // Fake Google Drive OAuth, Search, Create Folder, Resumable Session, Upload, and Permissions
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token-abc',
                'expires_in' => 3600,
            ], 200),
            'https://www.googleapis.com/drive/v3/files?q=*' => Http::response([
                'files' => [],
            ], 200),
            'https://www.googleapis.com/drive/v3/files' => Http::response([
                'id' => 'CREATED_FOLDER_ID_123',
                'name' => '01_Surat_Masuk',
            ], 200),
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable' => Http::response([], 200, [
                'Location' => 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&session_id=fake-session-uri',
            ]),
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&session_id=fake-session-uri' => Http::sequence()
                ->push([
                    'id' => 'GDRIVE_FILE_INCOMING_999',
                    'name' => 'SM_1_421_01_2026.pdf',
                    'size' => '25',
                ], 200)
                ->push([
                    'id' => 'GDRIVE_FILE_SIG_888',
                    'name' => 'TTD_Resi_1_Budi.png',
                    'size' => '25',
                ], 200),
            'https://www.googleapis.com/drive/v3/files/*/permissions' => Http::response([
                'id' => 'anyoneWithLink',
                'role' => 'reader',
                'type' => 'anyone',
            ], 200),
        ]);

        $service = app(AttachmentDriveSyncService::class);
        $result = $service->syncBatch(10);

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(2, $result['synced']);
        $this->assertEquals(0, $result['failed']);

        // Assert incoming attachment updated
        $incomingAttachment->refresh();
        $this->assertEquals('GDRIVE_FILE_INCOMING_999', $incomingAttachment->google_drive_file_id);
        $this->assertEquals('https://drive.google.com/file/d/GDRIVE_FILE_INCOMING_999/view', $incomingAttachment->google_drive_view_url);
        $this->assertNotNull($incomingAttachment->google_drive_synced_at);

        // Assert signature attachment updated
        $sigAttachment->refresh();
        $this->assertEquals('GDRIVE_FILE_SIG_888', $sigAttachment->google_drive_file_id);
        $this->assertEquals('https://drive.google.com/file/d/GDRIVE_FILE_SIG_888/view', $sigAttachment->google_drive_view_url);
        $this->assertNotNull($sigAttachment->google_drive_synced_at);

        // Assert sync stats reflect 100% fully synced
        $stats = $service->getSyncStats();
        $this->assertEquals(2, $stats['total_files']);
        $this->assertEquals(2, $stats['synced_files']);
        $this->assertEquals(0, $stats['pending_files']);
        $this->assertTrue($stats['is_fully_synced']);
    }

    public function test_sync_drive_endpoint_requires_super_admin(): void
    {
        // Guest denied
        $this->post('/admin/backups/sync-drive')
            ->assertRedirect('/login');

        // Regular operator forbidden
        $this->actingAs($this->operator)
            ->post('/admin/backups/sync-drive')
            ->assertForbidden();

        // Super admin succeeds
        $this->actingAs($this->superAdmin)
            ->post('/admin/backups/sync-drive')
            ->assertRedirect('/admin/backups');
    }

    public function test_artisan_sync_drive_files_command_executes_successfully(): void
    {
        $this->artisan('sisurat:sync-drive-files')
            ->assertExitCode(0);
    }

    public function test_artisan_backup_spreadsheet_command_generates_local_file(): void
    {
        $customOutput = storage_path('app/private/backups/test_artisan_rekap.xls');
        if (File::exists($customOutput)) {
            File::delete($customOutput);
        }

        $this->artisan('sisurat:backup-spreadsheet', ['--output' => $customOutput])
            ->assertExitCode(0);

        $this->assertTrue(File::exists($customOutput));
        $content = File::get($customOutput);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<Worksheet ss:Name="Surat Masuk">', $content);

        File::delete($customOutput);
    }
}
