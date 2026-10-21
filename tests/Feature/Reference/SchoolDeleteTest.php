<?php

namespace Tests\Feature\Reference;

use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\Piagam;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SchoolDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_delete_school_without_piagam_references(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $school = School::factory()->create(['name' => 'SMP Tanpa Piagam', 'npsn' => '20300001']);

        $response = $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/schools/{$school->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('schools', ['id' => $school->id]);
    }

    public function test_school_deletion_is_prevented_when_active_piagam_exists(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();
        $school = School::factory()->create(['name' => 'SMP Aktif', 'npsn' => '20300002', 'is_active' => true]);
        $category = CompetitionCategory::create([
            'division_id' => $division->id,
            'name' => 'OSN',
            'is_active' => true,
        ]);

        Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'school_id' => $school->id,
            'competition_category_id' => $category->id,
            'student_name' => 'Siswa Aktif',
            'competition_year' => 2026,
        ]);

        $response = $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/schools/{$school->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Sekolah memiliki data piagam terkait.',
            ]);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'is_active' => false,
        ]);
    }

    public function test_school_deletion_is_prevented_when_soft_deleted_piagam_exists(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();
        $school = School::factory()->create(['name' => 'SMP Soft Deleted Piagam', 'npsn' => '20300003', 'is_active' => true]);
        $category = CompetitionCategory::create([
            'division_id' => $division->id,
            'name' => 'FLS2N',
            'is_active' => true,
        ]);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'school_id' => $school->id,
            'competition_category_id' => $category->id,
            'student_name' => 'Siswa Piagam Dihapus',
            'competition_year' => 2026,
        ]);

        // Soft delete the piagam
        $piagam->delete();
        $this->assertSoftDeleted('piagams', ['id' => $piagam->id]);

        // Attempting to delete the school must NOT cause a 500 DB integrity constraint violation
        $response = $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/schools/{$school->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Sekolah memiliki data piagam terkait.',
            ]);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'is_active' => false,
        ]);
    }

    public function test_non_super_admin_cannot_delete_school(): void
    {
        $division = Division::factory()->create();
        $divisionAdmin = User::factory()->for($division)->divisionAdmin()->create();
        $operator = User::factory()->for($division)->create(['role' => 'operator']);
        $school = School::factory()->create();

        $this->actingAs($divisionAdmin)
            ->deleteJson("/api/v1/schools/{$school->id}")
            ->assertForbidden();

        $this->actingAs($operator)
            ->deleteJson("/api/v1/schools/{$school->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('schools', ['id' => $school->id]);
    }
}
