<?php

namespace App\Services\Ocr;

use App\DTOs\ExtractedDocumentData;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DocumentExtractionService
{
    private const SYSTEM_INSTRUCTION = <<<PROMPT
Anda adalah asisten AI resmi pencatat administrasi persuratan dan piagam penghargaan dinas pendidikan.
Tugas Anda adalah membaca gambar/dokumen resmi dan mengekstrak entitas penting dengan presisi tinggi.

Format output HARUS selalu berupa JSON murni tanpa markdown/teks pengantar:
{
  "document_type": "surat" | "piagam" | "lainnya",
  "nomor_surat": string atau null (contoh: "421.2/123/DISDIK/2026"),
  "tanggal_surat": string format YYYY-MM-DD atau null (contoh: "2026-10-14"),
  "instansi_pengirim": string nama instansi/lembaga pengirim pada kop surat atau null,
  "perihal": string perihal/hal surat atau null,
  "nama_siswa": string nama lengkap siswa (jika piagam/sertifikat) atau null,
  "nama_sekolah": string nama sekolah asal siswa atau null,
  "npsn": string 8 digit NPSN jika tertera atau null,
  "tahun_lomba": integer 4 digit tahun (jika piagam) atau null,
  "kategori_lomba": string nama lomba/kejuaraan (jika piagam) atau null,
  "confidence": float antara 0.0 sampai 1.0
}
PROMPT;

    public function extract(UploadedFile $file, string $hintType = 'surat'): ?ExtractedDocumentData
    {
        $mime = $file->getMimeType();
        $binary = file_get_contents($file->getRealPath());
        $base64 = base64_encode($binary);

        // Tier 1: Gemini 3.5 Flash Lite
        $result = $this->tryGemini($base64, $mime, config('services.gemini.model_primary', 'gemini-3.5-flash-lite'));
        if ($result !== null) {
            $result->provider = 'gemini';
            $result->model = config('services.gemini.model_primary', 'gemini-3.5-flash-lite');
            return $result;
        }

        // Tier 2: Gemini 3.1 Flash Lite
        $result = $this->tryGemini($base64, $mime, config('services.gemini.model_fallback', 'gemini-3.1-flash-lite'));
        if ($result !== null) {
            $result->provider = 'gemini';
            $result->model = config('services.gemini.model_fallback', 'gemini-3.1-flash-lite');
            return $result;
        }

        // Tier 3: Groq Cloud Vision (Qwen 3.8 27B Vision - 25s timeout)
        $result = $this->tryGroq($base64, $mime);
        if ($result !== null) {
            $result->provider = 'groq';
            $result->model = config('services.groq.model', 'qwen/qwen3.8-27b');
            return $result;
        }

        // Tier 4: OCR.space Engine
        $result = $this->tryOcrSpace($file, $hintType);
        if ($result !== null) {
            $result->provider = 'ocr_space';
            $result->model = 'ocr_engine_2';
            return $result;
        }

        return null;
    }

    private function getGeminiApiKeys(): array
    {
        $keys = [];
        $primary = config('services.gemini.api_key');
        if (! empty($primary)) {
            $keys[] = trim($primary);
        }

        $pooled = config('services.gemini.api_keys');
        if (! empty($pooled)) {
            foreach (explode(',', $pooled) as $k) {
                $trimmed = trim($k);
                if ($trimmed !== '' && ! in_array($trimmed, $keys, true)) {
                    $keys[] = $trimmed;
                }
            }
        }

        return $keys;
    }

    private function tryGemini(string $base64, string $mime, string $model): ?ExtractedDocumentData
    {
        $keys = $this->getGeminiApiKeys();
        if (empty($keys)) {
            return null;
        }

        foreach ($keys as $apiKey) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout(6)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    ['text' => self::SYSTEM_INSTRUCTION],
                                    [
                                        'inline_data' => [
                                            'mime_type' => $mime,
                                            'data' => $base64,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'response_mime_type' => 'application/json',
                            'temperature' => 0.1,
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($rawText) {
                        return $this->parseJsonToDto($rawText);
                    }
                } else {
                    Log::warning("Gemini {$model} returned [{$response->status()}]: " . substr($response->body(), 0, 300));
                }

                if ($response->status() === 429) {
                    Log::warning("Gemini model {$model} rate-limited on key: " . substr($apiKey, 0, 6) . "...");
                    continue; // try next key
                }
            } catch (Throwable $e) {
                Log::warning("Gemini exception on {$model}: " . $e->getMessage());
            }
        }

        return null;
    }

    private function tryGroq(string $base64, string $mime): ?ExtractedDocumentData
    {
        $apiKey = config('services.groq.api_key');
        if (empty($apiKey)) {
            return null;
        }

        // Groq vision ONLY accepts image formats (JPEG, PNG, WEBP, GIF). It does NOT support PDF documents.
        if (! str_starts_with($mime, 'image/')) {
            Log::info("Groq vision skipped: MIME type [{$mime}] is not a supported image format.");
            return null;
        }

        $model = config('services.groq.model', 'qwen/qwen3.8-27b');

        try {
            $dataUrl = "data:{$mime};base64,{$base64}";
            $response = Http::timeout(25)
                ->withToken($apiKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => self::SYSTEM_INSTRUCTION],
                                ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                            ],
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                if ($content) {
                    return $this->parseJsonToDto($content);
                }
            } else {
                Log::warning("Groq vision returned [{$response->status()}]: " . substr($response->body(), 0, 300));
            }
        } catch (Throwable $e) {
            Log::warning("Groq vision exception: " . $e->getMessage());
        }

        return null;
    }

    private function tryOcrSpace(UploadedFile $file, string $hintType): ?ExtractedDocumentData
    {
        $apiKey = config('services.ocr_space.api_key', 'helloworld');

        try {
            $response = Http::timeout(25)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post('https://api.ocr.space/parse/image', [
                    'apikey' => $apiKey,
                    'language' => 'ind',
                    'isOverlayRequired' => 'false',
                    'detectOrientation' => 'true',
                    'scale' => 'true',
                    'OCREngine' => '2',
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $parsedText = $json['ParsedResults'][0]['ParsedText'] ?? null;
                if (! empty($parsedText)) {
                    return $this->parseRawTextRegex($parsedText, $hintType);
                }
            }
        } catch (Throwable $e) {
            Log::warning("OCR.space exception: " . $e->getMessage());
        }

        return null;
    }

    private function parseJsonToDto(string $jsonString): ?ExtractedDocumentData
    {
        $clean = trim($jsonString);
        // Remove code block markdown if present
        if (str_starts_with($clean, '```json')) {
            $clean = substr($clean, 7);
        }
        if (str_starts_with($clean, '```')) {
            $clean = substr($clean, 3);
        }
        if (str_ends_with($clean, '```')) {
            $clean = substr($clean, 0, -3);
        }
        $clean = trim($clean);

        $parsed = json_decode($clean, true);
        if (! is_array($parsed)) {
            return null;
        }

        $letterDate = null;
        if (! empty($parsed['tanggal_surat'])) {
            $letterDate = rescue(fn () => Carbon::parse($parsed['tanggal_surat'])->format('Y-m-d'), null);
        }

        return new ExtractedDocumentData(
            letterNumber: ! empty($parsed['nomor_surat']) ? trim((string) $parsed['nomor_surat']) : null,
            letterDate: $letterDate,
            originAgency: ! empty($parsed['instansi_pengirim']) ? trim((string) $parsed['instansi_pengirim']) : null,
            subject: ! empty($parsed['perihal']) ? trim((string) $parsed['perihal']) : null,
            studentName: ! empty($parsed['nama_siswa']) ? trim((string) $parsed['nama_siswa']) : null,
            schoolName: ! empty($parsed['nama_sekolah']) ? trim((string) $parsed['nama_sekolah']) : null,
            npsn: ! empty($parsed['npsn']) ? preg_replace('/\D/', '', (string) $parsed['npsn']) : null,
            competitionYear: ! empty($parsed['tahun_lomba']) ? (int) $parsed['tahun_lomba'] : null,
            competitionCategory: ! empty($parsed['kategori_lomba']) ? trim((string) $parsed['kategori_lomba']) : null,
            confidence: (float) ($parsed['confidence'] ?? 0.8),
            raw: $parsed
        );
    }

    private function parseRawTextRegex(string $text, string $hintType): ExtractedDocumentData
    {
        $letterNumber = null;
        $originAgency = null;
        $subject = null;
        $letterDate = null;

        // Nomor Surat Pattern
        if (preg_match('/(?:Nomor|No|NOMOR|NO)\s*[:.]\s*([0-9A-Za-z\.\/\-_]{5,50})/i', $text, $matches)) {
            $letterNumber = trim($matches[1]);
        }

        // Perihal Pattern
        if (preg_match('/(?:Perihal|Hal|PERIHAL|HAL)\s*[:.]\s*([^\r\n]+)/i', $text, $matches)) {
            $subject = trim($matches[1]);
        }

        // Tanggal Pattern (e.g. 14 Oktober 2026 or 14-10-2026)
        if (preg_match('/(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember|[A-Za-z]+)\s+\d{4})/i', $text, $matches)) {
            $letterDate = rescue(fn () => Carbon::parse($matches[1])->format('Y-m-d'), null);
        } elseif (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/', $text, $matches)) {
            $letterDate = rescue(fn () => Carbon::parse($matches[1])->format('Y-m-d'), null);
        }

        // First lines often contain origin agency / Kop Surat
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        if (! empty($lines)) {
            $topLines = array_slice($lines, 0, 3);
            $originAgency = implode(' ', $topLines);
            if (strlen($originAgency) > 150) {
                $originAgency = substr($originAgency, 0, 150);
            }
        }

        return new ExtractedDocumentData(
            letterNumber: $letterNumber,
            letterDate: $letterDate,
            originAgency: $originAgency,
            subject: $subject,
            confidence: 0.5,
            raw: ['text_sample' => substr($text, 0, 200)]
        );
    }
}

