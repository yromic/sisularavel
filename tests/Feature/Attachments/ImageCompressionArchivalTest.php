<?php

namespace Tests\Feature\Attachments;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\School;
use App\Models\User;
use App\Services\ImageOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImageCompressionArchivalTest extends TestCase
{
    use RefreshDatabase;

    private function createGdImage(int $width, int $height, string $format = 'jpg', ?string $label = null): UploadedFile
    {
        $im = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($im, 245, 245, 245);
        imagefilledrectangle($im, 0, 0, $width, $height, $bg);

        // Tambahkan garis berpola agar citra memiliki konten riil yang dapat dikompresi
        $lineColor = imagecolorallocate($im, 40, 40, 40);
        for ($i = 0; $i < $height; $i += 30) {
            imageline($im, 0, $i, $width, $i, $lineColor);
        }

        if ($label !== null) {
            $textColor = imagecolorallocate($im, 180, 20, 20);
            imagestring($im, 5, 30, 30, $label, $textColor);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_img_').'.'.$format;
        if ($format === 'png') {
            imagepng($im, $tempPath);
            $mime = 'image/png';
        } else {
            imagejpeg($im, $tempPath, 95);
            $mime = 'image/jpeg';
        }
        imagedestroy($im);

        return new UploadedFile(
            path: $tempPath,
            originalName: "test_{$width}x{$height}.{$format}",
            mimeType: $mime,
            error: null,
            test: true
        );
    }

    private function countTempPhotos(): int
    {
        $dir = storage_path('app/private/temp_photos');
        if (! is_dir($dir)) {
            return 0;
        }

        return count(glob($dir.DIRECTORY_SEPARATOR.'*.jpg'));
    }

    public function test_server_side_image_optimization_resizes_large_jpeg_to_max_1280px_and_reduces_size(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();

        $initialTempCount = $this->countTempPhotos();
        $largeImage = $this->createGdImage(2400, 1600, 'jpg');
        $originalSizeBytes = $largeImage->getSize();

        $response = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '001/KOMPRES/2026',
                'asal_surat' => 'Dinas Pendidikan Provinsi',
                'tanggal_surat' => '2026-11-01',
                'tanggal_diterima' => '2026-11-02',
                'perihal' => 'Uji Kompresi Citra Tunggal JPEG',
                'attachment' => $largeImage,
            ])
            ->assertCreated();

        $letter = IncomingLetter::where('letter_number', '001/KOMPRES/2026')->firstOrFail();
        $attachment = $letter->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame('image/jpeg', $attachment->mime_type);
        $this->assertStringEndsWith('.jpg', $attachment->file_path);

        // Verifikasi berkas fisik pada disk privat
        Storage::disk('private_documents')->assertExists($attachment->file_path);
        $storedContent = Storage::disk('private_documents')->get($attachment->file_path);
        $storedSize = strlen($storedContent);

        // Ukuran harus lebih kecil dari ukuran asli
        $this->assertLessThan($originalSizeBytes, $storedSize);
        $this->assertSame($storedSize, $attachment->file_size_bytes);

        // Hash SHA-256 pada database harus sama persis dengan hash berkas fisik
        $this->assertSame(hash('sha256', $storedContent), $attachment->file_hash);

        // Verifikasi dimensi tidak melebihi 1280 piksel
        $img = @imagecreatefromstring($storedContent);
        $this->assertNotFalse($img);
        $this->assertLessThanOrEqual(1280, imagesx($img));
        $this->assertLessThanOrEqual(1280, imagesy($img));
        imagedestroy($img);

        // Verifikasi seluruh berkas temporary telah dibersihkan
        $this->assertSame($initialTempCount, $this->countTempPhotos());
    }

    public function test_png_image_converted_to_jpg_with_correct_disk_extension_and_mime_type(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'PAUD']);
        $operator = User::factory()->for($division)->create();

        $initialTempCount = $this->countTempPhotos();
        $pngImage = $this->createGdImage(1800, 1200, 'png');

        $response = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-keluar', [
                'nomor_surat' => '002/PNG-TO-JPG/2026',
                'tanggal_surat' => '2026-11-01',
                'tanggal_share' => '2026-11-02',
                'perihal' => 'Uji Konversi PNG ke JPEG',
                'attachment' => $pngImage,
            ])
            ->assertCreated();

        $letter = OutgoingLetter::where('letter_number', '002/PNG-TO-JPG/2026')->firstOrFail();
        $attachment = $letter->attachments()->first();

        $this->assertNotNull($attachment);

        // Catatan Wajib 1: Ekstensi fisik harus .jpg dan mime_type harus image/jpeg
        $this->assertSame('image/jpeg', $attachment->mime_type);
        $this->assertStringEndsWith('.jpg', $attachment->file_path);
        $this->assertStringNotContainsString('.png', $attachment->file_path);

        // Verifikasi citra yang disimpan valid sebagai JPEG murni
        Storage::disk('private_documents')->assertExists($attachment->file_path);
        $storedContent = Storage::disk('private_documents')->get($attachment->file_path);
        $img = @imagecreatefromstring($storedContent);
        $this->assertNotFalse($img);
        $this->assertLessThanOrEqual(1280, imagesx($img));
        $this->assertLessThanOrEqual(1280, imagesy($img));
        imagedestroy($img);

        // Verifikasi temporary dibersihkan
        $this->assertSame($initialTempCount, $this->countTempPhotos());
    }

    public function test_non_image_documents_bypass_optimization_and_preserve_original_extension_and_mime(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'SEKRETARIAT']);
        $operator = User::factory()->for($division)->create();

        $rawPdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n>>\nendobj\ntrailer\n<<\n/Root 1 0 R\n>>\n%%EOF";
        $pdfFile = UploadedFile::fake()->createWithContent('arsip_resmi.pdf', $rawPdfContent);

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '003/PDF/2026',
                'asal_surat' => 'Kementerian Dalam Negeri',
                'tanggal_surat' => '2026-11-01',
                'tanggal_diterima' => '2026-11-02',
                'perihal' => 'Uji Dokumen Asli PDF Tidak Disentuh GD',
                'attachment' => $pdfFile,
            ])
            ->assertCreated();

        $letter = IncomingLetter::where('letter_number', '003/PDF/2026')->firstOrFail();
        $attachment = $letter->attachments()->first();

        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertStringEndsWith('.pdf', $attachment->file_path);
        $this->assertSame(strlen($rawPdfContent), $attachment->file_size_bytes);
        $this->assertSame(hash('sha256', $rawPdfContent), $attachment->file_hash);
    }

    public function test_decompression_bomb_image_exceeding_4096px_is_rejected_with_422(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();

        $bombImage = $this->createGdImage(4200, 200, 'jpg');

        // Uji pada lampiran tunggal
        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '004/BOMB/2026',
                'asal_surat' => 'Sumber Tidak Dikenal',
                'tanggal_surat' => '2026-11-01',
                'tanggal_diterima' => '2026-11-02',
                'perihal' => 'Uji Penolakan Decompression Bomb',
                'attachment' => $bombImage,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attachment']);

        // Uji pada lembar foto multi-kamera
        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '005/BOMB-PHOTO/2026',
                'asal_surat' => 'Sumber Tidak Dikenal',
                'tanggal_surat' => '2026-11-01',
                'tanggal_diterima' => '2026-11-02',
                'perihal' => 'Uji Penolakan Decompression Bomb Photos',
                'photos' => [$bombImage],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photos.0']);
    }

    public function test_exif_orientation_6_is_rotated_upright(): void
    {
        $optimizer = new ImageOptimizationService;

        // Buat JPEG 300x150 dengan header TIFF Orientation 6
        $tiff = "Exif\x00\x00".
                "II\x2A\x00\x08\x00\x00\x00".
                "\x01\x00".
                "\x12\x01".
                "\x03\x00".
                "\x01\x00\x00\x00".
                "\x06\x00\x00\x00".
                "\x00\x00\x00\x00";

        $app1Length = strlen($tiff) + 2;
        $app1 = "\xFF\xE1".pack('n', $app1Length).$tiff;

        $im = imagecreatetruecolor(300, 150);
        $bg = imagecolorallocate($im, 50, 100, 150);
        imagefill($im, 0, 0, $bg);
        ob_start();
        imagejpeg($im);
        $rawJpeg = ob_get_clean();
        imagedestroy($im);

        $jpegWithExif = substr($rawJpeg, 0, 2).$app1.substr($rawJpeg, 2);
        $tempPath = tempnam(sys_get_temp_dir(), 'test_exif_').'.jpg';
        file_put_contents($tempPath, $jpegWithExif);

        $result = $optimizer->optimize($tempPath);
        $this->assertTrue($result->isOptimized);

        $info = @getimagesize($result->path);
        $this->assertNotFalse($info);

        // Setelah diputar 90 derajat searah jarum jam, dimensi 300x150 menjadi 150x300
        $this->assertSame(150, $info[0]);
        $this->assertSame(300, $info[1]);

        $result->cleanup();
        @unlink($tempPath);
    }

    public function test_multi_photo_merge_to_pdf_produces_compact_pdf_and_cleans_temp_photos(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();

        $initialTempCount = $this->countTempPhotos();

        // Siapkan 4 foto berdimensi 1400x1000 dengan konten unik
        $photo1 = $this->createGdImage(1400, 1000, 'jpg', 'Lembar 1 - Surat Masuk');
        $photo2 = $this->createGdImage(1400, 1000, 'jpg', 'Lembar 2 - Lampiran Tabel');
        $photo3 = $this->createGdImage(1400, 1000, 'jpg', 'Lembar 3 - Uraian Kegiatan');
        $photo4 = $this->createGdImage(1400, 1000, 'jpg', 'Lembar 4 - Tanda Tangan');

        $response = $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/surat-masuk', [
                'nomor_surat' => '006/MULTI-FOTO/2026',
                'asal_surat' => 'Balai Penjaminan Mutu',
                'tanggal_surat' => '2026-11-01',
                'tanggal_diterima' => '2026-11-02',
                'perihal' => 'Uji Kompresi Multi-Foto 4 Lembar ke PDF',
                'photos' => [$photo1, $photo2, $photo3, $photo4],
            ])
            ->assertCreated();

        $letter = IncomingLetter::where('letter_number', '006/MULTI-FOTO/2026')->firstOrFail();
        $attachment = $letter->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertStringEndsWith('.pdf', $attachment->original_name);
        $this->assertStringContainsString('4_lembar', $attachment->original_name);

        // Ukuran PDF 4 halaman hasil kompresi harus realistis (tidak kosong/rusak dan efisien di bawah 500 KB)
        $this->assertGreaterThan(15 * 1024, $attachment->file_size_bytes);
        $this->assertLessThan(500 * 1024, $attachment->file_size_bytes);

        // Verifikasi isi biner PDF: Memastikan 4 objek gambar tertanam sempurna (bukan broken image/kotak silang)
        $pdfContent = Storage::disk('private_documents')->get($attachment->file_path);
        $imageCount = substr_count($pdfContent, '/Subtype /Image') + substr_count($pdfContent, '/Subtype/Image');
        $this->assertSame(4, $imageCount, 'PDF wajib memuat tepat 4 objek /Subtype /Image (bukan broken image).');
        $this->assertGreaterThanOrEqual(4, substr_count($pdfContent, '/DCTDecode'), 'PDF wajib memuat filter kompresi /DCTDecode untuk setiap citra JPEG.');
        $this->assertStringNotContainsString('Image not found or type unknown', $pdfContent);

        // Seluruh berkas temporary di storage/app/private/temp_photos wajib bersih
        $this->assertSame($initialTempCount, $this->countTempPhotos());

        // Verifikasi stream preview endpoint berfungsi untuk PDF gabungan ini
        $this->actingAs($operator)
            ->get("/api/v1/attachments/{$attachment->uuid}/preview")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_public_piagam_signature_canvas_security_hardening(): void
    {
        Storage::fake('private_signatures');

        $division = Division::factory()->create(['code' => 'DIKDAS', 'status' => 'active']);
        $school = School::factory()->create(['name' => 'SD Negeri 1 Bergas', 'npsn' => '20320111']);

        // 1. Uji tanda tangan korup/bukan gambar ditolak dengan 422
        $corruptDataUri = 'data:image/png;base64,'.base64_encode('totally-not-an-image-data-string-here');
        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', [
                'division_id' => $division->id,
                'recipient_name' => 'Budi Santoso',
                'recipient_position' => 'Guru',
                'work_unit_selection' => 'school:'.$school->id,
                'student_name' => 'Ahmad Dahlan',
                'competition_year' => 2026,
                'signature_canvas' => $corruptDataUri,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['signature_canvas']);

        // 2. Uji payload melebihi 700 KB string ditolak dengan 422
        $oversizedDataUri = 'data:image/png;base64,'.str_repeat('A', 750000);
        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', [
                'division_id' => $division->id,
                'recipient_name' => 'Budi Santoso',
                'recipient_position' => 'Guru',
                'work_unit_selection' => 'school:'.$school->id,
                'student_name' => 'Ahmad Dahlan',
                'competition_year' => 2026,
                'signature_canvas' => $oversizedDataUri,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['signature_canvas']);

        // 3. Uji tanda tangan digital sah dari canvas berhasil diproses
        $validDataUri = 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', [
                'division_id' => $division->id,
                'recipient_name' => 'Budi Santoso',
                'recipient_position' => 'Guru',
                'work_unit_selection' => 'school:'.$school->id,
                'student_name' => 'Ahmad Dahlan',
                'competition_year' => 2026,
                'signature_canvas' => $validDataUri,
            ])
            ->assertCreated();

        $response->assertJsonPath('success', true);
    }

    public function test_remediation_compresses_replacement_image_and_updates_mime_type(): void
    {
        Storage::fake('private_documents');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $admin = User::factory()->for($division)->create(['role' => 'division_admin']);

        // Buat surat dengan lampiran yang missing (status legacy)
        $letter = IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => '007/REMEDIATION/2026',
            'origin_agency' => 'Dinas Pendidikan Provinsi',
            'letter_date' => '2026-11-01',
            'received_date' => '2026-11-02',
            'subject' => 'Surat Remediasi Uji Kompresi',
            'created_by_user_id' => $admin->id,
        ]);

        $attachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => IncomingLetter::class,
            'attachable_id' => $letter->id,
            'file_path' => null,
            'original_name' => 'missing_original.png',
            'file_size_bytes' => 0,
            'mime_type' => 'image/png',
            'file_hash' => 'dummy',
            'is_missing' => true,
            'migration_status' => 'missing_drive_file',
            'created_by_user_id' => $admin->id,
        ]);

        // Unggah berkas pemulihan berupa PNG 1800x1200
        $replacementPng = $this->createGdImage(1800, 1200, 'png');

        $this->actingAs($admin)
            ->postJson("/api/v1/surat-masuk/{$letter->id}/remediate-attachment", [
                'attachment' => $replacementPng,
            ])
            ->assertOk();

        $attachment->refresh();
        $this->assertFalse($attachment->is_missing);
        $this->assertSame('migrated', $attachment->migration_status);
        $this->assertSame('image/jpeg', $attachment->mime_type);
        $this->assertStringEndsWith('.jpg', $attachment->file_path);
        $this->assertGreaterThan(0, $attachment->file_size_bytes);
        Storage::disk('private_documents')->assertExists($attachment->file_path);
    }
}
