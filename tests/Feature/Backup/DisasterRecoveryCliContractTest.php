<?php

namespace Tests\Feature\Backup;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\Piagam;
use App\Models\User;
use App\Services\Backup\ArchivePackerService;
use App\Services\Backup\BackupConsistencyService;
use App\Services\Backup\BackupEncryptionService;
use App\Services\Backup\BackupOrchestrator;
use App\Services\Recovery\ArchiveExtractorService;
use App\Services\Recovery\FileRecoveryService;
use App\Services\Recovery\RecoveryOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DisasterRecoveryCliContractTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Clean up test archives and staging folders
        $backupsDir = storage_path('app/private/backups');
        if (File::isDirectory($backupsDir)) {
            File::deleteDirectory($backupsDir);
        }

        $backupTempDir = storage_path('app/private/backup-temp');
        if (File::isDirectory($backupTempDir)) {
            File::deleteDirectory($backupTempDir);
        }

        $recoveryTempDir = storage_path('app/private/recovery-temp');
        if (File::isDirectory($recoveryTempDir)) {
            File::deleteDirectory($recoveryTempDir);
        }

        app(BackupConsistencyService::class)->releaseLock();

        // Clean up committed database rows without dropping schema
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        foreach (['attachments', 'incoming_letters', 'outgoing_letters', 'piagams', 'users', 'divisions', 'backup_runs', 'idempotency_keys'] as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        parent::tearDown();
    }

    /**
     * Helper to create a realistic backup archive with DB data and private storage files.
     */
    protected function generateTestBackupArchive(string $key = 'recovery-test-key-2026'): array
    {
        $division = Division::factory()->create();
        $user = User::factory()->for($division)->create();

        // Create document on private_documents disk
        $docPath = 'documents/2026/10/test-restore-letter.pdf';
        Storage::disk('private_documents')->put($docPath, 'PDF CONTENT FOR DISASTER RECOVERY RESTORATION TEST');

        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '111/REC/X/2026',
            'origin_agency' => 'Kantor Arsip Bencana',
            'letter_date' => '2026-10-01',
            'received_date' => '2026-10-02',
            'subject' => 'Surat Pengujian Pemulihan Bencana',
        ]);

        $docAttachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'division_id' => $division->id,
            'file_name' => '111-rec.pdf',
            'original_name' => 'surat_bencana.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 52,
            'disk' => 'private_documents',
            'file_path' => $docPath,
        ]);

        // Create signature on private_signatures disk
        $sigPath = 'signatures/2026/10/test-restore-sig.png';
        Storage::disk('private_signatures')->put($sigPath, 'FAKE SIGNATURE PNG BINARY FOR DISASTER RECOVERY');

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Budi Santoso',
            'piagam_description' => 'Piagam Penghargaan Juara 1',
            'recipient_name' => 'Penerima Sertifikat Uji',
        ]);

        $sigAttachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'attachable_type' => Piagam::class,
            'attachable_id' => $piagam->id,
            'division_id' => $division->id,
            'file_name' => 'sig-rec.png',
            'original_name' => 'tanda_tangan.png',
            'mime_type' => 'image/png',
            'file_size' => 48,
            'disk' => 'private_signatures',
            'file_path' => $sigPath,
        ]);

        $orchestrator = app(BackupOrchestrator::class);
        $run = $orchestrator->run([
            'type' => 'full',
            'trigger' => 'manual',
            'encryption_key' => $key,
            'no_upload' => true,
        ]);

        $archivePath = storage_path("app/private/backups/{$run->backup_id}.enc");

        return [
            'run' => $run,
            'archive_path' => $archivePath,
            'division' => $division,
            'letter' => $letter,
            'doc_attachment' => $docAttachment,
            'sig_attachment' => $sigAttachment,
            'doc_path' => $docPath,
            'sig_path' => $sigPath,
        ];
    }

    public function test_archive_extractor_decrypts_and_verifies_archive(): void
    {
        $setup = $this->generateTestBackupArchive();
        $extractor = app(ArchiveExtractorService::class);

        $extraction = $extractor->extract($setup['archive_path'], null, 'recovery-test-key-2026');

        $this->assertNotEmpty($extraction['staging_dir']);
        $this->assertTrue(File::isDirectory($extraction['staging_dir']));
        $this->assertTrue($extraction['checksums_verified']);
        $this->assertSame('sisurat-backup', $extraction['manifest']['format']);
        $this->assertSame($setup['run']->backup_id, $extraction['manifest']['backup_id']);
        $this->assertNotNull($extraction['sql_dump_path']);
        $this->assertFileExists($extraction['sql_dump_path']);

        $extractor->cleanupStaging($extraction['staging_dir']);
        $this->assertFalse(File::isDirectory($extraction['staging_dir']));
    }

    public function test_archive_extractor_rejects_wrong_decryption_key(): void
    {
        $setup = $this->generateTestBackupArchive();
        $extractor = app(ArchiveExtractorService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tamper detected or invalid key');

        $extractor->extract($setup['archive_path'], null, 'wrong-decryption-key');
    }

    public function test_archive_extractor_rejects_tampered_encrypted_payload(): void
    {
        $setup = $this->generateTestBackupArchive();
        $extractor = app(ArchiveExtractorService::class);

        // Tamper 1 byte in the middle of ciphertext
        $content = File::get($setup['archive_path']);
        $tamperedPos = strlen(BackupEncryptionService::MAGIC_HEADER) + 20;
        $content[$tamperedPos] = chr(ord($content[$tamperedPos]) ^ 0xFF);
        File::put($setup['archive_path'], $content);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tamper detected or invalid key');

        $extractor->extract($setup['archive_path'], null, 'recovery-test-key-2026');
    }

    public function test_archive_extractor_rejects_tampered_checksums_inside_bundle(): void
    {
        $setup = $this->generateTestBackupArchive();
        $encryption = app(BackupEncryptionService::class);
        $packer = app(ArchivePackerService::class);
        $extractor = app(ArchiveExtractorService::class);

        // Decrypt the bundle
        $tempDir = storage_path('app/private/recovery-temp/tamper_test');
        $decryptedTar = storage_path('app/private/recovery-temp/tamper.tar');
        $unpackedDir = storage_path('app/private/recovery-temp/unpacked_tamper');

        $encryption->decryptArchive($setup['archive_path'], $decryptedTar, 'recovery-test-key-2026');
        $packer->unpack($decryptedTar, $unpackedDir);

        // Deliberately tamper with the SQL dump file without updating checksums.sha256
        $sqlPath = $unpackedDir.'/database/sisurat.sql';
        File::append($sqlPath, "\n-- MALICIOUS INJECTION --\n");

        // Repackage and re-encrypt
        $repackedTar = storage_path('app/private/recovery-temp/repacked.tar');
        $packer->pack($unpackedDir, $repackedTar);
        $reEncryptedArchive = storage_path('app/private/recovery-temp/re_encrypted.enc');
        $encryption->encryptArchive($repackedTar, $reEncryptedArchive, 'recovery-test-key-2026');

        // Extracting should fail on checksum verification
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Checksum verification failed');

        try {
            $extractor->extract($reEncryptedArchive, null, 'recovery-test-key-2026');
        } finally {
            File::deleteDirectory($tempDir);
            File::deleteDirectory($unpackedDir);
            File::delete($decryptedTar);
            File::delete($repackedTar);
            File::delete($reEncryptedArchive);
        }
    }

    public function test_file_recovery_service_restores_documents_and_signatures(): void
    {
        $tempSource = storage_path('app/private/recovery-temp/source_files_test');
        $docsSource = $tempSource.'/documents';
        $sigsSource = $tempSource.'/signatures';

        File::makeDirectory($docsSource, 0755, true, true);
        File::makeDirectory($sigsSource, 0755, true, true);

        File::put($docsSource.'/sample.pdf', 'SAMPLE DOCUMENT CONTENT');
        File::put($sigsSource.'/sample.png', 'SAMPLE SIGNATURE CONTENT');

        $service = app(FileRecoveryService::class);
        $result = $service->restore($docsSource, $sigsSource);

        $this->assertSame(1, $result['documents_restored']);
        $this->assertSame(1, $result['signatures_restored']);
        $this->assertSame(2, $result['total_files']);

        $this->assertTrue(Storage::disk('private_documents')->exists('sample.pdf'));
        $this->assertTrue(Storage::disk('private_signatures')->exists('sample.png'));

        // Clean up
        Storage::disk('private_documents')->delete('sample.pdf');
        Storage::disk('private_signatures')->delete('sample.png');
        File::deleteDirectory($tempSource);
    }

    public function test_recovery_orchestrator_bare_metal_full_restore(): void
    {
        $setup = $this->generateTestBackupArchive();
        $orchestrator = app(RecoveryOrchestrator::class);

        // Mutate the database: change letter subject and delete the attachment record
        IncomingLetter::query()->where('id', $setup['letter']->id)->update(['subject' => 'SUBYEK DIUBAH SEBELUM BENCANA']);
        $setup['doc_attachment']->delete();

        // Mutate storage: delete the file from disk
        Storage::disk('private_documents')->delete($setup['doc_path']);
        $this->assertFalse(Storage::disk('private_documents')->exists($setup['doc_path']));

        // Execute recovery
        $result = $orchestrator->recover($setup['archive_path'], [
            'key' => 'recovery-test-key-2026',
        ]);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['verify_only']);
        $this->assertSame($setup['run']->backup_id, $result['backup_id']);
        $this->assertGreaterThan(0, $result['database']['tables_restored']);
        $this->assertGreaterThan(0, $result['files']['total_files']);

        // Verify database data was restored to original backup state
        $restoredLetter = IncomingLetter::query()->where('id', $setup['letter']->id)->first();
        $this->assertNotNull($restoredLetter);
        $this->assertSame('Surat Pengujian Pemulihan Bencana', $restoredLetter->subject);

        $restoredAttachment = Attachment::query()->where('uuid', $setup['doc_attachment']->uuid)->first();
        $this->assertNotNull($restoredAttachment);

        // Verify physical file was restored on disk
        $this->assertTrue(Storage::disk('private_documents')->exists($setup['doc_path']));
        $this->assertSame('PDF CONTENT FOR DISASTER RECOVERY RESTORATION TEST', Storage::disk('private_documents')->get($setup['doc_path']));
    }

    public function test_recovery_orchestrator_verify_only_mode_does_not_mutate_data(): void
    {
        $setup = $this->generateTestBackupArchive();
        $orchestrator = app(RecoveryOrchestrator::class);

        // Set subject to a modified value
        IncomingLetter::query()->where('id', $setup['letter']->id)->update(['subject' => 'MODIFIED SUBJECT']);

        $result = $orchestrator->recover($setup['archive_path'], [
            'key' => 'recovery-test-key-2026',
            'verify_only' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['verify_only']);
        $this->assertTrue($result['integrity']['checksums_verified']);
        $this->assertTrue($result['integrity']['sql_dump_exists']);
        $this->assertTrue($result['integrity']['sql_dump_hash_match']);

        // Database subject should remain modified (untouched)
        $letter = IncomingLetter::query()->where('id', $setup['letter']->id)->first();
        $this->assertSame('MODIFIED SUBJECT', $letter->subject);
    }

    public function test_artisan_restore_command_requires_confirmation_unless_force(): void
    {
        $setup = $this->generateTestBackupArchive();

        // User declines confirmation prompt
        $this->artisan('sisurat:restore', [
            '--archive' => $setup['archive_path'],
            '--key' => 'recovery-test-key-2026',
        ])
            ->expectsConfirmation('Do you really wish to proceed with this restoration?', 'no')
            ->expectsOutputToContain('Restoration aborted by user.')
            ->assertExitCode(1);
    }

    public function test_artisan_restore_command_with_force_and_json_flag(): void
    {
        $setup = $this->generateTestBackupArchive();

        $this->artisan('sisurat:restore', [
            '--archive' => $setup['archive_path'],
            '--key' => 'recovery-test-key-2026',
            '--force' => true,
            '--json' => true,
        ])
            ->assertSuccessful();
    }

    public function test_artisan_restore_command_with_verify_only_flag(): void
    {
        $setup = $this->generateTestBackupArchive();

        $this->artisan('sisurat:restore', [
            '--archive' => $setup['archive_path'],
            '--key' => 'recovery-test-key-2026',
            '--verify-only' => true,
        ])
            ->expectsOutputToContain('ARCHIVE INTEGRITY VERIFIED SUCCESSFULLY (DRY RUN)')
            ->assertExitCode(0);
    }

    public function test_artisan_restore_command_with_skip_database_flag(): void
    {
        $setup = $this->generateTestBackupArchive();

        $this->artisan('sisurat:restore', [
            '--archive' => $setup['archive_path'],
            '--key' => 'recovery-test-key-2026',
            '--skip-database' => true,
            '--force' => true,
        ])
            ->expectsOutputToContain('DISASTER RECOVERY COMPLETED SUCCESSFULLY')
            ->assertExitCode(0);
    }
}
