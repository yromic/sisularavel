<?php

namespace Tests\Feature\UI;

use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FullClientUiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_operator_can_access_dashboard_with_metrics_and_division_scope(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Pembinaan SD']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/001/2026',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat Koordinasi Pendidikan Dasar',
        ]);

        OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '421/100/2026',
            'letter_date' => '2026-09-05',
            'disseminated_date' => '2026-09-05',
            'subject' => 'Pemberitahuan Pelaksanaan Ujian',
        ]);

        $response = $this->actingAs($operator)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Dashboard Pengarsipan Surat')
            ->assertSee('DIKDAS')
            ->assertSee('Surat Masuk')
            ->assertSee('Surat Keluar')
            ->assertSee('Piagam & Sertifikat', false)
            ->assertSee('420/001/2026')
            ->assertSee('421/100/2026');
    }

    public function test_operator_can_access_surat_keluar_index_and_create_pages(): void
    {
        $division = Division::factory()->create(['code' => 'PAUD']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => 'SK-PAUD-001',
            'letter_date' => '2026-09-10',
            'disseminated_date' => '2026-09-10',
            'subject' => 'Edaran PAUD Inklusi',
        ]);

        $this->actingAs($operator)
            ->get('/surat-keluar')
            ->assertOk()
            ->assertSee('data-testid="mobile-outgoing-card"', false)
            ->assertSee('data-testid="desktop-outgoing-grid"', false)
            ->assertSee('SK-PAUD-001')
            ->assertSee('Aksi');

        $this->actingAs($operator)
            ->get('/surat-keluar/create')
            ->assertOk()
            ->assertSee('data-testid="outgoing-letter-form"', false)
            ->assertSee('name="nomor_surat"', false)
            ->assertSee('name="tanggal_surat"', false)
            ->assertSee('name="tanggal_share"', false)
            ->assertSee('data-idempotent-form', false);
    }

    public function test_operator_can_access_piagam_index_and_create_pages(): void
    {
        $division = Division::factory()->create();
        $school = School::factory()->create(['npsn' => '20320340', 'name' => 'SMP Negeri 1 Ungaran']);
        $cat = CompetitionCategory::query()->create([
            'division_id' => $division->id,
            'name' => 'FLS2N',
            'is_active' => true,
        ]);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'school_id' => $school->id,
            'competition_category_id' => $cat->id,
            'student_name' => 'Ahmad Santoso',
            'competition_year' => 2026,
        ]);

        $this->actingAs($operator)
            ->get('/piagam')
            ->assertOk()
            ->assertSee('data-testid="mobile-piagam-card"', false)
            ->assertSee('data-testid="desktop-piagam-grid"', false)
            ->assertSee('Ahmad Santoso')
            ->assertSee('SMP Negeri 1 Ungaran')
            ->assertSee('Tersedia');

        $this->actingAs($operator)
            ->get('/piagam/create')
            ->assertOk()
            ->assertSee('data-testid="piagam-create-form"', false)
            ->assertSee('name="student_name"', false)
            ->assertSee('20320340')
            ->assertSee('FLS2N');
    }

    public function test_admin_can_access_master_data_views(): void
    {
        $division = Division::factory()->create(['name' => 'Bidang Kebudayaan']);
        $admin = User::factory()->for($division)->divisionAdmin()->create(['username' => 'admin_budaya']);
        School::factory()->create(['name' => 'SD Negeri Bandungan 01', 'npsn' => '20320678']);
        CompetitionCategory::query()->create([
            'division_id' => $division->id,
            'name' => 'Festival Tari',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/master/sekolah')
            ->assertOk()
            ->assertSee('SD Negeri Bandungan 01')
            ->assertSee('20320678');

        $this->actingAs($admin)
            ->get('/master/pengguna')
            ->assertOk()
            ->assertSee('admin_budaya')
            ->assertSee('Tambah Pengguna');

        $this->actingAs($admin)
            ->get('/master/kategori')
            ->assertOk()
            ->assertSee('Festival Tari');
    }

    public function test_operator_can_access_unified_reports_search(): void
    {
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create();

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/CARI/01',
            'origin_agency' => 'Kementerian',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Sosialisasi Kurikulum',
        ]);

        $this->actingAs($operator)
            ->get('/laporan?q=CARI')
            ->assertOk()
            ->assertSee('Pencarian Terpadu & Ekspor Laporan', false)
            ->assertSee('420/CARI/01')
            ->assertSee('Unduh Rekap CSV');
    }

    public function test_user_with_must_reset_password_can_access_activation_page(): void
    {
        $user = User::factory()->create([
            'username' => 'migrated_user',
            'must_reset_password' => true,
        ]);

        $this->actingAs($user)
            ->get('/password/activate')
            ->assertOk()
            ->assertSee('Perbarui Kata Sandi')
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_super_admin_can_access_master_divisi_view(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Division::factory()->create(['code' => 'DIK-TEST', 'name' => 'Divisi Uji Coba']);

        $this->actingAs($superAdmin)
            ->get('/master/divisi')
            ->assertOk()
            ->assertSee('Manajemen Divisi Kerja')
            ->assertSee('DIK-TEST')
            ->assertSee('Divisi Uji Coba')
            ->assertSee('Tambah Divisi');
    }

    public function test_public_guest_can_access_welcome_landing_page_and_query_piagams(): void
    {
        $school = School::factory()->create(['npsn' => '20320999', 'name' => 'SMP Negeri 2 Ungaran']);
        $division = Division::factory()->create();
        $cat = CompetitionCategory::create([
            'division_id' => $division->id,
            'name' => 'Olimpiade Matematika',
            'is_active' => true,
        ]);
        Piagam::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'school_id' => $school->id,
            'competition_category_id' => $cat->id,
            'student_name' => 'Siti Nurhaliza',
            'competition_year' => 2026,
        ]);

        // Landing page view (Phase B: landing page shows CTA button to /piagam/ambil)
        $this->get('/')
            ->assertOk()
            ->assertSee('Ambil Piagam')
            ->assertSee('/piagam/ambil');

        // Handover receipt page view
        $this->get('/piagam/ambil')
            ->assertOk()
            ->assertSee('data-testid="piagam-claim-form"', false)
            ->assertSee('data-testid="signature-pad"', false);

        // Public API endpoint for guest piagam search
        $this->getJson('/api/v1/public/piagams?npsn=20320999')
            ->assertOk()
            ->assertJsonPath('school.npsn', '20320999')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student_name', 'Siti Nurhaliza');
    }

    public function test_sidebar_navigation_contains_grouped_persuratan_and_navbar_has_logout(): void
    {
        $operator = User::factory()->for(Division::factory())->create();

        $this->actingAs($operator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Layanan Persuratan & Piagam', false)
            ->assertSee('Persuratan & Piagam', false)
            ->assertSee('Ganti Password')
            ->assertSee('Logout');
    }

    public function test_operator_can_access_surat_masuk_detail_and_edit_pages_and_rejects_cross_division(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $operator = User::factory()->for($dikdas)->create();
        $otherOperator = User::factory()->for($paud)->create();

        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $dikdas->id,
            'letter_number' => '420/DIKDAS/01',
            'origin_agency' => 'Bupati',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat masuk dikdas detail test',
        ]);

        // Operator can view detail but cannot see edit button or access edit page
        $this->actingAs($operator)
            ->get("/surat-masuk/{$letter->id}")
            ->assertOk()
            ->assertSee('420/DIKDAS/01')
            ->assertSee('Surat masuk dikdas detail test')
            ->assertSee('Informasi Surat Masuk')
            ->assertDontSee('Edit Surat');

        $this->actingAs($operator)
            ->get("/surat-masuk/{$letter->id}/edit")
            ->assertForbidden();

        // Division admin can view detail with edit button and access edit page
        $admin = User::factory()->for($dikdas)->divisionAdmin()->create();
        $this->actingAs($admin)
            ->get("/surat-masuk/{$letter->id}")
            ->assertOk()
            ->assertSee('Edit Surat');

        $this->actingAs($admin)
            ->get("/surat-masuk/{$letter->id}/edit")
            ->assertOk()
            ->assertSee('Perbarui Surat Masuk')
            ->assertSee('420/DIKDAS/01');

        // Other division is rejected with 403 Forbidden
        $this->actingAs($otherOperator)
            ->get("/surat-masuk/{$letter->id}")
            ->assertForbidden();

        $this->actingAs($otherOperator)
            ->get("/surat-masuk/{$letter->id}/edit")
            ->assertForbidden();
    }

    public function test_operator_can_access_surat_keluar_detail_and_edit_pages_and_rejects_cross_division(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $operator = User::factory()->for($dikdas)->create();
        $otherOperator = User::factory()->for($paud)->create();

        $letter = OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $dikdas->id,
            'letter_year' => 2026,
            'letter_number' => 'SK/DIKDAS/01',
            'letter_date' => '2026-09-03',
            'disseminated_date' => '2026-09-04',
            'subject' => 'Surat keluar dikdas detail test',
        ]);

        // Operator can view detail but cannot see edit button or access edit page
        $this->actingAs($operator)
            ->get("/surat-keluar/{$letter->id}")
            ->assertOk()
            ->assertSee('SK/DIKDAS/01')
            ->assertSee('Surat keluar dikdas detail test')
            ->assertSee('Informasi Surat Keluar')
            ->assertDontSee('Edit Surat');

        $this->actingAs($operator)
            ->get("/surat-keluar/{$letter->id}/edit")
            ->assertForbidden();

        // Division admin can view detail with edit button and access edit page
        $admin = User::factory()->for($dikdas)->divisionAdmin()->create();
        $this->actingAs($admin)
            ->get("/surat-keluar/{$letter->id}")
            ->assertOk()
            ->assertSee('Edit Surat');

        $this->actingAs($admin)
            ->get("/surat-keluar/{$letter->id}/edit")
            ->assertOk()
            ->assertSee('Perbarui Surat Keluar')
            ->assertSee('SK/DIKDAS/01');

        // Other division is rejected with 403 Forbidden
        $this->actingAs($otherOperator)
            ->get("/surat-keluar/{$letter->id}")
            ->assertForbidden();

        $this->actingAs($otherOperator)
            ->get("/surat-keluar/{$letter->id}/edit")
            ->assertForbidden();
    }

    public function test_operator_can_access_piagam_detail_page_and_rejects_cross_division(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $school = School::factory()->create(['name' => 'SD N 1']);
        $cat = CompetitionCategory::create(['division_id' => $dikdas->id, 'name' => 'Lomba Sains']);
        $operator = User::factory()->for($dikdas)->create();
        $otherOperator = User::factory()->for($paud)->create();

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $dikdas->id,
            'student_name' => 'Bambang Sudirman',
            'school_id' => $school->id,
            'competition_category_id' => $cat->id,
            'competition_year' => 2026,
        ]);

        // Own division can view detail
        $this->actingAs($operator)
            ->get("/piagam/{$piagam->id}")
            ->assertOk()
            ->assertSee('Bambang Sudirman')
            ->assertSee('Lomba Sains')
            ->assertSee('Bukti Tanda Tangan');

        // Other division is rejected with 403 Forbidden
        $this->actingAs($otherOperator)
            ->get("/piagam/{$piagam->id}")
            ->assertForbidden();
    }

    public function test_deleting_incoming_letter_from_web_redirects_to_index_with_success_and_never_404(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();
        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/DEL/01',
            'origin_agency' => 'Instansi Tes',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat yang akan dihapus',
        ]);

        // Simulating form submission from show page: referer is /surat-masuk/{id}
        $response = $this->actingAs($admin)
            ->from("/surat-masuk/{$letter->id}")
            ->delete("/api/v1/surat-masuk/{$letter->id}");

        $response->assertRedirect('/surat-masuk');
        $response->assertSessionHas('success', 'Surat masuk berhasil dihapus.');

        // Also test direct web route DELETE /surat-masuk/{id}
        $letter2 = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/DEL/02',
            'origin_agency' => 'Instansi Tes 2',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat yang akan dihapus 2',
        ]);

        $response2 = $this->actingAs($admin)
            ->delete("/surat-masuk/{$letter2->id}");

        $response2->assertRedirect('/surat-masuk');
        $response2->assertSessionHas('success', 'Surat masuk berhasil dihapus.');
    }

    public function test_deleting_outgoing_letter_from_web_redirects_to_index_with_success_and_never_404(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();
        $letter = OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '420/OUT-DEL/01',
            'letter_date' => '2026-09-01',
            'disseminated_date' => '2026-09-02',
            'subject' => 'Surat keluar yang akan dihapus',
        ]);

        $response = $this->actingAs($admin)
            ->from("/surat-keluar/{$letter->id}")
            ->delete("/api/v1/surat-keluar/{$letter->id}");

        $response->assertRedirect('/surat-keluar');
        $response->assertSessionHas('success', 'Surat keluar berhasil dihapus.');

        // Also test direct web route DELETE /surat-keluar/{id}
        $letter2 = OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '420/OUT-DEL/02',
            'letter_date' => '2026-09-01',
            'disseminated_date' => '2026-09-02',
            'subject' => 'Surat keluar yang akan dihapus 2',
        ]);

        $response2 = $this->actingAs($admin)
            ->delete("/surat-keluar/{$letter2->id}");

        $response2->assertRedirect('/surat-keluar');
        $response2->assertSessionHas('success', 'Surat keluar berhasil dihapus.');
    }

    public function test_web_form_validation_failure_redirects_back_with_errors_instead_of_raw_json(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // 1. Web browser submit to /api/v1/users with invalid password (< 8 chars)
        $response = $this->actingAs($superAdmin)
            ->from('/master/pengguna')
            ->withHeaders(['Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'])
            ->post('/api/v1/users', [
                'username' => 'testuser',
                'name' => 'Test User',
                'email' => 'testuser@example.test',
                'role' => 'operator',
                'password' => '123', // Invalid: min 8 chars
            ]);

        // Must redirect back with validation errors, NOT return raw JSON
        $response->assertRedirect('/master/pengguna');
        $response->assertSessionHasErrors(['password']);
        $this->assertSame('testuser', session('_old_input.username'));

        // 2. Following the redirect displays the error message and old input on the page
        $this->actingAs($superAdmin)
            ->get('/master/pengguna')
            ->assertOk()
            ->assertSee('Gagal Menyimpan Data Pengguna:');
    }

    public function test_api_client_validation_failure_still_returns_json_error_envelope(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // API client requesting with application/json receives official contract JSON envelope
        $this->actingAs($superAdmin)
            ->postJson('/api/v1/users', [
                'username' => 'testuser',
                'name' => 'Test User',
                'role' => 'operator',
                'password' => '123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ERR_422_VALIDATION')
            ->assertJsonStructure(['success', 'code', 'message', 'errors', 'timestamp']);
    }
}
