<?php

namespace Tests\Feature\Report;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportExportLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_export_contains_formal_indonesian_labels(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Pembinaan SD']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '421/001/2026',
            'letter_date' => '2026-02-10',
            'received_date' => '2026-02-12',
            'origin_agency' => 'Kemendikbud',
            'subject' => 'Undangan ANBK',
            'created_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get('/api/v1/reports/export.pdf?type=incoming');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        // Verify view content rendering directly with the localized view
        $view = $this->actingAs($admin)->view('reports.pdf', [
            'type' => 'incoming',
            'activeDivision' => $division,
            'printedBy' => $admin,
            'dateFrom' => null,
            'dateTo' => null,
            'year' => null,
            'month' => null,
            'incomingLetters' => IncomingLetter::all(),
            'outgoingLetters' => collect(),
            'piagams' => collect(),
        ]);

        $view->assertSee('Rekapitulasi Surat Masuk');
        $view->assertSee('Super Administrator');
        $view->assertDontSee(': INCOMING');
    }
}
