# 14. ERROR HANDLING CONTRACT

## 1. Scope & Objective

In legacy SISURAT, error handling was dispersed inconsistently across Google Apps Script functions, returning arbitrary JSON strings (`_errorResponse("ERR_401_SESSION")`, `{ status: "error", message: "..." }`) or unhandled JavaScript exceptions that surfaced Google runtime stack traces.

This contract defines a unified, predictable error-handling architecture for the Laravel target system (initial baseline: Laravel ^12.0 / PHP ^8.2). All API and web endpoints must adhere to consistent HTTP status codes, structured JSON error envelopes, localized error messaging, and secure internal logging without information disclosure.

---

## 2. Standardized Error Response Envelope

All error responses returned by the application (API requests or AJAX requests expecting JSON) must conform to the following schema:

```json
{
  "success": false,
  "code": "ERR_422_VALIDATION",
  "message": "Data yang dikirimkan tidak valid.",
  "errors": {
    "nomor_surat": [
      "Nomor surat keluar sudah terdaftar pada divisi dan tahun ini."
    ]
  },
  "timestamp": "2026-09-18T12:00:00Z"
}
```

- `success` (Boolean): Always `false`.
- `code` (String): Machine-readable standardized error code (prefixed with `ERR_`).
- `message` (String): Safe, human-readable localized explanation suitable for direct presentation to end users in UI modals or alert banners.
- `errors` (Object): Field-specific validation violation messages (populated primarily for HTTP 422).
- `timestamp` (String): ISO 8601 UTC timestamp.

---

## 3. Standard Error Code Registry

| Error Code | HTTP Status | Trigger Condition | Safe User Message | Internal Log Action |
| :--- | :--- | :--- | :--- | :--- |
| `ERR_401_SESSION` | 401 Unauthorized | Missing, expired, or invalid session token | Sesi Anda telah berakhir. Silakan login kembali. | Log auth check miss |
| `ERR_403_SCOPE` | 403 Forbidden | User lacks required role permission (e.g. Operator attempting edit) | Anda tidak memiliki wewenang untuk melakukan tindakan ini. | Log warning with user/role |
| `ERR_403_DIVISI` | 403 Forbidden | Attempt to read/write records belonging to another division | Anda tidak memiliki izin untuk mengakses data divisi ini. | Write `SEC_CROSS_DIVISION_BLOCKED` to audit log |
| `ERR_400_DIVISI_REQUIRED` | 400 Bad Request | Super Admin attempted division operation without specifying active division | Silakan pilih divisi tujuan terlebih dahulu. | Notice log |
| `ERR_404_RECORD` | 404 Not Found | Requested entity ID does not exist or is soft-deleted | Data yang Anda cari tidak ditemukan. | None |
| `ERR_404_ATTACHMENT` | 404 Not Found | Attachment metadata exists but file is missing or inaccessible in storage | Berkas lampiran tidak tersedia pada penyimpanan server. | Alert logger: physical file missing |
| `ERR_409_DUPLICATE_NUMBER` | 409 Conflict | Outgoing letter number already registered in division and year | Nomor surat keluar sudah terdaftar pada divisi dan tahun ini. | Informational log |
| `ERR_409_IDEMPOTENCY_CONFLICT` | 409 Conflict | Same Idempotency-Key reused with an altered request payload | Idempotency key telah digunakan untuk permintaan yang berbeda. | Warning log with key/fingerprint |
| `ERR_413_FILE_SIZE` | 413 Payload Too Large | Uploaded attachment exceeds maximum limit (10MB) | Ukuran berkas melebihi batas maksimum 10 MB. | Informational log |
| `ERR_415_FILE_TYPE` | 415 Unsupported Media Type | File MIME type is not among whitelisted formats | Format berkas tidak didukung. Harap unggah berkas PDF, Word, atau Gambar. | Warning log |
| `ERR_422_VALIDATION` | 422 Unprocessable Content | Request payload failed FormRequest validation rules | Data yang dikirim tidak memenuhi persyaratan validasi. | None |
| `ERR_429_RATE_LIMIT` | 429 Too Many Requests | Rate limit exceeded (login attempts or public claims) | Terlalu banyak permintaan. Silakan tunggu beberapa saat. | Write `SEC_RATE_LIMIT_EXCEEDED` to audit log |
| `ERR_500_STORAGE_FAIL` | 500 Internal Server Error | Local private disk failed write/unlink operation | Terjadi kesalahan saat menyimpan berkas ke server. | Critical error logger with exception |
| `ERR_500_SERVER` | 500 Internal Server Error | Unhandled runtime exception, database timeout | Terjadi gangguan internal pada server. Silakan hubungi administrator. | Critical error logger with full stack trace |

---

## 4. Implementation Rules & Global Exception Handler

1. **Bootstrap / Handler Integration (`bootstrap/app.php` in Laravel 11/13):**
   - Register custom exception rendering closures:
     ```php
     $exceptions->render(function (ValidationException $e, Request $request) {
         if ($request->expectsJson()) {
             return response()->json([
                 'success' => false,
                 'code' => 'ERR_422_VALIDATION',
                 'message' => 'Data yang dikirimkan tidak valid.',
                 'errors' => $e->errors(),
                 'timestamp' => now()->toIso8601String(),
             ], 422);
         }
     });

     $exceptions->render(function (AuthorizationException $e, Request $request) {
         if ($request->expectsJson()) {
             return response()->json([
                 'success' => false,
                 'code' => 'ERR_403_SCOPE',
                 'message' => 'Anda tidak memiliki wewenang untuk melakukan tindakan ini.',
                 'errors' => (object)[],
                 'timestamp' => now()->toIso8601String(),
             ], 403);
         }
     });
     ```
2. **Never Expose Internal Details in Production:**
   - Database connection strings, SQL queries, table names, file paths, and stack traces must never appear in `message` or `errors` when `APP_DEBUG=false`.
3. **Idempotency & Retry Safety:**
   - Server-side idempotency middleware evaluates `Idempotency-Key` headers on `POST` requests, safely replaying responses on network drops and returning `ERR_409_IDEMPOTENCY_CONFLICT` on payload divergence.
