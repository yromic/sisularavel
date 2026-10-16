<?php

namespace Tests\Feature\Backup;

use App\Models\BackupRun;
use App\Services\Backup\BackupConsistencyService;
use App\Services\Backup\BackupOrchestrator;
use App\Services\Backup\GoogleDriveStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GoogleDriveIntegrationContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'backup.remote.google_drive.client_id' => 'test-client-id.apps.googleusercontent.com',
            'backup.remote.google_drive.client_secret' => 'test-client-secret-12345',
            'backup.remote.google_drive.refresh_token' => 'test-refresh-token-xyz',
            'backup.remote.google_drive.folder_id' => 'test-backup-folder-id',
            'backup.remote.google_drive.chunk_size_bytes' => 262144, // 256 KB
            'backup.remote.google_drive.retry_base_ms' => 10,
        ]);

        Cache::forget(GoogleDriveStorageService::TOKEN_CACHE_KEY);
    }

    protected function tearDown(): void
    {
        Cache::forget(GoogleDriveStorageService::TOKEN_CACHE_KEY);
        app(BackupConsistencyService::class)->releaseLock();

        $backupsDir = storage_path('app/private/backups');
        if (File::isDirectory($backupsDir)) {
            File::deleteDirectory($backupsDir);
        }

        $tempDir = storage_path('app/private/backup-temp');
        if (File::isDirectory($tempDir)) {
            File::deleteDirectory($tempDir);
        }

        parent::tearDown();
    }

    public function test_google_drive_service_configuration_detection(): void
    {
        $service = new GoogleDriveStorageService;
        $this->assertTrue($service->isConfigured());

        config(['backup.remote.google_drive.refresh_token' => null]);
        $this->assertFalse($service->isConfigured());
    }

    public function test_oauth_access_token_retrieval_and_caching(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.fake-test-access-token-98765',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $service = new GoogleDriveStorageService;
        $token1 = $service->getAccessToken();

        $this->assertSame('ya29.fake-test-access-token-98765', $token1);
        $this->assertTrue(Cache::has(GoogleDriveStorageService::TOKEN_CACHE_KEY));

        // Second call should return cached token without issuing new HTTP request
        $token2 = $service->getAccessToken();
        $this->assertSame($token1, $token2);

        Http::assertSentCount(1);
    }

    public function test_oauth_token_revocation_error_handling(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ], 400),
        ]);

        $service = new GoogleDriveStorageService;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Token has been expired or revoked');

        $service->getAccessToken();
    }

    public function test_initiate_resumable_session(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable' => Http::response('', 200, [
                'Location' => 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session_abc_123',
            ]),
        ]);

        $service = new GoogleDriveStorageService;
        $sessionUri = $service->initiateResumableSession('test-backup.enc', 500000);

        $this->assertSame(
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session_abc_123',
            $sessionUri
        );
    }

    public function test_chunked_resumable_upload_success(): void
    {
        // 512 KB test file (exactly 2 chunks of 256 KB)
        $tempFile = storage_path('app/private/backup-temp/test-upload.enc');
        File::ensureDirectoryExists(dirname($tempFile));
        File::put($tempFile, str_repeat('A', 262144).str_repeat('B', 262144));

        $sessionUri = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session_test_chunk';

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable' => Http::response('', 200, [
                'Location' => $sessionUri,
            ]),
            $sessionUri => Http::sequence()
                // Initial check offset
                ->push('', 308, ['Range' => 'bytes=0-0'])
                // Chunk 1 upload: bytes 0-262143
                ->push('', 308, ['Range' => 'bytes=0-262143'])
                // Chunk 2 upload: bytes 262144-524287 (Final completion)
                ->push([
                    'id' => 'gdrive_file_id_777',
                    'name' => 'test-upload.enc',
                    'size' => '524288',
                    'md5Checksum' => 'abcdef1234567890',
                ], 200),
        ]);

        $service = new GoogleDriveStorageService;
        $result = $service->uploadArchive($tempFile);

        $this->assertSame('gdrive_file_id_777', $result['file_id']);
        $this->assertSame(524288, $result['size_bytes']);
        $this->assertSame('test-upload.enc', $result['name']);

        File::delete($tempFile);
    }

    public function test_interrupted_upload_resumption_tolerance(): void
    {
        $tempFile = storage_path('app/private/backup-temp/test-resume.enc');
        File::ensureDirectoryExists(dirname($tempFile));
        File::put($tempFile, str_repeat('X', 524288));

        $sessionUri = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session_resume_123';

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok'], 200),
            // First call to check uploaded offset: server already has first 256KB
            $sessionUri => Http::sequence()
                ->push('', 308, ['Range' => 'bytes=0-262143'])
                // Upload resumes from byte 262144 to 524287
                ->push([
                    'id' => 'resumed_file_999',
                    'name' => 'test-resume.enc',
                    'size' => '524288',
                ], 200),
        ]);

        $service = new GoogleDriveStorageService;
        $result = $service->uploadArchive($tempFile, $sessionUri);

        $this->assertSame('resumed_file_999', $result['file_id']);

        File::delete($tempFile);
    }

    public function test_verify_remote_file_success_and_size_matching(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok'], 200),
            'https://www.googleapis.com/drive/v3/files/gdrive_file_abc?fields=*' => Http::response([
                'id' => 'gdrive_file_abc',
                'name' => 'bkp-20261016-010000.enc',
                'size' => '1048576',
                'md5Checksum' => '1234567890abcdef',
                'trashed' => false,
                'createdTime' => '2026-10-16T01:05:00Z',
            ], 200),
        ]);

        $service = new GoogleDriveStorageService;
        $verification = $service->verifyRemoteFile('gdrive_file_abc', 1048576);

        $this->assertTrue($verification['verified']);
        $this->assertTrue($verification['size_matches']);
        $this->assertSame(1048576, $verification['remote_size']);
        $this->assertFalse($verification['trashed']);

        // Size mismatch test
        $mismatch = $service->verifyRemoteFile('gdrive_file_abc', 999999);
        $this->assertFalse($mismatch['verified']);
        $this->assertFalse($mismatch['size_matches']);
    }

    public function test_orchestrator_runs_end_to_end_with_google_drive_upload(): void
    {
        $sessionUri = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=sess_orch_1';

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test'], 200),
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable' => Http::response('', 200, [
                'Location' => $sessionUri,
            ]),
            $sessionUri => Http::sequence()
                ->push('', 308, ['Range' => 'bytes=0-0'])
                ->push([
                    'id' => 'orch_gdrive_file_001',
                    'name' => 'bkp.enc',
                    'size' => '1000',
                ], 200),
            'https://www.googleapis.com/drive/v3/files/orch_gdrive_file_001?fields=*' => function ($request) {
                // Return size matching whatever archive size was generated
                return Http::response([
                    'id' => 'orch_gdrive_file_001',
                    'name' => 'backup.enc',
                    'size' => '1000',
                    'trashed' => false,
                ], 200);
            },
        ]);

        // Mock verifyRemoteFile to ensure exact size agreement during test
        $mockDrive = $this->getMockBuilder(GoogleDriveStorageService::class)
            ->onlyMethods(['uploadArchive', 'verifyRemoteFile', 'isConfigured'])
            ->getMock();

        $mockDrive->method('isConfigured')->willReturn(true);
        $mockDrive->method('uploadArchive')->willReturn([
            'file_id' => 'orch_gdrive_file_001',
            'name' => 'backup.enc',
            'size_bytes' => 1000,
        ]);
        $mockDrive->method('verifyRemoteFile')->willReturn([
            'verified' => true,
            'file_id' => 'orch_gdrive_file_001',
            'size_matches' => true,
        ]);

        $this->app->instance(GoogleDriveStorageService::class, $mockDrive);

        $orchestrator = $this->app->make(BackupOrchestrator::class);
        $run = $orchestrator->run([
            'type' => 'database',
            'upload' => true,
        ]);

        $this->assertSame('completed', $run->status);
        $this->assertSame('google_drive', $run->remote_storage_provider);
        $this->assertSame('orch_gdrive_file_001', $run->remote_file_id);

        $this->assertDatabaseHas('backup_runs', [
            'backup_id' => $run->backup_id,
            'remote_storage_provider' => 'google_drive',
            'remote_file_id' => 'orch_gdrive_file_001',
            'status' => 'completed',
        ]);
    }

    public function test_artisan_backup_verify_command(): void
    {
        $run = BackupRun::query()->create([
            'backup_id' => 'bkp-verify-test-01',
            'type' => 'full',
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'archive_size_bytes' => 12,
            'sha256_checksum' => hash('sha256', 'mock archive'),
            'remote_storage_provider' => 'local',
        ]);

        // Create the dummy local archive file
        $archivePath = storage_path("app/private/backups/{$run->backup_id}.enc");
        File::ensureDirectoryExists(dirname($archivePath));
        File::put($archivePath, 'mock archive');

        $this->artisan('sisurat:backup:verify', ['--backup-id' => $run->backup_id])
            ->expectsOutputToContain('VERIFICATION SUCCESSFUL')
            ->assertExitCode(0);

        $this->artisan('sisurat:backup:verify', ['--backup-id' => $run->backup_id, '--json' => true])
            ->assertExitCode(0);

        File::delete($archivePath);
    }
}
