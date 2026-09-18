<?php

namespace Tests\Feature\Operations;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\IdempotencyKey;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditOperationalHardeningContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_are_append_only_records(): void
    {
        $log = AuditLog::query()->create([
            'username' => 'operator',
            'role' => 'operator',
            'event_code' => 'CONFIG_UPDATED',
            'ip_address' => '127.0.0.1',
            'description' => 'Original immutable event',
        ]);

        $this->expectException(LogicException::class);
        $log->update(['description' => 'Tampered event']);
    }

    public function test_audit_logs_cannot_be_deleted_through_the_model(): void
    {
        $log = AuditLog::query()->create([
            'username' => 'operator',
            'role' => 'operator',
            'event_code' => 'CONFIG_UPDATED',
            'ip_address' => '127.0.0.1',
            'description' => 'Original immutable event',
        ]);

        try {
            $log->delete();
        } catch (LogicException) {
            $this->assertDatabaseHas('audit_logs', [
                'id' => $log->id,
                'description' => 'Original immutable event',
            ]);

            return;
        }

        $this->fail('Audit log deletion was not blocked.');
    }

    public function test_api_errors_use_the_standard_error_envelope(): void
    {
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create();

        $this->actingAs($operator)
            ->getJson('/api/v1/surat-masuk/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ERR_404_RECORD')
            ->assertJsonPath('message', 'Data yang Anda cari tidak ditemukan.')
            ->assertJsonStructure(['success', 'code', 'message', 'errors', 'timestamp']);
    }

    public function test_audit_payloads_redact_contract_sensitive_keys(): void
    {
        $division = Division::factory()->create();
        $actor = User::factory()->for($division)->divisionAdmin()->create();
        $target = User::factory()->for($division)->create();

        app(AuditLogger::class)->userEvent('USER_UPDATED', $actor, $target, 'User updated.', [
            'password' => 'secret',
            'password_hash' => 'hash',
            'password_salt' => 'salt',
            'remember_token' => 'remember',
            'session_token' => 'session',
            'authorization' => 'Bearer secret',
            'credit_card' => '4111111111111111',
            'name' => 'Visible Name',
        ]);

        $payload = AuditLog::query()->latest('id')->firstOrFail()->new_values;

        $this->assertSame('[REDACTED]', $payload['password']);
        $this->assertSame('[REDACTED]', $payload['password_hash']);
        $this->assertSame('[REDACTED]', $payload['password_salt']);
        $this->assertSame('[REDACTED]', $payload['remember_token']);
        $this->assertSame('[REDACTED]', $payload['session_token']);
        $this->assertSame('[REDACTED]', $payload['authorization']);
        $this->assertSame('[REDACTED]', $payload['credit_card']);
        $this->assertSame('Visible Name', $payload['name']);
    }

    public function test_expired_idempotency_keys_are_pruned_by_maintenance_command(): void
    {
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create();

        $expired = IdempotencyKey::query()->create([
            'key' => '11111111-1111-4111-8111-111111111111',
            'user_id' => $operator->id,
            'division_id' => $division->id,
            'request_hash' => hash('sha256', 'expired'),
            'response_code' => 201,
            'response_body' => '{}',
            'expires_at' => now()->subMinute(),
        ]);

        $active = IdempotencyKey::query()->create([
            'key' => '22222222-2222-4222-8222-222222222222',
            'user_id' => $operator->id,
            'division_id' => $division->id,
            'request_hash' => hash('sha256', 'active'),
            'response_code' => 201,
            'response_body' => '{}',
            'expires_at' => now()->addHour(),
        ]);

        $this->artisan('sisurat:prune-idempotency')
            ->expectsOutput('Pruned 1 expired idempotency key.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('idempotency_keys', ['id' => $expired->id]);
        $this->assertDatabaseHas('idempotency_keys', ['id' => $active->id]);
    }
}
