<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Piagam;
use App\Services\AttachmentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function preview(Request $request, string $uuid): Response
    {
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return $this->streamAttachment($request, $uuid, $disposition);
    }

    public function download(Request $request, string $uuid): Response
    {
        return $this->streamAttachment($request, $uuid, 'attachment');
    }

    private function streamAttachment(Request $request, string $uuid, string $disposition = 'inline'): Response
    {
        $attachment = Attachment::query()->where('uuid', $uuid)->firstOrFail();

        if (! $this->canView($request, $attachment)) {
            return $this->forbiddenDivisionResponse($request);
        }

        $diskName = $attachment->attachable_type === Piagam::class ? 'private_signatures' : AttachmentStorageService::DISK;
        $disk = Storage::disk($diskName);
        $filePath = $attachment->file_path;

        if ($filePath !== null && ! $disk->exists($filePath)) {
            $stripped = preg_replace('/^(documents|signatures)\//', '', $filePath);
            if ($stripped && $disk->exists($stripped)) {
                $filePath = $stripped;
            }
        }

        if ($attachment->is_missing || $filePath === null || ! $disk->exists($filePath)) {
            if ($this->isBrowserNavigation($request)) {
                abort(404, 'Berkas lampiran lama tidak ditemukan pada penyimpanan.');
            }

            return response()->json([
                'success' => false,
                'code' => 'ERR_404_ATTACHMENT',
                'message' => 'Berkas lampiran lama tidak ditemukan pada penyimpanan',
            ], 404);
        }

        $headers = [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => $disposition.'; filename="'.$attachment->original_name.'"',
        ];

        if (app()->runningUnitTests()) {
            return response($disk->get($filePath), 200, $headers);
        }

        return $disk->response($filePath, $attachment->original_name, $headers);
    }

    private function canView(Request $request, Attachment $attachment): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->division_id === $attachment->division_id;
    }

    private function forbiddenDivisionResponse(?Request $request = null): Response
    {
        if ($request && $this->isBrowserNavigation($request)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
        }

        return response()->json([
            'success' => false,
            'code' => 'ERR_403_DIVISI',
            'message' => 'Anda tidak memiliki izin untuk mengakses data divisi ini.',
        ], 403);
    }

    private function isBrowserNavigation(Request $request): bool
    {
        return $request->header('Sec-Fetch-Dest') === 'document'
            || $request->header('Sec-Fetch-Mode') === 'navigate'
            || $request->hasHeader('Upgrade-Insecure-Requests')
            || ($request->acceptsHtml() && ! $request->expectsJson() && $request->hasHeader('User-Agent') && str_contains($request->header('User-Agent'), 'Mozilla'));
    }
}
