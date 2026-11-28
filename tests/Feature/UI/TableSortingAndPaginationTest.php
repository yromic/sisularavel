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
}
