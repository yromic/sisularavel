# 03 — Feature Catalog & Functional Specifications

> **Requirement Prefix:** `FUN`  
> **Status:** RATIFIED BASELINE  
> **Target Audience:** System Architects, Developers, QA Engineers, Product Owners  

---

## Feature Index

| Feature ID | Feature Name | Domain | Primary Actors | Target Decision |
|---|---|---|---|---|
| `FUN-AUTH-01` | User Login & Authentication | Auth | All authenticated personas | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-AUTH-02` | User Logout & Invalidation | Auth | All authenticated personas | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-AUTH-03` | Session Verification & Sliding Refresh | Auth | All authenticated personas | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-TEN-01` | Super Admin Division Context Switch | Multi-Tenancy | Super Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-TEN-02` | Division Provisioning (Tenant Creation)| Multi-Tenancy | Super Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-TEN-03` | Division Deactivation | Multi-Tenancy | Super Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-TEN-04` | Division Hard Deletion & Teardown | Multi-Tenancy | Super Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SM-01` | Surat Masuk Retrieval & Pagination | Persuratan | Super Admin, Division Admin, Operator | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SM-02` | Surat Masuk Registration & Upload | Persuratan | Super Admin, Division Admin, Operator | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SM-03` | Surat Masuk Update & Attachment Replace| Persuratan | Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SM-04` | Surat Masuk Soft Delete & Restoration | Persuratan | Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SK-01` | Surat Keluar Retrieval & Pagination | Persuratan | Super Admin, Division Admin, Operator | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SK-02` | Surat Keluar Registration & Upload | Persuratan | Super Admin, Division Admin, Operator | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SK-03` | Surat Keluar Update & Attachment Replace| Persuratan | Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SK-04` | Surat Keluar Soft Delete & Restoration | Persuratan | Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-PG-01` | Piagam Administration (Internal CRUD) | Piagam | Super Admin, Division Admin, Operator | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-PG-02` | Public Piagam Submission & Canvas Sign | Piagam | Public / School Representative | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SRCH-01`| Unified Cross-Table Smart Search | Search | Super Admin, Division Admin, Operator | `REPLACE_WITH_NEW_BEHAVIOR` |
| `FUN-REP-01` | Monthly Recap & Multi-Filter Reporting | Reporting | Super Admin, Division Admin, Operator | `REPLACE_WITH_NEW_BEHAVIOR` |
| `FUN-REP-02` | Document Export (CSV & PDF Generation) | Reporting | Super Admin, Division Admin, Operator | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-DASH-01`| Analytics Dashboard & Metric Counters | Analytics | Super Admin, Division Admin, Operator | `REPLACE_WITH_NEW_BEHAVIOR` |
| `FUN-REF-01` | National Schools Registry (NPSN) CRUD | Master Data | Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-REF-02` | Division Reference Lists (Pengambilan/Jenis)| Master Data | Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-USER-01`| User Management & Role Assignment | Administration| Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-USER-02`| Administrative Password Reset | Administration| Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-SET-01` | Global Agency & Application Settings | Administration| Super Admin | `PRESERVE_WITH_IMPROVEMENT` |
| `FUN-FILE-01`| Private Document Streaming & Download | Storage | Super Admin, Division Admin, Operator | `REPLACE_WITH_NEW_BEHAVIOR` |
| `FUN-AUD-01` | Audit Log Query & Security Inspection | Audit | Super Admin, Division Admin | `PRESERVE_WITH_IMPROVEMENT` |

---

## Detailed Functional Specifications

### FUN-AUTH-01: User Login & Authentication
* **Primary Actors:** `super_admin`, `admin_divisi`, `operator`.
* **Purpose:** Authenticate administrative users into the system and grant appropriate division and role permissions.
* **Preconditions:** User account exists in `users` table with `is_active = true`. If assigned to a division, the division must have `status = 'active'`.
* **Trigger:** User submits username and password on `/login`.
* **Primary Flow:**
  1. System checks brute-force rate limit (max 5 failed attempts per IP/username in 15 minutes).
  2. System looks up user by `username`.
  3. System verifies password using Laravel `Hash::check()` (Bcrypt).
  4. System establishes an authenticated session, generating a sliding expiration token.
  5. System writes an audit log event (`login_success`).
  6. User is redirected to `/dashboard`.
* **Alternative Flow (Migrated Account Activation):** If user account has `must_reset_password = true`, direct authentication using legacy credentials is strictly blocked (legacy plaintext and salted SHA-256 formats are rejected). The user is directed to the Account Activation workflow via Path A (Email Reset Token) or Path B (Administrative One-Time Secret).
* **Failure Flow:**
  - Account inactive: System rejects with error `ERR_403_ACCOUNT_INACTIVE`.
  - Division inactive: System rejects with error `ERR_403_DIVISION_INACTIVE`.
  - Must reset password: System rejects standard login with `ERR_403_PASSWORD_RESET_REQUIRED` and redirects to `/password/activate`.
  - Invalid credentials: System logs failed attempt, increments throttle counter, and returns `ERR_401_INVALID_CREDENTIALS`.
* **Data Read:** `users`, `divisions`.
* **Data Written:** `audit_logs`, session state.
* **File Interaction:** None.
* **Authorization:** Public endpoint (`guest` middleware). Rate-limited.
* **Audit Events:** `LOGIN_SUCCESS`, `LOGIN_FAILED`, `LOGIN_BLOCKED`, `PASSWORD_RESET_REQUIRED`.
* **Legacy Evidence:** `backend/02_Auth.js:L227-L309` (`cekLogin`).
* **Acceptance IDs:** `TEST-AUTH-01`, `TEST-AUTH-02`, `TEST-AUTH-06`.

---

### FUN-TEN-01: Super Admin Division Context Switch
* **Primary Actors:** `super_admin`.
* **Purpose:** Enable Super Admin to view, enter, and manipulate records belonging to any specific division without logging out.
* **Preconditions:** User is authenticated with `role = 'super_admin'`. Target division exists with `status = 'active'`.
* **Trigger:** Super Admin selects a division in the sidebar dropdown switcher.
* **Primary Flow:**
  1. Super Admin selects target division code (e.g. `DIKDAS`).
  2. Client sends POST request to `/admin/switch-division` with `division_id`.
  3. Controller validates that division exists and is active.
  4. Controller sets `active_division_id` in Super Admin's session.
  5. Controller returns success; UI updates badge to display active division.
* **Failure Flow:** If division does not exist or is inactive, returns `ERR_404_DIVISION_NOT_FOUND` or `ERR_403_DIVISION_INACTIVE`.
* **Data Read:** `divisions`.
* **Data Written:** Session state.
* **Authorization:** Strictly restricted to `super_admin` (`RoleMiddleware:super_admin`).
* **Audit Events:** `DIVISION_SWITCHED`.
* **Legacy Evidence:** `js/auth.js:L48-L83` (`SisuratDivision`), `backend/11_DivisionService.js:L410-L418`.
* **Acceptance IDs:** `TEST-TEN-01`.

---

### FUN-SM-02: Surat Masuk Registration & Upload
* **Primary Actors:** `super_admin`, `admin_divisi`, `operator`.
* **Purpose:** Register incoming formal mail received by the division and attach digital scans.
* **Preconditions:** User is authenticated. Active division context is established.
* **Trigger:** User submits "Tambah Surat Masuk" form on `/letters/incoming`.
* **Primary Flow:**
  1. Client attaches UUIDv4 in `Idempotency-Key` header.
  2. System verifies `Idempotency-Key` via middleware (rejecting concurrent requests, returning cached response on exact re-submission, returning `ERR_409_IDEMPOTENCY_CONFLICT` on mismatched payload).
  3. System validates inputs: `nomor_surat`, `asal_surat`, `perihal`, `tanggal_surat`, `received_date`, attachment file.
  4. System checks for potential duplicate incoming letters: query `(division_id, asal_surat, nomor_surat, tanggal_surat)`. If matching record exists, system flags advisory warning `WARN_POTENTIAL_DUPLICATE_LETTER` (user confirmation required, but registration is permitted; incoming letters have no unique constraint).
  5. File is validated: MIME type `application/pdf`, `image/jpeg`, `image/png`; size $\le$ 10 MB.
  6. System opens `DB::transaction()`.
  7. System inserts record into `incoming_letters` with `division_id`, `uuid`, and `created_by_user_id`.
  8. System stores binary attachment on private disk (`storage/app/private/attachments/{division_id}/`) with generated UUID name.
  9. System creates associated `attachments` record linked via polymorphic relation.
  10. System records idempotency resolution in `idempotency_keys` table.
  11. System logs audit event `LETTER_INCOMING_CREATED`.
  12. Transaction commits. Returns HTTP 201 with newly created record.
* **Failure Flow:**
  - Idempotency conflict: Payload mismatch under same key returns `ERR_409_IDEMPOTENCY_CONFLICT`.
  - File upload validation fails: Form displays specific error message; no database write occurs.
  - Storage write fails: Database transaction rolls back; no orphan record is created.
* **Data Written:** `incoming_letters`, `attachments`, `idempotency_keys`, `audit_logs`.
* **File Interaction:** Stores binary document in private filesystem.
* **Authorization:** `create` permission on `incoming_letters` scoped strictly to active division (`Policy:create`).
* **Audit Events:** `LETTER_INCOMING_CREATED`.
* **Legacy Evidence:** `backend/08_RecordService.js:L234-L336` (`simpanRecord`), `master.js:L950-L1058`.
* **Acceptance IDs:** `TEST-SM-01`, `TEST-SM-02`, `TEST-SM-03`, `TEST-IDEM-01`.

---

### FUN-PG-02: Public Piagam Submission & Canvas Sign
* **Primary Actors:** Public User (School Representative, Student, Teacher).
* **Purpose:** Allow external recipients to record receipt of competition awards and capture digital signature proof without authentication.
* **Preconditions:** Target division is active. School exists in `schools` registry.
* **Trigger:** Public user submits form on `/piagam-penghargaan`.
* **Primary Flow:**
  1. User selects target division, selects school from autocomplete dropdown (NPSN auto-filled), enters student name, competition year, competition category, collector name, position, and workplace.
  2. User signs on HTML5 canvas signature pad.
  3. System validates form fields and validates that `ttd_base64` is a valid PNG Data URL.
  4. System decodes canvas image to binary PNG.
  5. System stores signature image on private disk (`storage/app/private/signatures/{division_id}/`).
  6. System creates record in `piagams` with `submitter_type = 'public'`, linking `school_id`, `division_id`, and `signature_path`.
  7. System logs audit event `PIAGAM_PUBLIC_SUBMITTED`.
  8. Form displays success message and resets canvas pad.
* **Failure Flow:**
  - Missing signature: Form validation fails with message "Tanda tangan wajib dibubuhkan."
  - Inactive division: Returns `ERR_403_DIVISION_INACTIVE`.
  - Rate limit exceeded (10 submissions per IP per hour): Returns `ERR_429_TOO_MANY_REQUESTS`.
* **Data Written:** `piagams`, `audit_logs`.
* **File Interaction:** Stores binary PNG signature image.
* **Authorization:** Public endpoint protected by IP rate-limiting (`throttle:10,60`).
* **Audit Events:** `PIAGAM_PUBLIC_SUBMITTED`.
* **Legacy Evidence:** `backend/09_PiagamService.js:L5-L123` (`simpanPiagam`), `piagam.html`, `js/piagam.js`.
* **Acceptance IDs:** `TEST-PG-01`, `TEST-PG-02`.

---

### FUN-DASH-01: Analytics Dashboard & Metric Counters
* **Primary Actors:** `super_admin`, `admin_divisi`, `operator`.
* **Purpose:** Display operational metrics, total document counters, and authentic monthly volume trends.
* **Preconditions:** User is authenticated. Active division context is resolved.
* **Trigger:** User accesses `/dashboard`.
* **Primary Flow:**
  1. System queries database scoped to active division:
     - Total Surat Masuk: `COUNT(*)` where `deleted_at IS NULL`.
     - Total Surat Keluar: `COUNT(*)` where `deleted_at IS NULL`.
     - Total Piagam: `COUNT(*)` where `deleted_at IS NULL`.
  2. System queries authentic monthly document volume:
     `SELECT MONTH(letter_date) as m, COUNT(*) as total FROM ... GROUP BY MONTH(letter_date)`
  3. System queries 10 most recent activities from `audit_logs` scoped to active division.
  4. View renders KPI cards, Chart.js bar chart, Chart.js authentic monthly line chart, and activity list.
* **Target Decision:** Replaces legacy synthetic percentage multipliers with authentic database aggregations.
* **Data Read:** `incoming_letters`, `outgoing_letters`, `piagams`, `audit_logs`.
* **Authorization:** `viewDashboard` policy. Non-super admins restricted to their assigned division.
* **Legacy Evidence:** `js/dashboard.js:L1-L200`, `backend/08_RecordService.js` (`db_summary`).
* **Acceptance IDs:** `TEST-DASH-01`.

---

### FUN-FILE-01: Private Document Streaming & Download
* **Primary Actors:** `super_admin`, `admin_divisi`, `operator`.
* **Purpose:** Stream confidential attached PDFs, images, and signature files securely to authenticated users.
* **Preconditions:** User is authenticated. Document attachment exists on private storage disk.
* **Trigger:** User clicks attachment link on letter table or modal.
* **Primary Flow:**
  1. User requests `/attachments/{uuid}/preview` or `/attachments/{uuid}/download`.
  2. Controller resolves attachment by `uuid`.
  3. Controller executes Policy check (`view`, `download`): verifies user has authority over the parent letter's `division_id`.
  4. Controller verifies file exists on private disk (`Storage::disk('private')->exists($path)`).
  5. Controller streams file via `Storage::download()` or returns binary inline response with appropriate `Content-Type` header.
* **Failure Flow:**
  - Cross-division unauthorized access attempt: Returns HTTP 403 Forbidden. Audit log records `UNAUTHORIZED_FILE_ACCESS`.
  - Legacy missing attachment (`is_missing = true`): Returns HTTP 404 with structured error `ERR_404_ATTACHMENT_MISSING` and user-friendly explanation "Berkas lampiran warisan tidak ditemukan di Google Drive saat migrasi".
  - File missing on disk: Returns HTTP 404 Not Found (`ERR_404_FILE_NOT_FOUND`). Audit log records `FILE_NOT_FOUND_ON_DISK`.
* **Authorization:** Policy-checked against parent entity's division ownership. No public links.
* **Audit Events:** `FILE_DOWNLOADED`, `UNAUTHORIZED_FILE_ACCESS`, `FILE_NOT_FOUND_ON_DISK`.
* **Target Decision:** Replaces legacy public Google Drive links with authenticated private streaming; guarantees graceful handling and non-fatal badges for missing legacy files.
* **Legacy Evidence:** `backend/05_Drive.js:L37`, `backend/10_UploadService.js:L103`.
* **Acceptance IDs:** `TEST-FILE-01`, `TEST-FILE-02`, `TEST-FILE-03`.
