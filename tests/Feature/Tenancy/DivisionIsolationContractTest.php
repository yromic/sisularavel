<?php

namespace Tests\Feature\Tenancy;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DivisionIsolationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_user_cannot_view_another_divisions_letter(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $operator = User::factory()->for($dikdas)->create();
        $paudLetter = $this->incomingLetter($paud, 'PAUD-001');

        $this->actingAs($operator)
            ->getJson("/api/v1/surat-masuk/{$paudLetter->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'ERR_403_DIVISI');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $operator->id,
            'event_code' => 'SEC_CROSS_DIVISION_BLOCKED',
            'auditable_type' => IncomingLetter::class,
            'auditable_id' => $paudLetter->id,
        ]);
    }

    public function test_division_user_list_is_scoped_to_own_division(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $operator = User::factory()->for($dikdas)->create();
        $dikdasLetter = $this->incomingLetter($dikdas, 'DIKDAS-001');
        $this->incomingLetter($paud, 'PAUD-001');

        $this->actingAs($operator)
            ->getJson('/api/v1/surat-masuk')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $dikdasLetter->id)
            ->assertJsonMissing(['letter_number' => 'PAUD-001']);
    }

    public function test_super_admin_switches_active_division_without_transferring_ownership(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $superAdmin = User::factory()->superAdmin()->create();
        $paudLetter = $this->incomingLetter($paud, 'PAUD-001');
        $this->incomingLetter($dikdas, 'DIKDAS-001');

        $this->actingAs($superAdmin)
            ->postJson('/api/v1/session/switch-division', ['division_id' => $paud->id])
            ->assertOk()
            ->assertJsonPath('active_division_id', $paud->id);

        $this->actingAs($superAdmin)
            ->getJson('/api/v1/surat-masuk')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paudLetter->id);

        $this->assertSame($paud->id, $paudLetter->refresh()->division_id);
    }

    public function test_non_super_admin_cannot_switch_division_context(): void
    {
        $operator = User::factory()->for(Division::factory())->create();
        $otherDivision = Division::factory()->create();

        $this->actingAs($operator)
            ->postJson('/api/v1/session/switch-division', ['division_id' => $otherDivision->id])
            ->assertForbidden();
    }

    public function test_inactive_user_or_division_cannot_access_protected_routes(): void
    {
        $inactiveDivision = Division::factory()->create(['status' => 'inactive']);
        $inactiveDivisionUser = User::factory()->for($inactiveDivision)->create();
        $inactiveUser = User::factory()->for(Division::factory())->create(['is_active' => false]);

        $this->actingAs($inactiveDivisionUser)
            ->getJson('/api/v1/surat-masuk')
            ->assertUnauthorized();

        $this->actingAs($inactiveUser)
            ->getJson('/api/v1/surat-masuk')
            ->assertUnauthorized();
    }

    private function incomingLetter(Division $division, string $number): IncomingLetter
    {
        return IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => $number,
            'origin_agency' => 'Dinas Pengirim',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Uji isolasi divisi',
        ]);
    }
}
