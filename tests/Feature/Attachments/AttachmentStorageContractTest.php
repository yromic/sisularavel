<?php

namespace Tests\Feature\Attachments;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttachmentStorageContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_letter_upload_stores_attachment_on_private_disk_and_streams_to_authorized_user(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();

        $attachmentUuid = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '099/09/2026',
                'asal_surat' => 'Bupati Semarang',
                'tanggal_surat' => '2026-09-01',
                'tanggal_diterima' => '2026-09-02',
                'perihal' => 'Lampiran aman',
                'attachment' => UploadedFile::fake()->create('scan.pdf', 128, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.attachment.original_name', 'scan.pdf')
            ->json('data.attachment.uuid');

        $attachment = Attachment::query()->where('uuid', $attachmentUuid)->firstOrFail();

        $this->assertStringStartsWith('documents/DIKDAS/2026/09/', $attachment->file_path);
        $this->assertFalse($attachment->is_missing);
        Storage::disk('private_documents')->assertExists($attachment->file_path);

        $this->actingAs($operator)
            ->get("/api/v1/attachments/{$attachment->uuid}/preview")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_attachment_preview_denies_cross_division_and_missing_legacy_file_is_safe_404(): void
    {
        Storage::fake('private_documents');

        $dikdas = Division::factory()->create(['code' => 'DIKDAS']);
        $paud = Division::factory()->create(['code' => 'PAUD']);
        $dikdasOperator = User::factory()->for($dikdas)->create();
        $paudOperator = User::factory()->for($paud)->create();
        $letter = $this->incomingLetter($dikdas);
        $path = 'documents/DIKDAS/2026/09/'.Str::uuid().'.pdf';
        Storage::disk('private_documents')->put($path, 'private pdf');

        $attachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $dikdas->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => $path,
            'original_name' => 'private.pdf',
            'file_size_bytes' => 11,
            'mime_type' => 'application/pdf',
            'file_hash' => hash('sha256', 'private pdf'),
            'created_by_user_id' => $dikdasOperator->id,
        ]);

        $this->actingAs($paudOperator)
            ->get("/api/v1/attachments/{$attachment->uuid}/preview")
            ->assertForbidden()
            ->assertJsonPath('code', 'ERR_403_DIVISI');

        $missing = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $dikdas->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => null,
            'original_name' => 'drive.pdf',
            'file_size_bytes' => 0,
            'mime_type' => 'application/pdf',
            'is_missing' => true,
            'migration_status' => 'missing_drive_file',
            'missing_reason' => 'Google Drive 404',
            'created_by_user_id' => $dikdasOperator->id,
        ]);

        $this->actingAs($dikdasOperator)
            ->get("/api/v1/attachments/{$missing->uuid}/preview")
            ->assertNotFound()
            ->assertJsonPath('code', 'ERR_404_ATTACHMENT');
    }

    public function test_division_admin_remediates_missing_attachment_with_private_pdf(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();
        $letter = $this->incomingLetter($division);
        $attachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => null,
            'original_name' => 'missing.pdf',
            'file_size_bytes' => 0,
            'mime_type' => 'application/pdf',
            'is_missing' => true,
            'migration_status' => 'missing_drive_file',
            'missing_reason' => 'Google Drive 404',
        ]);

        $this->actingAs($admin)
            ->postJson("/api/v1/surat-masuk/{$letter->id}/remediate-attachment", [
                'attachment' => UploadedFile::fake()->create('replacement.pdf', 64, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('data.is_missing', false)
            ->assertJsonPath('data.migration_status', 'migrated');

        $attachment->refresh();
        $this->assertNotNull($attachment->file_path);
        Storage::disk('private_documents')->assertExists($attachment->file_path);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event_code' => 'ATTACHMENT_UPLOADED',
            'auditable_type' => Attachment::class,
            'auditable_id' => $attachment->id,
        ]);
    }

    public function test_attachment_upload_rejects_unapproved_mime_type(): void
    {
        $operator = User::factory()->for(Division::factory())->create();

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '100/09/2026',
                'asal_surat' => 'Bupati Semarang',
                'tanggal_surat' => '2026-09-01',
                'tanggal_diterima' => '2026-09-02',
                'perihal' => 'Lampiran ditolak',
                'attachment' => UploadedFile::fake()->create('payload.exe', 8, 'application/x-msdownload'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attachment']);

        $this->assertDatabaseCount('incoming_letters', 0);
        $this->assertDatabaseCount('attachments', 0);
    }

    private function incomingLetter(Division $division): IncomingLetter
    {
        return IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '001/09/2026',
            'origin_agency' => 'Dinas Pengirim',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Arsip awal',
        ]);
    }
}
