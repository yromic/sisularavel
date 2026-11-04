<?php

namespace Tests\Feature\Ocr;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentOcrExtractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_ocr_endpoint(): void
    {
        $file = UploadedFile::fake()->create('surat.jpg', 500, 'image/jpeg');

        $this->postJson('/api/v1/ocr/scan', ['file' => $file])
            ->assertUnauthorized();
    }

    public function test_ocr_endpoint_validates_file_presence_and_mimes(): void
    {
        $user = User::factory()->operator()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/ocr/scan', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $invalidFile = UploadedFile::fake()->create('script.exe', 500, 'application/x-msdownload');
        $this->actingAs($user)
            ->postJson('/api/v1/ocr/scan', ['file' => $invalidFile])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_ocr_extracts_letter_successfully_via_gemini_cascade(): void
    {
        $user = User::factory()->operator()->create();
        $file = UploadedFile::fake()->image('surat_masuk.jpg', 800, 600);

        config(['services.gemini.api_key' => 'fake-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'document_type' => 'surat',
                                        'nomor_surat' => '421.2/100/DISDIK/2026',
                                        'tanggal_surat' => '2026-10-15',
                                        'instansi_pengirim' => 'SMP Negeri 1 Bawen',
                                        'perihal' => 'Laporan Pelaksanaan Ujian Sekolah',
                                        'confidence' => 0.95,
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ocr/scan', ['file' => $file, 'type' => 'surat']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.letter_number', '421.2/100/DISDIK/2026')
            ->assertJsonPath('data.letter_date', '2026-10-15')
            ->assertJsonPath('data.origin_agency', 'SMP Negeri 1 Bawen')
            ->assertJsonPath('data.subject', 'Laporan Pelaksanaan Ujian Sekolah')
            ->assertJsonPath('data.provider', 'gemini');
    }

    public function test_ocr_falls_back_to_groq_when_gemini_rate_limited(): void
    {
        $user = User::factory()->operator()->create();
        $file = UploadedFile::fake()->image('surat_masuk.jpg', 800, 600);

        config([
            'services.gemini.api_key' => 'fake-gemini-key',
            'services.groq.api_key' => 'fake-groq-key',
        ]);

        Http::fake([
            // Gemini fails with 429 Rate Limit
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota exceeded'], 429),
            // Groq succeeds
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'document_type' => 'surat',
                                'nomor_surat' => '005/789/2026',
                                'tanggal_surat' => '2026-10-20',
                                'instansi_pengirim' => 'Kecamatan Bergas',
                                'perihal' => 'Undangan Upacara Sumpah Pemuda',
                                'confidence' => 0.9,
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ocr/scan', ['file' => $file, 'type' => 'surat']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.letter_number', '005/789/2026')
            ->assertJsonPath('data.provider', 'groq');
    }

    public function test_ocr_gracefully_fails_when_all_providers_offline(): void
    {
        $user = User::factory()->operator()->create();
        $file = UploadedFile::fake()->image('surat.jpg', 800, 600);

        config([
            'services.gemini.api_key' => 'fake-gemini-key',
            'services.groq.api_key' => 'fake-groq-key',
            'services.ocr_space.api_key' => 'fake-ocr-key',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'server error'], 500),
            'api.groq.com/*' => Http::response(['error' => 'server error'], 500),
            'api.ocr.space/*' => Http::response(['error' => 'server error'], 500),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ocr/scan', ['file' => $file, 'type' => 'surat']);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertSee('Layanan pemindai AI sedang sibuk');
    }
}

