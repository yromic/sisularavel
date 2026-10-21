<?php

namespace Tests\Feature\User;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_preview_and_bulk_delete_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();

        $op1 = User::factory()->for($division)->create(['username' => 'op1', 'role' => 'operator']);
        $op2 = User::factory()->for($division)->create(['username' => 'op2', 'role' => 'operator']);
        $op3 = User::factory()->for($division)->create(['username' => 'op3', 'role' => 'operator']);

        // 1. Preview
        $previewRes = $this->actingAs($superAdmin)
            ->postJson('/api/v1/users/bulk-delete/preview', [
                'ids' => [$op1->id, $op2->id, $op3->id],
            ]);

        $previewRes->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 3,
                'skipped_count' => 0,
            ]);

        // 2. Execute Bulk Delete
        $idempotencyKey = (string) Str::uuid();
        $deleteRes = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/users/bulk-delete', [
                'ids' => [$op1->id, $op2->id, $op3->id],
            ]);

        $deleteRes->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 3,
                'skipped_count' => 0,
            ]);

        $this->assertSoftDeleted('users', ['id' => $op1->id]);
        $this->assertSoftDeleted('users', ['id' => $op2->id]);
        $this->assertSoftDeleted('users', ['id' => $op3->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'USER_BULK_DELETED',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_bulk_delete_skips_self_and_last_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['username' => 'superadmin1']);
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create(['username' => 'operator1']);

        $idempotencyKey = (string) Str::uuid();
        $res = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/users/bulk-delete', [
                'ids' => [$superAdmin->id, $operator->id],
            ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 1,
                'skipped_count' => 1,
            ]);

        // Operator deleted, but super admin skipped
        $this->assertSoftDeleted('users', ['id' => $operator->id]);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'deleted_at' => null]);
    }

    public function test_division_admin_cannot_bulk_delete_across_divisions_or_non_operators(): void
    {
        $divA = Division::factory()->create(['code' => 'DIV-A']);
        $divB = Division::factory()->create(['code' => 'DIV-B']);

        $adminA = User::factory()->for($divA)->divisionAdmin()->create(['username' => 'adminA']);
        $opA = User::factory()->for($divA)->create(['role' => 'operator', 'username' => 'opA']);
        $opB = User::factory()->for($divB)->create(['role' => 'operator', 'username' => 'opB']);

        // Attempting to delete opB from Division B must result in 403 Forbidden
        $idempotencyKey = (string) Str::uuid();
        $this->actingAs($adminA)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/users/bulk-delete', [
                'ids' => [$opA->id, $opB->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $opA->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $opB->id, 'deleted_at' => null]);
    }

    public function test_super_admin_can_bulk_restore_users_with_validation_safeguards(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $activeDiv = Division::factory()->create(['status' => 'active']);
        $inactiveDiv = Division::factory()->create(['status' => 'inactive']);

        $trashed1 = User::factory()->for($activeDiv)->create(['username' => 'trashed1', 'deleted_at' => now()]);
        $trashedInactiveDiv = User::factory()->for($inactiveDiv)->create(['username' => 'trashed_inactive', 'deleted_at' => now()]);
        $trashedSelf = User::factory()->superAdmin()->create(['username' => 'trashed_self', 'deleted_at' => now()]);

        $idempotencyKey = (string) Str::uuid();
        $res = $this->actingAs($trashedSelf)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/users/bulk-restore', [
                'ids' => [$trashed1->id, $trashedInactiveDiv->id, $trashedSelf->id],
            ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 1, // 1 restored
                'skipped_count' => 2, // 2 skipped (inactive division & self)
            ]);

        $this->assertDatabaseHas('users', ['id' => $trashed1->id, 'deleted_at' => null, 'is_active' => false]);
        $this->assertSoftDeleted('users', ['id' => $trashedInactiveDiv->id]);
        $this->assertSoftDeleted('users', ['id' => $trashedSelf->id]);
    }

    public function test_bulk_delete_idempotency_prevents_duplicate_processing(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();
        $op = User::factory()->for($division)->create(['role' => 'operator']);

        $idempotencyKey = (string) Str::uuid();

        // First call
        $res1 = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/users/bulk-delete', [
                'ids' => [$op->id],
            ]);
        $res1->assertOk()
            ->assertJsonPath('deleted_count', 1);

        // Second call with same idempotency key
        $res2 = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/users/bulk-delete', [
                'ids' => [$op->id],
            ]);
        $res2->assertOk()
            ->assertJsonPath('deleted_count', 1);

        // Audits should only contain 1 bulk event
        $bulkAuditCount = AuditLog::where('event_code', 'USER_BULK_DELETED')->count();
        $this->assertEquals(1, $bulkAuditCount);
    }

    public function test_can_preview_bulk_restore_without_idempotency_key(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();
        $trashedOp = User::factory()->for($division)->create([
            'role' => 'operator',
            'deleted_at' => now(),
        ]);

        // 1. Preview via dedicated restore preview route without idempotency key
        $previewRes = $this->actingAs($superAdmin)
            ->postJson('/api/v1/users/bulk-restore/preview', [
                'ids' => [$trashedOp->id],
            ]);

        $previewRes->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 1,
                'skipped_count' => 0,
            ]);

        // 2. Preview via generic bulk-delete preview route with action=restore without idempotency key
        $previewRes2 = $this->actingAs($superAdmin)
            ->postJson('/api/v1/users/bulk-delete/preview', [
                'ids' => [$trashedOp->id],
                'action' => 'restore',
            ]);

        $previewRes2->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 1,
                'skipped_count' => 0,
            ]);
    }
}
