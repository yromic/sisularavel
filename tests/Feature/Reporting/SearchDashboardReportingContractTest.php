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

    public function test_csv_export_can_export_each_entity_type_with_specific_columns(): void
    {
        $dikdas = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Dikdas']);
        $operator = User::factory()->for($dikdas)->create(['name' => 'Staf Dikdas']);
        $this->incomingLetter($dikdas, ['letter_number' => 'IN-SPECIFIC', 'subject' => 'Surat masuk tes']);
        $this->outgoingLetter($dikdas, ['letter_number' => 'OUT-SPECIFIC', 'subject' => 'Surat keluar tes']);
        $this->piagam($dikdas, 'Siswa Berprestasi');

        // 1. Export type=incoming
        $incomingCsv = $this->actingAs($operator)
            ->get('/api/v1/reports/export.csv?type=incoming')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Nomor Surat', $incomingCsv);
        $this->assertStringContainsString('Asal Surat', $incomingCsv);
        $this->assertStringContainsString('Tanggal Diterima', $incomingCsv);
        $this->assertStringContainsString('IN-SPECIFIC', $incomingCsv);
        $this->assertStringNotContainsString('OUT-SPECIFIC', $incomingCsv);
        $this->assertStringNotContainsString('Siswa Berprestasi', $incomingCsv);

        // 2. Export type=outgoing
        $outgoingCsv = $this->actingAs($operator)
            ->get('/api/v1/reports/export.csv?type=outgoing')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Nomor Surat', $outgoingCsv);
        $this->assertStringContainsString('Tanggal Shared', $outgoingCsv);
        $this->assertStringContainsString('Tahun Surat', $outgoingCsv);
        $this->assertStringContainsString('OUT-SPECIFIC', $outgoingCsv);
        $this->assertStringNotContainsString('IN-SPECIFIC', $outgoingCsv);

        // 3. Export type=piagam
        $piagamCsv = $this->actingAs($operator)
            ->get('/api/v1/reports/export.csv?type=piagam')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Nama Siswa', $piagamCsv);
        $this->assertStringContainsString('Kategori Lomba', $piagamCsv);
        $this->assertStringContainsString('Status Pengambilan', $piagamCsv);
        $this->assertStringContainsString('Siswa Berprestasi', $piagamCsv);
        $this->assertStringNotContainsString('IN-SPECIFIC', $piagamCsv);
        $this->assertStringNotContainsString('OUT-SPECIFIC', $piagamCsv);

        // 4. Export type=all
        $allCsv = $this->actingAs($operator)
            ->get('/api/v1/reports/export.csv?type=all')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Jenis Administrasi', $allCsv);
        $this->assertStringContainsString('IN-SPECIFIC', $allCsv);
        $this->assertStringContainsString('OUT-SPECIFIC', $allCsv);
        $this->assertStringContainsString('Siswa Berprestasi', $allCsv);
    }

    public function test_csv_export_applies_date_and_year_filters(): void
    {
        $dikdas = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($dikdas)->create();
        $this->incomingLetter($dikdas, ['letter_number' => 'IN-2025', 'letter_date' => '2025-05-10']);
        $this->incomingLetter($dikdas, ['letter_number' => 'IN-2026', 'letter_date' => '2026-06-15']);

        $csv2026 = $this->actingAs($operator)
            ->get('/api/v1/reports/export.csv?type=incoming&year=2026')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('IN-2026', $csv2026);
        $this->assertStringNotContainsString('IN-2025', $csv2026);
    }

    public function test_pdf_export_returns_pdf_and_respects_division_scoping(): void
    {
        [$dikdas, $paud] = [Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Dikdas']), Division::factory()->create(['code' => 'PAUD', 'name' => 'Bidang PAUD'])];
        $operator = User::factory()->for($dikdas)->create();
        $this->incomingLetter($dikdas, ['letter_number' => 'IN-DIKDAS-PDF', 'subject' => 'Surat Dikdas']);
        $this->incomingLetter($paud, ['letter_number' => 'IN-PAUD-SECRET', 'subject' => 'Surat PAUD Rahasia']);
        $this->outgoingLetter($dikdas, ['letter_number' => 'OUT-DIKDAS-PDF', 'subject' => 'Edaran Dikdas']);
        $this->piagam($dikdas, 'Juara Siswa Dikdas');

        $response = $this->actingAs($operator)
            ->get('/api/v1/reports/export.pdf?type=all');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $pdfOutput = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $pdfOutput);
    }

    public function test_pdf_export_supports_entity_types_and_filters(): void
    {
        $dikdas = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Dikdas']);
        $operator = User::factory()->for($dikdas)->create();
        $this->incomingLetter($dikdas, ['letter_number' => 'IN-FILT-2026', 'letter_date' => '2026-04-10']);
        $this->outgoingLetter($dikdas, ['letter_number' => 'OUT-FILT-2026', 'letter_date' => '2026-04-12']);
        $this->piagam($dikdas, 'Budi Siswa 2026');

        // Test incoming only
        $resIncoming = $this->actingAs($operator)->get('/api/v1/reports/export.pdf?type=incoming&year=2026');
        $resIncoming->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $resIncoming->headers->get('content-type'));

        // Test outgoing only
        $resOutgoing = $this->actingAs($operator)->get('/api/v1/reports/export.pdf?type=outgoing&year=2026');
        $resOutgoing->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $resOutgoing->headers->get('content-type'));

        // Test piagam only
        $resPiagam = $this->actingAs($operator)->get('/api/v1/reports/export.pdf?type=piagam&year=2026');
        $resPiagam->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $resPiagam->headers->get('content-type'));
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
