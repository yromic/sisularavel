# ACCEPTANCE SCENARIOS

> [!NOTE]
> These acceptance scenarios provide human-readable Given/When/Then specifications directly translatable into automated end-to-end test suites (e.g. Pest / PHPUnit / Playwright) for SISURAT v1.3.0.

---

## Scenario Group: Mobile-First Data Entry & Responsive Behavior

### Scenario: Mobile Letter Creation at 360px Viewport
- **Feature:** Mobile-First Incoming Letter Entry
- **Requirement IDs:** `UI-001`, `UI-002`, `DEC-UI-001`, `TEST-UI-MOB-001`
- **Given:** An authenticated operator opens the application on a smartphone browser with a 360px viewport width.
- **When:** The operator navigates to `/surat-masuk/create`:
- **Then:**
  - The form renders in a clean single-column linear layout with zero horizontal scrolling.
  - All touch targets (text inputs, date controls, buttons) measure at least 44 × 44 CSS pixels.
  - Tapping `tanggal_surat` opens the smartphone's native date picker interface.
  - The file attachment control allows selecting files from device storage or capturing a photo via device camera.
  - Action buttons ("Batal", "Simpan Surat") remain anchored and visible in a sticky bottom action bar.

---

### Scenario: Form Input Preservation on Validation Failure
- **Feature:** Mobile Form Resilience
- **Requirement IDs:** `UI-005`, `VAL-001`, `TEST-UI-MOB-002`
- **Given:** An operator fills out 5 detailed fields on a smartphone and mistakenly enters an invalid future date in `tanggal_surat`.
- **When:** The operator taps "Simpan Surat", causing the backend to reject the request with HTTP 422.
- **Then:**
  - The page re-renders with high-contrast red error text immediately beneath the `tanggal_surat` field.
  - None of the previously typed data (nomor surat, subject, sender) is cleared or reset.
  - The viewport smoothly scrolls to highlight the first invalid input.

---

### Scenario: Double-Tap Duplicate Submission Shielding (Client & Server)
- **Feature:** Mobile Network Latency Resilience & Idempotency
- **Requirement IDs:** `UI-006`, `IDEM-001`, `BR-IDEM-001`, `TEST-UI-MOB-003`, `TEST-IDEM-001`
- **Given:** An operator on a congested 3G mobile network has completed the letter creation form.
- **When:** The operator taps the "Simpan Surat" button 3 times in rapid succession within 400 milliseconds.
- **Then:**
  - On the very first tap, the button immediately disables, shows an active loading spinner, and updates text to *"Menyimpan..."*.
  - The client attaches an `Idempotency-Key` UUIDv4 header.
  - Even if two identical requests reach the backend over mobile network retransmission, `IdempotencyMiddleware` processes the first and replays the cached HTTP 201 response for the second.
  - Exactly one incoming letter record is created in the database.

---

### Scenario: Touch-Only Action Menu on Mobile Summary Cards
- **Feature:** Zero-Hover Mobile Interaction
- **Requirement IDs:** `UI-002`, `DEC-UI-001`, `TEST-UI-MOB-005`
- **Given:** An operator views the list of letters on a smartphone screen where records are displayed as compact summary cards.
- **When:** The operator taps the action menu trigger icon (`...`) on a letter card:
- **Then:**
  - An action bottom sheet opens immediately on tap without relying on mouse hover.
  - Touch targets for "Lihat Detail", "Edit", and "Hapus" are at least 44px tall.
  - Tapping "Hapus" opens a modal confirmation prompt with distinct "Batal" and "Ya, Hapus" buttons.

---

## Scenario Group: Letter Numbering & Duplicate Semantics

### Scenario: Incoming Letter Number Collision from Different Senders
- **Feature:** External Incoming Letter Non-Uniqueness
- **Requirement IDs:** `FUN-SM-01`, `BR-SM-003`, `DEC-LET-001`, `TEST-LET-003`
- **Given:** Division `DIKDAS` has an existing incoming letter with number `"005/UND/2026"` from `"Dinas Kesehatan"`.
- **When:** The operator registers another incoming letter with the identical number `"005/UND/2026"` from `"Kementerian Agama"`.
- **Then:**
  - The system accepts the submission and returns HTTP 201 Created.
  - A new distinct record is inserted with its own unique primary key `id`.
  - No uniqueness constraint violation occurs.

### Scenario: Outgoing Letter Annual Uniqueness Enforcement
- **Feature:** Internal Outgoing Letter Numbering Scope
- **Requirement IDs:** `FUN-SK-01`, `BR-SK-002`, `DEC-LET-002`, `TEST-LET-005`
- **Given:** Division `DIKDAS` has already registered an outgoing letter with number `"042/DIKDAS/2026"` and letter date `"2026-03-01"`.
- **When:** The operator attempts to register a new outgoing letter for division `DIKDAS` with number `"042/DIKDAS/2026"` and letter date `"2026-05-15"`.
- **Then:**
  - The backend derives `letter_year = 2026` from `tanggal_surat`.
  - The request is rejected with HTTP 422 Unprocessable Entity (`ERR_422_DUPLICATE_OUTGOING_NUMBER`).
  - An error message states: *"Nomor surat keluar telah digunakan pada tahun 2026 untuk divisi ini."*

---

## Scenario Group: Authentication, Password Activation & Security

### Scenario: Migrated Account Rejects Legacy Credentials and Enforces Activation
- **Feature:** Deprecation of Insecure Legacy Passwords
- **Requirement IDs:** `AUTH-001`, `BR-USER-003`, `DEC-AUTH-001`, `TEST-AUTH-005`
- **Given:** User `staf_lama` was migrated from Google Sheets with `must_reset_password = true`.
- **When:** The user attempts to authenticate on `/login` using their old legacy password.
- **Then:**
  - The login attempt is rejected with HTTP 401 Unauthorized (legacy hash format is never recognized).
  - When authenticating with an admin-generated temporary secret, the system returns HTTP 403 redirecting to `/password/activate`.
  - The user cannot access any dashboard or document endpoints until setting a new permanent password.

### Scenario: Login Brute Force Lockout
- **Feature:** Authentication Security
- **Requirement IDs:** `AUTH-001`, `SEC-001`, `TEST-AUTH-001`
- **Given:** A registered user account `op_dikdas01` exists.
- **When:** An attacker attempts 5 consecutive login requests with incorrect passwords.
- **Then:**
  - The first 5 requests return HTTP 401 with message `"Username atau Password salah!"`.
  - The 6th request within 15 minutes returns HTTP 429 Too Many Requests with code `"ERR_429_RATE_LIMIT"`.
  - The response includes the localized lockout warning: `"Terlalu banyak percobaan login. Silakan tunggu 15 menit."`.
  - An audit log event `SEC_RATE_LIMIT_EXCEEDED` is recorded with the source IP.

---

## Scenario Group: Missing Legacy Attachments

### Scenario: Detail Rendering and Download of Missing Legacy Attachment
- **Feature:** Durable Missing Attachment Handling
- **Requirement IDs:** `FILE-003`, `BR-FILE-003`, `DEC-FILE-003`, `TEST-FILE-002`
- **Given:** An incoming letter record exists in the database where `attachments.is_missing = true` and `migration_status = 'missing_drive_file'`.
- **When:** The operator opens the letter detail page:
- **Then:**
  - The page renders completely with all letter metadata.
  - The attachment section displays an informational badge: *"Lampiran berkas warisan tidak tersedia di Google Drive"*.
  - Clicking the download link returns HTTP 404 with structured JSON error `ERR_404_ATTACHMENT_MISSING` and does not cause an application crash.

---

## Scenario Group: Tenancy & Division Context

### Scenario: Super Admin Division Switching
- **Feature:** Multi-Division Operations
- **Requirement IDs:** `TEN-002`, `RBAC-001`
- **Given:** A logged-in Super Admin with default active division `DIKDAS`.
- **When:** The Super Admin selects `PAUD` from the division switcher dropdown (`POST /api/v1/session/switch-division`, payload: `{"division_id": 2}`).
- **Then:**
  - The active session updates `division_id` to `2`.
  - Subsequent requests to `GET /api/v1/surat-masuk` return letters belonging strictly to `PAUD`.
  - An audit event `AUTH_DIVISION_SWITCHED` is logged.

---

## Scenario Group: Public Piagam Claim & Digital Signature

### Scenario: Public School Representative Claims Certificate
- **Feature:** Public Piagam Portal
- **Requirement IDs:** `FUN-PG-02`, `BR-PG-001`, `TEST-PG-001`
- **Given:**
  - A registered school exists with NPSN `20320251` (`SMP Negeri 1 Ungaran`).
  - An unclaimed certificate record exists for student `"Reza Pratama"`, competition `"FLS2N"`, year `2026`.
- **When:**
  - A school representative navigates to `/piagam/ambil` on a mobile smartphone.
  - Enters NPSN `20320251`.
  - Selects the certificate record for `"Reza Pratama"`.
  - Inputs `"Budi Hartono"` as claimant name, `"Guru Pembimbing"` as position.
  - Draws a signature on the full-width touch canvas pad and clicks submit.
- **Then:**
  - The submission succeeds with HTTP 200 OK.
  - The canvas Base64 signature is decoded and persisted as a PNG file in private storage.
  - An `attachments` record is linked to the piagam.
  - The `piagams` record is updated with claimant metadata and timestamp.
  - The certificate is no longer listed as unclaimed.
  - A printable mobile receipt with verification QR code is presented to the user.

---

## Scenario Group: Desktop Dense Management View

### Scenario: Desktop Dense Grid Inspection
- **Feature:** Desktop Management Parity
- **Requirement IDs:** `UI-007`, `DEC-UI-004`, `TEST-UI-DSK-001`
- **Given:** A Division Administrator accesses `/surat-masuk` on a desktop monitor with a 1280px viewport width.
- **When:** The page loads:
- **Then:**
  - The interface displays a full dense tabular grid with all 8 operational columns visible simultaneously.
  - Column sorting, pagination, and multi-row selection are fully operable.
  - Business rules, data filters, and permissions match the mobile experience exactly.
