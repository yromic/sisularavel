# WORKFLOW SCENARIOS

> [!NOTE]
> This document details end-to-end execution scenarios illustrating normal operations, security violations, server-side idempotency, password activation, missing legacy attachment handling, and transactional rollback behaviors (v1.3.0).

---

## Scenario 1: Standard Incoming Letter Creation with Idempotency & Attachment

### Narrative
Operator `op_dikdas01` receives an official paper letter from the Regional Civil Service Agency. The operator logs in, navigates to the Incoming Letters tab, uploads the scanned PDF, attaches an `Idempotency-Key` UUIDv4, and saves the record.

### HTTP & Execution Trace
1. **Request:**
   ```http
   POST /api/v1/surat-masuk HTTP/1.1
   Host: sisurat.example.go.id
   Cookie: sisurat_session=eyJpdiI6...
   Idempotency-Key: e6a42211-f187-4b19-94ae-34a055ff70b2
   Content-Type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW

   ------WebKitFormBoundary7MA4YWxkTrZu0gW
   Content-Disposition: form-data; name="nomor_surat"

   421.2/085/DISDIK/2026
   ------WebKitFormBoundary7MA4YWxkTrZu0gW
   Content-Disposition: form-data; name="asal_surat"

   Badan Kepegawaian Daerah
   ------WebKitFormBoundary7MA4YWxkTrZu0gW
   Content-Disposition: form-data; name="tanggal_surat"

   2026-02-14
   ------WebKitFormBoundary7MA4YWxkTrZu0gW
   Content-Disposition: form-data; name="tanggal_diterima"

   2026-02-16
   ------WebKitFormBoundary7MA4YWxkTrZu0gW
   Content-Disposition: form-data; name="perihal"

   Undangan Sosialisasi Penilaian Kinerja Guru SD
   ------WebKitFormBoundary7MA4YWxkTrZu0gW
   Content-Disposition: form-data; name="file"; filename="scan_surat.pdf"
   Content-Type: application/pdf

   %PDF-1.4 ...binary data...
   ------WebKitFormBoundary7MA4YWxkTrZu0gW--
   ```

2. **Server-Side Operations:**
   - Auth middleware confirms session belongs to user ID `101`, active division ID `1` (`DIKDAS`).
   - `IdempotencyMiddleware` verifies key `e6a42211-f187-4b19-94ae-34a055ff70b2` is not already recorded for user `101`; reserves key in `idempotency_keys` table with status `in_progress`.
   - `StoreIncomingLetterRequest` validates inputs: `tanggal_diterima` >= `tanggal_surat`, MIME is `application/pdf`, size is 1.5MB (< 10MB).
   - Duplicate advisory check checks `(division_id=1, asal_surat='Badan Kepegawaian Daerah', nomor_surat='421.2/085/DISDIK/2026', tanggal_surat='2026-02-14')`. No collision detected.
   - `LetterService` begins database transaction:
     - Generates UUID `7c9e6679-7425-40de-944b-e07fc1f90ae7`.
     - Writes stream to `storage/app/private/documents/DIKDAS/2026/02/{uuid}.pdf`.
     - Inserts row into `incoming_letters` table (PK `id = 5001` is authoritative).
     - Inserts row into `attachments` table.
     - Updates `idempotency_keys` status to `completed` with cached HTTP 201 response.
     - Inserts audit row with `event_code = 'LETTER_CREATED'`.
     - Commits database transaction.

3. **Response:**
   ```http
   HTTP/1.1 201 Created
   Content-Type: application/json

   {
     "success": true,
     "message": "Surat masuk berhasil disimpan.",
     "data": {
       "id": 5001,
       "nomor_surat": "421.2/085/DISDIK/2026",
       "perihal": "Undangan Sosialisasi Penilaian Kinerja Guru SD",
       "attachment": {
         "uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
         "original_name": "scan_surat.pdf",
         "file_size_bytes": 1548291
       }
     }
   }
   ```

---

## Scenario 2: Cross-Division Unauthorized Access Attempt

### Narrative
An operator assigned to `DIKDAS` attempts to inspect an attachment belonging to division `PAUD` by directly tampering with the URL identifier.

### HTTP & Execution Trace
1. **Request:**
   ```http
   GET /api/v1/attachments/3a81f9b2-11c4-42b1-b999-52e80091aa01/preview HTTP/1.1
   Host: sisurat.example.go.id
   Cookie: sisurat_session=eyJpdiI6... (Authenticated as DIKDAS operator)
   ```

2. **Server-Side Operations:**
   - Controller queries `Attachment::where('uuid', $uuid)->firstOrFail()`.
   - Attachment record found; belongs to `division_id = 2` (`PAUD`).
   - `AttachmentPolicy::view()` compares user active division (`1` - `DIKDAS`) with attachment division (`2` - `PAUD`).
   - Policy denies access: user is neither `super_admin` nor member of `PAUD`. In SISURAT v1, single-division ownership is strictly enforced.
   - Controller immediately writes audit log:
     - `event_code = 'SEC_CROSS_DIVISION_BLOCKED'`
     - `description = 'Unauthorized preview attempt on PAUD attachment by DIKDAS user'`
     - `ip_address = '192.168.1.55'`
   - Controller aborts with HTTP 403 Forbidden.

3. **Response:**
   ```http
   HTTP/1.1 403 Forbidden
   Content-Type: application/json

   {
     "success": false,
     "code": "ERR_403_DIVISI",
     "message": "Anda tidak memiliki izin untuk mengakses data divisi ini.",
     "errors": {},
     "timestamp": "2026-09-18T12:30:00Z"
   }
   ```

---

## Scenario 3: Transactional Rollback on Disk Storage Failure

### Narrative
An operator submits a letter, but during disk writing the server storage volume encounters an I/O error or quota exhaustion.

### HTTP & Execution Trace
1. **Server-Side Operations:**
   - Transaction initiated (`DB::beginTransaction()`).
   - `IncomingLetter::create()` successfully generates row ID `5002`.
   - `Storage::disk('private_documents')->putStream()` throws `StorageException` (Disk full).
   - Exception caught in `LetterService`:
     - Calls `DB::rollBack()`: row ID `5002` is completely discarded.
     - Releases or marks `idempotency_keys` entry as failed so user can retry.
     - Cleans up any partially written temp files.
     - Logs critical error with exception message to `storage/logs/laravel.log`.
   - Controller returns HTTP 500.

2. **Response:**
   ```http
   HTTP/1.1 500 Internal Server Error
   Content-Type: application/json

   {
     "success": false,
     "code": "ERR_500_STORAGE_FAIL",
     "message": "Terjadi kesalahan saat menyimpan berkas ke server.",
     "errors": {},
     "timestamp": "2026-09-18T12:35:10Z"
   }
   ```
   *Result:* Database remains clean; zero orphan records created.

---

## Scenario 4: Server-Side Idempotency Replay & Conflict

### Narrative
Due to high mobile network latency, an operator's mobile client resends the creation request with the same `Idempotency-Key`.

### HTTP & Execution Trace
1. **First Request:** Processed normally as in Scenario 1; HTTP 201 response cached in `idempotency_keys`.
2. **Duplicate Request (Same Payload, Same Key):**
   - `IdempotencyMiddleware` intercepts request.
   - Queries `idempotency_keys` where `key = 'e6a42211-f187-4b19-94ae-34a055ff70b2'` and `user_id = 101`.
   - Confirms matching payload hash and status `completed`.
   - Replays cached HTTP 201 response without executing application services or touching database/storage.
3. **Mismatched Request (Different Payload, Same Key):**
   - If a client sends an altered payload using the same key, middleware returns HTTP 409 Conflict with code `ERR_409_IDEMPOTENCY_CONFLICT`.

---

## Scenario 5: Migrated User Login & Password Activation via Admin-Issued Secret (Path B)

### Narrative
A legacy user `operator_lama` was migrated from Google Sheets. Legacy salted SHA-256 password was rejected. The user has `must_reset_password = true` and no valid email address.

### HTTP & Execution Trace
1. **Admin Issues Temporary Activation Secret:**
   - Division Admin accesses `/admin/users/42/activation-secret`.
   - Backend generates secure random secret `T3mp-9821-XqZa`, hashes it with Bcrypt, updates user password hash, and logs `ADMIN_ISSUED_ACTIVATION_CREDENTIAL` (without secret).
   - Admin delivers secret in person to operator.
2. **User Attempts Login with Temporary Secret:**
   - Operator submits `POST /login` with username and temporary secret.
   - Backend verifies Bcrypt hash.
   - Backend detects `must_reset_password == true`.
   - Backend denies full session access; issues temporary restricted activation token and returns HTTP 403 redirecting to `/password/activate`.
3. **User Establishes New Permanent Password:**
   - User submits `POST /password/activate` with new 14-character password.
   - Backend updates user record: `password = Hash::make(new)`, `must_reset_password = false`.
   - Backend logs `ACCOUNT_ACTIVATED_VIA_ADMIN_SECRET`.
   - Full authenticated session is granted; user redirected to `/dashboard`.

---

## Scenario 6: Legacy Letter with Missing Drive Attachment (Durable Handling)

### Narrative
During migration, legacy letter ID `1044` referenced Google Drive file `1xAbC...` which returned HTTP 404. The migration preserved the letter and created an attachment with `is_missing = true`.

### HTTP & Execution Trace
1. **Letter Detail Rendering:**
   - Operator views `/surat-masuk/1044`.
   - View displays letter metadata correctly.
   - Attachment section displays amber badge: *"Lampiran Berkas Warisan Tidak Tersedia di Google Drive"*.
   - Download button shows disabled state or informational link with warning icon.
2. **Direct API Download Attempt:**
   - Client sends `GET /api/v1/attachments/d912b48e-.../download`.
   - Controller verifies user's division authority.
   - Controller inspects `attachment.is_missing == true`.
   - Controller records audit log `LEGACY_ATTACHMENT_UNAVAILABLE` and returns HTTP 404:
     ```json
     {
       "success": false,
       "code": "ERR_404_ATTACHMENT_MISSING",
       "message": "Berkas lampiran warisan tidak ditemukan di Google Drive saat migrasi.",
       "legacy_url": "https://drive.google.com/file/d/1xAbC.../view"
     }
     ```
   *Result:* Application does not throw unhandled exception; letter metadata remains fully accessible.
