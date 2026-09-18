<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_tables_exist(): void
    {
        $tables = [
            'divisions',
            'users',
            'schools',
            'incoming_letters',
            'outgoing_letters',
            'piagams',
            'attachments',
            'pickup_categories',
            'competition_categories',
            'settings',
            'audit_logs',
            'idempotency_keys',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected table [{$table}] to exist.");
        }
    }

    public function test_reconciled_letter_number_constraints_match_contract(): void
    {
        $this->assertFalse(
            $this->hasUniqueIndex('incoming_letters', ['division_id', 'letter_number']),
            'Incoming letters must not enforce uniqueness on external letter numbers.'
        );

        $this->assertTrue(
            $this->hasUniqueIndex('outgoing_letters', ['division_id', 'letter_year', 'letter_number']),
            'Outgoing letters must be unique per division, year, and number.'
        );
    }

    public function test_security_and_migration_columns_exist(): void
    {
        foreach (['uuid', 'username', 'role', 'division_id', 'is_active', 'must_reset_password', 'password_reset_token', 'password_reset_expires_at', 'deleted_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "Expected users.{$column}.");
        }

        foreach (['is_missing', 'migration_status', 'missing_reason', 'legacy_drive_file_id', 'legacy_drive_url'] as $column) {
            $this->assertTrue(Schema::hasColumn('attachments', $column), "Expected attachments.{$column}.");
        }

        foreach (['key', 'user_id', 'division_id', 'request_hash', 'response_code', 'response_body', 'expires_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('idempotency_keys', $column), "Expected idempotency_keys.{$column}.");
        }
    }

    public function test_core_foreign_keys_are_present(): void
    {
        $expectedForeignKeys = [
            ['users', 'division_id', 'divisions'],
            ['incoming_letters', 'division_id', 'divisions'],
            ['outgoing_letters', 'division_id', 'divisions'],
            ['piagams', 'school_id', 'schools'],
            ['attachments', 'division_id', 'divisions'],
        ];

        foreach ($expectedForeignKeys as [$table, $column, $referencedTable]) {
            $this->assertTrue(
                $this->hasForeignKey($table, $column, $referencedTable),
                "Expected {$table}.{$column} to reference {$referencedTable}."
            );
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasUniqueIndex(string $table, array $columns): bool
    {
        $indexes = DB::select(
            <<<'SQL'
                SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_csv, NON_UNIQUE
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                GROUP BY INDEX_NAME, NON_UNIQUE
            SQL,
            [$table]
        );

        $expected = implode(',', $columns);

        foreach ($indexes as $index) {
            if ((int) $index->NON_UNIQUE === 0 && $index->columns_csv === $expected) {
                return true;
            }
        }

        return false;
    }

    private function hasForeignKey(string $table, string $column, string $referencedTable): bool
    {
        $result = DB::selectOne(
            <<<'SQL'
                SELECT COUNT(*) AS aggregate
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME = ?
            SQL,
            [$table, $column, $referencedTable]
        );

        return (int) $result->aggregate > 0;
    }
}
