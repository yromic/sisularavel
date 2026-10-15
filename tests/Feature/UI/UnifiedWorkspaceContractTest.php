<?php

namespace Tests\Feature\UI;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnifiedWorkspaceContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_operator_can_access_unified_workspace_and_sees_all_three_tabs_and_modals(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Pendidikan Dasar']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '001/IN/DIKDAS/2026',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat Koordinasi Kurikulum',
        ]);

        OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '002/OUT/DIKDAS/2026',
            'letter_date' => '2026-09-10',
            'disseminated_date' => '2026-09-10',
            'subject' => 'Surat Edaran Kurikulum Mandiri',
        ]);

        Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Ahmad Dahlan',
            'piagam_description' => 'Juara 1 OSN Matematika',
            'school_origin' => 'SMP Negeri 1 Ungaran',
            'recipient_name' => 'Budi Santoso',
            'recipient_position' => 'Guru Pendamping',
            'recipient_unit' => 'SMP Negeri 1 Ungaran',
            'pickup_category_name' => 'Diambil Langsung',
            'created_by_user_id' => $operator->id,
        ]);

        $this->actingAs($operator)
            ->get('/persuratan')
            ->assertOk()
            ->assertSee('Pusat Persuratan &amp; Buku Ekspedisi', false)
            ->assertSee('id="tab-btn-masuk"', false)
            ->assertSee('id="tab-btn-keluar"', false)
            ->assertSee('id="tab-btn-piagam"', false)
            ->assertSee('id="panel-masuk"', false)
            ->assertSee('id="panel-keluar"', false)
            ->assertSee('id="panel-piagam"', false)
            ->assertSee('001/IN/DIKDAS/2026')
            ->assertSee('002/OUT/DIKDAS/2026')
            ->assertSee('Juara 1 OSN Matematika')
            ->assertSee('id="split-letter-detail-modal"', false)
            ->assertSee('id="letter-form-modal"', false)
            ->assertSee('id="letter-delete-modal"', false)
            ->assertSee('id="split-piagam-detail-modal"', false)
            ->assertSee('switchWorkspaceTab', false);
    }

    public function test_switching_tab_via_query_parameter_renders_correct_active_tab(): void
    {
        $division = Division::factory()->create(['code' => 'PAUD']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        // Tab Keluar
        $responseKeluar = $this->actingAs($operator)
            ->get('/persuratan?tab=keluar')
            ->assertOk();

        $responseKeluar->assertSee('id="panel-keluar" class=""', false);
        $responseKeluar->assertSee('id="panel-masuk" class="hidden"', false);

        // Tab Piagam
        $responsePiagam = $this->actingAs($operator)
            ->get('/persuratan?tab=piagam')
            ->assertOk();

        $responsePiagam->assertSee('id="panel-piagam" class=""', false);
        $responsePiagam->assertSee('id="panel-masuk" class="hidden"', false);
    }

    public function test_universal_filter_filters_records_per_division_scope(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/MATCH/2026',
            'origin_agency' => 'Kementerian Sosial',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Bantuan Khusus Beasiswa',
        ]);

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/OTHER/2026',
            'origin_agency' => 'Dinas Kesehatan',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Vaksinasi Massal Pelajar',
        ]);

        $this->actingAs($operator)
            ->get('/persuratan?q=Beasiswa')
            ->assertOk()
            ->assertSee('420/MATCH/2026')
            ->assertDontSee('420/OTHER/2026');
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get('/persuratan')
            ->assertRedirect('/login');
    }

    public function test_division_scoping_hides_other_division_records_in_unified_workspace(): void
    {
        $divisionA = Division::factory()->create(['code' => 'DIKDAS']);
        $divisionB = Division::factory()->create(['code' => 'PAUD']);

        $operatorA = User::factory()->for($divisionA)->create(['role' => 'operator']);

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $divisionB->id,
            'letter_number' => '999/SECRET/PAUD/2026',
            'origin_agency' => 'TK Pembina',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat Rahasia Divisi B',
        ]);

        $this->actingAs($operatorA)
            ->get('/persuratan')
            ->assertOk()
            ->assertDontSee('999/SECRET/PAUD/2026')
            ->assertDontSee('Surat Rahasia Divisi B');
    }
}
