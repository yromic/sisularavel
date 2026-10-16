<?php

namespace App\Services\Recovery;

use App\Services\Backup\BackupPreflightService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseRecoveryService
{
    public function __construct(
        protected BackupPreflightService $preflight
    ) {}

    /**
     * Restore database from a SQL dump file.
     *
     * Attempts restoration using mysql CLI binary if available, or falls back
     * to a robust PHP PDO streaming execution engine for shared hosting environments.
     *
     * @param  string  $sqlDumpPath  Path to the uncompressed SQL dump file
     * @param  string|null  $connectionName  Database connection name (defaults to default connection)
     * @return array Execution statistics and table count
     */
    public function restore(string $sqlDumpPath, ?string $connectionName = null): array
    {
        if (! File::exists($sqlDumpPath)) {
            throw new RuntimeException("SQL dump file not found for database recovery: {$sqlDumpPath}");
        }

        if (File::size($sqlDumpPath) === 0) {
            throw new RuntimeException("SQL dump file is empty: {$sqlDumpPath}");
        }

        $connection = $connectionName ?? config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if (! is_array($dbConfig)) {
            throw new RuntimeException("Database connection '{$connection}' is not configured.");
        }

        $databaseName = $dbConfig['database'] ?? '';
        $startTime = microtime(true);
        $method = 'mysql_binary';
        $restored = false;

        $mysqlBinary = app()->environment('testing') ? null : $this->resolveMysqlBinaryPath();

        if ($mysqlBinary !== null) {
            try {
                $restored = $this->restoreViaMysqlBinary($mysqlBinary, $sqlDumpPath, $dbConfig);
            } catch (Throwable) {
                $restored = false;
            }
        }

        if (! $restored) {
            $method = 'pdo_fallback';
            $this->restoreViaPdo($sqlDumpPath, $connection);
        }

        $duration = microtime(true) - $startTime;

        // Verify restoration by counting restored base tables
        $tablesCount = DB::connection($connection)
            ->table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->count();

        return [
            'connection' => $connection,
            'database' => $databaseName,
            'tables_restored' => $tablesCount,
            'method' => $method,
            'dump_size_bytes' => File::size($sqlDumpPath),
            'duration_seconds' => round($duration, 3),
        ];
    }

    /**
     * Restore using the mysql command line client.
     */
    protected function restoreViaMysqlBinary(string $binaryPath, string $sqlDumpPath, array $dbConfig): bool
    {
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 3306;
        $user = $dbConfig['username'] ?? 'root';
        $pass = $dbConfig['password'] ?? '';
        $database = $dbConfig['database'] ?? '';

        $command = [
            $binaryPath,
            "--host={$host}",
            "--port={$port}",
            "--user={$user}",
            '--default-character-set=utf8mb4',
        ];

        if (! empty($pass)) {
            $command[] = "--password={$pass}";
        }

        $command[] = $database;

        $process = new Process($command);
        $process->setTimeout(180);

        if (File::size($sqlDumpPath) <= 20 * 1024 * 1024) {
            $process->setInput(File::get($sqlDumpPath));
        } else {
            $handle = fopen($sqlDumpPath, 'rb');
            if (! $handle) {
                return false;
            }
            $process->setInput($handle);
        }

        $process->run();

        if (isset($handle) && is_resource($handle)) {
            fclose($handle);
        }

        return $process->isSuccessful();
    }

    /**
     * Pure PHP PDO streaming SQL importer.
     * Operates without external binaries or shell execution capabilities.
     */
    public function restoreViaPdo(string $sqlDumpPath, string $connectionName): void
    {
        $sql = File::get($sqlDumpPath);

        DB::connection($connectionName)->statement('SET FOREIGN_KEY_CHECKS = 0;');
        try {
            DB::connection($connectionName)->unprepared($sql);
        } finally {
            DB::connection($connectionName)->statement('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    /**
     * Resolve the path to the mysql CLI client binary.
     */
    public function resolveMysqlBinaryPath(): ?string
    {
        // 1. If mysqldump path is resolved, check for mysql in the same bin directory
        $dumpPath = $this->preflight->resolveMysqldumpPath();
        if ($dumpPath !== null) {
            $binDir = dirname($dumpPath);
            $candidate = $binDir.DIRECTORY_SEPARATOR.(PHP_OS_FAMILY === 'Windows' ? 'mysql.exe' : 'mysql');
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        // 2. Common paths on Windows
        $windowsCandidates = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'D:\\xampp\\mysql\\bin\\mysql.exe',
            'E:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\laragon\\bin\\mysql\\current\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'C:\\Program Files\\MariaDB 10.4\\bin\\mysql.exe',
        ];

        // 3. Common paths on Linux
        $linuxCandidates = [
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
            '/usr/local/cpanel/3rdparty/bin/mysql',
            '/opt/lampp/bin/mysql',
        ];

        $candidates = (PHP_OS_FAMILY === 'Windows') ? $windowsCandidates : $linuxCandidates;

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        // 4. Try PATH
        $whichCommand = (PHP_OS_FAMILY === 'Windows') ? 'where mysql' : 'which mysql';
        try {
            $process = Process::fromShellCommandline($whichCommand);
            $process->run();
            if ($process->isSuccessful()) {
                $lines = explode("\n", trim($process->getOutput()));
                $first = trim($lines[0] ?? '');
                if (! empty($first) && File::exists($first)) {
                    return $first;
                }
            }
        } catch (Throwable) {
            // Path lookup failure is non-fatal
        }

        return null;
    }
}
