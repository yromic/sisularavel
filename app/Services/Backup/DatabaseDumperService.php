<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseDumperService
{
    public function __construct(
        protected BackupPreflightService $preflight
    ) {}

    /**
     * Dump the database to the specified SQL file path.
     * Uses mysqldump binary if available, or falls back to PDO-based transactional dump.
     */
    public function dump(string $destinationSqlPath): array
    {
        $dir = dirname($destinationSqlPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $binaryPath = app()->environment('testing') ? null : $this->preflight->resolveMysqldumpPath();
        $dumped = false;
        $method = 'mysqldump';

        if ($binaryPath !== null) {
            try {
                $dumped = $this->dumpViaMysqldumpBinary($binaryPath, $destinationSqlPath);
            } catch (Throwable) {
                $dumped = false;
            }
        }

        if (! $dumped) {
            $method = 'pdo_fallback';
            $this->dumpViaPdo($destinationSqlPath);
        }

        $size = File::size($destinationSqlPath);
        $sha256 = hash_file('sha256', $destinationSqlPath);

        $dbName = config('database.connections.'.config('database.default').'.database');
        $tablesCount = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $dbName)
            ->count();

        $versionResult = DB::select('SELECT VERSION() as v');
        $dbVersion = $versionResult[0]->v ?? 'Unknown';

        return [
            'dump_file' => $destinationSqlPath,
            'size_bytes' => $size,
            'sha256' => $sha256,
            'tables_count' => $tablesCount,
            'version' => $dbVersion,
            'method' => $method,
        ];
    }

    /**
     * Dump via system mysqldump binary.
     */
    protected function dumpViaMysqldumpBinary(string $binaryPath, string $destinationPath): bool
    {
        $conn = config('database.default');
        $dbConfig = config("database.connections.{$conn}");

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
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--default-character-set=utf8mb4',
            '--routines',
            '--triggers',
        ];

        if (! empty($pass)) {
            $command[] = "--password={$pass}";
        }

        $command[] = $database;

        $process = new Process($command);
        $process->setTimeout(300);

        // Open destination file stream for memory-efficient pipe
        $handle = fopen($destinationPath, 'wb');
        if (! $handle) {
            return false;
        }

        $process->run(function ($type, $buffer) use ($handle) {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            }
        });

        fclose($handle);

        if ($process->isSuccessful() && File::exists($destinationPath) && File::size($destinationPath) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Pure PHP PDO-based transactional dumper fallback.
     * Operates completely inside PHP without needing shell/exec.
     */
    public function dumpViaPdo(string $destinationPath): void
    {
        $pdo = DB::connection()->getPdo();
        $dbName = config('database.connections.'.config('database.default').'.database');

        $handle = fopen($destinationPath, 'wb');

        $header = "-- ============================================================\n";
        $header .= "-- SISURAT DATABASE BACKUP (PDO FALLBACK DUMP)\n";
        $header .= '-- Database: '.$dbName."\n";
        $header .= '-- Generated: '.now()->toIso8601String()."\n";
        $header .= "-- Target Environment: Laravel 12 / PHP ^8.2 / MariaDB / MySQL\n";
        $header .= "-- ============================================================\n\n";
        $header .= "SET NAMES utf8mb4;\n";
        $header .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $header .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $header .= "SET AUTOCOMMIT = 0;\n";
        $header .= "START TRANSACTION;\n\n";

        fwrite($handle, $header);

        // Fetch tables
        $tablesStmt = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
        $tables = [];
        while ($row = $tablesStmt->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            // Write DROP and CREATE TABLE
            fwrite($handle, "-- ------------------------------------------------------------\n");
            fwrite($handle, "-- Table structure for table `{$table}`\n");
            fwrite($handle, "-- ------------------------------------------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(\PDO::FETCH_NUM);
            if (! empty($createRow[1])) {
                fwrite($handle, $createRow[1].";\n\n");
            }

            // Dump data
            fwrite($handle, "-- Dumping data for table `{$table}`\n");
            $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rowsBuffer = [];
            $batchSize = 100;

            while ($row = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                $escapedValues = array_map(function ($val) use ($pdo) {
                    if ($val === null) {
                        return 'NULL';
                    }

                    return $pdo->quote($val);
                }, array_values($row));

                $rowsBuffer[] = '('.implode(', ', $escapedValues).')';

                if (count($rowsBuffer) >= $batchSize) {
                    $insertSql = "INSERT INTO `{$table}` VALUES \n".implode(",\n", $rowsBuffer).";\n";
                    fwrite($handle, $insertSql);
                    $rowsBuffer = [];
                }
            }

            if (! empty($rowsBuffer)) {
                $insertSql = "INSERT INTO `{$table}` VALUES \n".implode(",\n", $rowsBuffer).";\n";
                fwrite($handle, $insertSql);
            }

            fwrite($handle, "\n");
        }

        $footer = "COMMIT;\n";
        $footer .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $footer .= "-- End of SISURAT Database Dump\n";
        fwrite($handle, $footer);

        fclose($handle);
    }
}
