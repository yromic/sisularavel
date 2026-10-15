<?php

namespace Tests\Feature\Incoming;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IncomingLetterWorkflowContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_number_collision_from_different_sender_is_allowed(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();
        $this->incomingLetter($division, [
            'letter_number' => '005/12/2026',
            'origin_agency' => 'Bupati Semarang',
        ]);

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', $this->validPayload([
                'nomor_surat' => '005/12/2026',
                'asal_surat' => 'Badan Kepegawaian Daerah',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.nomor_surat', '005/12/2026')
            ->assertJsonPath('duplicate_advisory', false);

        $this->assertDatabaseCount('incoming_letters', 2);
        $this->assertDatabaseHas('incoming_letters', [
            'division_id' => $division->id,
            'letter_number' => '005/12/2026',
            'origin_agency' => 'Badan Kepegawaian Daerah',
            'created_by_user_id' => $operator->id,
        ]);
    }

    public function test_exact_duplicate_requires_advisory_confirmation_before_insert(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();
        $this->incomingLetter($division, [
            'letter_number' => '010/09/2026',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-09-01',
        ]);

        $payload = $this->validPayload([
            'nomor_surat' => '010/09/2026',
            'asal_surat' => 'Bupati Semarang',
            'tanggal_surat' => '2026-09-01',
            'tanggal_diterima' => '2026-09-02',
        ]);

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', $payload)
            ->assertOk()
            ->assertJsonPath('code', 'DUPLICATE_ADVISORY')
            ->assertJsonPath('duplicate_advisory', true);

        $this->assertDatabaseCount('incoming_letters', 1);

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', $payload + ['confirm_duplicate' => true])
            ->assertCreated()
            ->assertJsonPath('duplicate_advisory', true);

        $this->assertDatabaseCount('incoming_letters', 2);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $operator->id,
            'event_code' => 'LETTER_INCOMING_DUPLICATE_CONFIRMED',
            'auditable_type' => IncomingLetter::class,
        ]);
    }

    public function test_incoming_letter_validation_and_idempotency_header_are_required(): void
    {
        $operator = User::factory()->for(Division::factory())->create();

        $this->actingAs($operator)
            ->postJson('/api/v1/surat-masuk', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Idempotency-Key']);

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', $this->validPayload([
                'nomor_surat' => '',
                'asal_surat' => '',
                'tanggal_surat' => '2026-09-10',
                'tanggal_diterima' => '2026-09-01',
                'perihal' => '',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nomor_surat', 'asal_surat', 'tanggal_diterima', 'perihal']);
    }

    public function test_idempotency_replays_same_payload_and_rejects_conflicting_payload(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();
        $key = (string) Str::uuid();
        $payload = $this->validPayload(['nomor_surat' => '020/09/2026']);

        $first = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/surat-masuk', $payload)
            ->assertCreated()
            ->json();

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/surat-masuk', $payload)
            ->assertCreated()
            ->assertExactJson($first);

        $this->assertDatabaseCount('incoming_letters', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
        $this->assertDatabaseHas('idempotency_keys', [
            'key' => $key,
            'user_id' => $operator->id,
            'division_id' => $division->id,
        ]);
        $this->assertSame(1, AuditLog::query()->where('event_code', 'LETTER_INCOMING_CREATED')->count());

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/surat-masuk', array_merge($payload, ['perihal' => 'Payload berbeda']))
            ->assertConflict()
            ->assertJsonPath('code', 'ERR_409_IDEMPOTENCY_CONFLICT');

        $this->assertDatabaseCount('incoming_letters', 1);
    }

    public function test_division_admin_updates_incoming_letter_without_changing_ownership(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $otherDivision = Division::factory()->create(['code' => 'PAUD']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();
        $operator = User::factory()->for($division)->create();
        $letter = $this->incomingLetter($division, ['letter_number' => '030/09/2026']);

        $this->actingAs($operator)
            ->putJson("/api/v1/surat-masuk/{$letter->id}", $this->validPayload())
            ->assertForbidden();

        $this->actingAs($admin)
            ->putJson("/api/v1/surat-masuk/{$letter->id}", $this->validPayload([
                'nomor_surat' => '031/09/2026',
                'asal_surat' => 'Sekretariat Daerah',
                'perihal' => 'Perubahan jadwal koordinasi',
                'division_id' => $otherDivision->id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.nomor_surat', '031/09/2026')
            ->assertJsonPath('data.division_id', $division->id);

        $this->assertDatabaseHas('incoming_letters', [
            'id' => $letter->id,
            'division_id' => $division->id,
            'letter_number' => '031/09/2026',
            'origin_agency' => 'Sekretariat Daerah',
            'updated_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event_code' => 'LETTER_INCOMING_UPDATED',
            'auditable_id' => $letter->id,
        ]);
    }

    public function test_incoming_letter_show_returns_enriched_resource_with_attachment_urls(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Pembinaan SD']);
        $operator = User::factory()->for($division)->create(['name' => 'Budi Santoso', 'username' => 'budi_sd']);
        $letter = $this->incomingLetter($division, [
            'letter_number' => '420/999/2026',
            'origin_agency' => 'Kementerian Pendidikan',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat Koordinasi Kurikulum',
            'created_by_user_id' => $operator->id,
        ]);

        $letter->attachments()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'file_path' => 'documents/DIKDAS/2026/09/test.pdf',
            'original_name' => 'Dokumen_Resmi.pdf',
            'file_size_bytes' => 1048576,
            'mime_type' => 'application/pdf',
            'is_missing' => false,
            'created_by_user_id' => $operator->id,
        ]);

        $this->actingAs($operator)
            ->getJson("/api/v1/surat-masuk/{$letter->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nomor_surat', '420/999/2026')
            ->assertJsonPath('data.division_name', 'Bidang Pembinaan SD')
            ->assertJsonPath('data.division_code', 'DIKDAS')
            ->assertJsonPath('data.tanggal_surat_formatted', '01 September 2026')
            ->assertJsonPath('data.attachment.original_name', 'Dokumen_Resmi.pdf')
            ->assertJsonPath('data.attachment.download_url', "/api/v1/attachments/{$letter->attachments->first()->uuid}/download")
            ->assertJsonPath('data.attachment.preview_url', "/api/v1/attachments/{$letter->attachments->first()->uuid}/preview");
    }

    public function test_division_admin_soft_deletes_and_restores_own_incoming_letter(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $otherDivision = Division::factory()->create(['code' => 'PAUD']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();
        $operator = User::factory()->for($division)->create();
        $letter = $this->incomingLetter($division, ['letter_number' => '040/09/2026']);
        $otherLetter = $this->incomingLetter($otherDivision, ['letter_number' => '041/09/2026']);

        $this->actingAs($operator)
            ->deleteJson("/api/v1/surat-masuk/{$letter->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/surat-masuk/{$otherLetter->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/surat-masuk/{$letter->id}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertSoftDeleted('incoming_letters', ['id' => $letter->id]);
        $this->assertDatabaseHas('incoming_letters', ['id' => $letter->id, 'deleted_by_user_id' => $admin->id]);

        $this->actingAs($admin)
            ->getJson('/api/v1/surat-masuk')
            ->assertOk()
            ->assertJsonMissing(['id' => $letter->id]);

        $this->actingAs($admin)
            ->postJson("/api/v1/surat-masuk/{$letter->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.deleted', false);

        $this->assertDatabaseHas('incoming_letters', ['id' => $letter->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event_code' => 'LETTER_INCOMING_RESTORED',
            'auditable_id' => $letter->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => '001/09/2026',
            'asal_surat' => 'Bupati Semarang',
            'tanggal_surat' => '2026-09-01',
            'tanggal_diterima' => '2026-09-02',
            'perihal' => 'Undangan koordinasi layanan pendidikan',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function incomingLetter(Division $division, array $overrides = []): IncomingLetter
    {
        return IncomingLetter::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '001/09/2026',
            'origin_agency' => 'Dinas Pengirim',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Arsip awal',
        ], $overrides));
    }
}
