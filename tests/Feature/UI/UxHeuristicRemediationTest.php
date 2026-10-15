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
}
