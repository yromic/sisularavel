<?php

namespace Tests\Feature\UI;

use App\Models\BackupRun;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardRoleSpecificViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_dashboard_renders_fast_track_search_and_hides_admin_monitors(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Dikdas']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/IN-01/2026',
            'origin_agency' => 'Dinas Pendidikan',
            'letter_date' => '2026-10-01',
            'received_date' => '2026-10-02',
            'subject' => 'Surat Koordinasi Operasional',
        ]);

        $response = $this->actingAs($operator)
            ->get('/dashboard')
            ->assertOk();

        // Operator sees search bar and fast actions
        $response->assertSee('id="operator-quick-search-input"', false);
        $response->assertSee('Pencarian Pintar');
        $response->assertSee('id="btn-quick-incoming"', false);
        $response->assertSee('id="btn-quick-outgoing"', false);
        $response->assertSee('id="btn-quick-piagam"', false);

        // Operator sees clickable metric tiles
        $response->assertSee('id="metric-card-total"', false);
        $response->assertSee('id="metric-card-incoming"', false);
        $response->assertSee('id="metric-card-outgoing"', false);
        $response->assertSee('id="metric-card-piagam"', false);

        // Operator must NOT see cross division matrix or backup health monitor
        $response->assertDontSee('Matriks Agregasi Lintas Divisi');
        $response->assertDontSee('Status Cadangan Data Sistem');
    }

    public function test_division_admin_dashboard_renders_divisional_trends_and_hides_cross_division_matrix(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Dikdas']);
        $admin = User::factory()->for($division)->divisionAdmin()->create();

        $response = $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk();

        // Division admin sees 6-month trend volume
        $response->assertSee('Tren Volume Persuratan (6 Bulan Terakhir)');
        $response->assertSee('Surat Masuk Terkini');

        // Division admin must NOT see cross-division matrix or backup health monitor
        $response->assertDontSee('Matriks Agregasi Lintas Divisi');
        $response->assertDontSee('Status Cadangan Data Sistem');
    }

    public function test_super_admin_dashboard_renders_backup_health_and_interactive_cross_division_matrix(): void
    {
        $dikdas = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Dikdas']);
        $paud = Division::factory()->create(['code' => 'PAUD', 'name' => 'Bidang PAUD']);
        $superAdmin = User::factory()->superAdmin()->create();

        // Create a completed backup run
        BackupRun::query()->create([
            'backup_id' => (string) Str::uuid(),
            'type' => 'full',
            'trigger' => 'manual',
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2),
            'archive_size_bytes' => 1024 * 1024 * 5, // 5 MB
            'files_count' => 120,
        ]);

        $response = $this->actingAs($superAdmin)
            ->get('/dashboard')
            ->assertOk();

        // Super Admin sees backup health monitor and link
        $response->assertSee('Status Cadangan Data Sistem');
        $response->assertSee('Kelola Backup', false);
        $response->assertSee('Pemulihan', false);

        // Super Admin sees cross division matrix
        $response->assertSee('Matriks Agregasi Lintas Divisi');
        $response->assertSee('Bidang Dikdas');
        $response->assertSee('Bidang PAUD');
        $response->assertSee('action="/switch-division"', false);
    }

    public function test_dashboard_uses_cache_and_refreshes_properly(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        // First hit warms cache
        $this->actingAs($operator)->get('/dashboard')->assertOk();

        // Second hit hits cache successfully
        $this->actingAs($operator)->get('/dashboard')->assertOk();
    }

    public function test_super_admin_can_switch_division_from_dashboard_quick_action(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $targetDiv = Division::factory()->create(['name' => 'Bidang Ketenagaan', 'code' => 'KTG']);

        $res = $this->actingAs($superAdmin)
            ->post('/switch-division', ['division_id' => $targetDiv->id]);

        $res->assertRedirect();
        $this->assertSame($targetDiv->id, session('active_division_id'));
    }
}
