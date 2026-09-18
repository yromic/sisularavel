<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class MigrateLegacySisurat extends Command
{
    protected $signature = 'sisurat:migrate-legacy {--path= : Directory containing exported legacy JSON files} {--dry-run : Roll back database changes after reconciliation}';

    protected $description = 'Import SISURAT legacy JSON exports into the relational schema.';

    /** @var array<string, array{total:int, imported:int, quarantined:int}> */
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
            $this->importIncomingLetters($path);
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

    private function importIncomingLetters(string $path): void
    {
        foreach (File::glob($path.DIRECTORY_SEPARATOR.'*_surat_masuk.json') as $file) {
            $table = pathinfo($file, PATHINFO_FILENAME);
            $divisionCode = Str::before($table, '_surat_masuk');

            foreach ($this->decodeRows($file, $table) as $row) {
                try {
                    $division = $this->divisionByCode($this->divisionCode($row['divisi_id'] ?? $divisionCode));

                    $letter = IncomingLetter::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'division_id' => $division->id,
                        'letter_number' => $this->cleanText($row['nomor_surat'] ?? ''),
                        'origin_agency' => $this->cleanText($row['asal_surat'] ?? ''),
                        'letter_date' => $this->date($row['tanggal_surat'] ?? null),
                        'received_date' => $this->date($row['tanggal_diterima'] ?? null),
                        'subject' => $this->cleanText($row['perihal'] ?? ''),
                    ]);

                    if ($this->nullableText($row['upload_file'] ?? null) !== null) {
                        $this->recordMissingAttachment($division, $letter, (string) $row['upload_file']);
                    }

                    $this->imported($table);
                } catch (Throwable $exception) {
                    $this->quarantine($table, $row, $exception->getMessage());
                }
            }
        }
    }

    private function recordMissingAttachment(Division $division, IncomingLetter $letter, string $legacyUrl): void
    {
        Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => null,
            'original_name' => 'legacy-drive-file',
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
            $this->tables[$table] = ['total' => 0, 'imported' => 0, 'quarantined' => 0];

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
        $this->tables[$table] ??= ['total' => count($rows), 'imported' => 0, 'quarantined' => 0];

        return $rows;
    }

    private function imported(string $table): void
    {
        $this->tables[$table]['imported']++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function quarantine(string $table, array $row, string $error): void
    {
        $this->tables[$table] ??= ['total' => 0, 'imported' => 0, 'quarantined' => 0];
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
