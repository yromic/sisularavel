<?php

namespace Tests\Feature\Migration;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyMigrationToolingContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reconciles_rows_and_rolls_back_database_changes(): void
    {
        $fixturePath = $this->legacyFixturePath('dry-run');
        $this->writeBaseLegacyFixture($fixturePath);

        $this->artisan('sisurat:migrate-legacy', [
            '--path' => $fixturePath,
            '--dry-run' => true,
        ])
            ->expectsOutput('Legacy migration dry run completed; database changes rolled back.')
            ->assertSuccessful();

        $this->assertDatabaseCount('divisions', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('schools', 0);
        $this->assertDatabaseCount('competition_categories', 0);
        $this->assertDatabaseCount('pickup_categories', 0);
        $this->assertDatabaseCount('incoming_letters', 0);
        $this->assertDatabaseCount('outgoing_letters', 0);
        $this->assertDatabaseCount('piagams', 0);
        $this->assertDatabaseCount('attachments', 0);

        $report = $this->readReport();

        $this->assertSame(1, $report['tables']['db_divisi']['imported']);
        $this->assertSame(1, $report['tables']['db_users']['imported']);
        $this->assertSame(1, $report['tables']['ref_sekolah']['imported']);
        $this->assertSame(1, $report['tables']['DIKDAS_ref_jenis']['imported']);
        $this->assertSame(1, $report['tables']['DIKDAS_ref_pengambilan']['imported']);
        $this->assertSame(1, $report['tables']['DIKDAS_surat_masuk']['imported']);
        $this->assertSame(1, $report['tables']['DIKDAS_surat_keluar']['imported']);
        $this->assertSame(1, $report['tables']['DIKDAS_piagam']['imported']);
        $this->assertSame(3, $report['missing_attachments']);
        $this->assertSame(0, $report['quarantined_rows']);
    }

    public function test_import_forces_password_reset_and_preserves_missing_drive_attachments(): void
    {
        $fixturePath = $this->legacyFixturePath('import');
        $this->writeBaseLegacyFixture($fixturePath);

        $this->artisan('sisurat:migrate-legacy', [
            '--path' => $fixturePath,
        ])
            ->expectsOutput('Legacy migration completed.')
            ->assertSuccessful();

        $division = Division::query()->where('code', 'DIKDAS')->firstOrFail();
        $user = User::query()->where('username', 'operator_dikdas')->firstOrFail();
        $incoming = IncomingLetter::query()->where('letter_number', '421.3/001/2026')->firstOrFail();
        $outgoing = OutgoingLetter::query()->where('letter_number', '421.3/002/2026')->firstOrFail();
        $piagam = Piagam::query()->where('student_name', 'Ahmad Zaki')->firstOrFail();

        // User & Division assertions
        $this->assertSame($division->id, $user->division_id);
        $this->assertTrue($user->must_reset_password);
        $this->assertFalse(Hash::check('legacy-secret', $user->password));

        // Incoming Letter assertions
        $this->assertSame($division->id, $incoming->division_id);
        $this->assertSame('Dinas Pendidikan', $incoming->origin_agency);

        // Outgoing Letter assertions
        $this->assertSame($division->id, $outgoing->division_id);
        $this->assertSame(2026, $outgoing->letter_year);
        $this->assertSame('Pemberitahuan lomba', $outgoing->subject);

        // Piagam assertions
        $this->assertSame($division->id, $piagam->division_id);
        $this->assertSame('SD Negeri 1', $piagam->school->name);
        $this->assertSame('FLS2N', $piagam->competitionCategory->name);
        $this->assertSame('Budi Guru', $piagam->recipient_name);

        // Attachment assertions (3 attachments across incoming, outgoing, and piagam)
        $this->assertDatabaseCount('attachments', 3);
        $this->assertTrue(Attachment::query()->where('attachable_type', IncomingLetter::class)->firstOrFail()->is_missing);
        $this->assertTrue(Attachment::query()->where('attachable_type', OutgoingLetter::class)->firstOrFail()->is_missing);
        $this->assertTrue(Attachment::query()->where('attachable_type', Piagam::class)->firstOrFail()->is_missing);
    }

    public function test_import_is_idempotent_and_tracks_duplicate_records(): void
    {
        $fixturePath = $this->legacyFixturePath('idempotent');
        $this->writeBaseLegacyFixture($fixturePath);

        // First run
        $this->artisan('sisurat:migrate-legacy', ['--path' => $fixturePath])->assertSuccessful();
        $this->assertDatabaseCount('incoming_letters', 1);
        $this->assertDatabaseCount('outgoing_letters', 1);
        $this->assertDatabaseCount('piagams', 1);

        // Second run on same dataset
        $this->artisan('sisurat:migrate-legacy', ['--path' => $fixturePath])->assertSuccessful();

        // Outgoing letters and Piagams must not duplicate
        $this->assertDatabaseCount('outgoing_letters', 1);
        $this->assertDatabaseCount('piagams', 1);

        $report = $this->readReport();
        $this->assertSame(1, $report['tables']['DIKDAS_surat_keluar']['duplicate']);
        $this->assertSame(1, $report['tables']['DIKDAS_piagam']['duplicate']);
    }

    public function test_invalid_rows_are_quarantined_without_discarding_valid_rows(): void
    {
        $fixturePath = $this->legacyFixturePath('quarantine');
        $this->writeBaseLegacyFixture($fixturePath);
        $this->writeJson($fixturePath, 'UNKNOWN_surat_masuk.json', [[
            'nomor_surat' => 'BAD-001',
            'asal_surat' => 'Unknown Division',
            'tanggal_surat' => '2026-09-01',
            'tanggal_diterima' => '2026-09-02',
            'perihal' => 'Should be quarantined',
        ]]);

        $this->artisan('sisurat:migrate-legacy', [
            '--path' => $fixturePath,
        ])->assertSuccessful();

        $this->assertDatabaseHas('incoming_letters', ['letter_number' => '421.3/001/2026']);
        $this->assertDatabaseMissing('incoming_letters', ['letter_number' => 'BAD-001']);

        $quarantine = json_decode(File::get(storage_path('migration/quarantine_UNKNOWN_surat_masuk.json')), true);

        $this->assertSame('Unknown division code [UNKNOWN].', $quarantine[0]['error']);
        $this->assertSame('BAD-001', $quarantine[0]['row']['nomor_surat']);
        $this->assertSame(1, $this->readReport()['quarantined_rows']);
    }

    private function legacyFixturePath(string $name): string
    {
        $path = storage_path("framework/testing/legacy-fixtures/{$name}");
        File::deleteDirectory($path);
        File::ensureDirectoryExists($path);
        File::deleteDirectory(storage_path('migration'));

        return $path;
    }

    private function writeBaseLegacyFixture(string $path): void
    {
        $this->writeJson($path, 'db_divisi.json', [[
            'kode_divisi' => ' DIKDAS ',
            'nama_divisi' => 'Bidang Pembinaan SD',
            'status' => 'AKTIF',
            'drive_folder_id' => 'legacy-folder-001',
        ]]);

        $this->writeJson($path, 'db_users.json', [[
            'username' => 'Operator_Dikdas',
            'password' => 'legacy-secret',
            'password_hash' => 'legacy-sha256',
            'password_salt' => 'legacy-salt',
            'nama' => 'Operator Dikdas',
            'email' => 'OPERATOR@example.test',
            'role' => 'admin',
            'aktif' => 'TRUE',
            'divisi_id' => 'DIKDAS',
        ]]);

        $this->writeJson($path, 'ref_sekolah.json', [[
            'nama_sekolah' => 'SD Negeri 1',
            'npsn' => '12345678',
            'aktif' => '1',
        ]]);

        $this->writeJson($path, 'DIKDAS_ref_jenis.json', [[
            'nama_jenis' => 'FLS2N',
            'aktif' => '1',
        ]]);

        $this->writeJson($path, 'DIKDAS_ref_pengambilan.json', [[
            'nama_pengambilan' => 'Diambil Langsung',
            'aktif' => '1',
        ]]);

        $this->writeJson($path, 'DIKDAS_surat_masuk.json', [[
            'nomor_surat' => ' 421.3/001/2026 ',
            'asal_surat' => ' Dinas Pendidikan ',
            'tanggal_surat' => '15/09/2026',
            'tanggal_diterima' => '2026-09-16',
            'perihal' => '=Undangan koordinasi',
            'upload_file' => 'https://drive.google.com/file/d/drive-file-001/view',
        ]]);

        $this->writeJson($path, 'DIKDAS_surat_keluar.json', [[
            'nomor_surat' => ' 421.3/002/2026 ',
            'tanggal_surat' => '17/09/2026',
            'tanggal_share' => '2026-09-18',
            'perihal' => 'Pemberitahuan lomba',
            'upload_file' => 'https://drive.google.com/file/d/drive-file-sk-001/view',
        ]]);

        $this->writeJson($path, 'DIKDAS_piagam.json', [[
            'nama_siswa' => 'Ahmad Zaki',
            'asal_sekolah' => 'SD Negeri 1',
            'npsn' => '12345678',
            'jenis_perlombaan' => 'FLS2N',
            'tahun_perlombaan' => '2026',
            'pengambilan' => 'Diambil Langsung',
            'nama_pengambil' => 'Budi Guru',
            'jabatan' => 'Guru Pendamping',
            'unit_kerja' => 'SD Negeri 1',
            'ttd_pengambil' => 'https://drive.google.com/file/d/drive-file-ttd-001/view',
        ]]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function writeJson(string $path, string $filename, array $rows): void
    {
        File::put($path.DIRECTORY_SEPARATOR.$filename, json_encode($rows, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, mixed>
     */
    private function readReport(): array
    {
        return json_decode(File::get(storage_path('migration/reconciliation.json')), true);
    }
}
