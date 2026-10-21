<?php

namespace Tests\Feature\Letters;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LetterBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_bulk_delete_and_restore_incoming_letters(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();

        $letter1 = IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/001/2026',
            'origin_agency' => 'Bupati',
            'letter_date' => '2026-01-10',
            'received_date' => '2026-01-12',
            'subject' => 'Surat 1',
        ]);

        $letter2 = IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/002/2026',
            'origin_agency' => 'Bupati',
            'letter_date' => '2026-01-11',
            'received_date' => '2026-01-13',
            'subject' => 'Surat 2',
        ]);

        // Preview
        $previewRes = $this->actingAs($superAdmin)
            ->postJson('/api/v1/surat-masuk/bulk-delete/preview', [
                'ids' => [$letter1->id, $letter2->id],
            ]);

        $previewRes->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 2,
                'skipped_count' => 0,
            ]);

        // Delete
        $idempotencyKey = (string) Str::uuid();
        $deleteRes = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/surat-masuk/bulk-delete', [
                'ids' => [$letter1->id, $letter2->id],
            ]);

        $deleteRes->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 2,
            ]);

        $this->assertSoftDeleted('incoming_letters', ['id' => $letter1->id]);
        $this->assertSoftDeleted('incoming_letters', ['id' => $letter2->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'LETTER_INCOMING_BULK_DELETED',
            'user_id' => $superAdmin->id,
        ]);

        // Restore
        $restoreIdempotencyKey = (string) Str::uuid();
        $restoreRes = $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', $restoreIdempotencyKey)
            ->postJson('/api/v1/surat-masuk/bulk-restore', [
                'ids' => [$letter1->id, $letter2->id],
            ]);

        $restoreRes->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 2,
            ]);

        $this->assertDatabaseHas('incoming_letters', ['id' => $letter1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('incoming_letters', ['id' => $letter2->id, 'deleted_at' => null]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'LETTER_INCOMING_BULK_RESTORED',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_super_admin_can_bulk_delete_and_restore_outgoing_letters(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create();

        $out1 = OutgoingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '421/001/2026',
            'letter_date' => '2026-02-01',
            'disseminated_date' => '2026-02-02',
            'subject' => 'Surat Keluar 1',
        ]);

        $out2 = OutgoingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '421/002/2026',
            'letter_date' => '2026-02-03',
            'disseminated_date' => '2026-02-04',
            'subject' => 'Surat Keluar 2',
        ]);

        // Delete
        $idempotencyKey = (string) Str::uuid();
        $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/surat-keluar/bulk-delete', [
                'ids' => [$out1->id, $out2->id],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 2,
            ]);

        $this->assertSoftDeleted('outgoing_letters', ['id' => $out1->id]);
        $this->assertSoftDeleted('outgoing_letters', ['id' => $out2->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'LETTER_OUTGOING_BULK_DELETED',
        ]);

        // Restore
        $restoreKey = (string) Str::uuid();
        $this->actingAs($superAdmin)
            ->withHeader('Idempotency-Key', $restoreKey)
            ->postJson('/api/v1/surat-keluar/bulk-restore', [
                'ids' => [$out1->id, $out2->id],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 2,
            ]);

        $this->assertDatabaseHas('outgoing_letters', ['id' => $out1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('outgoing_letters', ['id' => $out2->id, 'deleted_at' => null]);
    }

    public function test_division_admin_cannot_bulk_delete_letters_of_foreign_division(): void
    {
        $divA = Division::factory()->create();
        $divB = Division::factory()->create();

        $adminA = User::factory()->for($divA)->divisionAdmin()->create();

        $letterA = IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $divA->id,
            'letter_number' => '420/A/2026',
            'origin_agency' => 'Asal A',
            'letter_date' => '2026-01-01',
            'received_date' => '2026-01-02',
            'subject' => 'Surat Divisi A',
        ]);

        $letterB = IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $divB->id,
            'letter_number' => '420/B/2026',
            'origin_agency' => 'Asal B',
            'letter_date' => '2026-01-01',
            'received_date' => '2026-01-02',
            'subject' => 'Surat Divisi B',
        ]);

        // Targeting foreign letterB must fail with 403 Forbidden
        $idempotencyKey = (string) Str::uuid();
        $this->actingAs($adminA)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/surat-masuk/bulk-delete', [
                'ids' => [$letterA->id, $letterB->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('incoming_letters', ['id' => $letterA->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('incoming_letters', ['id' => $letterB->id, 'deleted_at' => null]);
    }

    public function test_operator_cannot_perform_bulk_letter_operations(): void
    {
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $letter = IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/OP/2026',
            'origin_agency' => 'Asal',
            'letter_date' => '2026-01-01',
            'received_date' => '2026-01-02',
            'subject' => 'Surat Op',
        ]);

        $idempotencyKey = (string) Str::uuid();
        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/surat-masuk/bulk-delete', [
                'ids' => [$letter->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('incoming_letters', ['id' => $letter->id, 'deleted_at' => null]);
    }

    public function test_can_preview_bulk_restore_letters_without_idempotency_key(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $div = Division::factory()->create();

        $trashedIn = IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $div->id,
            'letter_number' => '420/IN-TRASH/2026',
            'origin_agency' => 'Asal',
            'letter_date' => '2026-01-01',
            'received_date' => '2026-01-02',
            'subject' => 'Surat Masuk Trashed',
        ]);
        $trashedIn->delete();

        $trashedOut = OutgoingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $div->id,
            'letter_year' => 2026,
            'letter_number' => '001/OUT-TRASH/2026',
            'letter_date' => '2026-01-01',
            'disseminated_date' => '2026-01-02',
            'subject' => 'Surat Keluar Trashed',
        ]);
        $trashedOut->delete();

        // 1. Preview restore incoming letter via dedicated route
        $this->actingAs($superAdmin)
            ->postJson('/api/v1/surat-masuk/bulk-restore/preview', [
                'ids' => [$trashedIn->id],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 1,
                'skipped_count' => 0,
            ]);

        // 2. Preview restore outgoing letter via generic route with action=restore
        $this->actingAs($superAdmin)
            ->postJson('/api/v1/surat-keluar/bulk-delete/preview', [
                'ids' => [$trashedOut->id],
                'action' => 'restore',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_count' => 1,
                'skipped_count' => 0,
            ]);
    }
}
