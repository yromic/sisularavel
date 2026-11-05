<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ocr\DocumentExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DocumentOcrController extends Controller
{
    public function scan(Request $request, DocumentExtractionService $ocr): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], // max 5MB
            'type' => ['sometimes', 'in:surat,piagam'],
        ], [
            'file.required' => 'Berkas gambar atau dokumen wajib diunggah untuk dipindai.',
            'file.mimes' => 'Format berkas harus berupa JPG, PNG, WEBP, atau PDF.',
            'file.max' => 'Ukuran berkas tidak boleh melebihi 5 MB.',
        ]);

        try {
            $file = $request->file('file');
            $hintType = $request->input('type', 'surat');

            $extracted = $ocr->extract($file, $hintType);

            if ($extracted === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan pemindai otomatis sedang sibuk atau tidak dapat mengenali teks pada berkas ini. Silakan isi formulir secara manual.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Teks dokumen berhasil dipindai secara otomatis.',
                'data' => $extracted->toArray(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pemindaian dokumen: ' . $e->getMessage(),
            ], 500);
        }
    }
}

