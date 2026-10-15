<?php

namespace Tests\Feature\Reference;

use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterReferenceDataContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_school_search_returns_active_matches_only(): void
    {
        School::factory()->create(['npsn' => '20320251', 'name' => 'SMP Negeri 1 Ungaran', 'is_active' => true]);
        School::factory()->create(['npsn' => '20320252', 'name' => 'SMP Tutup', 'is_active' => false]);

        $this->getJson('/api/v1/schools?search=Ungaran')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.npsn', '20320251');
    }

    public function test_only_super_admin_can_manage_schools(): void
    {
        $divisionAdmin = User::factory()->for(Division::factory())->divisionAdmin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $payload = ['npsn' => '20320251', 'name' => 'SMP Negeri 1 Ungaran'];

        $this->actingAs($divisionAdmin)
            ->postJson('/api/v1/schools', $payload)
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->postJson('/api/v1/schools', $payload)
            ->assertCreated()
            ->assertJsonPath('data.npsn', '20320251');

        $this->assertDatabaseHas('schools', $payload + ['is_active' => true]);
    }

    public function test_division_admin_manages_categories_only_in_own_division(): void
    {
        $division = Division::factory()->create();
        $otherDivision = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/reference/pickup-categories', [
                'name' => 'Piagam',
                'division_id' => $division->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.division_id', $division->id);

        $this->actingAs($admin)
            ->postJson('/api/v1/reference/competition-categories', [
                'name' => 'FLS2N',
                'division_id' => $otherDivision->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('pickup_categories', ['division_id' => $division->id, 'name' => 'Piagam']);
        $this->assertDatabaseMissing('competition_categories', ['division_id' => $otherDivision->id, 'name' => 'FLS2N']);
    }

    public function test_super_admin_can_manage_divisions_and_settings(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $divisionId = $this->actingAs($superAdmin)
            ->postJson('/api/v1/divisions', [
                'code' => 'gtk',
                'name' => 'Guru dan Tenaga Kependidikan',
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'GTK')
            ->json('data.id');

        $this->actingAs($superAdmin)
            ->patchJson("/api/v1/divisions/{$divisionId}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->actingAs($superAdmin)
            ->putJson('/api/v1/settings/app.name', ['value' => 'SISURAT Kabupaten Semarang'])
            ->assertOk();

        $this->assertDatabaseHas('settings', [
            'key' => 'app.name',
            'value' => 'SISURAT Kabupaten Semarang',
        ]);
    }

    public function test_division_admin_can_create_operator_only_in_own_division(): void
    {
        $division = Division::factory()->create();
        $otherDivision = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'username' => 'operator_baru',
                'name' => 'Operator Baru',
                'password' => 'secret-password',
                'role' => 'operator',
                'division_id' => $division->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.username', 'operator_baru')
            ->assertJsonPath('data.division_id', $division->id);

        $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'username' => 'admin_global',
                'name' => 'Admin Global',
                'password' => 'secret-password',
                'role' => 'super_admin',
                'division_id' => null,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'username' => 'operator_lain',
                'name' => 'Operator Lain',
                'password' => 'secret-password',
                'role' => 'operator',
                'division_id' => $otherDivision->id,
            ])
            ->assertForbidden();

        $created = User::query()->where('username', 'operator_baru')->firstOrFail();
        $this->assertTrue(Hash::check('secret-password', $created->password));
    }

    public function test_operator_cannot_manage_reference_data(): void
    {
        $operator = User::factory()->for(Division::factory())->create();

        $this->actingAs($operator)
            ->postJson('/api/v1/reference/pickup-categories', ['name' => 'Piala'])
            ->assertForbidden();

        $this->actingAs($operator)
            ->postJson('/api/v1/users', [
                'username' => 'forbidden',
                'name' => 'Forbidden',
                'password' => 'secret-password',
                'role' => 'operator',
                'division_id' => $operator->division_id,
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_delete_division_with_dependency_protection(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $emptyDivision = Division::factory()->create();
        $divisionWithUser = Division::factory()->create();
        User::factory()->for($divisionWithUser)->create();

        // Empty division gets deleted
        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/divisions/{$emptyDivision->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSoftDeleted('divisions', ['id' => $emptyDivision->id]);

        // Division with user is protected and switched to inactive
        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/divisions/{$divisionWithUser->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertDatabaseHas('divisions', ['id' => $divisionWithUser->id, 'status' => 'inactive']);
    }

    public function test_user_can_be_deleted_preventing_self_deletion(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create();

        // Cannot delete self
        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/users/{$superAdmin->id}")
            ->assertStatus(422);

        // Can delete other user
        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/users/{$operator->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSoftDeleted('users', ['id' => $operator->id]);
    }

    public function test_categories_and_schools_can_be_deleted(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();
        $competitionCategory = CompetitionCategory::create([
            'division_id' => $division->id,
            'name' => 'Lomba Sains',
            'is_active' => true,
        ]);
        $pickupCategory = PickupCategory::create([
            'division_id' => $division->id,
            'name' => 'Langsung',
            'is_active' => true,
        ]);
        $school = School::factory()->create();

        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/reference/competition-categories/{$competitionCategory->id}")
            ->assertOk();
        $this->assertDatabaseMissing('competition_categories', ['id' => $competitionCategory->id]);

        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/reference/pickup-categories/{$pickupCategory->id}")
            ->assertOk();
        $this->assertDatabaseMissing('pickup_categories', ['id' => $pickupCategory->id]);

        $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/schools/{$school->id}")
            ->assertOk();
        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    }

    public function test_schools_divisions_users_and_categories_can_be_updated_by_authorized_roles(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create(['code' => 'TEST-DIV', 'name' => 'Nama Awal']);
        $school = School::factory()->create(['npsn' => '12345678', 'name' => 'Sekolah Lama']);
        $user = User::factory()->for($division)->create(['username' => 'staf_lama', 'name' => 'Nama Lama']);
        $cat = CompetitionCategory::create(['division_id' => $division->id, 'name' => 'Kategori Lama']);

        // 1. Update School
        $this->actingAs($superAdmin)
            ->putJson("/api/v1/schools/{$school->id}", [
                'npsn' => '12345678',
                'name' => 'Sekolah Baru Diperbarui',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Sekolah Baru Diperbarui');
        $this->assertDatabaseHas('schools', ['id' => $school->id, 'name' => 'Sekolah Baru Diperbarui']);

        // 2. Update Division
        $this->actingAs($superAdmin)
            ->putJson("/api/v1/divisions/{$division->id}", [
                'code' => 'TEST-UPD',
                'name' => 'Nama Divisi Baru',
            ])
            ->assertOk()
            ->assertJsonPath('data.code', 'TEST-UPD');
        $this->assertDatabaseHas('divisions', ['id' => $division->id, 'code' => 'TEST-UPD', 'name' => 'Nama Divisi Baru']);

        // 3. Update User
        $this->actingAs($superAdmin)
            ->putJson("/api/v1/users/{$user->id}", [
                'name' => 'Nama Pengguna Baru',
                'email' => 'pengguna_baru@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nama Pengguna Baru');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Pengguna Baru']);

        // 4. Update Category
        $this->actingAs($superAdmin)
            ->putJson("/api/v1/reference/competition-categories/{$cat->id}", [
                'name' => 'Kategori Baru Diperbarui',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Kategori Baru Diperbarui');
        $this->assertDatabaseHas('competition_categories', ['id' => $cat->id, 'name' => 'Kategori Baru Diperbarui']);
    }
}
