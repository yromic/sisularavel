<?php

namespace Tests\Feature\UI;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RuntimeRemediationRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BUG-001: Unauthenticated guests are redirected to /login with HTTP 302, never HTTP 500.
     */
    public function test_bug001_unauthenticated_protected_routes_redirect_to_login_never_500(): void
    {
        $protectedUrls = [
            '/dashboard',
            '/surat-masuk',
            '/surat-keluar',
            '/piagam',
            '/laporan',
            '/master/pengguna',
            '/master/sekolah',
            '/master/divisi',
            '/master/kategori',
        ];

        foreach ($protectedUrls as $url) {
            $response = $this->get($url);
            $response->assertStatus(302);
            $response->assertRedirect('/login');
        }
    }

    /**
     * BUG-002: Login failure returns human readable validation error and retains username.
     */
    public function test_bug002_browser_login_failure_returns_validation_error_and_preserves_username(): void
    {
        $division = Division::factory()->create();
        User::factory()->for($division)->create([
            'username' => 'petugas_arsip',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'petugas_arsip',
            'password' => 'passwordsalah',
        ]);

        $response->assertSessionHasErrors(['username']);
        $response->assertRedirect();
        $this->assertEquals('petugas_arsip', session('_old_input.username'));
        $this->assertNull(session('_old_input.password'));
    }

    /**
     * BUG-003: Login rate limit returns browser redirect back with error notice, never raw JSON.
     */
    public function test_bug003_login_rate_limiting_returns_browser_redirect_with_errors(): void
    {
        $rateLimitKey = 'test_locked|127.0.0.1';
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit($rateLimitKey, 900);
        }

        $response = $this->from('/login')->post('/login', [
            'username' => 'test_locked',
            'password' => 'sembarang',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['username']);
        $errors = session('errors')->get('username');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errors[0]);

        RateLimiter::clear($rateLimitKey);
    }

    /**
     * BUG-004: /piagam/ambil renders full public claim experience with all required elements.
     */
    public function test_bug004_public_piagam_claim_portal_renders_full_interactive_workflow(): void
    {
        School::factory()->create(['name' => 'SMP Negeri 1 Ungaran', 'npsn' => '20320340', 'is_active' => true]);

        $response = $this->get('/piagam/ambil');

        $response->assertOk();
        $response->assertSee('data-testid="piagam-claim-form"', false);
        $response->assertSee('data-testid="signature-pad"', false);
        $response->assertSee('name="npsn"', false);
        $response->assertSee('inputmode="numeric"', false);
        $response->assertSee('name="recipient_name"', false);
        $response->assertSee('name="recipient_position"', false);
        $response->assertSee('name="recipient_unit"', false);
        $response->assertSee('name="piagam_id"', false);
        $response->assertSee('name="signature_canvas"', false);
        $response->assertSee('Bersihkan Tanda Tangan');
        $response->assertSee('Konfirmasi & Ambil Piagam', false);
    }

    /**
     * BUG-005: Attachment preview returns HTML 404 for missing file and HTML 403 for cross-division on browser navigation.
     */
    public function test_bug005_attachment_preview_browser_navigation_returns_html_errors(): void
    {
        Storage::fake('private_documents');
        $dikdas = Division::factory()->create(['code' => 'DIKDAS']);
        $paud = Division::factory()->create(['code' => 'PAUD']);
        $dikdasAdmin = User::factory()->for($dikdas)->divisionAdmin()->create();
        $paudAdmin = User::factory()->for($paud)->divisionAdmin()->create();

        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $dikdas->id,
            'letter_number' => '420/DIK/BUG5',
            'origin_agency' => 'Dinas',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Tes lampiran',
        ]);

        $missingAttachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $dikdas->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => 'documents/DIKDAS/2026/09/missing.pdf',
            'original_name' => 'missing.pdf',
            'file_size_bytes' => 10,
            'mime_type' => 'application/pdf',
            'file_hash' => 'dummy',
            'is_missing' => true,
            'created_by_user_id' => $dikdasAdmin->id,
        ]);

        // Browser navigation (with Upgrade-Insecure-Requests and text/html Accept) for missing file returns HTML 404
        $browserHeaders = [
            'Upgrade-Insecure-Requests' => '1',
            'Accept' => 'text/html,application/xhtml+xml',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ];

        $responseMissing = $this->actingAs($dikdasAdmin)
            ->withHeaders($browserHeaders)
            ->get("/api/v1/attachments/{$missingAttachment->uuid}/preview");
        $responseMissing->assertStatus(404);
        $responseMissing->assertSee('Data Tidak Ditemukan');

        // Browser navigation across divisions returns HTML 403
        $responseForbidden = $this->actingAs($paudAdmin)
            ->withHeaders($browserHeaders)
            ->get("/api/v1/attachments/{$missingAttachment->uuid}/preview");
        $responseForbidden->assertStatus(403);
        $responseForbidden->assertSee('Akses Ditolak');
    }

    /**
     * BUG-006: Outgoing letter edit duplicate number returns browser redirect back with validation errors, never raw JSON 409.
     */
    public function test_bug006_outgoing_letter_duplicate_number_edit_returns_browser_errors(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $letter1 = OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => 'SK/NOMOR/SAMA',
            'letter_date' => '2026-09-01',
            'disseminated_date' => '2026-09-02',
            'subject' => 'Surat Pertama',
        ]);

        $letter2 = OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => 'SK/NOMOR/LAIN',
            'letter_date' => '2026-09-01',
            'disseminated_date' => '2026-09-02',
            'subject' => 'Surat Kedua',
        ]);

        // Submit edit via browser PUT form
        $response = $this->actingAs($admin)
            ->from("/surat-keluar/{$letter2->id}/edit")
            ->put("/api/v1/surat-keluar/{$letter2->id}", [
                'nomor_surat' => 'SK/NOMOR/SAMA',
                'tanggal_surat' => '2026-09-01',
                'tanggal_share' => '2026-09-02',
                'perihal' => 'Ganti nomor menjadi duplikat',
            ]);

        $response->assertRedirect("/surat-keluar/{$letter2->id}/edit");
        $response->assertSessionHasErrors(['nomor_surat']);
    }

    /**
     * BUG-007: Deactivated account mid-session redirects browser to /login with flash error.
     */
    public function test_bug007_deactivated_account_mid_session_redirects_browser_to_login(): void
    {
        $division = Division::factory()->create();
        $user = User::factory()->for($division)->create(['is_active' => true]);

        // Deactivate user in database
        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
    }

    /**
     * BUG-008: Operator has no edit button in UI and is rejected 403 on edit URL.
     */
    public function test_bug008_operator_denied_edit_access_and_ui_hides_edit_button(): void
    {
        $division = Division::factory()->create();
        $operator = User::factory()->for($division)->create();
        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/RBAC/01',
            'origin_agency' => 'Disdik',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat Uji RBAC',
        ]);

        // Detail page does not show Edit Surat button to operator
        $this->actingAs($operator)
            ->get("/surat-masuk/{$letter->id}")
            ->assertOk()
            ->assertDontSee('Edit Surat');

        // Direct GET /edit aborts with 403 Forbidden
        $this->actingAs($operator)
            ->get("/surat-masuk/{$letter->id}/edit")
            ->assertForbidden();
    }

    /**
     * BUG-009: Master users UI includes Edit modal and Edit action buttons.
     */
    public function test_bug009_master_users_ui_renders_edit_button_and_modal(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get('/master/pengguna');

        $response->assertOk();
        $response->assertSee('modal-edit-user', false);
        $response->assertSee('openEditUserModal', false);
        $response->assertSee('title="Edit Pengguna"', false);
    }

    /**
     * BUG-010: Master schools UI includes Edit modal and Edit action buttons.
     */
    public function test_bug010_master_schools_ui_renders_edit_button_and_modal(): void
    {
        $admin = User::factory()->superAdmin()->create();
        School::factory()->create(['name' => 'SMP Negeri 1 Bawen', 'npsn' => '20320399']);

        $response = $this->actingAs($admin)->get('/master/sekolah');

        $response->assertOk();
        $response->assertSee('modal-edit-sekolah', false);
        $response->assertSee('openEditSchoolModal', false);
        $response->assertSee('title="Edit Sekolah"', false);
    }

    /**
     * BUG-011: Missing attachment detail page renders remediation form for division admin.
     */
    public function test_bug011_missing_attachment_renders_remediation_upload_form_for_admin(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/REMEDIATE/01',
            'origin_agency' => 'Bupati',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Lampiran Perlu Remediasi',
        ]);

        Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => null,
            'original_name' => 'surat_hilang.pdf',
            'file_size_bytes' => 0,
            'mime_type' => 'application/pdf',
            'file_hash' => null,
            'is_missing' => true,
            'created_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get("/surat-masuk/{$letter->id}");

        $response->assertOk();
        $response->assertSee('Remediasi Berkas Lampiran');
        $response->assertSee("action=\"/api/v1/surat-masuk/{$letter->id}/remediate-attachment\"", false);
        $response->assertSee('Unggah Dokumen Pengganti');
    }

    /**
     * BUG-012: Storage path backward compatibility resolves whether prefix is stripped or intact.
     */
    public function test_bug012_storage_path_compatibility_resolves_without_double_nesting(): void
    {
        Storage::fake('private_documents');
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/DIK/BUG12',
            'origin_agency' => 'Dinas',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Tes BUG-012 path',
        ]);

        // File physically placed without repeating documents prefix on the disk
        $actualPhysicalPath = 'DIKDAS/2026/09/sample.pdf';
        Storage::disk('private_documents')->put($actualPhysicalPath, 'sample-binary-content');

        // Attachment record has the prefixed path 'documents/DIKDAS/2026/09/sample.pdf'
        $attachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => 'documents/'.$actualPhysicalPath,
            'original_name' => 'sample.pdf',
            'file_size_bytes' => 21,
            'mime_type' => 'application/pdf',
            'file_hash' => hash('sha256', 'sample-binary-content'),
            'is_missing' => false,
            'created_by_user_id' => $admin->id,
        ]);

        // Preview should resolve the file using compatibility stripping
        $response = $this->actingAs($admin)->get("/api/v1/attachments/{$attachment->uuid}/preview");

        $response->assertOk();
        $this->assertEquals('sample-binary-content', $response->getContent());
    }
}
