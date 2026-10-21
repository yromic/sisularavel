<?php

namespace Tests\Feature\Reference;

use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\PickupCategory;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Requirement Traceability:
 * - UX-BULK-001: Bulk Operations Foundation & UI
 * - DEC-REF-001: Reference Master Soft Delete and Division Scoping
 */
class CategoryBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_admin_can_preview_and_bulk_delete_competition_categories(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $cat1 = CompetitionCategory::create(['division_id' => $division->id, 'name' => 'OSN Matematika']);
        $cat2 = CompetitionCategory::create(['division_id' => $division->id, 'name' => 'OSN IPA']);

        // Preview
        $previewRes = $this->actingAs($admin)
            ->postJson('/api/v1/reference/competition-categories/bulk-delete/preview', [
                'ids' => [$cat1->id, $cat2->id],
            ]);

        $previewRes->assertOk()
            ->assertJson([
                'total' => 2,
                'deleted_count' => 2,
                'skipped_count' => 0,
            ]);

        // Bulk Delete
        $deleteRes = $this->actingAs($admin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/competition-categories/bulk-delete', [
                'ids' => [$cat1->id, $cat2->id],
            ]);

        $deleteRes->assertOk()
            ->assertJson([
                'deleted_count' => 2,
            ]);

        $this->assertSoftDeleted('competition_categories', ['id' => $cat1->id]);
        $this->assertSoftDeleted('competition_categories', ['id' => $cat2->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'COMPETITION_CATEGORY_BULK_DELETED',
            'user_id' => $admin->id,
        ]);
    }

    public function test_division_admin_can_bulk_restore_competition_categories(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $cat = CompetitionCategory::create(['division_id' => $division->id, 'name' => 'FLS2N Menyanyi']);
        $cat->delete();

        $response = $this->actingAs($admin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/competition-categories/bulk-restore', [
                'ids' => [$cat->id],
            ]);

        $response->assertOk()
            ->assertJson(['deleted_count' => 1]);

        $this->assertDatabaseHas('competition_categories', [
            'id' => $cat->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'COMPETITION_CATEGORY_BULK_RESTORED',
            'user_id' => $admin->id,
        ]);
    }

    public function test_cross_division_bulk_delete_is_strictly_forbidden_with_403(): void
    {
        $divisionA = Division::factory()->create();
        $divisionB = Division::factory()->create();

        $adminA = User::factory()->for($divisionA)->divisionAdmin()->create();
        $catB = CompetitionCategory::create(['division_id' => $divisionB->id, 'name' => 'Kategori Divisi B']);

        $this->actingAs($adminA)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/competition-categories/bulk-delete', [
                'ids' => [$catB->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('competition_categories', [
            'id' => $catB->id,
            'deleted_at' => null,
        ]);
    }

    public function test_division_admin_can_bulk_delete_and_restore_pickup_categories(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $pickup1 = PickupCategory::create(['division_id' => $division->id, 'name' => 'Diambil Langsung']);
        $pickup2 = PickupCategory::create(['division_id' => $division->id, 'name' => 'Kurir Dinas']);

        $deleteRes = $this->actingAs($admin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/pickup-categories/bulk-delete', [
                'ids' => [$pickup1->id, $pickup2->id],
            ]);

        $deleteRes->assertOk()->assertJson(['deleted_count' => 2]);
        $this->assertSoftDeleted('pickup_categories', ['id' => $pickup1->id]);
        $this->assertSoftDeleted('pickup_categories', ['id' => $pickup2->id]);

        $restoreRes = $this->actingAs($admin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/pickup-categories/bulk-restore', [
                'ids' => [$pickup1->id],
            ]);

        $restoreRes->assertOk()->assertJson(['deleted_count' => 1]);
        $this->assertDatabaseHas('pickup_categories', ['id' => $pickup1->id, 'deleted_at' => null]);
    }

    public function test_super_admin_can_bulk_delete_and_restore_work_units(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $unit1 = WorkUnit::create(['name' => 'Bagian Keuangan']);
        $unit2 = WorkUnit::create(['name' => 'Bagian Umum']);

        $deleteRes = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/work-units/bulk-delete', [
                'ids' => [$unit1->id, $unit2->id],
            ]);

        $deleteRes->assertOk()->assertJson(['deleted_count' => 2]);
        $this->assertSoftDeleted('work_units', ['id' => $unit1->id]);
        $this->assertSoftDeleted('work_units', ['id' => $unit2->id]);

        $restoreRes = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/work-units/bulk-restore', [
                'ids' => [$unit1->id, $unit2->id],
            ]);

        $restoreRes->assertOk()->assertJson(['deleted_count' => 2]);
        $this->assertDatabaseHas('work_units', ['id' => $unit1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('work_units', ['id' => $unit2->id, 'deleted_at' => null]);
    }

    public function test_division_admin_cannot_bulk_manage_work_units(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();
        $unit = WorkUnit::create(['name' => 'Sekretariat']);

        $this->actingAs($admin)
            ->postJson('/api/v1/reference/work-units/bulk-delete/preview', ['ids' => [$unit->id]])
            ->assertForbidden();

        $this->actingAs($admin)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/reference/work-units/bulk-delete', ['ids' => [$unit->id]])
            ->assertForbidden();
    }

    public function test_single_restore_works_for_all_reference_types(): void
    {
        $division = Division::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $comp = CompetitionCategory::create(['division_id' => $division->id, 'name' => 'Futsal']);
        $comp->delete();

        $pickup = PickupCategory::create(['division_id' => $division->id, 'name' => 'Pos']);
        $pickup->delete();

        $unit = WorkUnit::create(['name' => 'Inspektorat']);
        $unit->delete();

        $this->actingAs($superAdmin)
            ->postJson("/api/v1/reference/competition-categories/{$comp->id}/restore")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($superAdmin)
            ->postJson("/api/v1/reference/pickup-categories/{$pickup->id}/restore")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($superAdmin)
            ->postJson("/api/v1/reference/work-units/{$unit->id}/restore")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('competition_categories', ['id' => $comp->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('pickup_categories', ['id' => $pickup->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('work_units', ['id' => $unit->id, 'deleted_at' => null]);
    }
}
