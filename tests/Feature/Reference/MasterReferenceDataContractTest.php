<?php

namespace Tests\Feature\Reference;

use App\Models\Division;
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
}
