# 20. TEST AND ACCEPTANCE CONTRACT

## 1. Scope & Objective

This contract specifies executable, testable acceptance scenarios covering all functional requirements, business rules, division isolation constraints, server-side idempotency, security boundaries, mobile-first responsive interactions, and legacy data migration verifications for SISURAT v1.3.0. Future test suites (PHPUnit / Pest / Playwright) in the Laravel application must map directly to these test identifiers.

---

## 2. Core Functional & Letter Uniqueness Acceptance Scenarios

### TEST-LET-IN-001: Incoming Letter External Number Collision Allowed
- **Related Requirements:** `FUN-SM-01`, `BR-SM-003`, `DEC-LET-001`
- **Actor:** Operator (Division `DIKDAS`)
- **Preconditions:** An existing incoming letter exists with `letter_number = '005/12/2026'`, `origin_agency = 'Bupati Semarang'`, `division_id = 1`.
- **Given:** A new official letter arrives from a different external sender (`origin_agency = 'Badan Kepegawaian Daerah'`) with the identical letter number `'005/12/2026'`.
- **When:** Operator submits `POST /api/v1/surat-masuk`.
- **Then:**
  - System responds with HTTP 201 Created.
  - New database row inserted with distinct surrogate primary key `id`.
  - Zero unique constraint collisions occur.
  - Both letters are retrievable independently.

---

### TEST-LET-IN-002: Incoming Letter Suspected Duplicate Advisory Warning
- **Related Requirements:** `FUN-SM-01`, `BR-SM-003`, `DEC-LET-001`
- **Actor:** Operator (Division `DIKDAS`)
- **Given:** An operator inputs an incoming letter with identical `division_id`, `letter_number`, `origin_agency`, and `letter_date` to an existing active record.
- **When:** Operator clicks "Simpan Surat" without confirmation override.
- **Then:**
  - System returns HTTP 200 / client advisory alert: *"Peringatan: Surat dari instansi, nomor, dan tanggal yang sama sudah terdaftar. Apakah Anda yakin ini bukan duplikat?"*.
  - When operator explicitly checks "Konfirmasi bukan duplikat" and re-submits, the letter is saved with an advisory note in `audit_logs`.

---

### TEST-LET-OUT-001: Outgoing Letter Number Uniqueness Scope Rejection
- **Related Requirements:** `FUN-SK-01`, `BR-SK-002`, `DEC-LET-002`
- **Actor:** Operator (Division `DIKDAS`)
- **Preconditions:** An outgoing letter exists in Division `DIKDAS` with `letter_number = '005/01/DIKDAS/2026'` and `letter_date = '2026-02-15'` (`letter_year = 2026`).
- **When:** An operator attempts to create another outgoing letter in `DIKDAS` with the same `letter_number = '005/01/DIKDAS/2026'` and `letter_date = '2026-03-01'` (`letter_year = 2026`).
- **Then:**
  - Submission fails with HTTP 409 Conflict (`ERR_409_DUPLICATE_NUMBER`: *"Nomor surat keluar sudah terdaftar pada divisi dan tahun ini"*).
  - Database rejects insertion via `uq_out_letters_div_year_number`.

---

### TEST-LET-OUT-002: Outgoing Letter Number Permitted Across Divisions or Years
- **Related Requirements:** `FUN-SK-01`, `BR-SK-002`, `DEC-LET-002`
- **Actor:** Operator (Division `PAUD`)
- **Preconditions:** Division `DIKDAS` has registered outgoing letter `'005/01/2026'` in `2026`.
- **When:** Division `PAUD` registers outgoing letter `'005/01/2026'` in `2026`.
- **Then:**
  - System responds with HTTP 201 Created.
  - Constraint `(division_id, letter_year, letter_number)` is satisfied because `division_id` differs.

---

### TEST-LET-OUT-003: Outgoing Letter Year Derivation on Date Edit
- **Related Requirements:** `FUN-SK-01`, `BR-SK-002`, `DEC-LET-002`
- **Actor:** Division Admin
- **Given:** Outgoing letter exists with `letter_date = '2026-12-30'` and `letter_year = 2026`.
- **When:** Admin updates `letter_date` to `'2027-01-05'`.
- **Then:**
  - System automatically recalculates `letter_year = 2027`.
  - Evaluates uniqueness for `(division_id, 2027, letter_number)`.
  - Successfully updates record.

---

## 3. Authentication & Account Activation Scenarios (DEC-AUTH-001)

### TEST-AUTH-SEC-001: Rejection of Legacy Password Credentials
- **Related Requirements:** `AUTH-001`, `SEC-001`, `BR-USER-003`, `DEC-AUTH-001`
- **Actor:** Migrated User Account
- **Preconditions:** User `miftah` was migrated from legacy Sheets with `must_reset_password = true` and randomized hash.
- **When:** User attempts to log in using their former legacy plaintext or salted password.
- **Then:**
  - Authentication fails immediately with HTTP 401 Unauthorized (`"Username atau Password salah!"`).
  - Legacy password material cannot authenticate.

---

### TEST-AUTH-ACT-001: Migrated User Activation via Verified Email (Path A)
- **Related Requirements:** `AUTH-001`, `SEC-001`, `BR-USER-003`, `DEC-AUTH-001`
- **Actor:** Migrated User with Valid Email
- **Given:** User requests password reset via `/forgot-password`.
- **When:** System sends one-time reset link with 64-char token (24h TTL).
- **Then:**
  - Response returns generic message to prevent account enumeration.
  - User opens link, enters new strong password.
  - System verifies token, hashes new password with Bcrypt, clears token, sets `must_reset_password = false`.
  - Subsequent login with new password succeeds.

---

### TEST-AUTH-ACT-002: Administrative Out-of-Band Activation (Path B)
- **Related Requirements:** `AUTH-001`, `SEC-001`, `BR-USER-003`, `DEC-AUTH-001`
- **Actor:** Super Admin & Operator without Email
- **Given:** Super Admin triggers account activation for user `operator_paud`.
- **When:** System generates single-use temporary password (24h TTL).
- **Then:**
  - Temporary password is displayed once in admin UI and delivered out-of-band.
  - When user logs in with temporary password, system redirects to mandatory password change screen.
  - Access to dashboard is blocked until new password is set.
  - Temporary password and reset tokens are **never logged** in `audit_logs` or application logs.

---

## 4. Missing Legacy Attachment Scenarios (DEC-FILE-003)

### TEST-FILE-MISS-001: Non-Fatal Display of Missing Legacy Attachment
- **Related Requirements:** `FILE-001`, `BR-FILE-003`, `DEC-FILE-003`
- **Actor:** Operator viewing migrated letter
- **Preconditions:** Legacy letter exists whose Google Drive file was deleted (HTTP 404). Record in `attachments` has `file_path = NULL`, `is_missing = true`, `migration_status = 'missing_drive_file'`.
- **When:** Operator navigates to `/surat-masuk/{id}`.
- **Then:**
  - Letter metadata loads completely without HTTP 500 error.
  - Attachment section displays high-contrast warning badge: *"Lampiran lama tidak tersedia di Google Drive (404/Dihapus)"*.
  - Direct request to `/api/v1/attachments/{uuid}/preview` returns HTTP 404 with safe localized envelope (`ERR_404_ATTACHMENT`).

---

### TEST-FILE-MISS-002: Authorized Remediation of Missing Attachment
- **Related Requirements:** `FILE-001`, `BR-FILE-003`, `DEC-FILE-003`
- **Actor:** Division Admin
- **Preconditions:** Letter has missing attachment (`is_missing = true`).
- **When:** Division Admin uploads a replacement PDF scan via `POST /api/v1/letters/{id}/remediate-attachment`.
- **Then:**
  - File binary stored in `storage/app/private/documents/`.
  - Attachment record updated: `file_path` populated, `is_missing = false`, `migration_status = 'migrated'`.
  - Audit log recorded with `event_code = 'ATTACHMENT_UPLOADED'`.
  - Warning badge disappears; preview button becomes active.

---

## 5. Single-Division Ownership Scenarios (DEC-TEN-001)

### TEST-TEN-OWN-001: Division Ownership Immutability & Payload Tampering
- **Related Requirements:** `TEN-001`, `BR-TEN-005`, `DEC-TEN-001`
- **Actor:** Division Admin (`DIKDAS`)
- **Preconditions:** Letter ID `10` belongs to Division `DIKDAS` (`division_id = 1`).
- **When:** Admin attempts `PUT /api/v1/surat-masuk/10` with payload `{"division_id": 2}`.
- **Then:**
  - System rejects ownership alteration (returns HTTP 422 or preserves `division_id = 1`).
  - Letter remains owned strictly by `DIKDAS`.

---

### TEST-TEN-OWN-002: Super Admin View Does Not Transfer Ownership
- **Related Requirements:** `TEN-001`, `TEN-002`, `DEC-TEN-001`
- **Actor:** Super Admin
- **Preconditions:** Letter belongs to Division `PAUD`.
- **When:** Super Admin switches active context to `DIKDAS` and inspects global search results.
- **Then:**
  - Record ownership remains `division_id = 2` (`PAUD`).
  - No routing or ownership mutation occurs.

---

## 6. Server-Side Idempotency Scenarios (DEC-SYS-001)

### TEST-IDEM-001: Double-Tap Replay with Identical Response
- **Related Requirements:** `SYS-001`, `UI-006`, `BR-IDEM-001`, `DEC-SYS-001`
- **Actor:** Operator on High-Latency Connection
- **Given:** Operator submits incoming letter with header `Idempotency-Key: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d`.
- **When:** Client fires two identical POST requests concurrently with the same key and payload.
- **Then:**
  - Exactly ONE row is inserted into `incoming_letters`.
  - Server returns HTTP 201 Created to both requests with the identical JSON response body.
  - Exactly one audit log entry is recorded.

---

### TEST-IDEM-002: Idempotency Key Conflict on Altered Payload
- **Related Requirements:** `SYS-001`, `BR-IDEM-001`, `DEC-SYS-001`
- **Actor:** Script / Replay Attacker
- **Given:** Key `9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d` was already used to create a letter.
- **When:** Client submits a second POST request using the SAME key but with an altered `perihal` text.
- **Then:**
  - System detects request fingerprint mismatch.
  - Request aborts with HTTP 409 Conflict (`ERR_409_IDEMPOTENCY_CONFLICT`: *"Idempotency key telah digunakan untuk permintaan berbeda"*).
  - No database insertion occurs.

---

## 7. Mobile-First & Desktop UI Acceptance Scenarios

### TEST-UI-MOB-001: Mobile Letter Creation at 360px Viewport
- Single-column linear layout, >= 44x44px touch targets, native date picker, camera/file upload, sticky bottom bar.

### TEST-UI-MOB-002: Form State Preservation on Validation Rejection
- HTTP 422 retains 100% of user-entered inputs without form reset.

### TEST-UI-MOB-003: Accidental Duplicate Submission Prevention
- Client submit button disables immediately on first tap with spinner.

### TEST-UI-MOB-004: Mobile Filter Drawer & Active Filter Badge
- Tap-to-open bottom filter sheet; active filters display as removable chip badges.

### TEST-UI-MOB-005: Zero-Hover Record Actions on Mobile
- Touch tap opens bottom action sheet without hover dependency.

### TEST-UI-MOB-006: Accessible SVG Icon System
- Dedicated SVG icon set; explicit `aria-label` bindings; zero emoji or generic symbols.

### TEST-UI-DSK-001: Desktop Dense Tabular Management
- Dense 8-column data grid with multi-column sorting and direct row action buttons at >= 1024px.

---

## 8. Legacy Data Migration Acceptance Scenarios

### TEST-MIG-001: Data Migration Reconciliation
- Total legacy rows in inspected snapshot match imported records + quarantined rows.
- 100% of legacy missing Drive files recorded in database with `is_missing = 1`.
- 100% of migrated user accounts have `must_reset_password = 1`.
