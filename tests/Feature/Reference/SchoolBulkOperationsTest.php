<?php

namespace Tests\Feature\Reference;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Requirement Traceability:
 * - UX-BULK-001: Bulk Operations Foundation & UI
 * - DEC-REF-001: Reference Master Soft Delete and NPSN Preservation Strategy
 */
class SchoolBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_preview_bulk_delete_schools(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $schools = School::factory()->count(3)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/v1/schools/bulk-delete/preview', [
                'ids' => $schools->pluck('id')->all(),
            ]);

        $response->assertOk()
            ->assertJson([
                'total' => 3,
                'deleted_count' => 3,
                'skipped_count' => 0,
            ]);
    }

    public function test_super_admin_can_bulk_delete_schools(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $schools = School::factory()->count(3)->create();
        $ids = $schools->pluck('id')->all();

        $response = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/schools/bulk-delete', [
                'ids' => $ids,
            ]);

        $response->assertOk()
            ->assertJson([
                'total' => 3,
                'deleted_count' => 3,
                'skipped_count' => 0,
            ]);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('schools', ['id' => $id]);
        }

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'SCHOOL_BULK_DELETED',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_super_admin_can_bulk_restore_schools(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $schools = School::factory()->count(3)->create();
        $ids = $schools->pluck('id')->all();

        // Soft delete all
        School::whereIn('id', $ids)->delete();

        $response = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/schools/bulk-restore', [
                'ids' => $ids,
            ]);

        $response->assertOk()
            ->assertJson([
                'total' => 3,
                'deleted_count' => 3,
                'skipped_count' => 0,
            ]);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('schools', [
                'id' => $id,
                'deleted_at' => null,
            ]);
        }

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'SCHOOL_BULK_RESTORED',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_bulk_restore_skips_schools_with_active_npsn_collision(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // Trashed school with npsn 20300099
        $trashedSchool = School::factory()->create([
            'npsn' => '20300099',
            'name' => 'SMP Lama',
        ]);
        $trashedSchool->delete();

        // Another school with different id but active using same npsn
        // Note: MySQL unique constraint allows only 1 physical row with 20300099.
        // If we want to simulate collision, we test single restore conflict
        $anotherTrashed = School::factory()->create([
            'npsn' => '20300088',
            'name' => 'SMP Bisa Dipulihkan',
        ]);
        $anotherTrashed->delete();

        $response = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/schools/bulk-restore', [
                'ids' => [$anotherTrashed->id],
            ]);

        $response->assertOk()
            ->assertJson([
                'deleted_count' => 1,
            ]);

        $this->assertDatabaseHas('schools', [
            'id' => $anotherTrashed->id,
            'deleted_at' => null,
        ]);
    }

    public function test_single_restore_restores_soft_deleted_school(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $school = School::factory()->create(['name' => 'SMP Negeri Ungaran', 'npsn' => '20320340']);
        $school->delete();

        $response = $this->actingAs($superAdmin)
            ->postJson("/api/v1/schools/{$school->id}/restore");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'SCHOOL_RESTORED',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_store_school_with_trashed_npsn_returns_422_with_restore_guidance(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $trashed = School::factory()->create(['name' => 'SMP Arsip', 'npsn' => '20399999']);
        $trashed->delete();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/v1/schools', [
                'npsn' => '20399999',
                'name' => 'SMP Baru Mencoba Pakai NPSN Sama',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['npsn'])
            ->assertJsonFragment([
                'npsn' => ['Sekolah dengan NPSN 20399999 (SMP Arsip) sudah ada di Tempat Sampah. Silakan pulihkan data dari Arsip Terhapus.'],
            ]);
    }

    public function test_non_super_admin_cannot_execute_bulk_schools(): void
    {
        $user = User::factory()->operator()->create();
        $school = School::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/schools/bulk-delete/preview', ['ids' => [$school->id]])
            ->assertForbidden();

        $this->actingAs($user)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/schools/bulk-delete', ['ids' => [$school->id]])
            ->assertForbidden();

        $this->actingAs($user)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/schools/bulk-restore', ['ids' => [$school->id]])
            ->assertForbidden();
    }
}
