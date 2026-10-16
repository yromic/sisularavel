<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupPreflightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupPreflightAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_preflight_service_runs_all_checks_successfully(): void
    {
        $service = new BackupPreflightService;
        $audit = $service->runAudit();

        $this->assertIsArray($audit);
        $this->assertArrayHasKey('timestamp', $audit);
        $this->assertArrayHasKey('is_ready', $audit);
        $this->assertArrayHasKey('summary', $audit);
        $this->assertArrayHasKey('checks', $audit);
        $this->assertArrayHasKey('storage_stats', $audit);
        $this->assertArrayHasKey('system_info', $audit);

        $this->assertTrue($audit['is_ready']);
        $this->assertGreaterThanOrEqual(10, count($audit['checks']));
    }

    public function test_php_version_check_satisfies_baseline(): void
    {
        $service = new BackupPreflightService;
        $check = $service->checkPhpVersion();

        $this->assertSame('php_version', $check['key']);
        $this->assertSame('PASS', $check['status']);
        $this->assertTrue($check['critical']);
        $this->assertStringContainsString('satisfies the minimum target requirement', $check['message']);
    }

    public function test_mandatory_php_extensions_check(): void
    {
        $service = new BackupPreflightService;
        $check = $service->checkMandatoryExtensions();

        $this->assertSame('mandatory_extensions', $check['key']);
        $this->assertSame('PASS', $check['status']);
        $this->assertTrue($check['critical']);
        $this->assertEmpty($check['data']['missing']);
    }

    public function test_authenticated_cipher_support(): void
    {
        $service = new BackupPreflightService;
        $check = $service->checkCipherSupport();

        $this->assertSame('encryption_cipher', $check['key']);
        $this->assertSame('PASS', $check['status']);
        $this->assertTrue($check['critical']);
        $this->assertSame('aes-256-gcm', $check['data']['target_cipher']);
    }

    public function test_memory_limit_check(): void
    {
        $service = new BackupPreflightService;
        $check = $service->checkMemoryLimit();

        $this->assertSame('memory_limit', $check['key']);
        $this->assertContains($check['status'], ['PASS', 'WARN']);
    }

    public function test_database_connection_and_table_count(): void
    {
        $service = new BackupPreflightService;
        $result = $service->checkDatabaseConnection();

        $this->assertSame('database_connection', $result['check']['key']);
        $this->assertSame('PASS', $result['check']['status']);
        $this->assertGreaterThan(0, $result['check']['data']['tables_count']);
    }

    public function test_private_storage_directories_check(): void
    {
        $service = new BackupPreflightService;
        $checks = $service->checkPrivateStorageDirectories();

        $this->assertCount(3, $checks);
        foreach ($checks as $check) {
            $this->assertSame('PASS', $check['status'], "Failed on {$check['name']}: {$check['message']}");
            $this->assertTrue($check['data']['readable']);
            $this->assertTrue($check['data']['writable']);
        }
    }

    public function test_disk_capacity_thresholds_evaluation(): void
    {
        $service = new BackupPreflightService;

        // 1. Normal usage: 50% used
        $normal = $service->checkDiskCapacity(null, 50.0, 200 * 1024 * 1024 * 1024);
        $this->assertSame('PASS', $normal['check']['status']);
        $this->assertFalse($normal['check']['critical']);

        // 2. Early Warning usage: 78% used
        $warning = $service->checkDiskCapacity(null, 78.0, 50 * 1024 * 1024 * 1024);
        $this->assertSame('WARN', $warning['check']['status']);
        $this->assertFalse($warning['check']['critical']);

        // 3. Critical usage: 92% used
        $critical = $service->checkDiskCapacity(null, 92.0, 10 * 1024 * 1024 * 1024);
        $this->assertSame('FAIL', $critical['check']['status']);
        $this->assertTrue($critical['check']['critical']);
        $this->assertStringContainsString('CRITICAL', $critical['check']['message']);

        // 4. Critical free space: below 100 MB free
        $lowFree = $service->checkDiskCapacity(null, 60.0, 50 * 1024 * 1024);
        $this->assertSame('FAIL', $lowFree['check']['status']);
        $this->assertTrue($lowFree['check']['critical']);
    }

    public function test_artisan_backup_audit_command_renders_table_and_exits_zero(): void
    {
        $this->artisan('sisurat:backup:audit')
            ->expectsOutputToContain('SISURAT BACKUP & DISASTER RECOVERY — READ-ONLY TECHNICAL AUDIT')
            ->expectsOutputToContain('SUCCESS: SERVER ENVIRONMENT MEETS ALL FASE 01 TECHNICAL AUDIT PREREQUISITES!')
            ->assertExitCode(0);
    }

    public function test_artisan_backup_audit_summary_only_flag(): void
    {
        $this->artisan('sisurat:backup:audit', ['--summary-only' => true])
            ->expectsOutputToContain('READY:')
            ->assertExitCode(0);
    }

    public function test_artisan_backup_audit_command_json_output(): void
    {
        $this->artisan('sisurat:backup:audit', ['--json' => true])
            ->assertExitCode(0);
    }

    public function test_artisan_backup_audit_fails_on_critical_failure(): void
    {
        // Mock BackupPreflightService to simulate a critical failure
        $mock = $this->createMock(BackupPreflightService::class);
        $mock->expects($this->once())
            ->method('runAudit')
            ->willReturn([
                'timestamp' => now()->toIso8601String(),
                'is_ready' => false,
                'has_warnings' => false,
                'has_failures' => true,
                'summary' => [
                    'total_checks' => 1,
                    'passed' => 0,
                    'warnings' => 0,
                    'failed' => 1,
                ],
                'checks' => [
                    [
                        'key' => 'disk_capacity',
                        'category' => 'Resources & Capacity',
                        'name' => 'Hosting SSD Storage Capacity',
                        'status' => 'FAIL',
                        'critical' => true,
                        'message' => 'Simulated disk 100% full.',
                    ],
                ],
                'storage_stats' => [
                    'disk_total_bytes' => 1000,
                    'disk_used_bytes' => 1000,
                    'disk_free_bytes' => 0,
                    'disk_used_percent' => 100.0,
                    'documents_count' => 0,
                    'documents_size_bytes' => 0,
                    'signatures_count' => 0,
                    'signatures_size_bytes' => 0,
                    'database_tables_count' => 0,
                    'database_size_bytes' => 0,
                ],
                'system_info' => [
                    'php_version' => '8.2.12',
                    'os' => 'Windows',
                    'sapi' => 'cli',
                    'db_version' => '10.4.32-MariaDB',
                    'memory_limit' => '512M',
                    'max_execution_time' => 0,
                ],
            ]);

        $this->app->instance(BackupPreflightService::class, $mock);

        $this->artisan('sisurat:backup:audit')
            ->expectsOutputToContain('CRITICAL: SERVER ENVIRONMENT FAILED ONE OR MORE MANDATORY AUDIT CHECKS!')
            ->assertExitCode(1);
    }
}
