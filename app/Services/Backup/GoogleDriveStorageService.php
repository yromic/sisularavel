<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleDriveStorageService
{
    public const CHUNK_UNIT = 262144; // 256 KB minimum chunk unit mandated by Google Drive API

    public const TOKEN_CACHE_KEY = 'sisurat:backup:gdrive_access_token';

    /**
     * Check if Google Drive OAuth credentials are fully configured.
     */
    public function isConfigured(): bool
    {
        $cfg = config('backup.remote.google_drive', []);

        return ! empty($cfg['client_id'])
            && ! empty($cfg['client_secret'])
            && ! empty($cfg['refresh_token']);
    }

    /**
     * Obtain a valid OAuth 2.0 access token using the configured refresh token.
     * Caches the token until near expiration.
     */
    public function getAccessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (! empty($cached)) {
            return (string) $cached;
        }

        $cfg = config('backup.remote.google_drive', []);
        $clientId = $cfg['client_id'] ?? null;
        $clientSecret = $cfg['client_secret'] ?? null;
        $refreshToken = $cfg['refresh_token'] ?? null;

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            throw new RuntimeException('Google Drive OAuth is not configured. Missing client_id, client_secret, or refresh_token.');
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException('Google OAuth token request failed: '.$e->getMessage(), 0, $e);
        }

        if ($response->status() === 400 || $response->status() === 401) {
            $errorData = $response->json();
            $desc = $errorData['error_description'] ?? $errorData['error'] ?? 'Invalid or revoked refresh token.';
            throw new RuntimeException("Google Drive OAuth authentication failed [{$response->status()}]: {$desc}");
        }

        if (! $response->successful()) {
            throw new RuntimeException("Google Drive token endpoint returned HTTP {$response->status()}: {$response->body()}");
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if (empty($accessToken)) {
            throw new RuntimeException('Google OAuth response did not contain an access_token.');
        }

        // Cache token with 2-minute safety buffer
        $ttl = max(60, $expiresIn - 120);
        Cache::put(self::TOKEN_CACHE_KEY, $accessToken, $ttl);

        return $accessToken;
    }

    /**
     * Clear cached access token (used on 401 retry).
     */
    public function clearTokenCache(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    /**
     * Initiate a Google Drive Resumable Upload session.
     *
     * @return string The session URI returned by Google Drive API in the Location header
     */
    public function initiateResumableSession(
        string $filename,
        int $fileSizeBytes,
        ?string $mimeType = 'application/octet-stream',
        ?string $folderId = null,
        ?string $description = 'SISURAT Digital Archive File'
    ): string {
        $token = $this->getAccessToken();
        $targetFolder = $folderId ?? config('backup.remote.google_drive.folder_id');

        $metadata = [
            'name' => $filename,
            'description' => $description ?? 'SISURAT Digital Archive File',
        ];

        if (! empty($targetFolder)) {
            $metadata['parents'] = [$targetFolder];
        }

        $endpoint = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable';

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Upload-Content-Type' => $mimeType,
            'X-Upload-Content-Length' => (string) $fileSizeBytes,
        ])->post($endpoint, $metadata);

        if ($response->status() === 401) {
            // Token expired; clear cache and retry once
            $this->clearTokenCache();
            $token = $this->getAccessToken();
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Upload-Content-Type' => $mimeType,
                'X-Upload-Content-Length' => (string) $fileSizeBytes,
            ])->post($endpoint, $metadata);
        }

        if (! $response->successful()) {
            throw new RuntimeException("Failed to initiate Google Drive resumable session [HTTP {$response->status()}]: {$response->body()}");
        }

        $sessionUri = $response->header('Location');
        if (empty($sessionUri)) {
            throw new RuntimeException('Google Drive API did not return a session Location header.');
        }

        return $sessionUri;
    }

    /**
     * Upload an encrypted backup archive to Google Drive using chunked resumable upload.
     */
    public function uploadArchive(
        string $filePath,
        ?string $sessionUri = null,
        ?callable $progressCallback = null
    ): array {
        if (! File::exists($filePath)) {
            throw new RuntimeException("Local backup file not found for upload: {$filePath}");
        }

        $fileSize = File::size($filePath);
        $filename = basename($filePath);

        // Calculate chunk size, normalized to multiple of 256 KB
        $chunkSize = $this->normalizeChunkSize((int) config('backup.remote.google_drive.chunk_size_bytes', 1048576));

        if (empty($sessionUri)) {
            $sessionUri = $this->initiateResumableSession($filename, $fileSize);
        }

        $currentOffset = $this->querySessionUploadedOffset($sessionUri, $fileSize);

        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            throw new RuntimeException("Could not open file handle for reading: {$filePath}");
        }

        $maxRetries = (int) config('backup.remote.google_drive.max_retries', 3);
        $retryBaseMs = (int) config('backup.remote.google_drive.retry_base_ms', 100);

        try {
            while ($currentOffset < $fileSize) {
                fseek($handle, $currentOffset);
                $chunkBytesToRead = min($chunkSize, $fileSize - $currentOffset);
                $chunkData = fread($handle, $chunkBytesToRead);

                if ($chunkData === false || strlen($chunkData) === 0) {
                    throw new RuntimeException("Unexpected EOF while reading {$filePath} at offset {$currentOffset}.");
                }

                $chunkLen = strlen($chunkData);
                $chunkEnd = $currentOffset + $chunkLen - 1;
                $contentRange = "bytes {$currentOffset}-{$chunkEnd}/{$fileSize}";

                // Attempt chunk upload with retry
                $attempt = 0;
                $uploadedSuccessfully = false;
                $lastError = null;

                while ($attempt < $maxRetries && ! $uploadedSuccessfully) {
                    $attempt++;
                    try {
                        $response = Http::withHeaders([
                            'Content-Length' => (string) $chunkLen,
                            'Content-Range' => $contentRange,
                            'Content-Type' => 'application/octet-stream',
                        ])->withBody($chunkData, 'application/octet-stream')
                            ->put($sessionUri);

                        $status = $response->status();

                        if ($status === 308) {
                            // Chunk received; resume incomplete
                            $currentOffset = $chunkEnd + 1;
                            $uploadedSuccessfully = true;

                            if ($progressCallback !== null) {
                                $progressCallback($currentOffset, $fileSize);
                            }
                        } elseif ($status === 200 || $status === 201) {
                            // Final chunk uploaded; file completed!
                            $data = $response->json();
                            fclose($handle);

                            return [
                                'file_id' => $data['id'] ?? null,
                                'name' => $data['name'] ?? $filename,
                                'size_bytes' => (int) ($data['size'] ?? $fileSize),
                                'md5_checksum' => $data['md5Checksum'] ?? null,
                                'session_uri' => $sessionUri,
                            ];
                        } else {
                            $lastError = "Google Drive API returned HTTP {$status}: {$response->body()}";
                        }
                    } catch (Throwable $e) {
                        $lastError = $e->getMessage();
                    }

                    if (! $uploadedSuccessfully && $attempt < $maxRetries) {
                        // Exponential backoff
                        usleep($retryBaseMs * 1000 * (2 ** ($attempt - 1)));
                    }
                }

                if (! $uploadedSuccessfully) {
                    fclose($handle);
                    throw new RuntimeException("Chunk upload failed at byte {$currentOffset} after {$maxRetries} attempts: {$lastError}");
                }
            }

            fclose($handle);
            throw new RuntimeException('Upload loop terminated without receiving completed HTTP 200/201 response.');
        } catch (Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw $e;
        }
    }

    /**
     * Query session URI to determine how many bytes have already been received.
     */
    public function querySessionUploadedOffset(string $sessionUri, int $totalSizeBytes): int
    {
        try {
            $response = Http::withHeaders([
                'Content-Range' => "bytes */{$totalSizeBytes}",
            ])->put($sessionUri);

            if ($response->status() === 308) {
                $range = $response->header('Range');
                if (! empty($range) && preg_match('/bytes=0-(\d+)/', $range, $matches)) {
                    return (int) $matches[1] + 1;
                }
            }
        } catch (Throwable) {
            // Ignore failure, start from 0
        }

        return 0;
    }

    /**
     * Verify existence and content length of remote file on Google Drive.
     */
    public function verifyRemoteFile(string $fileId, int $expectedSizeBytes): array
    {
        $token = $this->getAccessToken();
        $endpoint = "https://www.googleapis.com/drive/v3/files/{$fileId}?fields=id,name,size,md5Checksum,createdTime,trashed";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])->get($endpoint);
        } catch (Throwable $e) {
            return [
                'verified' => false,
                'file_id' => $fileId,
                'error' => 'Connection failed: '.$e->getMessage(),
                'size_matches' => false,
            ];
        }

        if (! $response->successful()) {
            return [
                'verified' => false,
                'file_id' => $fileId,
                'error' => "HTTP {$response->status()}: {$response->body()}",
                'size_matches' => false,
            ];
        }

        $remote = $response->json();
        $remoteSize = (int) ($remote['size'] ?? 0);
        $isTrashed = (bool) ($remote['trashed'] ?? false);
        $sizeMatches = ($remoteSize === $expectedSizeBytes);

        return [
            'verified' => ! $isTrashed && $sizeMatches,
            'file_id' => $fileId,
            'name' => $remote['name'] ?? null,
            'remote_size' => $remoteSize,
            'expected_size' => $expectedSizeBytes,
            'size_matches' => $sizeMatches,
            'md5_checksum' => $remote['md5Checksum'] ?? null,
            'created_time' => $remote['createdTime'] ?? null,
            'trashed' => $isTrashed,
        ];
    }

    /**
     * Delete remote file from Google Drive (e.g. during retention cleanup).
     */
    public function deleteRemoteFile(string $fileId): bool
    {
        $token = $this->getAccessToken();
        $endpoint = "https://www.googleapis.com/drive/v3/files/{$fileId}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])->delete($endpoint);

            return $response->status() === 204 || $response->status() === 200;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Ensure a subfolder exists on Google Drive (finds or creates it) and caches its ID.
     */
    public function ensureFolder(string $folderName, ?string $parentFolderId = null): string
    {
        $parent = $parentFolderId ?? config('backup.remote.google_drive.folder_id');
        $cacheKey = 'sisurat:backup:gdrive_folder:'.md5($folderName.':'.($parent ?? 'root'));

        $cachedId = Cache::get($cacheKey);
        if (! empty($cachedId)) {
            return (string) $cachedId;
        }

        $existingId = $this->findFolderIdByName($folderName, $parent);
        if (! empty($existingId)) {
            Cache::put($cacheKey, $existingId, 86400);

            return $existingId;
        }

        $newId = $this->createFolder($folderName, $parent);
        Cache::put($cacheKey, $newId, 86400);

        return $newId;
    }

    /**
     * Search for a folder by name inside a parent folder.
     */
    public function findFolderIdByName(string $folderName, ?string $parentFolderId = null): ?string
    {
        $token = $this->getAccessToken();
        $endpoint = 'https://www.googleapis.com/drive/v3/files';

        $escapedName = str_replace("'", "\\'", $folderName);
        $q = "mimeType = 'application/vnd.google-apps.folder' and name = '{$escapedName}' and trashed = false";
        if (! empty($parentFolderId)) {
            $q .= " and '{$parentFolderId}' in parents";
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])->get($endpoint, [
                'q' => $q,
                'fields' => 'files(id, name)',
                'pageSize' => 1,
            ]);

            if ($response->successful()) {
                $files = $response->json('files', []);

                return ! empty($files) ? $files[0]['id'] : null;
            }
        } catch (Throwable) {
            // Fail open, return null to attempt creation
        }

        return null;
    }

    /**
     * Create a new folder on Google Drive.
     */
    public function createFolder(string $folderName, ?string $parentFolderId = null): string
    {
        $token = $this->getAccessToken();
        $endpoint = 'https://www.googleapis.com/drive/v3/files';

        $metadata = [
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];

        if (! empty($parentFolderId)) {
            $metadata['parents'] = [$parentFolderId];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ])->post($endpoint, $metadata);

        if (! $response->successful()) {
            throw new RuntimeException("Failed to create Google Drive folder [{$folderName}]: {$response->body()}");
        }

        $folderId = $response->json('id');
        if (empty($folderId)) {
            throw new RuntimeException("Google Drive did not return an ID for created folder [{$folderName}].");
        }

        return $folderId;
    }

    /**
     * Upload an individual document or image to Google Drive and return view URL.
     */
    public function uploadSingleFile(
        string $filePath,
        string $filename,
        ?string $folderId = null,
        ?string $mimeType = 'application/octet-stream',
        bool $makePublic = true
    ): array {
        if (! File::exists($filePath)) {
            throw new RuntimeException("Local file not found for upload: {$filePath}");
        }

        $fileSize = File::size($filePath);
        $sessionUri = $this->initiateResumableSession(
            $filename,
            $fileSize,
            $mimeType,
            $folderId,
            'SISURAT Human-Readable Document File'
        );

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new RuntimeException("Unable to read local file contents: {$filePath}");
        }

        $response = Http::withHeaders([
            'Content-Length' => (string) $fileSize,
            'Content-Range' => 'bytes 0-'.($fileSize > 0 ? $fileSize - 1 : 0)."/{$fileSize}",
            'Content-Type' => $mimeType ?: 'application/octet-stream',
        ])->withBody($contents, $mimeType ?: 'application/octet-stream')->put($sessionUri);

        if (! $response->successful()) {
            throw new RuntimeException("Failed to upload single file [{$filename}] to Google Drive: {$response->body()}");
        }

        $data = $response->json();
        $fileId = $data['id'] ?? null;

        if (empty($fileId)) {
            throw new RuntimeException("Google Drive did not return a valid file ID for {$filename}.");
        }

        if ($makePublic) {
            $this->setFilePublicPermission($fileId);
        }

        return [
            'file_id' => $fileId,
            'name' => $data['name'] ?? $filename,
            'size_bytes' => (int) ($data['size'] ?? $fileSize),
            'view_url' => $this->getFileViewUrl($fileId),
            'md5_checksum' => $data['md5Checksum'] ?? null,
        ];
    }

    /**
     * Grant 'reader' permission to 'anyone' with the link on Google Drive.
     */
    public function setFilePublicPermission(string $fileId): bool
    {
        $token = $this->getAccessToken();
        $endpoint = "https://www.googleapis.com/drive/v3/files/{$fileId}/permissions";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'role' => 'reader',
                'type' => 'anyone',
            ]);

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Generate standard Google Drive preview/view URL.
     */
    public function getFileViewUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/view";
    }

    /**
     * Normalize chunk size to a positive multiple of 256 KB.
     */
    protected function normalizeChunkSize(int $bytes): int
    {
        if ($bytes <= self::CHUNK_UNIT) {
            return self::CHUNK_UNIT;
        }

        return (int) (round($bytes / self::CHUNK_UNIT) * self::CHUNK_UNIT);
    }
}
