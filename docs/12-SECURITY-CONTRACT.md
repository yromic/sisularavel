# 12. SECURITY CONTRACT

## 1. Scope & Core Directives

Legacy SISURAT exhibited critical security vulnerabilities resulting from Google Apps Script architecture:
1. `file.setSharing(ANYONE_WITH_LINK, VIEW)` exposed internal government documents publicly.
2. Origin checks inspected `params.origin` inside the JSON body rather than the trusted HTTP `Origin` header.
3. Row-number targeting in user management allowed race conditions and out-of-order mutations.
4. Passwords in legacy Sheets were stored in plain text or custom salted SHA-256 strings.
5. Fictitious multiplier curves were used in analytics rather than true aggregate counts.

The Laravel target system (initial baseline: Laravel ^12.0 / PHP ^8.2) must enforce an uncompromising, zero-trust security architecture. Under no circumstances may insecure legacy patterns be carried forward for convenience or parity.

---

## 2. Authentication & Credential Security (DEC-AUTH-001)

### 2.1 Password Hashing & Encryption Standard
- **Target Standard:** Passwords must be hashed using PHP's native `password_hash()` with **Bcrypt** (cost factor 12) or **Argon2id** (`PASSWORD_ARGON2ID`).
- **Rejection of Insecure Legacy Credentials:**
  - In `db_users`, legacy passwords exist as plain text, v1 SHA-256 with salt, or marked `__MIGRATED__`.
  - **The target system MUST NOT migrate legacy plaintext passwords, reproduce custom salted SHA-256 verification, or accept weak legacy hashes for transitional login.** The target security contract explicitly rejects insecure legacy credential formats.
  - Every migrated account must be assigned an unguessable randomized hash (e.g., `Hash::make(Str::random(40))`) and flagged with `must_reset_password = true`.

### 2.2 Controlled Account Activation & Reset Lifecycle
To activate an account or establish a new password, the system provides two distinct operational paths:

#### Path A: Verified Email Available
1. A user or administrator requests a password reset.
2. The system generates a cryptographically secure 64-character random token, stores its SHA-256 hash in `password_reset_token` with an expiration timestamp (`password_reset_expires_at` = 24 hours), and emails a one-time link.
3. The reset endpoint verifies the token hash, enforces password complexity, sets the new Bcrypt password, clears the token, and resets `must_reset_password = false`.
4. Password reset requests always return a generic response (*"Jika akun terdaftar, tautan reset telah dikirimkan"*) to mitigate user enumeration attacks.

#### Path B: No Reliable Email Available (Controlled Administrative Activation)
1. An authorized administrator (Super Admin globally, or Division Admin for users within their own division) initiates an activation/reset for a specific account.
2. The system generates a single-use temporary activation secret with a 24-hour expiration threshold.
3. The temporary secret is displayed once to the initiating administrator and delivered to the user through an approved out-of-band organizational channel (e.g. formal administrative dispatch).
4. The temporary secret is **never displayed again** and is never logged.
5. Upon first successful authentication using the temporary secret, the system forces the user to set a new permanent password immediately before granting access to application features.
6. Bulk distribution of permanent passwords is strictly prohibited. All reset and activation events are logged in `audit_logs` without logging secret or token values.

### 2.3 Session Security
- **Session Driver:** `database` or encrypted cookie session storage.
- **Session Cookie Flags:**
  - `HttpOnly = true` (Prevents JavaScript / XSS access to session tokens).
  - `Secure = true` (Enforces HTTPS transmission in production).
  - `SameSite = 'Lax'` (or `'Strict'` to thwart Cross-Site Request Forgery).
- **Session Lifecycle:**
  - Regenerate session ID upon every successful authentication (`$request->session()->regenerate()`) to prevent session fixation attacks.
  - Invalidate session upon logout (`$request->session()->invalidate()`).
  - Strict inactivity timeout: Default 120 minutes (`SESSION_LIFETIME=120`).

### 2.4 Brute-Force & Rate Limiting
- **Login Endpoint:** Rate-limited to a maximum of 5 attempts per minute per IP/username tuple using Laravel's `RateLimiter`. Exceeding this threshold triggers HTTP 429 Too Many Requests with localized message: *"Terlalu banyak percobaan login. Silakan tunggu 15 menit."*
- **Public Piagam Endpoints:** Rate-limited to 10 submissions per minute per IP to prevent automated submission attacks against certificate claim forms.

---

## 3. Server-Side Idempotency & Replay Defense (DEC-SYS-001)

1. **Mandatory Server-Side Protection:**
   - Client-side submit button disabling (`UI-006`) is an ergonomics control; it does NOT provide an architectural data integrity guarantee.
   - The backend MUST enforce server-side duplicate submission protection for all high-risk create mutations (`POST /api/v1/surat-masuk`, `POST /api/v1/surat-keluar`, `POST /api/v1/piagam`, `POST /api/v1/piagam/ambil`).
2. **Idempotency Architecture (`idempotency_keys` table):**
   - The client transmits a unique UUIDv4 token in the `Idempotency-Key` HTTP header.
   - The server hashes the HTTP method, route, and normalized JSON body (`SHA-256`).
   - If an active unexpired key matches the exact request fingerprint: the server replays the cached status code and response body without re-executing database inserts.
   - If an active key is submitted with a different request fingerprint: the server rejects the request with HTTP 409 Conflict (`ERR_409_IDEMPOTENCY_CONFLICT`: *"Idempotency key telah digunakan untuk permintaan berbeda"*).
   - Keys expire automatically after 24 hours and are pruned via a scheduled task.

---

## 4. Authorization & Single-Division Ownership Architecture

### 4.1 Strict Server-Side Verification
- **Principle:** Never trust client-provided state or parameters. Client UI hiding is strictly a presentation convenience, never a security boundary.
- **Form Injection Prevention:** Any request submitting `division_id` must be validated against the authenticated user's assigned division. Unless the actor possesses global Super Admin authority, an attempt to mutate a record with a mismatched `division_id` must be terminated immediately with HTTP 403 Forbidden (`ERR_403_DIVISI`).
- **Single-Division Ownership in v1 (DEC-TEN-001):** Each document has exactly one immutable `division_id`. Inter-division routing, transfer, or disposition is classified as a DEFERRED FUTURE FEATURE and is strictly blocked in v1. Attempts to tamper with ownership via PUT/PATCH payloads are rejected.

### 4.2 Eloquent Policies & Scopes
- Every domain model (`IncomingLetter`, `OutgoingLetter`, `Piagam`, `User`, `Attachment`) must have a corresponding Laravel Policy (`LetterPolicy`, `PiagamPolicy`, etc.).
- Resource routes must invoke policy authorization in controller methods:
  ```php
  $this->authorize('view', $incomingLetter);
  $this->authorize('update', $incomingLetter);
  $this->authorize('delete', $incomingLetter);
  ```

---

## 5. Input Sanitization & Attack Mitigation

### 5.1 SQL Injection Mitigation
- Direct string concatenation in SQL queries is strictly prohibited. All queries must use Eloquent ORM or parameterized PDO query builders.

### 5.2 Cross-Site Scripting (XSS) Prevention
- All user-supplied data rendered in Blade templates must use automatic HTML entity encoding: `{{ $record->perihal }}`. Unescaped rendering `{!! $data !!}` is strictly forbidden.

### 5.3 Cross-Site Request Forgery (CSRF) Protection
- All state-changing HTTP requests (`POST`, `PUT`, `PATCH`, `DELETE`) within the web middleware group must be validated by `ValidateCsrfToken` middleware.

### 5.4 Mass Assignment Protection
- All Eloquent models must explicitly define `$fillable` attributes. Sensitive columns (`id`, `role`, `password`, `division_id`, `created_by_user_id`) must never be mass-assignable without explicit authorization.

### 5.5 CSV / Excel Formula Injection (CSV Injection)
- During spreadsheet/CSV export, any cell value beginning with `=`, `+`, `-`, `@`, tab (`\t`), or carriage return (`\r`) must be prepended with a single quote (`'`) to force text interpretation.

---

## 6. File Upload & Private Storage Security

1. **Server-Side MIME Verification:** Validate MIME type on the server using PHP `finfo_file()`. Client-supplied MIME types and file extensions are never trusted.
2. **Whitelisted Extensions:** Only `.pdf`, `.doc`, `.docx`, `.xls`, `.xlsx`, `.jpg`, `.jpeg`, `.png` are accepted. Executable extensions (`.exe`, `.php`, `.sh`, `.bat`) are rejected immediately.
3. **Private File Access:** Attachments are written outside the web root (`storage/app/private/`). Streaming controllers enforce division-scoping authorization before reading file streams.
4. **Missing Attachment Resilience:** Files missing from Google Drive (404/deleted) are marked `is_missing = 1` in the database. Download requests return a safe HTTP 404 envelope (`ERR_404_ATTACHMENT`) without revealing filesystem paths or throwing 500 exceptions.

---

## 7. Information Leakage & Audit Integrity

1. **Production Error Masking:** In production (`APP_ENV=production`), `APP_DEBUG` must be `false`. Database errors and stack traces are never exposed to clients.
2. **Log Redaction:** Sensitive parameters (`password`, `password_reset_token`, `token`, `authorization`) must be redacted from application logs.
3. **Audit Log Immutability:** Audit logs (`audit_logs` table) are append-only. No application routes or controllers may update or delete audit records.
