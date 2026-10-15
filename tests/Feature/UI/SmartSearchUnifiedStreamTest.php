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

class SmartSearchUnifiedStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_search_menu_link_is_rendered_right_after_dashboard_in_sidebar(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $response = $this->actingAs($operator)
            ->get('/dashboard')
            ->assertOk();

        $content = $response->getContent();

        // Dashboard appears first
        $dashboardPos = strpos($content, 'href="/dashboard"');
        // Pencarian Pintar appears right after dashboard
        $searchPos = strpos($content, 'href="/laporan"');

        $this->assertNotFalse($dashboardPos);
        $this->assertNotFalse($searchPos);
        $this->assertLessThan($searchPos, $dashboardPos, 'Pencarian Pintar should appear right below Dashboard in navigation.');
        $response->assertSee('Pencarian Pintar', false);
    }

    public function test_search_page_renders_unified_stream_with_mixed_documents_and_pure_svg_view_switchers(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $in = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '420/FEST-IN/2026',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Festival Inovasi Pendidikan',
        ]);

        $out = OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => '420/FEST-OUT/2026',
            'letter_date' => '2026-09-10',
            'disseminated_date' => '2026-09-10',
            'subject' => 'Surat Edaran Festival Pelajar',
        ]);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Adinda Saraswati',
            'piagam_description' => 'Juara Festival Seni Tari',
            'school_origin' => 'SMP Negeri 1 Ungaran',
            'recipient_name' => 'Budi Guru',
            'recipient_position' => 'Pendamping',
            'created_by_user_id' => $operator->id,
        ]);

        $response = $this->actingAs($operator)
            ->get('/laporan?q=Festival')
            ->assertOk();

        // Verifies all 3 types exist in the unified feed
        $response->assertSee('420/FEST-IN/2026')
            ->assertSee('Surat Masuk', false)
            ->assertSee('420/FEST-OUT/2026')
            ->assertSee('Surat Keluar', false)
            ->assertSee('Juara Festival Seni Tari')
            ->assertSee('Piagam Penghargaan', false);

        // Verifies Grid and List view containers exist
        $response->assertSee('id="search-grid-view"', false)
            ->assertSee('id="search-list-view"', false)
            ->assertSee('id="btn-view-grid"', false)
            ->assertSee('id="btn-view-list"', false);

        // Verifies shared split modals are included for in-place detail
        $response->assertSee('id="split-letter-detail-modal"', false)
            ->assertSee('id="split-piagam-detail-modal"', false)
            ->assertSee('handleSearchDetailClick', false)
            ->assertSee('setSearchViewMode', false);

        // Asserts zero OS / Windows symbols used in switcher buttons
        $response->assertDontSee('⊞', false);
        $response->assertDontSee('☰', false);
    }

    public function test_search_division_scoping_prevents_leakage_of_other_divisions_records(): void
    {
        $divisionA = Division::factory()->create(['code' => 'DIKDAS']);
        $divisionB = Division::factory()->create(['code' => 'PAUD']);

        $operatorA = User::factory()->for($divisionA)->create(['role' => 'operator']);

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $divisionB->id,
            'letter_number' => '420/PAUD-SECRET/2026',
            'origin_agency' => 'TK Pembina',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat Rahasia Divisi Lain',
        ]);

        $this->actingAs($operatorA)
            ->get('/laporan?q=Rahasia')
            ->assertOk()
            ->assertDontSee('420/PAUD-SECRET/2026')
            ->assertDontSee('Surat Rahasia Divisi Lain');
    }
}
