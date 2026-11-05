<?php

namespace Tests\Feature\Piagam;

use App\Models\AuditLog;
use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\Piagam;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PiagamDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_soft_delete_and_restore_any_piagam(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $school = School::factory()->create();
        $category = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'OSN']);
        $piagam = $this->createPiagam($division, $school, $category);

        // Delete via JSON API
        $response = $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/piagam/{$piagam->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.deleted', true);

        $this->assertSoftDeleted('piagams', ['id' => $piagam->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'PIAGAM_DELETED',
            'user_id' => $superAdmin->id,
            'auditable_type' => Piagam::class,
            'auditable_id' => $piagam->id,
        ]);

        // Restore
        $restoreRes = $this->actingAs($superAdmin)
            ->postJson("/api/v1/piagam/{$piagam->id}/restore");

        $restoreRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.restored', true);

        $this->assertNotSoftDeleted('piagams', ['id' => $piagam->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'PIAGAM_RESTORED',
            'user_id' => $superAdmin->id,
            'auditable_type' => Piagam::class,
            'auditable_id' => $piagam->id,
        ]);
    }

    public function test_division_admin_can_soft_delete_piagam_in_own_division(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->for($division)->create(['role' => 'division_admin']);
        $school = School::factory()->create();
        $category = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'OSN']);
        $piagam = $this->createPiagam($division, $school, $category);

        $this->actingAs($admin)
            ->delete("/piagam/{$piagam->id}")
            ->assertRedirect('/persuratan?tab=piagam');

        $this->assertSoftDeleted('piagams', ['id' => $piagam->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'PIAGAM_DELETED',
            'user_id' => $admin->id,
            'auditable_type' => Piagam::class,
            'auditable_id' => $piagam->id,
        ]);
    }

    public function test_division_admin_cannot_delete_piagam_in_other_division(): void
    {
        $divisionA = Division::factory()->create(['code' => 'DIKDAS']);
        $divisionB = Division::factory()->create(['code' => 'PAUD']);
        $adminA = User::factory()->for($divisionA)->create(['role' => 'division_admin']);
        $school = School::factory()->create();
        $categoryB = CompetitionCategory::query()->create(['division_id' => $divisionB->id, 'name' => 'Mewarnai']);
        $piagamB = $this->createPiagam($divisionB, $school, $categoryB);

        $this->actingAs($adminA)
            ->deleteJson("/api/v1/piagam/{$piagamB->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'ERR_403_DIVISI');

        $this->assertNotSoftDeleted('piagams', ['id' => $piagamB->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'SEC_CROSS_DIVISION_BLOCKED',
            'user_id' => $adminA->id,
            'auditable_type' => Piagam::class,
            'auditable_id' => $piagamB->id,
        ]);
    }

    public function test_operator_cannot_delete_piagam(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);
        $school = School::factory()->create();
        $category = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'OSN']);
        $piagam = $this->createPiagam($division, $school, $category);

        $this->actingAs($operator)
            ->deleteJson("/api/v1/piagam/{$piagam->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'ERR_403_ROLE');

        $this->assertNotSoftDeleted('piagams', ['id' => $piagam->id]);
    }

    private function createPiagam(Division $division, School $school, CompetitionCategory $category): Piagam
    {
        return Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Bambang Sudirman',
            'piagam_description' => 'Juara 1 Sains',
            'school_id' => $school->id,
            'competition_category_id' => $category->id,
            'competition_year' => 2026,
            'recipient_name' => 'Bambang',
            'recipient_position' => 'Guru Pendamping',
        ]);
    }
}

