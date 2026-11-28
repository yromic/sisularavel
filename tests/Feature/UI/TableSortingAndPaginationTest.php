<?php

namespace Tests\Feature\UI;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TableSortingAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_supports_sorting_and_per_page(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->create(['role' => 'super_admin', 'division_id' => $division->id]);

        IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => 'SURAT-AAA',
            'origin_agency' => 'Instansi A',
            'subject' => 'Perihal A',
            'letter_date' => '2026-01-01',
            'received_date' => '2026-01-02',
            'created_by_user_id' => $admin->id,
        ]);

        IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => 'SURAT-ZZZ',
            'origin_agency' => 'Instansi Z',
            'subject' => 'Perihal Z',
            'letter_date' => '2026-02-01',
            'received_date' => '2026-02-02',
            'created_by_user_id' => $admin->id,
        ]);

        // 1. Sort by letter_date ascending
        $responseAsc = $this->actingAs($admin)
            ->get('/persuratan?tab=masuk&sort=letter_date&dir=asc');

        $responseAsc->assertOk();
        $lettersAsc = $responseAsc->viewData('incomingLetters');
        $this->assertEquals('SURAT-AAA', $lettersAsc->first()->letter_number);

        // 2. Sort by letter_date descending
        $responseDesc = $this->actingAs($admin)
            ->get('/persuratan?tab=masuk&sort=letter_date&dir=desc');

        $responseDesc->assertOk();
        $lettersDesc = $responseDesc->viewData('incomingLetters');
        $this->assertEquals('SURAT-ZZZ', $lettersDesc->first()->letter_number);

        // 3. Custom per_page within whitelist
        $responsePerPage = $this->actingAs($admin)
            ->get('/persuratan?tab=masuk&per_page=50');

        $responsePerPage->assertOk();
        $this->assertEquals(50, $responsePerPage->viewData('perPage'));

        // 4. Invalid per_page falls back to default 15
        $responseInvalidPerPage = $this->actingAs($admin)
            ->get('/persuratan?tab=masuk&per_page=9999');

        $responseInvalidPerPage->assertOk();
        $this->assertEquals(15, $responseInvalidPerPage->viewData('perPage'));

        // 5. Malicious sort parameter falls back safely
        $responseMalicious = $this->actingAs($admin)
            ->get('/persuratan?tab=masuk&sort=unknown_column;drop+table&dir=asc');

        $responseMalicious->assertOk();
    }

    public function test_incoming_letters_index_supports_sorting_and_per_page(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->create(['role' => 'division_admin', 'division_id' => $division->id]);

        IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => 'NO-01',
            'origin_agency' => 'Instansi 1',
            'subject' => 'Perihal 1',
            'letter_date' => '2026-01-10',
            'received_date' => '2026-01-12',
            'created_by_user_id' => $admin->id,
        ]);

        IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => 'NO-02',
            'origin_agency' => 'Instansi 2',
            'subject' => 'Perihal 2',
            'letter_date' => '2026-02-10',
            'received_date' => '2026-02-12',
            'created_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get('/surat-masuk?sort=letter_date&dir=asc&per_page=30');

        $response->assertOk();
        $letters = $response->viewData('letters');
        $this->assertEquals(30, $letters->perPage());
        $this->assertEquals('NO-01', $letters->first()->letter_number);
    }

    public function test_workspace_sort_links_preserve_active_tab_for_each_panel(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->create(['role' => 'super_admin', 'division_id' => $division->id]);

        // 1. When tab=keluar is active, sort links for Surat Keluar must retain tab=keluar
        $responseKeluar = $this->actingAs($admin)->get('/persuratan?tab=keluar&sort=id&dir=desc');
        $responseKeluar->assertOk();
        $htmlKeluar = $responseKeluar->getContent();

        // Sort link on panel-keluar for letter_date must contain both sort=letter_date and tab=keluar
        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=letter_date)(?=.*tab=keluar)[^"]*"/', $htmlKeluar);
        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=disseminated_date)(?=.*tab=keluar)[^"]*"/', $htmlKeluar);

        // 2. When tab=masuk is active, sort links for Surat Masuk must retain tab=masuk
        $responseMasuk = $this->actingAs($admin)->get('/persuratan?tab=masuk&sort=id&dir=desc');
        $responseMasuk->assertOk();
        $htmlMasuk = $responseMasuk->getContent();

        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=letter_date)(?=.*tab=masuk)[^"]*"/', $htmlMasuk);
        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=origin_agency)(?=.*tab=masuk)[^"]*"/', $htmlMasuk);

        // 3. When tab=piagam is active, sort links for Piagam must retain tab=piagam
        $responsePiagam = $this->actingAs($admin)->get('/persuratan?tab=piagam&sort=id&dir=desc');
        $responsePiagam->assertOk();
        $htmlPiagam = $responsePiagam->getContent();

        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=created_at)(?=.*tab=piagam)[^"]*"/', $htmlPiagam);
        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=recipient_name)(?=.*tab=piagam)[^"]*"/', $htmlPiagam);

        // 4. Default /persuratan load (no tab param) must render appropriate panel tabs on headers
        $responseDefault = $this->actingAs($admin)->get('/persuratan');
        $responseDefault->assertOk();
        $htmlDefault = $responseDefault->getContent();

        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=disseminated_date)(?=.*tab=keluar)[^"]*"/', $htmlDefault);
        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=received_date)(?=.*tab=masuk)[^"]*"/', $htmlDefault);
    }

    public function test_workspace_sort_links_preserve_search_filter_and_reset_pagination(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->create(['role' => 'super_admin', 'division_id' => $division->id]);

        $response = $this->actingAs($admin)
            ->get('/persuratan?tab=keluar&q=disposisi&date_from=2026-01-01&keluar_page=3&masuk_page=2');

        $response->assertOk();
        $html = $response->getContent();

        // Must retain tab=keluar, q=disposisi, and date_from=2026-01-01, but omit/reset page query params
        $this->assertMatchesRegularExpression('/href="[^"]*tab=keluar[^"]*q=disposisi[^"]*"/', $html);
        $this->assertStringNotContainsString('sort=letter_date&amp;dir=desc&amp;keluar_page=3', $html);
        $this->assertStringNotContainsString('sort=letter_date&amp;dir=desc&amp;masuk_page=2', $html);
    }

    public function test_workspace_filter_chips_preserve_tab(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->create(['role' => 'super_admin', 'division_id' => $division->id]);

        $response = $this->actingAs($admin)
            ->get('/persuratan?tab=keluar&q=undangan&date_from=2026-01-01');

        $response->assertOk();
        $html = $response->getContent();

        // The filter chip removal for 'q' must retain tab=keluar
        $this->assertMatchesRegularExpression('/href="[^"]*persuratan\?[^"]*tab=keluar[^"]*date_from=2026-01-01[^"]*"/', $html);
    }

    public function test_piagam_index_sorting_and_filter_form_preserves_query_state(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->create(['role' => 'super_admin', 'division_id' => $division->id]);

        $response = $this->actingAs($admin)
            ->get('/piagam?q=olimpiade&sort=created_at&dir=asc&per_page=30');

        $response->assertOk();
        $html = $response->getContent();

        // Filter form contains hidden inputs for sort, dir, and per_page
        $this->assertStringContainsString('name="sort" value="created_at"', $html);
        $this->assertStringContainsString('name="dir" value="asc"', $html);
        $this->assertStringContainsString('name="per_page" value="30"', $html);

        // Sorting link on student_name retains q=olimpiade and per_page=30
        $this->assertMatchesRegularExpression('/href="[^"]*(?=.*sort=student_name)(?=.*q=olimpiade)[^"]*"/', $html);
    }
}
