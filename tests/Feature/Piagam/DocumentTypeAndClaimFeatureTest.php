<?php

namespace Tests\Feature\Piagam;

use App\Models\Division;
use App\Models\DocumentType;
use App\Models\Piagam;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentTypeAndClaimFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_displays_ambil_dokumen_dinas_button(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Ambil Dokumen Dinas', false);
        $response->assertDontSee('Ambil Piagam</a>', false);
    }

    public function test_public_claim_page_renders_dropdown_jenis_dokumen(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $docType = DocumentType::query()->create([
            'name' => 'Piagam Penghargaan',
            'code' => 'PIAGAM',
            'is_active' => true,
        ]);
        $docTypeIjazah = DocumentType::query()->create([
            'name' => 'Ijazah',
            'code' => 'IJAZAH',
            'is_active' => true,
        ]);

        $response = $this->get('/piagam/ambil');
        $response->assertOk();
        $response->assertSee('Jenis Dokumen Dinas', false);
        $response->assertSee('Piagam Penghargaan', false);
        $response->assertSee('Ijazah', false);
        $response->assertSee('<select name="document_type_id"', false);
        $response->assertSee('name="document_type_name"', false);
    }

    public function test_guest_can_submit_ijazah_handover_without_competition_category(): void
    {
        Storage::fake('private_signatures');

        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $school = School::factory()->create(['name' => 'SMP Negeri 1 Ungaran', 'npsn' => '20320999']);
        $docTypeIjazah = DocumentType::query()->create([
            'name' => 'Ijazah',
            'code' => 'IJAZAH',
            'is_active' => true,
        ]);

        $dataUri = 'data:image/png;base64,'.base64_encode('fake-png-binary-content');

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', [
                'division_id' => $division->id,
                'recipient_name' => 'Hendra Setiawan',
                'recipient_position' => 'Orang Tua / Wali',
                'work_unit_selection' => 'school:'.$school->id,
                'student_name' => 'Rizky Ramadhan',
                'document_type_id' => $docTypeIjazah->id,
                'document_type_name' => 'Ijazah',
                'competition_year' => 2026,
                'signature_canvas' => $dataUri,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.document_type_id', $docTypeIjazah->id);
        $response->assertJsonPath('data.document_type_name', 'Ijazah');

        $this->assertDatabaseHas('piagams', [
            'division_id' => $division->id,
            'student_name' => 'Rizky Ramadhan',
            'document_type_id' => $docTypeIjazah->id,
            'document_type_name' => 'Ijazah',
            'piagam_description' => 'Ijazah – Rizky Ramadhan',
            'recipient_name' => 'Hendra Setiawan',
        ]);
    }

    public function test_guest_can_submit_handover_with_document_type(): void
    {
        Storage::fake('private_signatures');

        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $school = School::factory()->create(['name' => 'SD Negeri 1 Bergas', 'npsn' => '20320111']);
        $docType = DocumentType::query()->create([
            'name' => 'Piagam Penghargaan',
            'code' => 'PIAGAM',
            'is_active' => true,
        ]);

        $dataUri = 'data:image/png;base64,'.base64_encode('fake-png-binary-content');

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', [
                'division_id' => $division->id,
                'recipient_name' => 'Bambang Irawan',
                'recipient_position' => 'Guru Olahraga',
                'work_unit_selection' => 'school:'.$school->id,
                'student_name' => 'Kevin Sanjaya',
                'document_type_id' => $docType->id,
                'document_type_name' => 'Piagam Penghargaan',
                'competition_year' => 2026,
                'signature_canvas' => $dataUri,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.document_type_id', $docType->id);
        $response->assertJsonPath('data.document_type_name', 'Piagam Penghargaan');

        $this->assertDatabaseHas('piagams', [
            'division_id' => $division->id,
            'student_name' => 'Kevin Sanjaya',
            'document_type_id' => $docType->id,
            'document_type_name' => 'Piagam Penghargaan',
            'recipient_name' => 'Bambang Irawan',
        ]);
    }

    public function test_piagam_index_and_detail_show_document_type(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);
        $docType = DocumentType::query()->create([
            'name' => 'Piagam Penghargaan',
            'code' => 'PIAGAM',
            'is_active' => true,
        ]);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'document_type_id' => $docType->id,
            'document_type_name' => $docType->name,
            'student_name' => 'Alisa Fitri',
            'piagam_description' => 'Juara 1 Debat Bahasa Inggris',
            'school_origin' => 'SMP Negeri 1 Ungaran',
            'recipient_name' => 'Nurul Hidayah',
            'recipient_position' => 'Guru',
            'recipient_unit' => 'SMP Negeri 1 Ungaran',
            'created_by_user_id' => $operator->id,
        ]);

        // 1. Web index grid
        $responseIndex = $this->actingAs($operator)->get('/piagam');
        $responseIndex->assertOk();
        $responseIndex->assertSee('Jenis Dokumen', false);
        $responseIndex->assertSee('Piagam Penghargaan', false);

        // 2. Web show page
        $responseShow = $this->actingAs($operator)->get("/piagam/{$piagam->id}");
        $responseShow->assertOk();
        $responseShow->assertSee('Jenis Dokumen', false);
        $responseShow->assertSee('Piagam Penghargaan', false);

        // 3. API detail JSON
        $responseApi = $this->actingAs($operator)->getJson("/api/v1/piagam/{$piagam->id}");
        $responseApi->assertOk();
        $responseApi->assertJsonPath('data.document_type_id', $docType->id);
        $responseApi->assertJsonPath('data.document_type_name', 'Piagam Penghargaan');
    }

    public function test_reference_category_document_types_management(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        // 1. Create document type
        $responseCreate = $this->actingAs($superAdmin)
            ->postJson('/api/v1/reference/document-types', [
                'name' => 'Surat Keterangan Pengganti Ijazah',
                'code' => 'SKPI',
            ]);
        $responseCreate->assertCreated();
        $createdId = $responseCreate->json('data.id');

        $this->assertDatabaseHas('document_types', [
            'id' => $createdId,
            'name' => 'Surat Keterangan Pengganti Ijazah',
            'code' => 'SKPI',
        ]);

        // 2. View in master/kategori
        $responseView = $this->actingAs($superAdmin)->get('/master/kategori');
        $responseView->assertOk();
        $responseView->assertSee('Jenis Dokumen Dinas', false);
        $responseView->assertSee('Surat Keterangan Pengganti Ijazah', false);

        // 3. Delete document type
        $doc = DocumentType::findOrFail($createdId);
        $responseDelete = $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/reference/document-types/{$doc->id}");
        $responseDelete->assertOk();
        $this->assertSoftDeleted('document_types', ['id' => $createdId]);

        // 4. Restore document type
        $responseRestore = $this->actingAs($superAdmin)
            ->postJson("/api/v1/reference/document-types/{$createdId}/restore");
        $responseRestore->assertOk();
        $this->assertDatabaseHas('document_types', [
            'id' => $createdId,
            'deleted_at' => null,
        ]);
    }
}
