<?php

namespace Tests\Feature\School;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SchoolCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_download_school_csv_template(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)
            ->get('/master/sekolah/template-csv');

        $response->assertOk();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('npsn,nama_sekolah,status_aktif', $content);
        $this->assertStringContainsString('SMP Negeri 1 Ungaran', $content);
    }

    public function test_non_super_admin_cannot_download_template_or_import_csv(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->get('/master/sekolah/template-csv')
            ->assertForbidden();

        $this->actingAs($operator)
            ->post('/api/v1/schools/import-csv', [])
            ->assertForbidden();
    }

    public function test_super_admin_can_import_schools_via_csv(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        // Existing school to be updated
        School::factory()->create([
            'npsn' => '20320340',
            'name' => 'SMP Lama',
        ]);

        // Soft-deleted school to be restored
        $trashed = School::factory()->create([
            'npsn' => '20320678',
            'name' => 'SD Terhapus',
        ]);
        $trashed->delete();

        $csvData = "npsn,nama_sekolah,status_aktif\n".
                   "20320340,SMP Negeri 1 Ungaran (Updated),1\n".
                   "20320678,SD Negeri Bandungan 01 (Restored),1\n".
                   "20320999,SMP Baru Swasta,1\n".
                   "12345,Sekolah Invalid,1\n"; // Invalid NPSN length

        $file = UploadedFile::fake()->createWithContent('schools.csv', $csvData);

        $response = $this->actingAs($superAdmin)
            ->post('/api/v1/schools/import-csv', ['file' => $file], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'created' => 1,
                'updated' => 1,
                'restored' => 1,
                'skipped' => 1,
            ],
        ]);

        $this->assertDatabaseHas('schools', [
            'npsn' => '20320340',
            'name' => 'SMP Negeri 1 Ungaran (Updated)',
        ]);

        $this->assertDatabaseHas('schools', [
            'npsn' => '20320678',
            'name' => 'SD Negeri Bandungan 01 (Restored)',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('schools', [
            'npsn' => '20320999',
            'name' => 'SMP Baru Swasta',
        ]);
    }
}
