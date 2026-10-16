<?php

namespace Tests\Feature\Backup;

use App\Models\Attachment;
use App\Models\BackupRun;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Services\Backup\ArchivePackerService;
use App\Services\Backup\BackupConsistencyService;
use App\Services\Backup\BackupEncryptionService;
use App\Services\Backup\BackupManifestService;
use App\Services\Backup\BackupOrchestrator;
use App\Services\Backup\BackupPreflightService;
use App\Services\Backup\DatabaseDumperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class BackupLocalCoreContractTest extends TestCase
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

        // Release any leftover lock
        app(BackupConsistencyService::class)->releaseLock();

        parent::tearDown();
    }

    public function test_backup_run_model_lifecycle(): void
    {
        $division = Division::factory()->create();
        $user = User::factory()->for($division)->create();

        $run = BackupRun::query()->create([
            'backup_id' => 'bkp-test-001',
            'type' => 'full',
            'trigger' => 'manual',
            'requested_by' => $user->id,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        $this->assertFalse($run->isCompleted());
        $this->assertFalse($run->isFailed());

        $run->transitionTo('archiving');
        $this->assertSame('archiving', $run->fresh()->status);

        $run->transitionTo('completed', [
            'completed_at' => now(),
            'archive_size_bytes' => 1024,
            'files_count' => 5,
            'sha256_checksum' => str_repeat('a', 64),
        ]);

        $this->assertTrue($run->fresh()->isCompleted());

        $failedRun = BackupRun::query()->create([
            'backup_id' => 'bkp-test-002',
            'type' => 'full',
            'trigger' => 'scheduled',
            'status' => 'pending',
            'started_at' => now(),
        ]);

        $failedRun->markFailed('DISK_FULL', 'Simulated disk full');
        $this->assertTrue($failedRun->fresh()->isFailed());
        $this->assertSame('DISK_FULL', $failedRun->fresh()->error_code);
    }

    public function test_backup_consistency_service_lock_prevents_concurrent_runs(): void
    {
        $consistency = app(BackupConsistencyService::class);

        $lock = $consistency->acquireLock(60);
        $this->assertTrue($consistency->isLocked());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Concurrent backup rejected');

        // Second acquire should throw
        $consistency2 = new BackupConsistencyService;
        $consistency2->acquireLock(60);
    }

    public function test_backup_consistency_service_cross_reference_matching(): void
    {
        $division = Division::factory()->create();
        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '001/IN/2026',
            'origin_agency' => 'Dinas Pendidikan',
            'letter_date' => '2026-10-16',
            'received_date' => '2026-10-16',
            'subject' => 'Surat Pengantar',
        ]);

        // 1. Create a matching physical file and attachment
        Storage::disk('private_documents')->put('documents/test-letter.pdf', 'dummy content');
        Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'disk' => 'private_documents',
            'file_path' => 'documents/test-letter.pdf',
            'original_name' => 'test-letter.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 13,
            'is_missing' => false,
        ]);

        // 2. Create a known missing attachment
        Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'disk' => 'private_documents',
            'file_path' => 'documents/missing-legacy.pdf',
            'original_name' => 'missing-legacy.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 0,
            'is_missing' => true,
        ]);

        $consistency = app(BackupConsistencyService::class);
        $report = $consistency->verifyCrossReference();

        $this->assertSame(2, $report['total_db_records']);
        $this->assertGreaterThanOrEqual(1, $report['matched_count']);
        $this->assertCount(1, $report['missing_files']);
        $this->assertTrue($report['missing_files'][0]['is_known_missing']);
    }

    public function test_manifest_service_builds_and_verifies_checksums(): void
    {
        $manifestService = new BackupManifestService;
        $stagingDir = storage_path('app/private/backup-temp/manifest-test');
        File::makeDirectory($stagingDir, 0755, true, true);

        // Put test files
        File::put($stagingDir.'/file1.txt', 'Content 1');
        File::put($stagingDir.'/file2.txt', 'Content 2');

        $manifestData = $manifestService->buildManifest(
            'bkp-test-manifest',
            'full',
            ['version' => '10.4', 'size_bytes' => 100, 'sha256' => 'abc', 'tables_count' => 5],
            ['file_count' => 1, 'total_size_bytes' => 50],
            ['file_count' => 1, 'total_size_bytes' => 50]
        );

        $this->assertSame('sisurat-backup', $manifestData['format']);
        $this->assertSame(1, $manifestData['format_version']);
        $this->assertSame('aes-256-gcm', $manifestData['encryption']['cipher']);
        $this->assertSame('sisurat-backup-master-2026', $manifestData['encryption']['key_id']);
        $this->assertArrayNotHasKey('password', $manifestData['encryption']);
        $this->assertArrayNotHasKey('key', $manifestData['encryption']);

        $manifestService->writeManifest($stagingDir, $manifestData);
        $this->assertFileExists($stagingDir.'/manifest.json');

        $checksumFile = $manifestService->generateChecksumsFile($stagingDir);
        $this->assertFileExists($checksumFile);

        $verification = $manifestService->verifyChecksums($stagingDir);
        $this->assertTrue($verification['verified']);
        $this->assertSame(3, $verification['checked_count']); // file1, file2, manifest.json

        // Tamper file1
        File::put($stagingDir.'/file1.txt', 'Tampered Content');
        $tamperedVerification = $manifestService->verifyChecksums($stagingDir);
        $this->assertFalse($tamperedVerification['verified']);
        $this->assertCount(1, $tamperedVerification['mismatches']);
        $this->assertSame('file1.txt', $tamperedVerification['mismatches'][0]['file']);
        $this->assertSame('HASH_MISMATCH', $tamperedVerification['mismatches'][0]['status']);

        File::deleteDirectory($stagingDir);
    }

    public function test_encryption_service_encrypts_and_decrypts_successfully(): void
    {
        $encryption = new BackupEncryptionService;
        $tempDir = storage_path('app/private/backup-temp/crypto-test');
        File::makeDirectory($tempDir, 0755, true, true);

        $originalFile = $tempDir.'/original.txt';
        $encryptedFile = $tempDir.'/encrypted.enc';
        $decryptedFile = $tempDir.'/decrypted.txt';

        $secretText = 'OFFICIAL_SISURAT_GOVERNMENT_ARCHIVE_2026_TEST_SECRET';
        File::put($originalFile, $secretText);

        $key = 'my-super-secret-backup-passphrase-2026';
        $encMeta = $encryption->encryptArchive($originalFile, $encryptedFile, $key);

        $this->assertFileExists($encryptedFile);
        $this->assertSame('aes-256-gcm', $encMeta['cipher']);
        $this->assertNotEmpty($encMeta['sha256']);

        // Assert file begins with magic header
        $rawBytes = File::get($encryptedFile);
        $this->assertStringStartsWith(BackupEncryptionService::MAGIC_HEADER, $rawBytes);

        // Decrypt
        $decrypted = $encryption->decryptArchive($encryptedFile, $decryptedFile, $key);
        $this->assertTrue($decrypted);
        $this->assertSame($secretText, File::get($decryptedFile));

        File::deleteDirectory($tempDir);
    }

    public function test_encryption_service_detects_tampering_and_fails(): void
    {
        $encryption = new BackupEncryptionService;
        $tempDir = storage_path('app/private/backup-temp/crypto-tamper');
        File::makeDirectory($tempDir, 0755, true, true);

        $originalFile = $tempDir.'/original.txt';
        $encryptedFile = $tempDir.'/encrypted.enc';
        $decryptedFile = $tempDir.'/decrypted.txt';

        File::put($originalFile, 'Sensitive archival content to be tampered');
        $key = 'secret-key-123';
        $encryption->encryptArchive($originalFile, $encryptedFile, $key);

        // Tamper with one byte near the end (in the ciphertext)
        $raw = File::get($encryptedFile);
        $tampered = substr_replace($raw, chr(ord($raw[strlen($raw) - 2]) ^ 0xFF), strlen($raw) - 2, 1);
        File::put($encryptedFile, $tampered);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tamper detected or invalid key');

        $encryption->decryptArchive($encryptedFile, $decryptedFile, $key);
    }

    public function test_encryption_service_rejects_wrong_key(): void
    {
        $encryption = new BackupEncryptionService;
        $tempDir = storage_path('app/private/backup-temp/crypto-wrong-key');
        File::makeDirectory($tempDir, 0755, true, true);

        $originalFile = $tempDir.'/original.txt';
        $encryptedFile = $tempDir.'/encrypted.enc';
        $decryptedFile = $tempDir.'/decrypted.txt';

        File::put($originalFile, 'Archival record payload');
        $encryption->encryptArchive($originalFile, $encryptedFile, 'correct-key');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tamper detected or invalid key');

        $encryption->decryptArchive($encryptedFile, $decryptedFile, 'wrong-key');
    }

    public function test_database_dumper_produces_valid_sql_dump(): void
    {
        $dumper = app(DatabaseDumperService::class);
        $destSql = storage_path('app/private/backup-temp/test-dump.sql');

        $stats = $dumper->dump($destSql);

        $this->assertFileExists($destSql);
        $this->assertGreaterThan(0, $stats['size_bytes']);
        $this->assertGreaterThan(0, $stats['tables_count']);
        $this->assertNotEmpty($stats['sha256']);

        $content = File::get($destSql);
        $this->assertStringContainsString('CREATE TABLE', $content);
        $this->assertStringContainsString('users', $content);

        File::delete($destSql);
    }

    public function test_backup_orchestrator_creates_full_backup_end_to_end(): void
    {
        $orchestrator = app(BackupOrchestrator::class);
        $encryption = app(BackupEncryptionService::class);
        $packer = app(ArchivePackerService::class);
        $manifestService = app(BackupManifestService::class);

        // Run full backup
        $run = $orchestrator->run([
            'type' => 'full',
            'trigger' => 'manual',
            'encryption_key' => 'test-archive-key-2026',
        ]);

        $this->assertSame('completed', $run->status);
        $this->assertGreaterThan(0, $run->archive_size_bytes);
        $this->assertGreaterThan(0, $run->files_count);
        $this->assertNotNull($run->completed_at);
        $this->assertNotEmpty($run->sha256_checksum);

        $encPath = storage_path("app/private/backups/{$run->backup_id}.enc");
        $this->assertFileExists($encPath);

        // Decrypt and unpack the archive to verify contents
        $decryptedBundle = storage_path("app/private/backup-temp/{$run->backup_id}.bundle.tar");
        $unpackDir = storage_path("app/private/backup-temp/{$run->backup_id}_extracted");

        $encryption->decryptArchive($encPath, $decryptedBundle, 'test-archive-key-2026');
        $this->assertFileExists($decryptedBundle);

        $packer->unpack($decryptedBundle, $unpackDir);

        $this->assertFileExists($unpackDir.'/manifest.json');
        $this->assertFileExists($unpackDir.'/checksums.sha256');
        $this->assertFileExists($unpackDir.'/database/sisurat.sql');

        // Checksum verification of all uncompressed files
        $checksumVerification = $manifestService->verifyChecksums($unpackDir);
        $this->assertTrue($checksumVerification['verified']);
        $this->assertEmpty($checksumVerification['mismatches']);

        // Clean up extracted verification files
        File::delete($decryptedBundle);
        File::deleteDirectory($unpackDir);
        File::delete($encPath);
    }

    public function test_backup_orchestrator_supports_database_only_type(): void
    {
        $orchestrator = app(BackupOrchestrator::class);

        $run = $orchestrator->run([
            'type' => 'database',
            'trigger' => 'manual',
        ]);

        $this->assertSame('completed', $run->status);
        $this->assertSame('database', $run->type);

        $encPath = storage_path("app/private/backups/{$run->backup_id}.enc");
        $this->assertFileExists($encPath);

        File::delete($encPath);
    }

    public function test_artisan_backup_command_executes_successfully(): void
    {
        $this->artisan('sisurat:backup', ['--type' => 'full'])
            ->expectsOutputToContain('SUCCESS')
            ->assertExitCode(0);

        $this->assertDatabaseHas('backup_runs', [
            'type' => 'full',
            'status' => 'completed',
        ]);
    }

    public function test_artisan_backup_command_dry_run(): void
    {
        $this->artisan('sisurat:backup', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN]')
            ->expectsOutputToContain('SUCCESS:')
            ->assertExitCode(0);
    }

    public function test_backup_orchestrator_cleans_up_on_failure_and_releases_lock(): void
    {
        // Mock preflight to fail
        $mockPreflight = $this->createMock(BackupPreflightService::class);
        $mockPreflight->method('runAudit')->willReturn([
            'is_ready' => false,
            'checks' => [
                ['name' => 'Disk Space', 'message' => 'Disk full simulation', 'status' => 'FAIL'],
            ],
        ]);

        $this->app->instance(BackupPreflightService::class, $mockPreflight);

        $orchestrator = $this->app->make(BackupOrchestrator::class);

        try {
            $orchestrator->run(['type' => 'full']);
            $this->fail('Expected preflight failure exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Preflight checks failed', $e->getMessage());
        }

        // Lock must be released
        $consistency = app(BackupConsistencyService::class);
        $this->assertFalse($consistency->isLocked());

        // BackupRun must be marked as failed
        $this->assertDatabaseHas('backup_runs', [
            'status' => 'failed',
            'error_code' => 'BACKUP_PIPELINE_ERROR',
        ]);
    }
}
