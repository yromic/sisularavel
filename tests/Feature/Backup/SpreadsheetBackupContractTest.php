<?php

namespace Tests\Feature\Backup;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\User;
use App\Services\Backup\SpreadsheetBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpreadsheetBackupContractTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $superAdmin;

    protected User $regularOperator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->division = Division::factory()->create(['name' => 'Bidang Pembinaan SD', 'code' => 'DIKDAS']);
        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->regularOperator = User::factory()->for($this->division)->create(['role' => 'operator']);
    }

    public function test_spreadsheet_download_endpoint_requires_super_admin_authentication(): void
    {
        // 1. Guest redirected to login
        $this->get('/admin/backups/spreadsheet')
            ->assertRedirect('/login');

        // 2. Regular operator forbidden
        $this->actingAs($this->regularOperator)
            ->get('/admin/backups/spreadsheet')
            ->assertForbidden();

        // 3. Super admin succeeds
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/backups/spreadsheet');

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');

        $this->assertMatchesRegularExpression(
            '/attachment; filename="?Rekap_Cadangan_SISURAT_.*\.xls"?/',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_spreadsheet_service_generates_three_sheets_with_active_google_drive_hyperlinks(): void
    {
        // 1. Seed Incoming Letter with Drive Attachment
        $incoming = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'letter_number' => '421.3/012/DISDIK/2026 & Rapat',
            'origin_agency' => 'Bupati Semarang',
            'letter_date' => '2026-01-15',
            'received_date' => '2026-01-16',
            'subject' => 'Undangan Rapat Koordinasi Pembinaan',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $driveLinkIncoming = 'https://drive.google.com/file/d/1SM_DRIVE_FILE_ID_ABC/view';
        Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $incoming->id,
            'file_path' => 'documents/DIKDAS/2026/01/sample.pdf',
            'original_name' => 'Surat_Undangan.pdf',
            'file_size_bytes' => 1024,
            'mime_type' => 'application/pdf',
            'google_drive_file_id' => '1SM_DRIVE_FILE_ID_ABC',
            'google_drive_view_url' => $driveLinkIncoming,
            'google_drive_synced_at' => now(),
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        // 2. Seed Outgoing Letter with Drive Attachment
        $outgoing = OutgoingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'letter_number' => '005/001/2026',
            'letter_year' => 2026,
            'letter_date' => '2026-01-18',
            'disseminated_date' => '2026-01-18',
            'subject' => 'Penyelenggaraan Lomba OSN 2026 <Sains>',
            'recipient_text' => 'Kepala Sekolah SMP Negeri & Swasta',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $driveLinkOutgoing = 'https://drive.google.com/file/d/2SK_DRIVE_FILE_ID_XYZ/view';
        Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'attachable_type' => OutgoingLetter::class,
            'attachable_id' => $outgoing->id,
            'file_path' => 'documents/DIKDAS/2026/01/outgoing.pdf',
            'original_name' => 'SK_005_001.pdf',
            'file_size_bytes' => 2048,
            'mime_type' => 'application/pdf',
            'google_drive_file_id' => '2SK_DRIVE_FILE_ID_XYZ',
            'google_drive_view_url' => $driveLinkOutgoing,
            'google_drive_synced_at' => now(),
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        // 3. Seed Piagam Handover with Signature Drive Attachment
        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'student_name' => 'Ananda Pratama',
            'school_origin' => 'SMP Negeri 2 Ungaran',
            'piagam_description' => 'Juara 1 FLS2N Tingkat Kabupaten',
            'recipient_name' => 'Bambang Wijaya, S.Pd.',
            'recipient_position' => 'Guru Pembina',
            'recipient_unit' => '081234567890',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $driveLinkSig = 'https://drive.google.com/file/d/3SIG_DRIVE_FILE_ID_123/view';
        Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'attachable_type' => Piagam::class,
            'attachable_id' => $piagam->id,
            'file_path' => 'signatures/DIKDAS/sig-001.png',
            'original_name' => 'signature.png',
            'file_size_bytes' => 512,
            'mime_type' => 'image/png',
            'google_drive_file_id' => '3SIG_DRIVE_FILE_ID_123',
            'google_drive_view_url' => $driveLinkSig,
            'google_drive_synced_at' => now(),
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        // Generate Workbook XML
        $service = app(SpreadsheetBackupService::class);
        $xml = $service->generateWorkbookXml();

        // 4. Assert XML Header & Structure
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<?mso-application progid="Excel.Sheet"?>', $xml);
        $this->assertStringContainsString('<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"', $xml);

        // 5. Assert all 3 Worksheets are present
        $this->assertStringContainsString('<Worksheet ss:Name="Surat Masuk">', $xml);
        $this->assertStringContainsString('<Worksheet ss:Name="Surat Keluar">', $xml);
        $this->assertStringContainsString('<Worksheet ss:Name="Ekspedisi Piagam">', $xml);

        // 6. Assert Active Hyperlinks
        $this->assertStringContainsString('ss:HRef="'.$driveLinkIncoming.'"', $xml);
        $this->assertStringContainsString('ss:HRef="'.$driveLinkOutgoing.'"', $xml);
        $this->assertStringContainsString('ss:HRef="'.$driveLinkSig.'"', $xml);

        // 7. Assert XML escaping for special characters (ampersand, brackets)
        $this->assertStringContainsString('421.3/012/DISDIK/2026 &amp; Rapat', $xml);
        $this->assertStringContainsString('&lt;Sains&gt;', $xml);
    }

    public function test_spreadsheet_handles_missing_or_unlinked_attachments_gracefully(): void
    {
        // Letter without any attachment
        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $this->division->id,
            'letter_number' => 'NO-ATT/001/2026',
            'origin_agency' => 'Kecamatan Bergas',
            'letter_date' => '2026-01-15',
            'received_date' => '2026-01-16',
            'subject' => 'Surat Tanpa Berkas Fisik',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $service = app(SpreadsheetBackupService::class);
        $xml = $service->generateWorkbookXml();

        $this->assertStringContainsString('NO-ATT/001/2026', $xml);
        $this->assertStringContainsString('[Tanpa Lampiran]', $xml);
    }
}
