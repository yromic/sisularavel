<?php

namespace Tests\Feature\UI;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LetterModalCrudAndSplitPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_letters_index_renders_all_three_modals_and_responsive_split_elements(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/001/DIKDAS/2026',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat Koordinasi Kurikulum',
        ]);

        $this->actingAs($operator)
            ->get('/surat-masuk')
            ->assertOk()
            ->assertSee('id="split-letter-detail-modal"', false)
            ->assertSee('id="letter-form-modal"', false)
            ->assertSee('id="letter-delete-modal"', false)
            ->assertSee('openLetterDetailModal', false)
            ->assertSee('openLetterFormModal', false)
            ->assertSee('openLetterDeleteModal', false)
            ->assertSee('id="modal-btn-download"', false)
            ->assertSee('flex flex-col lg:flex-row', false);
    }

    public function test_outgoing_letters_index_renders_all_three_modals_and_responsive_split_elements(): void
    {
        $division = Division::factory()->create(['code' => 'PAUD']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '421/002/PAUD/2026',
            'letter_date' => '2026-09-10',
            'disseminated_date' => '2026-09-10',
            'subject' => 'Surat Edaran PAUD Inklusi',
        ]);

        $this->actingAs($operator)
            ->get('/surat-keluar')
            ->assertOk()
            ->assertSee('id="split-letter-detail-modal"', false)
            ->assertSee('id="letter-form-modal"', false)
            ->assertSee('id="letter-delete-modal"', false)
            ->assertSee('openLetterDetailModal', false)
            ->assertSee('openLetterFormModal', false)
            ->assertSee('openLetterDeleteModal', false)
            ->assertSee('id="modal-btn-download"', false)
            ->assertSee('flex flex-col lg:flex-row', false);
    }

    public function test_attachment_download_endpoint_streams_with_attachment_disposition_for_letters(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();

        $response = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '055/TEST/2026',
                'asal_surat' => 'Dinas Pendidikan',
                'tanggal_surat' => '2026-09-01',
                'tanggal_diterima' => '2026-09-02',
                'perihal' => 'Pengujian tombol download',
                'attachment' => UploadedFile::fake()->create('surat_resmi.pdf', 256, 'application/pdf'),
            ])
            ->assertCreated();

        $uuid = $response->json('data.attachment.uuid');

        $this->actingAs($operator)
            ->get("/api/v1/attachments/{$uuid}/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="surat_resmi.pdf"');
    }

    public function test_super_admin_topbar_division_switcher_is_mobile_responsive_and_prevents_overflow(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('name="division_id"', false)
            ->assertSee('max-w-[135px]', false)
            ->assertSee('truncate', false)
            ->assertSee('min-w-0 flex-1', false)
            ->assertSee('shrink-0', false);
    }

    public function test_incoming_letter_creation_with_multiple_camera_photos_merges_into_single_pdf(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();

        $photo1 = UploadedFile::fake()->image('lembar1.jpg', 600, 800);
        $photo2 = UploadedFile::fake()->image('lembar2.jpg', 600, 800);

        $response = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '099/FOTO/2026',
                'asal_surat' => 'Kantor Wilayah',
                'tanggal_surat' => '2026-09-01',
                'tanggal_diterima' => '2026-09-02',
                'perihal' => 'Pengujian Foto Multi-Lembar',
                'photos' => [$photo1, $photo2],
            ])
            ->assertCreated();

        $letter = IncomingLetter::where('letter_number', '099/FOTO/2026')->firstOrFail();
        $attachment = $letter->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertStringEndsWith('.pdf', $attachment->original_name);
        $this->assertStringContainsString('2_lembar', $attachment->original_name);
        $this->assertTrue(Storage::disk('private_documents')->exists($attachment->file_path));

        // Test preview and download of the merged PDF
        $this->actingAs($operator)
            ->get("/api/v1/attachments/{$attachment->uuid}/preview")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_outgoing_letter_creation_with_multiple_camera_photos_merges_into_single_pdf(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'PAUD']);
        $operator = User::factory()->for($division)->create();

        $photo1 = UploadedFile::fake()->image('lembar1.jpg', 600, 800);
        $photo2 = UploadedFile::fake()->image('lembar2.jpg', 600, 800);
        $photo3 = UploadedFile::fake()->image('lembar3.jpg', 600, 800);

        $response = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-keluar', [
                'nomor_surat' => '100/FOTO-OUT/2026',
                'tanggal_surat' => '2026-09-01',
                'tanggal_share' => '2026-09-02',
                'perihal' => 'Pengujian Foto Multi-Lembar Keluar',
                'photos' => [$photo1, $photo2, $photo3],
            ])
            ->assertCreated();

        $letter = OutgoingLetter::where('letter_number', '100/FOTO-OUT/2026')->firstOrFail();
        $attachment = $letter->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertStringEndsWith('.pdf', $attachment->original_name);
        $this->assertStringContainsString('3_lembar', $attachment->original_name);
        $this->assertTrue(Storage::disk('private_documents')->exists($attachment->file_path));
    }
}
