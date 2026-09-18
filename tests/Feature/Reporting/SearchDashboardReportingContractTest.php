<?php

namespace Tests\Feature\Reporting;

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

class SearchDashboardReportingContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_are_scoped_to_authenticated_users_division(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $operator = User::factory()->for($dikdas)->create();
        $this->incomingLetter($dikdas, ['subject' => 'DIKDAS incoming']);
        $this->incomingLetter($paud, ['subject' => 'PAUD incoming']);
        $this->outgoingLetter($dikdas, ['subject' => 'DIKDAS outgoing']);
        $this->piagam($dikdas, 'Siti Aminah');
        $this->piagam($paud, 'Budi PAUD');

        $this->actingAs($operator)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_incoming_letters', 1)
            ->assertJsonPath('data.total_outgoing_letters', 1)
            ->assertJsonPath('data.total_piagams', 1)
            ->assertJsonPath('data.monthly.incoming.2026-09', 1);
    }

    public function test_search_returns_only_scoped_archive_results(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $operator = User::factory()->for($dikdas)->create();
        $dikdasIncoming = $this->incomingLetter($dikdas, ['letter_number' => 'SEARCH-001', 'subject' => 'Festival sekolah']);
        $this->incomingLetter($paud, ['letter_number' => 'SEARCH-002', 'subject' => 'Festival PAUD']);
        $dikdasOutgoing = $this->outgoingLetter($dikdas, ['letter_number' => 'OUT-SEARCH', 'subject' => 'Festival edaran']);
        $dikdasPiagam = $this->piagam($dikdas, 'Juara Festival');

        $this->actingAs($operator)
            ->getJson('/api/v1/search?q=Festival')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['type' => 'incoming_letter', 'id' => $dikdasIncoming->id])
            ->assertJsonFragment(['type' => 'outgoing_letter', 'id' => $dikdasOutgoing->id])
            ->assertJsonFragment(['type' => 'piagam', 'id' => $dikdasPiagam->id])
            ->assertJsonMissing(['letter_number' => 'SEARCH-002']);
    }

    public function test_csv_export_is_scoped_and_sanitizes_formula_cells(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS']), Division::factory()->create(['code' => 'PAUD'])];
        $operator = User::factory()->for($dikdas)->create();
        $this->incomingLetter($dikdas, ['letter_number' => '=CMD()', 'subject' => '+Formula subject']);
        $this->incomingLetter($paud, ['letter_number' => 'PAUD-SECRET', 'subject' => 'Should not export']);

        $content = $this->actingAs($operator)
            ->get('/api/v1/reports/export.csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString("'=CMD()", $content);
        $this->assertStringContainsString("'+Formula subject", $content);
        $this->assertStringNotContainsString('PAUD-SECRET', $content);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function incomingLetter(Division $division, array $overrides = []): IncomingLetter
    {
        return IncomingLetter::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => 'IN-001',
            'origin_agency' => 'Dinas Pengirim',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Surat masuk',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function outgoingLetter(Division $division, array $overrides = []): OutgoingLetter
    {
        return OutgoingLetter::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_year' => 2026,
            'letter_number' => 'OUT-001',
            'letter_date' => '2026-09-03',
            'disseminated_date' => '2026-09-04',
            'subject' => 'Surat keluar',
        ], $overrides));
    }

    private function piagam(Division $division, string $studentName): Piagam
    {
        $school = School::factory()->create();
        $category = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'FLS2N']);

        return Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => $studentName,
            'school_id' => $school->id,
            'competition_category_id' => $category->id,
            'competition_year' => 2026,
        ]);
    }
}
