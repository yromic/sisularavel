<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class MigrateLegacySisurat extends Command
{
    protected $signature = 'sisurat:migrate-legacy {--path= : Directory containing exported legacy JSON files} {--dry-run : Roll back database changes after reconciliation}';

    protected $description = 'Import SISURAT legacy JSON exports into the relational schema.';

    /** @var array<string, array{total:int, imported:int, duplicate:int, quarantined:int, failed:int}> */
    private array $tables = [];

    private int $missingAttachments = 0;

    private int $quarantinedRows = 0;

    public function handle(): int
    {
        $path = $this->option('path');

        if (! is_string($path) || $path === '' || ! File::isDirectory($path)) {
            $this->error('A valid --path directory is required.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(storage_path('migration'));

        try {
            DB::beginTransaction();

            $this->importDivisions($path);
            $this->importUsers($path);
            $this->importSchools($path);
            $this->importCompetitionCategories($path);
            $this->importPickupCategories($path);
            $this->importIncomingLetters($path);
            $this->importOutgoingLetters($path);
            $this->importPiagams($path);

            $this->writeReconciliationReport();

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->info('Legacy migration dry run completed; database changes rolled back.');

                return self::SUCCESS;
            }

            DB::commit();
            $this->info('Legacy migration completed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    private function importDivisions(string $path): void
    {
        foreach ($this->rows($path, 'db_divisi') as $row) {
            try {
                Division::query()->updateOrCreate(
                    ['code' => $this->divisionCode($row['kode_divisi'] ?? '')],
                    [
                        'uuid' => (string) Str::uuid(),
                        'name' => $this->cleanText($row['nama_divisi'] ?? ''),
                        'status' => $this->truthy($row['status'] ?? true) ? 'active' : 'inactive',
                        'legacy_drive_folder_id' => $this->nullableText($row['drive_folder_id'] ?? null),
                    ]
                );

                $this->imported('db_divisi');
            } catch (Throwable $exception) {
                $this->quarantine('db_divisi', $row, $exception->getMessage());
            }
        }
    }

    private function importUsers(string $path): void
    {
        foreach ($this->rows($path, 'db_users') as $row) {
            try {
                $role = $this->role($row['role'] ?? 'operator');
                $divisionId = null;

                if ($role !== 'super_admin') {
                    $divisionId = $this->divisionByCode($this->divisionCode($row['divisi_id'] ?? ''))->id;
                }

                User::query()->updateOrCreate(
                    ['username' => Str::lower($this->cleanText($row['username'] ?? ''))],
                    [
                        'uuid' => (string) Str::uuid(),
                        'name' => $this->cleanText($row['nama'] ?? $row['username'] ?? ''),
                        'email' => Str::lower((string) $this->nullableText($row['email'] ?? null)),
                        'password' => Hash::make(Str::random(40)),
                        'role' => $role,
                        'division_id' => $divisionId,
                        'is_active' => $this->truthy($row['aktif'] ?? true),
                        'must_reset_password' => true,
                    ]
                );

                $this->imported('db_users');
            } catch (Throwable $exception) {
                $this->quarantine('db_users', $row, $exception->getMessage());
            }
        }
    }

    private function importSchools(string $path): void
    {
        foreach ($this->rows($path, 'ref_sekolah') as $row) {
            try {
                School::query()->updateOrCreate(
                    ['npsn' => $this->npsn($row['npsn'] ?? '')],
                    [
                        'name' => $this->cleanText($row['nama_sekolah'] ?? ''),
                        'is_active' => $this->truthy($row['aktif'] ?? true),
                    ]
                );

                $this->imported('ref_sekolah');
            } catch (Throwable $exception) {
                $this->quarantine('ref_sekolah', $row, $exception->getMessage());
            }
        }
    }

    private function importCompetitionCategories(string $path): void
    {
        foreach (File::glob($path.DIRECTORY_SEPARATOR.'*_ref_jenis.json') as $file) {
            $table = pathinfo($file, PATHINFO_FILENAME);
            $divisionCode = Str::before($table, '_ref_jenis');

            foreach ($this->decodeRows($file, $table) as $row) {
                try {
                    $division = $this->divisionByCode($this->divisionCode($row['divisi_id'] ?? $divisionCode));
                    $name = $this->cleanText($row['nama_jenis'] ?? $row['nama'] ?? $row['name'] ?? '');
                    if ($name === '') {
                        throw new \RuntimeException('Nama jenis perlombaan kosong.');
                    }

                    CompetitionCategory::query()->firstOrCreate(
                        ['division_id' => $division->id, 'name' => $name],
                        ['is_active' => $this->truthy($row['aktif'] ?? true)]
                    );

                    $this->imported($table);
                } catch (Throwable $exception) {
                    $this->quarantine($table, $row, $exception->getMessage());
                }
            }
        }
    }

    private function importPickupCategories(string $path): void
    {
        foreach (File::glob($path.DIRECTORY_SEPARATOR.'*_ref_pengambilan.json') as $file) {
            $table = pathinfo($file, PATHINFO_FILENAME);
            $divisionCode = Str::before($table, '_ref_pengambilan');

            foreach ($this->decodeRows($file, $table) as $row) {
                try {
                    $division = $this->divisionByCode($this->divisionCode($row['divisi_id'] ?? $divisionCode));
                    $name = $this->cleanText($row['nama_pengambilan'] ?? $row['nama'] ?? $row['name'] ?? '');
                    if ($name === '') {
                        throw new \RuntimeException('Nama kategori pengambilan kosong.');
                    }

                    PickupCategory::query()->firstOrCreate(
                        ['division_id' => $division->id, 'name' => $name],
                        ['is_active' => $this->truthy($row['aktif'] ?? true)]
                    );

                    $this->imported($table);
                } catch (Throwable $exception) {
                    $this->quarantine($table, $row, $exception->getMessage());
                }
            }
        }
    }

    private function importIncomingLetters(string $path): void
    {
        foreach (File::glob($path.DIRECTORY_SEPARATOR.'*_surat_masuk.json') as $file) {
            $table = pathinfo($file, PATHINFO_FILENAME);
            $divisionCode = Str::before($table, '_surat_masuk');

            foreach ($this->decodeRows($file, $table) as $row) {
                try {
                    $division = $this->divisionByCode($this->divisionCode($row['divisi_id'] ?? $divisionCode));
                    $letterNumber = $this->cleanText($row['nomor_surat'] ?? '');

                    $letter = IncomingLetter::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'division_id' => $division->id,
                        'letter_number' => $letterNumber,
                        'origin_agency' => $this->cleanText($row['asal_surat'] ?? ''),
                        'letter_date' => $this->date($row['tanggal_surat'] ?? null),
                        'received_date' => $this->date($row['tanggal_diterima'] ?? null),
                        'subject' => $this->cleanText($row['perihal'] ?? ''),
                    ]);

                    if ($this->nullableText($row['upload_file'] ?? null) !== null) {
                        $this->recordMissingAttachment($division, $letter, (string) $row['upload_file'], 'legacy-drive-file');
                    }

                    $this->imported($table);
                } catch (Throwable $exception) {
                    $this->quarantine($table, $row, $exception->getMessage());
                }
            }
        }
    }

    private function importOutgoingLetters(string $path): void
    {
        foreach (File::glob($path.DIRECTORY_SEPARATOR.'*_surat_keluar.json') as $file) {
            $table = pathinfo($file, PATHINFO_FILENAME);
            $divisionCode = Str::before($table, '_surat_keluar');

            foreach ($this->decodeRows($file, $table) as $row) {
                try {
                    $division = $this->divisionByCode($this->divisionCode($row['divisi_id'] ?? $divisionCode));
                    $letterDate = $this->date($row['tanggal_surat'] ?? null);
                    $year = (int) (Carbon::parse($letterDate)->year);
                    $letterNumber = $this->cleanText($row['nomor_surat'] ?? '');

                    if ($letterNumber === '') {
                        throw new \RuntimeException('Nomor surat keluar tidak boleh kosong.');
                    }

                    $existing = OutgoingLetter::query()
                        ->where('division_id', $division->id)
                        ->where('letter_year', $year)
                        ->where('letter_number', $letterNumber)
                        ->first();

                    if ($existing) {
                        $this->duplicated($table);

                        continue;
                    }

                    $letter = OutgoingLetter::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'division_id' => $division->id,
                        'letter_year' => $year,
                        'letter_number' => $letterNumber,
                        'letter_date' => $letterDate,
                        'disseminated_date' => ! empty($row['tanggal_share']) ? $this->date($row['tanggal_share']) : null,
                        'subject' => $this->cleanText($row['perihal'] ?? ''),
                    ]);

                    if ($this->nullableText($row['upload_file'] ?? null) !== null) {
                        $this->recordMissingAttachment($division, $letter, (string) $row['upload_file'], 'legacy-outgoing-letter');
                    }

                    $this->imported($table);
                } catch (Throwable $exception) {
                    $this->quarantine($table, $row, $exception->getMessage());
                }
            }
        }
    }

    private function importPiagams(string $path): void
    {
        foreach (File::glob($path.DIRECTORY_SEPARATOR.'*_piagam.json') as $file) {
            $table = pathinfo($file, PATHINFO_FILENAME);
            $divisionCode = Str::before($table, '_piagam');

            foreach ($this->decodeRows($file, $table) as $row) {
                try {
                    $division = $this->divisionByCode($this->divisionCode($row['divisi_id'] ?? $divisionCode));
                    $studentName = $this->cleanText($row['nama_siswa'] ?? '');
                    if ($studentName === '') {
                        throw new \RuntimeException('Nama siswa piagam tidak boleh kosong.');
                    }

                    // School resolution
                    $npsn = ! empty($row['npsn']) ? $this->npsn($row['npsn']) : null;
                    $schoolName = $this->cleanText($row['asal_sekolah'] ?? $row['nama_sekolah'] ?? '');
                    $school = null;

                    if ($npsn) {
                        $school = School::query()->where('npsn', $npsn)->first();
                    }
                    if (! $school && $schoolName !== '') {
                        $school = School::query()->where('name', $schoolName)->first();
                    }
                    if (! $school) {
                        $school = School::query()->create([
                            'npsn' => $npsn ?? str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                            'name' => $schoolName !== '' ? $schoolName : 'Sekolah Belum Terdata',
                            'is_active' => true,
                        ]);
                    }

                    // Competition category resolution
                    $compCatName = $this->cleanText($row['jenis_perlombaan'] ?? $row['kategori_lomba'] ?? 'Umum');
                    $competitionCategory = CompetitionCategory::query()->firstOrCreate(
                        ['division_id' => $division->id, 'name' => $compCatName],
                        ['is_active' => true]
                    );

                    // Competition year
                    $compYear = (int) ($row['tahun_perlombaan'] ?? $row['tahun'] ?? date('Y'));

                    // Pickup category resolution
                    $pickupCatName = $this->cleanText($row['pengambilan'] ?? $row['jenis_pengambilan'] ?? '');
                    $pickupCategory = null;
                    if ($pickupCatName !== '') {
                        $pickupCategory = PickupCategory::query()->firstOrCreate(
                            ['division_id' => $division->id, 'name' => $pickupCatName],
                            ['is_active' => true]
                        );
                    }

                    $existing = Piagam::query()
                        ->where('division_id', $division->id)
                        ->where('student_name', $studentName)
                        ->where('school_id', $school->id)
                        ->where('competition_category_id', $competitionCategory->id)
                        ->where('competition_year', $compYear)
                        ->first();

                    if ($existing) {
                        $this->duplicated($table);

                        continue;
                    }

                    $piagam = Piagam::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'division_id' => $division->id,
                        'student_name' => $studentName,
                        'school_id' => $school->id,
                        'competition_category_id' => $competitionCategory->id,
                        'competition_year' => $compYear,
                        'pickup_category_id' => $pickupCategory?->id,
                        'recipient_name' => $this->nullableText($row['nama_pengambil'] ?? null),
                        'recipient_position' => $this->nullableText($row['jabatan'] ?? null),
                        'recipient_unit' => $this->nullableText($row['unit_kerja'] ?? null),
                    ]);

                    if ($this->nullableText($row['ttd_pengambil'] ?? null) !== null) {
                        $this->recordMissingAttachment($division, $piagam, (string) $row['ttd_pengambil'], 'legacy-piagam-signature');
                    }

                    $this->imported($table);
                } catch (Throwable $exception) {
                    $this->quarantine($table, $row, $exception->getMessage());
                }
            }
        }
    }

    private function recordMissingAttachment(Division $division, Model $attachable, string $legacyUrl, string $originalName = 'legacy-drive-file'): void
    {
        Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => get_class($attachable),
            'attachable_id' => $attachable->getKey(),
            'file_path' => null,
            'original_name' => $originalName,
            'file_size_bytes' => 0,
            'mime_type' => 'application/octet-stream',
            'file_hash' => null,
            'is_missing' => true,
            'migration_status' => 'missing_drive_file',
            'missing_reason' => 'Google Drive file not fetched during migration import.',
            'legacy_drive_file_id' => $this->driveFileId($legacyUrl),
            'legacy_drive_url' => $legacyUrl,
        ]);

        $this->missingAttachments++;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(string $path, string $table): array
    {
        $file = $path.DIRECTORY_SEPARATOR.$table.'.json';

        if (! File::exists($file)) {
            $this->tables[$table] = ['total' => 0, 'imported' => 0, 'duplicate' => 0, 'quarantined' => 0, 'failed' => 0];

            return [];
        }

        return $this->decodeRows($file, $table);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeRows(string $file, string $table): array
    {
        $rows = json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);
        $rows = is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
        $this->tables[$table] ??= ['total' => count($rows), 'imported' => 0, 'duplicate' => 0, 'quarantined' => 0, 'failed' => 0];

        return $rows;
    }

    private function imported(string $table): void
    {
        $this->tables[$table]['imported']++;
    }

    private function duplicated(string $table): void
    {
        $this->tables[$table]['duplicate']++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function quarantine(string $table, array $row, string $error): void
    {
        $this->tables[$table] ??= ['total' => 0, 'imported' => 0, 'duplicate' => 0, 'quarantined' => 0, 'failed' => 0];
        $this->tables[$table]['quarantined']++;
        $this->quarantinedRows++;

        $file = storage_path("migration/quarantine_{$table}.json");
        $rows = File::exists($file) ? json_decode(File::get($file), true) : [];
        $rows[] = ['row' => $row, 'error' => $error];

        File::put($file, json_encode($rows, JSON_PRETTY_PRINT));
    }

    private function writeReconciliationReport(): void
    {
        File::put(storage_path('migration/reconciliation.json'), json_encode([
            'tables' => $this->tables,
            'missing_attachments' => $this->missingAttachments,
            'quarantined_rows' => $this->quarantinedRows,
        ], JSON_PRETTY_PRINT));

        $rows = [];
        foreach ($this->tables as $entity => $stats) {
            $rows[] = [
                $entity,
                $stats['total'] ?? 0,
                $stats['imported'] ?? 0,
                $stats['duplicate'] ?? 0,
                $stats['quarantined'] ?? 0,
                $stats['failed'] ?? 0,
            ];
        }

        $this->table(['Entity', 'Source Count', 'Imported', 'Existing/Duplicate', 'Quarantined', 'Failed'], $rows);
    }

    private function divisionByCode(string $code): Division
    {
        $division = Division::query()->where('code', $code)->first();

        if (! $division) {
            throw new \RuntimeException("Unknown division code [{$code}].");
        }

        return $division;
    }

    private function cleanText(mixed $value): string
    {
        $text = trim(str_replace("\u{200B}", '', (string) $value));

        return ltrim($text, '=+-@');
    }

    private function nullableText(mixed $value): ?string
    {
        $text = $this->cleanText($value);

        return $text === '' ? null : $text;
    }

    private function divisionCode(mixed $value): string
    {
        return Str::upper($this->cleanText($value));
    }

    private function role(mixed $value): string
    {
        return match (Str::lower($this->cleanText($value))) {
            'super_admin' => 'super_admin',
            'admin', 'admin_divisi', 'division_admin' => 'division_admin',
            default => 'operator',
        };
    }

    private function truthy(mixed $value): bool
    {
        return in_array(Str::upper(trim((string) $value)), ['1', 'TRUE', 'AKTIF', 'ACTIVE', 'YA', 'YES'], true);
    }

    private function npsn(mixed $value): string
    {
        return str_pad(preg_replace('/\D+/', '', (string) $value) ?? '', 8, '0', STR_PAD_LEFT);
    }

    private function date(mixed $value): string
    {
        $text = trim((string) $value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $text);

                if ($date !== false && $date->format($format) === $text) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                continue;
            }
        }

        return Carbon::parse($text)->toDateString();
    }

    private function driveFileId(string $url): ?string
    {
        preg_match('/(?:id=|\/d\/)([a-zA-Z0-9_-]+)/', $url, $matches);

        return $matches[1] ?? null;
    }
}
