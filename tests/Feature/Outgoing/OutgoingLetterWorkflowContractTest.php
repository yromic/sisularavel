<?php

namespace Tests\Feature\Outgoing;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class OutgoingLetterWorkflowContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_outgoing_number_in_same_division_and_year_is_rejected(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();
        $this->outgoingLetter($division, [
            'letter_number' => '005/01/DIKDAS/2026',
            'letter_date' => '2026-02-15',
            'letter_year' => 2026,
        ]);

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-keluar', $this->validPayload([
                'nomor_surat' => '005/01/DIKDAS/2026',
                'tanggal_surat' => '2026-03-01',
            ]))
            ->assertConflict()
            ->assertJsonPath('code', 'ERR_409_DUPLICATE_NUMBER')
            ->assertJsonPath('message', 'Nomor surat keluar sudah terdaftar pada divisi dan tahun ini');

        $this->assertDatabaseCount('outgoing_letters', 1);
    }

    public function test_same_outgoing_number_is_allowed_across_divisions_or_years(): void
    {
        Carbon::setTestNow('2027-01-10 08:00:00');

        $dikdas = Division::factory()->create(['code' => 'DIKDAS']);
        $paud = Division::factory()->create(['code' => 'PAUD']);
        $paudOperator = User::factory()->for($paud)->create();
        $dikdasOperator = User::factory()->for($dikdas)->create();
        $this->outgoingLetter($dikdas, [
            'letter_number' => '005/01/2026',
            'letter_date' => '2026-02-15',
            'letter_year' => 2026,
        ]);

        $this->actingAs($paudOperator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-keluar', $this->validPayload([
                'nomor_surat' => '005/01/2026',
                'tanggal_surat' => '2026-03-01',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.letter_year', 2026);

        $this->actingAs($dikdasOperator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-keluar', $this->validPayload([
                'nomor_surat' => '005/01/2026',
                'tanggal_surat' => '2027-01-05',
                'tanggal_share' => '2027-01-06',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.letter_year', 2027);

        $this->assertDatabaseCount('outgoing_letters', 3);
    }

    public function test_outgoing_letter_year_is_recomputed_when_letter_date_changes(): void
    {
        Carbon::setTestNow('2027-01-10 08:00:00');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();
        $operator = User::factory()->for($division)->create();
        $letter = $this->outgoingLetter($division, [
            'letter_number' => '010/12/DIKDAS',
            'letter_date' => '2026-12-30',
            'letter_year' => 2026,
        ]);

        $this->actingAs($operator)
            ->putJson("/api/v1/surat-keluar/{$letter->id}", $this->validPayload())
            ->assertForbidden();

        $this->actingAs($admin)
            ->putJson("/api/v1/surat-keluar/{$letter->id}", $this->validPayload([
                'nomor_surat' => '010/12/DIKDAS',
                'tanggal_surat' => '2027-01-05',
                'tanggal_share' => '2027-01-06',
            ]))
            ->assertOk()
            ->assertJsonPath('data.letter_year', 2027);

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'division_id' => $division->id,
            'letter_number' => '010/12/DIKDAS',
            'letter_year' => 2027,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_outgoing_create_is_idempotent_for_identical_payload(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();
        $key = (string) Str::uuid();
        $payload = $this->validPayload(['nomor_surat' => '020/09/DIKDAS/2026']);

        $first = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/surat-keluar', $payload)
            ->assertCreated()
            ->json();

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/surat-keluar', $payload)
            ->assertCreated()
            ->assertExactJson($first);

        $this->assertDatabaseCount('outgoing_letters', 1);
        $this->assertSame(1, AuditLog::query()->where('event_code', 'LETTER_OUTGOING_CREATED')->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => '001/09/DIKDAS/2026',
            'tanggal_surat' => '2026-09-01',
            'tanggal_share' => '2026-09-02',
            'perihal' => 'Surat edaran layanan pendidikan',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function outgoingLetter(Division $division, array $overrides = []): OutgoingLetter
    {
        return OutgoingLetter::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '001/09/DIKDAS/2026',
            'letter_date' => '2026-09-01',
            'disseminated_date' => '2026-09-02',
            'subject' => 'Arsip keluar awal',
        ], $overrides));
    }
}
