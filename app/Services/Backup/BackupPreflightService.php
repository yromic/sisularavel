<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class BackupPreflightService
{
    /**
     * Mandatory PHP extensions required by AGENTS.md and docs/backup.md.
     */
    public const MANDATORY_EXTENSIONS = [
        'pdo_mysql',
        'mbstring',
        'openssl',
        'fileinfo',
        'curl',
        'xml',
        'ctype',
    ];

    /**
     * Run the full technical audit and preflight checks.
     */
    public function runAudit(array $options = []): array
    {
        $checks = [];

        // 1. PHP Runtime & Version
        $checks[] = $this->checkPhpVersion();

        // 2. Mandatory Extensions
        $checks[] = $this->checkMandatoryExtensions();

        // 3. Recommended / Backup Specific Extensions
        $checks[] = $this->checkBackupExtensions();

        // 4. Authenticated Cryptographic Cipher (AES-256-GCM)
        $checks[] = $this->checkCipherSupport();

        // 5. Memory Limit
        $checks[] = $this->checkMemoryLimit();

        // 6. Execution Time Limit
        $checks[] = $this->checkExecutionTimeLimit();

        // 7. Database Connectivity & Relational Engine
        $dbCheck = $this->checkDatabaseConnection();
        $checks[] = $dbCheck['check'];

        // 8. MySQL Dump Tooling Availability
        $checks[] = $this->checkMysqldumpAvailability();

        // 9. Private Storage Directories & Permissions
        $storageDirChecks = $this->checkPrivateStorageDirectories();
        foreach ($storageDirChecks as $dirCheck) {
            $checks[] = $dirCheck;
        }

        // 10. SSD Storage Capacity & Thresholds
        $diskCheck = $this->checkDiskCapacity(
            $options['disk_path'] ?? null,
            $options['override_used_percent'] ?? null,
            $options['override_free_bytes'] ?? null
        );
        $checks[] = $diskCheck['check'];

        // 11. CLI / Cron Readiness
        $checks[] = $this->checkCronCliReadiness();

        // Aggregate statistics
        $passedCount = 0;
        $warnCount = 0;
        $failCount = 0;
        $hasCriticalFail = false;

        foreach ($checks as $c) {
            if ($c['status'] === 'PASS') {
                $passedCount++;
            } elseif ($c['status'] === 'WARN') {
                $warnCount++;
            } elseif ($c['status'] === 'FAIL') {
                $failCount++;
                if (! empty($c['critical'])) {
                    $hasCriticalFail = true;
                }
            }
        }

        $storageStats = $this->gatherStorageStats($diskCheck['data'], $dbCheck['meta'] ?? []);

        return [
            'timestamp' => now()->toIso8601String(),
            'is_ready' => ! $hasCriticalFail && $failCount === 0,
            'has_warnings' => $warnCount > 0,
            'has_failures' => $failCount > 0,
            'summary' => [
                'total_checks' => count($checks),
                'passed' => $passedCount,
                'warnings' => $warnCount,
                'failed' => $failCount,
            ],
            'checks' => $checks,
            'storage_stats' => $storageStats,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS_FAMILY.' ('.PHP_OS.')',
                'sapi' => PHP_SAPI,
                'db_version' => $dbCheck['meta']['version'] ?? 'Unknown',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => (int) ini_get('max_execution_time'),
            ],
        ];
    }

    /**
     * Check PHP version >= 8.2.0.
     */
    public function checkPhpVersion(): array
    {
        $version = PHP_VERSION;
        $minVersion = '8.2.0';
        $passes = version_compare($version, $minVersion, '>=');

        return [
            'key' => 'php_version',
            'category' => 'Runtime & Environment',
            'name' => 'PHP Version',
            'status' => $passes ? 'PASS' : 'FAIL',
            'critical' => true,
            'message' => $passes
                ? "PHP {$version} satisfies the minimum target requirement (>= {$minVersion})."
                : "PHP {$version} is below the required baseline of >= {$minVersion}.",
            'data' => [
                'current' => $version,
                'required' => $minVersion,
            ],
        ];
    }

    /**
     * Check all mandatory PHP extensions.
     */
    public function checkMandatoryExtensions(): array
    {
        $missing = [];
        foreach (self::MANDATORY_EXTENSIONS as $ext) {
            if (! extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        // Image extension check (either gd or imagick is acceptable)
        $hasImageExt = extension_loaded('gd') || extension_loaded('imagick');
        if (! $hasImageExt) {
            $missing[] = 'gd (or imagick)';
        }

        $passes = empty($missing);

        return [
            'key' => 'mandatory_extensions',
            'category' => 'Runtime & Environment',
            'name' => 'Mandatory PHP Extensions',
            'status' => $passes ? 'PASS' : 'FAIL',
            'critical' => true,
            'message' => $passes
                ? 'All mandatory extensions (pdo_mysql, mbstring, openssl, fileinfo, gd/imagick, curl, xml, ctype) are loaded.'
                : 'Missing mandatory extension(s): '.implode(', ', $missing),
            'data' => [
                'checked' => array_merge(self::MANDATORY_EXTENSIONS, ['gd/imagick']),
                'missing' => $missing,
            ],
        ];
    }

    /**
     * Check optional / backup extensions (zip, zlib, sodium).
     */
    public function checkBackupExtensions(): array
    {
        $details = [
            'zip' => extension_loaded('zip'),
            'zlib' => extension_loaded('zlib'),
            'sodium' => extension_loaded('sodium'),
        ];

        $hasNativeZip = $details['zip'];

        return [
            'key' => 'backup_extensions',
            'category' => 'Runtime & Environment',
            'name' => 'Archive Compression Extensions (zip/zlib)',
            'status' => $hasNativeZip ? 'PASS' : 'WARN',
            'critical' => false,
            'message' => $hasNativeZip
                ? 'PHP zip extension (ZipArchive) is loaded and ready for archive packing.'
                : 'PHP zip extension is not loaded; archive generation will require fallback tooling.',
            'data' => $details,
        ];
    }

    /**
     * Check AES-256-GCM cipher availability.
     */
    public function checkCipherSupport(): array
    {
        $targetCipher = config('backup.encryption.cipher', 'aes-256-gcm');
        $availableCiphers = openssl_get_cipher_methods();
        $supported = in_array(strtolower($targetCipher), array_map('strtolower', $availableCiphers), true);

        return [
            'key' => 'encryption_cipher',
            'category' => 'Security & Cryptography',
            'name' => 'Authenticated Encryption Cipher (AEAD)',
            'status' => $supported ? 'PASS' : 'FAIL',
            'critical' => true,
            'message' => $supported
                ? "Target authenticated cipher '{$targetCipher}' is supported natively by OpenSSL."
                : "Target cipher '{$targetCipher}' is NOT supported by current OpenSSL.",
            'data' => [
                'target_cipher' => $targetCipher,
                'supported' => $supported,
            ],
        ];
    }

    /**
     * Check memory limit (>= 128M recommended, >= 256M optimal).
     */
    public function checkMemoryLimit(): array
    {
        $limitStr = ini_get('memory_limit');
        $limitBytes = $this->parseBytes($limitStr);
        $minMb = (int) config('backup.thresholds.min_memory_mb', 128);
        $minBytes = $minMb * 1024 * 1024;

        if ($limitBytes === -1) {
            $status = 'PASS';
            $msg = 'Memory limit is unlimited (-1).';
        } elseif ($limitBytes >= 256 * 1024 * 1024) {
            $status = 'PASS';
            $msg = "Memory limit is {$limitStr} (>= 256M recommended).";
        } elseif ($limitBytes >= $minBytes) {
            $status = 'WARN';
            $msg = "Memory limit is {$limitStr}. Sufficient, but >= 256M is recommended for large file streams.";
        } else {
            $status = 'FAIL';
            $msg = "Memory limit is {$limitStr}, which is below the minimum required {$minMb}M.";
        }

        return [
            'key' => 'memory_limit',
            'category' => 'Resources & Capacity',
            'name' => 'PHP Memory Limit',
            'status' => $status,
            'critical' => ($status === 'FAIL'),
            'message' => $msg,
            'data' => [
                'raw' => $limitStr,
                'bytes' => $limitBytes,
                'min_recommended_mb' => $minMb,
            ],
        ];
    }

    /**
     * Check execution time limit for CLI and Web.
     */
    public function checkExecutionTimeLimit(): array
    {
        $maxTime = (int) ini_get('max_execution_time');
        $isCli = (PHP_SAPI === 'cli');

        if ($isCli) {
            $status = ($maxTime === 0 || $maxTime >= 300) ? 'PASS' : 'WARN';
            $msg = ($maxTime === 0)
                ? 'CLI execution time is unlimited (0), optimal for background and artisan backups.'
                : "CLI execution time limit is {$maxTime} seconds.";
        } else {
            $status = ($maxTime >= 60) ? 'PASS' : 'WARN';
            $msg = "Web SAPI execution time is {$maxTime} seconds. Manual backups must be dispatched asynchronously to avoid HTTP timeouts.";
        }

        return [
            'key' => 'execution_time_limit',
            'category' => 'Resources & Capacity',
            'name' => 'Max Execution Time',
            'status' => $status,
            'critical' => false,
            'message' => $msg,
            'data' => [
                'seconds' => $maxTime,
                'sapi' => PHP_SAPI,
            ],
        ];
    }

    /**
     * Check database connectivity and MariaDB/MySQL engine compatibility.
     */
    public function checkDatabaseConnection(): array
    {
        try {
            $pdo = DB::connection()->getPdo();
            $versionResult = DB::select('SELECT VERSION() as v');
            $rawVersion = $versionResult[0]->v ?? 'Unknown';

            $dbName = config('database.connections.'.config('database.default').'.database');

            // Query table count and approximate size from information_schema
            $tableStats = DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', $dbName)
                ->selectRaw('COUNT(*) as total_tables, COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH), 0) as total_size')
                ->first();

            $tableCount = (int) ($tableStats->total_tables ?? 0);
            $totalSizeBytes = (int) ($tableStats->total_size ?? 0);

            return [
                'check' => [
                    'key' => 'database_connection',
                    'category' => 'Database Engine',
                    'name' => 'MySQL / MariaDB Connection',
                    'status' => 'PASS',
                    'critical' => true,
                    'message' => "Successfully connected to {$dbName} on server version {$rawVersion} ({$tableCount} tables).",
                    'data' => [
                        'database' => $dbName,
                        'version' => $rawVersion,
                        'tables_count' => $tableCount,
                        'size_bytes' => $totalSizeBytes,
                    ],
                ],
                'meta' => [
                    'version' => $rawVersion,
                    'database' => $dbName,
                    'tables_count' => $tableCount,
                    'size_bytes' => $totalSizeBytes,
                ],
            ];
        } catch (Throwable $e) {
            return [
                'check' => [
                    'key' => 'database_connection',
                    'category' => 'Database Engine',
                    'name' => 'MySQL / MariaDB Connection',
                    'status' => 'FAIL',
                    'critical' => true,
                    'message' => 'Database connection failed: '.$e->getMessage(),
                    'data' => ['error' => $e->getMessage()],
                ],
                'meta' => [],
            ];
        }
    }

    /**
     * Check mysqldump binary availability.
     */
    public function checkMysqldumpAvailability(): array
    {
        $resolvedPath = $this->resolveMysqldumpPath();

        if ($resolvedPath !== null) {
            $versionOutput = $this->testMysqldumpVersion($resolvedPath);

            return [
                'key' => 'mysqldump_binary',
                'category' => 'Database Engine',
                'name' => 'MySQL Dump Tool (mysqldump)',
                'status' => 'PASS',
                'critical' => false,
                'message' => "mysqldump found at '{$resolvedPath}'".($versionOutput ? " ({$versionOutput})" : ''),
                'data' => [
                    'path' => $resolvedPath,
                    'version' => $versionOutput,
                    'is_fallback_needed' => false,
                ],
            ];
        }

        return [
            'key' => 'mysqldump_binary',
            'category' => 'Database Engine',
            'name' => 'MySQL Dump Tool (mysqldump)',
            'status' => 'WARN',
            'critical' => false,
            'message' => 'mysqldump binary not detected in PATH or common directories. Configure MYSQL_DUMP_PATH in .env or utilize PDO query snapshot fallback.',
            'data' => [
                'path' => null,
                'is_fallback_needed' => true,
            ],
        ];
    }

    /**
     * Check private storage directories and permissions.
     */
    public function checkPrivateStorageDirectories(): array
    {
        $checks = [];

        $directories = [
            'documents' => [
                'name' => 'Private Documents Storage',
                'path' => storage_path('app/private/documents'),
            ],
            'signatures' => [
                'name' => 'Private Signatures Storage',
                'path' => storage_path('app/private/signatures'),
            ],
            'temp' => [
                'name' => 'Backup Staging Working Directory',
                'path' => config('backup.temp_path', storage_path('app/private/backup-temp')),
            ],
        ];

        foreach ($directories as $key => $dir) {
            $path = $dir['path'];

            // Ensure directory exists if possible
            if (! File::exists($path)) {
                try {
                    File::makeDirectory($path, 0755, true, true);
                } catch (Throwable) {
                    // Ignore, will be caught by is_readable/is_writable checks
                }
            }

            $exists = File::exists($path) && File::isDirectory($path);
            $readable = $exists && is_readable($path);
            $writable = $exists && is_writable($path);

            $passes = $exists && $readable && $writable;

            $checks[] = [
                'key' => 'storage_dir_'.$key,
                'category' => 'Storage & Permissions',
                'name' => $dir['name'],
                'status' => $passes ? 'PASS' : 'FAIL',
                'critical' => true,
                'message' => $passes
                    ? "Directory '{$path}' exists and is fully readable and writable."
                    : "Directory '{$path}' is not accessible or lacks write permissions.",
                'data' => [
                    'path' => $path,
                    'exists' => $exists,
                    'readable' => $readable,
                    'writable' => $writable,
                ],
            ];
        }

        return $checks;
    }

    /**
     * Check SSD storage capacity against safety thresholds (Section 10.4 of docs/backup.md).
     *
     * <= 70%: Normal (PASS)
     * 71% - 89%: Warning (WARN)
     * >= 90% or free space < min_free_bytes: Critical (FAIL - auto abort)
     */
    public function checkDiskCapacity(?string $path = null, ?float $overrideUsedPercent = null, ?int $overrideFreeBytes = null): array
    {
        $targetPath = $path ?? storage_path('app/private');

        $warnThreshold = (float) config('backup.thresholds.disk_warning_percent', 75.0);
        $critThreshold = (float) config('backup.thresholds.disk_critical_percent', 90.0);
        $minFreeBytes = (int) config('backup.thresholds.min_free_bytes', 100 * 1024 * 1024);

        if ($overrideUsedPercent !== null) {
            $usedPercent = $overrideUsedPercent;
            $freeBytes = $overrideFreeBytes ?? (int) (100 * 1024 * 1024);
            $totalBytes = (int) ($freeBytes / max(0.01, (1 - ($usedPercent / 100))));
            $usedBytes = $totalBytes - $freeBytes;
        } else {
            $freeBytes = @disk_free_space($targetPath);
            $totalBytes = @disk_total_space($targetPath);

            if ($freeBytes === false || $totalBytes === false || $totalBytes <= 0) {
                return [
                    'check' => [
                        'key' => 'disk_capacity',
                        'category' => 'Resources & Capacity',
                        'name' => 'Hosting SSD Storage Capacity',
                        'status' => 'WARN',
                        'critical' => false,
                        'message' => "Unable to read disk capacity metrics on path '{$targetPath}'.",
                        'data' => ['path' => $targetPath],
                    ],
                    'data' => [
                        'total_bytes' => 0,
                        'free_bytes' => 0,
                        'used_bytes' => 0,
                        'used_percent' => 0.0,
                    ],
                ];
            }

            $usedBytes = $totalBytes - $freeBytes;
            $usedPercent = round(($usedBytes / $totalBytes) * 100, 2);
        }

        $freeGb = round($freeBytes / (1024 * 1024 * 1024), 2);
        $totalGb = round($totalBytes / (1024 * 1024 * 1024), 2);

        if ($usedPercent >= $critThreshold || $freeBytes < $minFreeBytes) {
            $status = 'FAIL';
            $msg = "CRITICAL: Disk usage is at {$usedPercent}% (Free: {$freeGb} GB / {$totalGb} GB). Exceeds {$critThreshold}% limit; backups blocked to prevent server crash.";
            $critical = true;
        } elseif ($usedPercent >= $warnThreshold) {
            $status = 'WARN';
            $msg = "WARNING: Disk usage is at {$usedPercent}% (Free: {$freeGb} GB / {$totalGb} GB). Exceeds warning threshold ({$warnThreshold}%).";
            $critical = false;
        } else {
            $status = 'PASS';
            $msg = "Disk usage is normal: {$usedPercent}% used ({$freeGb} GB free of {$totalGb} GB).";
            $critical = false;
        }

        return [
            'check' => [
                'key' => 'disk_capacity',
                'category' => 'Resources & Capacity',
                'name' => 'Hosting SSD Storage Capacity',
                'status' => $status,
                'critical' => $critical,
                'message' => $msg,
                'data' => [
                    'path' => $targetPath,
                    'total_bytes' => $totalBytes,
                    'used_bytes' => $usedBytes,
                    'free_bytes' => $freeBytes,
                    'used_percent' => $usedPercent,
                    'warning_threshold' => $warnThreshold,
                    'critical_threshold' => $critThreshold,
                    'min_free_bytes' => $minFreeBytes,
                ],
            ],
            'data' => [
                'total_bytes' => $totalBytes,
                'used_bytes' => $usedBytes,
                'free_bytes' => $freeBytes,
                'used_percent' => $usedPercent,
            ],
        ];
    }

    /**
     * Check CLI & cPanel Cron readiness.
     */
    public function checkCronCliReadiness(): array
    {
        $artisanPath = base_path('artisan');
        $artisanExists = File::exists($artisanPath);
        $phpBinary = PHP_BINARY;

        $passes = $artisanExists && ! empty($phpBinary);

        return [
            'key' => 'cron_cli_readiness',
            'category' => 'Automation & Scheduler',
            'name' => 'cPanel Cron / Artisan CLI Runner',
            'status' => $passes ? 'PASS' : 'WARN',
            'critical' => false,
            'message' => $passes
                ? "Artisan entrypoint and PHP CLI executable detected ({$phpBinary}). Ready for cPanel crontab: * * * * * {$phpBinary} {$artisanPath} schedule:run"
                : 'Artisan entrypoint or PHP CLI binary could not be resolved.',
            'data' => [
                'artisan_path' => $artisanPath,
                'php_binary' => $phpBinary,
            ],
        ];
    }

    /**
     * Resolve path to mysqldump binary.
     */
    public function resolveMysqldumpPath(): ?string
    {
        // 1. Explicitly configured path in config or env
        $configured = config('backup.mysqldump_path');
        if (! empty($configured) && File::exists($configured)) {
            return $configured;
        }

        // 2. Common paths on Windows (XAMPP, Laragon, MySQL install)
        $windowsCandidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'D:\\xampp\\mysql\\bin\\mysqldump.exe',
            'E:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\current\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB 10.4\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB 10.5\\bin\\mysqldump.exe',
        ];

        // 3. Common paths on Linux / Unix / Shared Hosting / cPanel
        $linuxCandidates = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/cpanel/3rdparty/bin/mysqldump',
            '/opt/lampp/bin/mysqldump',
        ];

        $candidates = (PHP_OS_FAMILY === 'Windows') ? $windowsCandidates : $linuxCandidates;

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        // 4. Try resolving via system PATH using where/which
        $whichCommand = (PHP_OS_FAMILY === 'Windows') ? 'where mysqldump' : 'which mysqldump';
        try {
            $process = Process::fromShellCommandline($whichCommand);
            $process->run();
            if ($process->isSuccessful()) {
                $output = trim(explode("\n", $process->getOutput())[0] ?? '');
                if (! empty($output) && File::exists($output)) {
                    return $output;
                }
            }
        } catch (Throwable) {
            // Process execution may be disabled in some shared hosting environments
        }

        return null;
    }

    /**
     * Test executing mysqldump --version.
     */
    protected function testMysqldumpVersion(string $binaryPath): ?string
    {
        try {
            $process = new Process([$binaryPath, '--version']);
            $process->setTimeout(5);
            $process->run();

            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        } catch (Throwable) {
            // Non-critical, ignore
        }

        return null;
    }

    /**
     * Gather file counts, sizes, and disk inventory statistics.
     */
    public function gatherStorageStats(array $diskData, array $dbMeta = []): array
    {
        $docCount = 0;
        $docBytes = 0;
        $sigCount = 0;
        $sigBytes = 0;

        try {
            if (Storage::disk('private_documents')->exists('')) {
                $docFiles = Storage::disk('private_documents')->allFiles();
                $docCount = count($docFiles);
                foreach ($docFiles as $f) {
                    $docBytes += Storage::disk('private_documents')->size($f);
                }
            }
        } catch (Throwable) {
            // Ignore if empty
        }

        try {
            if (Storage::disk('private_signatures')->exists('')) {
                $sigFiles = Storage::disk('private_signatures')->allFiles();
                $sigCount = count($sigFiles);
                foreach ($sigFiles as $f) {
                    $sigBytes += Storage::disk('private_signatures')->size($f);
                }
            }
        } catch (Throwable) {
            // Ignore if empty
        }

        return [
            'disk_total_bytes' => $diskData['total_bytes'] ?? 0,
            'disk_used_bytes' => $diskData['used_bytes'] ?? 0,
            'disk_free_bytes' => $diskData['free_bytes'] ?? 0,
            'disk_used_percent' => $diskData['used_percent'] ?? 0.0,
            'documents_count' => $docCount,
            'documents_size_bytes' => $docBytes,
            'signatures_count' => $sigCount,
            'signatures_size_bytes' => $sigBytes,
            'database_tables_count' => $dbMeta['tables_count'] ?? 0,
            'database_size_bytes' => $dbMeta['size_bytes'] ?? 0,
        ];
    }

    /**
     * Parse ini string like '512M', '1G' into integer bytes.
     */
    protected function parseBytes(string $val): int
    {
        $val = trim($val);
        if ($val === '-1') {
            return -1;
        }

        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;

        switch ($last) {
            case 'g':
                $val *= 1024;
                // no break
            case 'm':
                $val *= 1024;
                // no break
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
