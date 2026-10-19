<?php

namespace Tests\Feature\Piagam;

use App\Models\Attachment;
use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\Piagam;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DigitalExpeditionBookHandoverContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private_signatures');
    }

    public function test_guest_can_access_piagam_ambil_without_login(): void
    {
        $response = $this->get('/piagam/ambil');

        $response->assertOk()
            ->assertSee('Tanda Terima Pengambilan Piagam')
            ->assertSee('data-testid="piagam-claim-form"', false)
            ->assertSee('data-testid="signature-pad"', false);
    }

    public function test_landing_page_displays_cta_button_and_no_embedded_claim_form(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Ambil Piagam')
            ->assertSee('/piagam/ambil')
            ->assertDontSee('id="signature-pad"', false);
    }

    public function test_guest_can_submit_handover_receipt_directly_without_pre_registration(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $school = School::factory()->create(['name' => 'SD Negeri 1 Ungaran']);

        $payload = [
            'piagam_description' => 'Juara 1 FLS2N Gambar Bercerita - Ahmad Zaki',
            'school_origin' => 'SD Negeri 1 Ungaran',
            'division_id' => $division->id,
            'recipient_name' => 'Budi Santoso, S.Pd.',
            'recipient_position' => 'Guru Pendamping',
            'recipient_unit' => 'SD Negeri 1 Ungaran',
            'signature_canvas' => $this->pngDataUrl(),
        ];

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recipient_name', 'Budi Santoso, S.Pd.')
            ->assertJsonPath('data.piagam_description', 'Juara 1 FLS2N Gambar Bercerita - Ahmad Zaki');

        $this->assertDatabaseHas('piagams', [
            'division_id' => $division->id,
            'student_name' => 'Juara 1 FLS2N Gambar Bercerita - Ahmad Zaki',
            'school_origin' => 'SD Negeri 1 Ungaran',
            'recipient_name' => 'Budi Santoso, S.Pd.',
            'recipient_position' => 'Guru Pendamping',
        ]);

        $piagam = Piagam::where('recipient_name', 'Budi Santoso, S.Pd.')->firstOrFail();
        $attachment = Attachment::where('attachable_type', Piagam::class)
            ->where('attachable_id', $piagam->id)
            ->firstOrFail();

        Storage::disk('private_signatures')->assertExists($attachment->file_path);
        $this->assertStringStartsWith('signatures/DIKDAS/', $attachment->file_path);
        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'PIAGAM_HANDOVER_RECORDED',
            'auditable_id' => $piagam->id,
        ]);
    }

    public function test_handover_receipt_submission_rejects_missing_mandatory_fields(): void
    {
        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', [
                'piagam_description' => '',
                'school_origin' => '',
                'division_id' => '',
                'recipient_name' => '',
                'recipient_position' => '',
                'signature_canvas' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'piagam_description',
                'school_origin',
                'division_id',
                'recipient_name',
                'recipient_position',
                'signature_canvas',
            ]);
    }

    public function test_handover_receipt_submission_rejects_blank_or_invalid_signature(): void
    {
        $division = Division::factory()->create(['status' => 'active']);

        $payload = [
            'piagam_description' => 'Piagam OSN',
            'school_origin' => 'SMP 1',
            'division_id' => $division->id,
            'recipient_name' => 'Pengambil',
            'recipient_position' => 'Guru',
            'signature_canvas' => 'invalid-data-url',
        ];

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['signature_canvas']);
    }

    public function test_handover_receipt_submission_is_idempotent(): void
    {
        $division = Division::factory()->create(['status' => 'active']);
        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'piagam_description' => 'Piagam MAPSI Rebana',
            'school_origin' => 'SD IT Ungaran',
            'division_id' => $division->id,
            'recipient_name' => 'Siti Khadijah',
            'recipient_position' => 'Pelatih',
            'signature_canvas' => $this->pngDataUrl(),
        ];

        $first = $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/piagam/ambil', $payload)
            ->assertCreated()
            ->json();

        $second = $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/piagam/ambil', $payload)
            ->assertCreated()
            ->json();

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('piagams', 1);
        $this->assertDatabaseCount('attachments', 1);
    }

    public function test_recorded_receipt_appears_in_internal_digital_expedition_book(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Juara 1 FLS2N Kriya',
            'piagam_description' => 'Juara 1 FLS2N Kriya Anyam',
            'school_origin' => 'SD Negeri 3 Bandungan',
            'recipient_name' => 'Rahmad Hidayat',
            'recipient_position' => 'Guru Seni',
            'competition_year' => 2026,
        ]);

        $response = $this->actingAs($operator)->get('/piagam');

        $response->assertOk()
            ->assertSee('Buku Ekspedisi Pengambilan Piagam')
            ->assertSee('Juara 1 FLS2N Kriya Anyam')
            ->assertSee('SD Negeri 3 Bandungan')
            ->assertSee('Rahmad Hidayat');
    }

    public function test_authorized_staff_can_view_receipt_detail_and_signature_preview(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Juara 2 Karate O2SN',
            'piagam_description' => 'Juara 2 Karate O2SN',
            'school_origin' => 'SD Negeri Ungaran 01',
            'recipient_name' => 'Bambang Irawan',
            'recipient_position' => 'Pelatih',
            'competition_year' => 2026,
        ]);

        $filePath = 'signatures/DIKDAS/test-sig.png';
        Storage::disk('private_signatures')->put($filePath, 'binary-data');

        $attachment = Attachment::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => Piagam::class,
            'attachable_id' => $piagam->id,
            'file_path' => $filePath,
            'original_name' => 'signature.png',
            'file_size_bytes' => 11,
            'mime_type' => 'image/png',
            'file_hash' => hash('sha256', 'binary-data'),
            'is_missing' => false,
            'migration_status' => 'migrated',
        ]);

        $response = $this->actingAs($operator)->get("/piagam/{$piagam->id}");

        $response->assertOk()
            ->assertSee('Juara 2 Karate O2SN')
            ->assertSee('Bambang Irawan')
            ->assertSee($attachment->uuid);

        // Preview signature
        $sigResponse = $this->actingAs($operator)->get("/api/v1/attachments/{$attachment->uuid}/preview");
        $sigResponse->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_guest_is_forbidden_from_internal_expedition_book(): void
    {
        $this->get('/piagam')->assertRedirect('/login');
    }

    public function test_operator_from_other_division_is_forbidden_from_viewing_receipt_detail(): void
    {
        $divisionA = Division::factory()->create();
        $divisionB = Division::factory()->create();

        $operatorB = User::factory()->for($divisionB)->create(['role' => 'operator']);

        $piagamA = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $divisionA->id,
            'student_name' => 'Piagam Divisi A',
            'recipient_name' => 'Penerima A',
            'competition_year' => 2026,
        ]);

        $this->actingAs($operatorB)->get("/piagam/{$piagamA->id}")->assertForbidden();
    }

    public function test_dashboard_metrics_counts_recorded_handover_receipts(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Piagam Test 1',
            'recipient_name' => 'Penerima 1',
            'competition_year' => 2026,
        ]);

        Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Piagam Test 2',
            'recipient_name' => 'Penerima 2',
            'competition_year' => 2026,
        ]);

        $response = $this->actingAs($operator)->get('/dashboard');

        $response->assertOk()
            ->assertSee('2'); // Count of piagams in division
    }

    public function test_smart_search_finds_recorded_handover_by_recipient_or_school(): void
    {
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Juara Kaligrafi MAPSI',
            'piagam_description' => 'Juara Kaligrafi MAPSI Tingkat Kabupaten',
            'school_origin' => 'MI Al Huda Bergas',
            'recipient_name' => 'Ustadz Mansyur',
            'competition_year' => 2026,
        ]);

        $response = $this->actingAs($operator)->getJson('/api/v1/search?q=Mansyur');

        $response->assertOk()
            ->assertJsonFragment(['type' => 'piagam', 'id' => $piagam->id]);

        $responseSchool = $this->actingAs($operator)->getJson('/api/v1/search?q=Bergas');

        $responseSchool->assertOk()
            ->assertJsonFragment(['type' => 'piagam', 'id' => $piagam->id]);
    }

    public function test_export_csv_includes_recorded_handover_data(): void
    {
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Lomba Bertutur Kata',
            'piagam_description' => 'Lomba Bertutur Kata Bahasa Jawa',
            'school_origin' => 'SMP N 1 Bawen',
            'recipient_name' => 'Dewi Lestari',
            'recipient_position' => 'Guru Bahasa',
            'competition_year' => 2026,
        ]);

        $response = $this->actingAs($operator)->get('/api/v1/reports/export.csv?type=piagam');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Lomba Bertutur Kata Bahasa Jawa', $content);
        $this->assertStringContainsString('SMP N 1 Bawen', $content);
        $this->assertStringContainsString('Dewi Lestari', $content);
    }

    public function test_public_form_renders_actual_active_references(): void
    {
        $division = Division::factory()->create(['name' => 'Bidang Pembinaan SD', 'code' => 'DIKDAS', 'status' => 'active']);
        $school = School::factory()->create(['name' => 'SD Negeri Bandungan 01', 'npsn' => '20320678', 'is_active' => true]);
        $workUnit = WorkUnit::query()->create(['name' => 'Korwilcam Bergas', 'code' => 'KOR-BRG', 'is_active' => true]);
        $pickupCat = PickupCategory::query()->create(['division_id' => $division->id, 'name' => 'Diambil Perwakilan Sekolah', 'is_active' => true]);
        $compCat = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'O2SN Renang Putra', 'is_active' => true]);

        // Inactive records should not be loaded
        $inactiveSchool = School::factory()->create(['name' => 'Sekolah Tutup', 'is_active' => false]);
        $inactiveDivision = Division::factory()->create(['name' => 'Bidang Nonaktif', 'status' => 'inactive']);

        $response = $this->get('/piagam/ambil');

        $response->assertOk()
            ->assertSee('Bidang Pembinaan SD')
            ->assertSee('SD Negeri Bandungan 01')
            ->assertSee('20320678')
            ->assertSee('Korwilcam Bergas')
            ->assertSee('Diambil Perwakilan Sekolah')
            ->assertSee('O2SN Renang Putra')
            ->assertDontSee('Sekolah Tutup')
            ->assertDontSee('Bidang Nonaktif');
    }

    public function test_submission_with_reference_selection_stores_relations_and_snapshots(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $school = School::factory()->create(['name' => 'SMP Negeri 1 Ungaran', 'npsn' => '20320340', 'is_active' => true]);
        $pickupCat = PickupCategory::query()->create(['division_id' => $division->id, 'name' => 'Diambil di Loket', 'is_active' => true]);
        $compCat = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'FLS2N Solo Vokal', 'is_active' => true]);

        $payload = [
            'division_id' => $division->id,
            'recipient_name' => 'Siti Nurhaliza',
            'recipient_position' => 'Guru Seni Musik',
            'work_unit_selection' => 'school:'.$school->id,
            'npsn' => '20320340',
            'pickup_category_id' => $pickupCat->id,
            'competition_category_id' => $compCat->id,
            'competition_year' => 2026,
            'student_name' => 'Rangga Sasana',
            'piagam_description' => 'Juara 1 Solo Vokal SMP',
            'signature_canvas' => $this->pngDataUrl(),
        ];

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pickup_category_name', 'Diambil di Loket')
            ->assertJsonPath('data.competition_category_name', 'FLS2N Solo Vokal')
            ->assertJsonPath('data.recipient_unit', 'SMP Negeri 1 Ungaran');

        $this->assertDatabaseHas('piagams', [
            'division_id' => $division->id,
            'school_id' => $school->id,
            'npsn' => '20320340',
            'pickup_category_id' => $pickupCat->id,
            'pickup_category_name' => 'Diambil di Loket',
            'competition_category_id' => $compCat->id,
            'competition_category_name' => 'FLS2N Solo Vokal',
            'student_name' => 'Rangga Sasana',
            'recipient_unit' => 'SMP Negeri 1 Ungaran',
        ]);
    }

    public function test_submission_with_manual_entries_stores_snapshots_without_master_pollution(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);

        $initialSchoolsCount = School::count();
        $initialWorkUnitsCount = WorkUnit::count();
        $initialPickupCount = PickupCategory::count();
        $initialCompCount = CompetitionCategory::count();

        $payload = [
            'division_id' => $division->id,
            'recipient_name' => 'Hendra Setiawan',
            'recipient_position' => 'Ketua Klub Olahraga',
            'work_unit_selection' => 'manual',
            'recipient_unit_manual' => 'Klub Panahan Ungaran Asri',
            'npsn' => 'NON-SEKOLAH-99',
            'pickup_category_id' => 'manual',
            'pickup_category_manual' => 'Diantar oleh Petugas Khusus',
            'competition_category_id' => 'manual',
            'competition_category_manual' => 'Kejurda Panahan Tradisional',
            'competition_year' => 2026,
            'student_name' => 'Bayu Wicaksono',
            'piagam_description' => 'Juara Umum Panahan',
            'signature_canvas' => $this->pngDataUrl(),
        ];

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.pickup_category_name', 'Diantar oleh Petugas Khusus')
            ->assertJsonPath('data.competition_category_name', 'Kejurda Panahan Tradisional')
            ->assertJsonPath('data.recipient_unit', 'Klub Panahan Ungaran Asri');

        $this->assertDatabaseHas('piagams', [
            'division_id' => $division->id,
            'school_id' => null,
            'work_unit_id' => null,
            'pickup_category_id' => null,
            'pickup_category_name' => 'Diantar oleh Petugas Khusus',
            'competition_category_id' => null,
            'competition_category_name' => 'Kejurda Panahan Tradisional',
            'recipient_unit' => 'Klub Panahan Ungaran Asri',
            'school_origin' => 'Klub Panahan Ungaran Asri',
            'npsn' => 'NON-SEKOLAH-99',
        ]);

        // Verify master tables were NOT polluted by manual entries
        $this->assertEquals($initialSchoolsCount, School::count());
        $this->assertEquals($initialWorkUnitsCount, WorkUnit::count());
        $this->assertEquals($initialPickupCount, PickupCategory::count());
        $this->assertEquals($initialCompCount, CompetitionCategory::count());
    }

    public function test_submission_rejects_invalid_or_inactive_reference_ids(): void
    {
        $division = Division::factory()->create(['status' => 'active']);
        $inactiveComp = CompetitionCategory::query()->create([
            'division_id' => $division->id,
            'name' => 'Lomba Usang',
            'is_active' => false,
        ]);

        $payload = [
            'division_id' => $division->id,
            'recipient_name' => 'Budi',
            'recipient_position' => 'Guru',
            'work_unit_selection' => 'school:999999', // Non-existent school
            'competition_category_id' => $inactiveComp->id, // Inactive competition
            'competition_year' => 2026,
            'student_name' => 'Ahmad',
            'signature_canvas' => $this->pngDataUrl(),
        ];

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', $payload);

        $response->assertUnprocessable();
    }

    public function test_submission_rejects_invalid_or_ambiguous_manual_division(): void
    {
        $payload = [
            'division_id' => 'manual',
            'division_manual' => 'Bidang Tidak Pernah Ada',
            'recipient_name' => 'Budi',
            'recipient_position' => 'Guru',
            'work_unit_selection' => 'manual',
            'recipient_unit_manual' => 'SD Negeri 1',
            'student_name' => 'Ali',
            'competition_year' => 2026,
            'signature_canvas' => $this->pngDataUrl(),
        ];

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['division_id']);
    }

    public function test_submission_accepts_valid_unique_manual_division_and_resolves_id(): void
    {
        $division = Division::factory()->create([
            'name' => 'Bidang Pembinaan SD',
            'code' => 'DIKDAS',
            'status' => 'active',
        ]);

        $payload = [
            'division_id' => 'manual',
            'division_manual' => 'dikdas', // Unique case-insensitive code
            'recipient_name' => 'Dwi Astuti',
            'recipient_position' => 'Kepala Sekolah',
            'work_unit_selection' => 'manual',
            'recipient_unit_manual' => 'SD Negeri Bergas 02',
            'student_name' => 'Doni Salman',
            'competition_year' => 2026,
            'signature_canvas' => $this->pngDataUrl(),
        ];

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('piagams', [
            'division_id' => $division->id,
            'student_name' => 'Doni Salman',
        ]);
    }

    public function test_historical_record_preserves_original_labels_when_reference_is_renamed_or_deleted(): void
    {
        $division = Division::factory()->create(['status' => 'active']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);
        $compCat = CompetitionCategory::query()->create([
            'division_id' => $division->id,
            'name' => 'Nama Kompetisi Asli 2026',
            'is_active' => true,
        ]);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Siswa Juara',
            'competition_category_id' => $compCat->id,
            'competition_category_name' => 'Nama Kompetisi Asli 2026',
            'competition_year' => 2026,
            'recipient_name' => 'Pengambil A',
            'recipient_unit' => 'Unit Kerja A',
        ]);

        // Admin subsequently renames the master reference category
        $compCat->update(['name' => 'Nama Kompetisi Baru 2027 Diubah']);

        // Historical record still returns original label
        $this->assertEquals('Nama Kompetisi Asli 2026', $piagam->fresh()->competition_display_name);

        $response = $this->actingAs($operator)->get("/piagam/{$piagam->id}");
        $response->assertOk()
            ->assertSee('Nama Kompetisi Asli 2026');
    }

    public function test_admin_can_manage_work_units_via_reference_endpoint(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // 1. Store Work Unit
        $createRes = $this->actingAs($superAdmin)
            ->postJson('/api/v1/reference/work-units', [
                'name' => 'Kantor Kecamatan Sumowono',
                'code' => 'KEC-SMW',
                'is_active' => true,
            ]);

        $createRes->assertCreated()
            ->assertJsonPath('data.name', 'Kantor Kecamatan Sumowono');

        $unit = WorkUnit::where('name', 'Kantor Kecamatan Sumowono')->firstOrFail();

        // 2. Update Work Unit
        $updateRes = $this->actingAs($superAdmin)
            ->putJson("/api/v1/reference/work-units/{$unit->id}", [
                'name' => 'Kantor Kecamatan Sumowono (Updated)',
                'code' => 'KEC-SMW-2',
                'is_active' => true,
            ]);

        $updateRes->assertOk()
            ->assertJsonPath('data.name', 'Kantor Kecamatan Sumowono (Updated)');

        // 3. Destroy Work Unit
        $deleteRes = $this->actingAs($superAdmin)
            ->deleteJson("/api/v1/reference/work-units/{$unit->id}");

        $deleteRes->assertOk();
        $this->assertDatabaseMissing('work_units', ['id' => $unit->id]);
    }

    public function test_piagam_claim_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/piagam/ambil', [], ['Idempotency-Key' => "rate-test-{$i}"]);
        }

        $this->postJson('/api/v1/piagam/ambil', [], ['Idempotency-Key' => 'rate-test-11'])
            ->assertStatus(429);
    }

    private function pngDataUrl(): string
    {
        return 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=').str_repeat('A', 150));
    }
}
