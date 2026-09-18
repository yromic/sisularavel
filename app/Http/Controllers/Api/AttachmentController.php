<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\AttachmentStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function preview(Request $request, string $uuid): Response|JsonResponse
    {
        $attachment = Attachment::query()->where('uuid', $uuid)->firstOrFail();

        if (! $this->canView($request, $attachment)) {
            return $this->forbiddenDivisionResponse();
        }

        if ($attachment->is_missing || $attachment->file_path === null || ! Storage::disk(AttachmentStorageService::DISK)->exists($attachment->file_path)) {
            return response()->json([
                'success' => false,
                'code' => 'ERR_404_ATTACHMENT',
                'message' => 'Berkas lampiran lama tidak ditemukan pada penyimpanan',
            ], 404);
        }

        return response(Storage::disk(AttachmentStorageService::DISK)->get($attachment->file_path), 200, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"',
        ]);
    }

    private function canView(Request $request, Attachment $attachment): bool
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->division_id === $attachment->division_id;
    }

    private function forbiddenDivisionResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'ERR_403_DIVISI',
            'message' => 'Anda tidak memiliki izin untuk mengakses data divisi ini.',
        ], 403);
    }
}
