<?php

namespace Tests\Feature\UI;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UxHeuristicRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ux010_admin_can_view_trashed_letters_and_restore_via_web_route(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $incoming = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/TRASH-IN/2026',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat Koordinasi Trashed',
        ]);
        $incoming->delete();

        $outgoing = OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '420/TRASH-OUT/2026',
            'letter_date' => '2026-09-10',
            'disseminated_date' => '2026-09-10',
            'subject' => 'Surat Edaran Trashed',
        ]);
        $outgoing->delete();

        // 1. Admin accesses trash view on surat-masuk
        $this->actingAs($admin)
            ->get('/surat-masuk?status=trashed')
            ->assertOk()
            ->assertSee('Kotak Sampah')
            ->assertSee('420/TRASH-IN/2026')
            ->assertSee('Pulihkan');

        // 2. Admin restores incoming letter
        $this->actingAs($admin)
            ->post("/surat-masuk/{$incoming->id}/restore")
            ->assertRedirect();

        $this->assertNull($incoming->fresh()->deleted_at);

        // 3. Admin accesses trash view on surat-keluar
        $this->actingAs($admin)
            ->get('/surat-keluar?status=trashed')
            ->assertOk()
            ->assertSee('Kotak Sampah')
            ->assertSee('420/TRASH-OUT/2026')
            ->assertSee('Pulihkan');

        // 4. Admin restores outgoing letter
        $this->actingAs($admin)
            ->post("/surat-keluar/{$outgoing->id}/restore")
            ->assertRedirect();

        $this->assertNull($outgoing->fresh()->deleted_at);
    }

    public function test_ux014_school_autocomplete_endpoint_returns_matching_schools_json(): void
    {
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        School::query()->create(['npsn' => '20320340', 'name' => 'SMP Negeri 1 Ungaran']);
        School::query()->create(['npsn' => '20320678', 'name' => 'SD Negeri Bandungan 01']);

        $response = $this->actingAs($operator)
            ->getJson('/api/v1/schools?search=Ungaran')
            ->assertOk();

        $response->assertJsonFragment(['npsn' => '20320340', 'name' => 'SMP Negeri 1 Ungaran']);
        $response->assertJsonMissing(['npsn' => '20320678']);
    }

    public function test_ux018_and_ux020_dashboard_renders_quick_navigation_cards_and_audit_timestamps(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        AuditLog::query()->create([
            'division_id' => $division->id,
            'user_id' => $operator->id,
            'event_code' => 'TEST_EVENT',
            'description' => 'Aktivitas pengujian sistem',
            'username' => $operator->username,
            'role' => $operator->role,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($operator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pusat Persuratan')
            ->assertSee('Surat Masuk')
            ->assertSee('Surat Keluar')
            ->assertSee('Pencarian Pintar')
            ->assertSee('TEST_EVENT')
            ->assertSee('cursor-help', false)
            ->assertSee('WIB', false);
    }

    public function test_ux011_active_filter_chips_render_when_search_parameters_are_present(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        // Check workspace active filter chips
        $this->actingAs($operator)
            ->get('/persuratan?q=Festival')
            ->assertOk()
            ->assertSee('Filter Aktif:')
            ->assertSee('Festival')
            ->assertSee('Hapus Semua Filter');

        // Check reports active filter chips
        $this->actingAs($operator)
            ->get('/laporan?q=Lomba&type=incoming&date_from=2026-01-01')
            ->assertOk()
            ->assertSee('Filter Aktif:')
            ->assertSee('Lomba')
            ->assertSee('Surat Masuk Saja')
            ->assertSee('Dari: 01/01/2026');
    }

    public function test_ux007_master_crud_views_render_modal_confirmation_dialogs(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // 1. Master Sekolah (Super Admin)
        $this->actingAs($superAdmin)
            ->get('/master/sekolah')
            ->assertOk()
            ->assertSee('id="modal-delete-sekolah"', false)
            ->assertSee('Konfirmasi Hapus Data Sekolah');

        // 2. Master Divisi (Super Admin)
        $this->actingAs($superAdmin)
            ->get('/master/divisi')
            ->assertOk()
            ->assertSee('id="modal-delete-divisi"', false)
            ->assertSee('Konfirmasi Hapus Divisi');

        // 3. Master Pengguna
        $this->actingAs($superAdmin)
            ->get('/master/pengguna')
            ->assertOk()
            ->assertSee('id="modal-delete-user"', false)
            ->assertSee('Konfirmasi Hapus Pengguna');
    }

    public function test_ux2_001_application_timezone_is_standardized_to_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->timezoneName);
    }

    public function test_ux2_002_piagam_claim_form_has_dynamic_idempotency_key_regeneration(): void
    {
        $this->get('/piagam/ambil')
            ->assertOk()
            ->assertSee('generateIdempotencyKey', false)
            ->assertSee('resetFormForNewReceipt', false);
    }

    public function test_ux2_026_page_header_neutralizes_malicious_script_tags(): void
    {
        $view = $this->blade('<x-page-header title="<script>alert(1)</script>Test Judul" subtitle="<img src=x onerror=alert(1)>Sub" />');

        $view->assertDontSee('<script>', false);
        $view->assertDontSee('onerror=alert(1)', false);
        $view->assertSee('Test Judul');
    }

    public function test_ux2_003_and_ux2_030_letter_form_modal_renders_edit_mode_notices_and_dirty_guard(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $this->actingAs($admin)
            ->get('/surat-masuk')
            ->assertOk()
            ->assertSee('id="form-edit-attachment-notice"', false)
            ->assertSee('Dokumen Lampiran Terkunci')
            ->assertSee('form-division-edit-locked-notice', false)
            ->assertSee('editInitialValues', false);
    }

    public function test_ux2_004_and_ux2_005_super_admin_in_global_mode_must_select_division_and_session_is_not_silently_mutated(): void
    {
        $div1 = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $div2 = Division::factory()->create(['code' => 'PAUD', 'status' => 'active']);
        $superAdmin = User::factory()->superAdmin()->create();

        // 1. In global mode, form renders disabled prompt requiring deliberate choice
        $this->actingAs($superAdmin)
            ->withSession([]) // Global mode (null active_division_id)
            ->get('/surat-masuk/create')
            ->assertOk()
            ->assertSee('-- Pilih Divisi Pengarsip (Wajib) --');

        // 2. Submitting without division in global mode is rejected with clean 422
        $this->actingAs($superAdmin)
            ->withSession([])
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '420/GLOBAL/001',
                'asal_surat' => 'Kemendikbud',
                'tanggal_surat' => '2026-10-19',
                'tanggal_diterima' => '2026-10-19',
                'perihal' => 'Uji Konteks Global',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['division_id']);

        // 3. Submitting with explicit division stores to that division WITHOUT mutating global session
        $this->actingAs($superAdmin)
            ->withSession([])
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'division_id' => $div2->id,
                'nomor_surat' => '420/PAUD/001',
                'asal_surat' => 'Kemendikbud',
                'tanggal_surat' => '2026-10-19',
                'tanggal_diterima' => '2026-10-19',
                'perihal' => 'Uji Konteks Global Tersimpan',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('incoming_letters', [
            'letter_number' => '420/PAUD/001',
            'division_id' => $div2->id,
        ]);
        $this->assertNull(session('active_division_id'));
    }

    public function test_ux2_007_operator_cannot_see_edit_button_on_letters_index(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/TEST-OP/2026',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-10-19',
            'received_date' => '2026-10-19',
            'subject' => 'Surat Uji Hak Akses Edit',
        ]);

        // Operator sees Detail but NEVER sees Edit button on index
        $this->actingAs($operator)
            ->get('/surat-masuk')
            ->assertOk()
            ->assertSee('Detail')
            ->assertDontSee('/surat-masuk/'.$letter->id.'/edit');

        // Admin sees Edit button
        $this->actingAs($admin)
            ->get('/surat-masuk')
            ->assertOk()
            ->assertSee('/surat-masuk/'.$letter->id.'/edit');

        // Direct API update by operator returns clean 403 role message
        $this->actingAs($operator)
            ->putJson("/api/v1/surat-masuk/{$letter->id}", [
                'nomor_surat' => '420/HACK/2026',
                'asal_surat' => 'Hacker',
                'tanggal_surat' => '2026-10-19',
                'tanggal_diterima' => '2026-10-19',
                'perihal' => 'Perubahan Ilegal',
            ])
            ->assertStatus(403)
            ->assertJsonPath('code', 'ERR_403_ROLE');
    }

    public function test_ux2_006_full_indonesian_localization_for_validation_and_auth(): void
    {
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('id', app()->getLocale());

        // 1. Validation messages in Indonesian
        $this->assertSame('Kolom NPSN wajib diisi.', trans('validation.required', ['attribute' => 'NPSN']));
        $this->assertSame('Kombinasi kredensial tersebut tidak cocok dengan data kami.', trans('auth.failed'));
        $this->assertSame('&laquo; Sebelumnya', trans('pagination.previous'));

        // 2. Form submission validation in Indonesian
        $superAdmin = User::factory()->superAdmin()->create();
        $response = $this->actingAs($superAdmin)
            ->post('/api/v1/schools', [
                'npsn' => '123', // invalid digits
                'name' => '',
            ]);

        $response->assertSessionHasErrors(['npsn', 'name']);
        $errors = session('errors');
        $this->assertStringContainsString('8 digit', $errors->first('npsn'));
        $this->assertStringContainsString('wajib diisi', $errors->first('name'));
    }

    public function test_ux2_008_and_ux2_009_global_confirm_modal_and_symmetric_popup_create_forms(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // 1. Authenticated layout renders reusable global confirm modal
        $this->actingAs($superAdmin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="global-confirm-modal"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false);

        // 2. Master Pengguna renders symmetric popup modal for create user
        $this->actingAs($superAdmin)
            ->get('/master/pengguna')
            ->assertOk()
            ->assertSee('id="modal-tambah-user"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('openCreateUserModal', false);

        // 3. Master Sekolah renders symmetric popup modal for create school
        $this->actingAs($superAdmin)
            ->get('/master/sekolah')
            ->assertOk()
            ->assertSee('id="modal-tambah-sekolah"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('openCreateSchoolModal', false);

        // 4. Master Divisi renders symmetric popup modal for create division
        $this->actingAs($superAdmin)
            ->get('/master/divisi')
            ->assertOk()
            ->assertSee('id="modal-tambah-divisi"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('openCreateDivisionModal', false);
    }

    public function test_ux2_010_and_ux2_011_modal_form_labels_have_explicit_for_bindings(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // 1. Persuratan Form Modal renders explicit for bindings
        $this->actingAs($superAdmin)
            ->get('/persuratan')
            ->assertOk()
            ->assertSee('for="form-input-nomor"', false)
            ->assertSee('id="form-input-nomor"', false)
            ->assertSee('for="form-input-perihal"', false)
            ->assertSee('id="form-input-perihal"', false);

        // 2. Master Pengguna inputs have matching labels
        $this->actingAs($superAdmin)
            ->get('/master/pengguna')
            ->assertOk()
            ->assertSee('for="create-username"', false)
            ->assertSee('id="create-username"', false)
            ->assertSee('for="edit-name"', false)
            ->assertSee('id="edit-name"', false);

        // 3. Master Sekolah inputs have matching labels
        $this->actingAs($superAdmin)
            ->get('/master/sekolah')
            ->assertOk()
            ->assertSee('for="create-school-npsn"', false)
            ->assertSee('id="create-school-npsn"', false)
            ->assertSee('for="edit-school-name"', false)
            ->assertSee('id="edit-school-name"', false);
    }

    public function test_ux2_012_workspace_renders_collapsible_mobile_date_filter_panel(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->get('/persuratan')
            ->assertOk()
            ->assertSee('id="workspace-date-filter-panel"', false)
            ->assertSee('id="btn-toggle-mobile-filter"', false)
            ->assertSee('toggleMobileFilterPanel', false)
            ->assertSee('id="filter-date-from"', false)
            ->assertSee('id="filter-date-to"', false);
    }

    public function test_ux2_019_and_ux2_034_piagam_claim_form_renders_a11y_signature_canvas_and_resize_guard(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $this->get('/piagam/ambil')
            ->assertOk()
            ->assertSee('id="signature-pad"', false)
            ->assertSee('aria-label="Kanvas Tanda Tangan Digital"', false)
            ->assertSee('lastCanvasWidth', false)
            ->assertSee('name="work_unit_selection"', false);
    }

    public function test_ux2_013_and_ux2_020_dashboard_wording_and_login_support_banner(): void
    {
        // UX2-020: Unauthenticated login renders recovery support help text
        $this->get('/login')
            ->assertOk()
            ->assertSee('Lupa kata sandi atau kendala akun? Hubungi Administrator SIM Disdikbudpora Kab. Semarang');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        // UX2-013: Dashboard card shows 'Register arsip surat masuk' and avoids speculative disposisi
        $this->actingAs($operator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Register arsip surat masuk')
            ->assertDontSee('Arsip surat masuk & disposisi');
    }
}
