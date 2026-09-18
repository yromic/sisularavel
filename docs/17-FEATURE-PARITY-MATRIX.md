# 17. FEATURE PARITY MATRIX

## 1. Scope & Decision Categories

This matrix documents the disposition of every legacy feature, architectural mechanism, and workflow in the target Laravel system (initial baseline: Laravel ^12.0 / PHP ^8.2, Reconciled v1.3.0). Every item is classified under one of six strict decision types:

- **`MUST_PRESERVE`:** Essential business behavior that must function identically from the user's operational perspective.
- **`PRESERVE_WITH_IMPROVEMENT`:** Business functionality is preserved, but the underlying implementation or user experience is modernized and made robust.
- **`REPLACE_WITH_NEW_BEHAVIOR`:** Legacy concept is retained, but the technical mechanism is replaced with standard Laravel idioms.
- **`DEPRECATED_DO_NOT_PORT`:** Legacy technical workarounds, insecure patterns, or accidental artifacts that must be explicitly rejected.
- **`DEFERRED_FUTURE_FEATURE`:** Features not present in legacy or non-essential for initial migration that are formally slated for post-v1.
- **`USER_DECISION_REQUIRED`:** A product decision is required from stakeholders before finalization.

---

## 2. Feature & Mechanism Disposition Matrix

| Feature / Mechanism | Legacy Behavior & Evidence | Target Decision | Target Contract & Requirement IDs | Migration Priority |
| :--- | :--- | :--- | :--- | :--- |
| **Authentication & Login** | Username/password checked against `db_users` sheet via `cekLogin` (`backend/02_Auth.js`). Plain/salt hash. | `REPLACE_WITH_NEW_BEHAVIOR` | Native Laravel authentication with Bcrypt/Argon2id and Session cookies. (`AUTH-001`, `SEC-001`, `BR-USER-001`) | **P0** |
| **Legacy Password Migration** | Legacy passwords stored in plain text or custom salted SHA-256. | `DEPRECATED_DO_NOT_PORT` | Legacy credentials rejected; accounts assigned random unusable hashes with `must_reset_password = true` and activated via Path A (Email) or Path B (Admin-assisted secret). (`BR-USER-003`, `DEC-AUTH-001`) | **P0** |
| **Multi-Tenancy (Divisions)** | Simulated by dynamically generating 5 separate sheet tabs per division (`{KODE}_...`). | `DEPRECATED_DO_NOT_PORT` | Unified relational tables with indexed `division_id` foreign keys and explicit division scoping. (`TEN-001`, `DB-001`, `BR-TEN-001`) | **P0** |
| **Inter-Division Routing** | No cross-division forwarding or shared ownership existed in legacy GAS code. | `DEFERRED_FUTURE_FEATURE` | Strictly single-division ownership ratified for v1 (`division_id`). Cross-division transfers or multi-division routing deferred to v2. (`BR-TEN-005`, `DEC-TEN-001`) | **P2** |
| **Incoming Letters CRUD** | Appends/reads rows in `{KODE}_surat_masuk` via `simpanRecord` (`backend/08_RecordService.js`). No unique constraint on `nomor_surat`. | `MUST_PRESERVE` | Standard Eloquent CRUD on `incoming_letters` with division scoping. Database PK is authoritative; external collisions allowed; duplicate advisory warning provided. (`FUN-SM-01`, `BR-SM-001`, `BR-SM-003`, `DEC-LET-001`) | **P0** |
| **Outgoing Letters CRUD** | Appends/reads rows in `{KODE}_surat_keluar`. Division-issued numbers. | `MUST_PRESERVE` | Eloquent CRUD on `outgoing_letters` with division scoping, explicit `letter_year` column, and annual uniqueness `UNIQUE (division_id, letter_year, nomor_surat)`. (`FUN-SK-01`, `BR-SK-001`, `BR-SK-002`, `DEC-LET-002`) | **P0** |
| **Attachment Storage & Streaming** | Uploaded to Google Drive with `ANYONE_WITH_LINK, VIEW` public permissions (`backend/05_Drive.js`). | `DEPRECATED_DO_NOT_PORT` | Private filesystem storage (`storage/app/private/`) accessed only via authorized streaming endpoints. (`FILE-001`, `SEC-002`) | **P0** |
| **Durable Missing Attachments** | Legacy Drive files that return 404 caused unhandled exceptions or missing links. | `PRESERVE_WITH_IMPROVEMENT` | Letter metadata preserved; attachment marked `is_missing = true` with `migration_status` ENUM (`missing_drive_file`); graceful UI warning and non-fatal download handling. (`FILE-003`, `BR-FILE-003`, `DEC-FILE-003`) | **P0** |
| **Chunked File Upload** | Splits files into 72KB Base64 chunks over sequential POSTs due to CacheService 100KB limits (`backend/10_UploadService.js`). | `DEPRECATED_DO_NOT_PORT` | Standard HTTP multipart/form-data upload via `Illuminate\Http\UploadedFile` (10MB limit). (`FILE-002`) | **P0** |
| **Request Idempotency & Shielding** | Client-side button disabling only; duplicate POSTs on network latency produced duplicate rows in Sheets. | `PRESERVE_WITH_IMPROVEMENT` | Client double-tap shielding maintained as ergonomic control; supplemented by mandatory server-side `Idempotency-Key` header with database deduplication table (`idempotency_keys`). (`IDEM-001`, `BR-IDEM-001`, `DEC-SYS-001`) | **P0** |
| **Piagam Certificate CRUD** | Records student and competition details in `{KODE}_piagam`. | `MUST_PRESERVE` | Eloquent CRUD on `piagams` with division scoping. (`FUN-PG-01`, `BR-PG-001`) | **P1** |
| **Public Piagam Claim & Signature** | Unauthenticated canvas signature export uploaded as Base64 to Drive (`backend/09_PiagamService.js`). | `PRESERVE_WITH_IMPROVEMENT` | Public form with HTML5 Canvas, rate-limited, Base64 decoded to private disk, and linked to piagam record. (`FUN-PG-02`, `SEC-003`) | **P1** |
| **School Reference Master** | Global table `ref_sekolah` with NPSN typeahead lookup. | `MUST_PRESERVE` | Relational `schools` table with unique NPSN index and typeahead API. (`FUN-REF-01`, `DB-002`) | **P1** |
| **User Management** | Managed by row-number index manipulation in `db_users` (`backend/07_UserService.js`). | `DEPRECATED_DO_NOT_PORT` | Relational `users` table manipulated by immutable primary keys (`id`). Division Admin scoped to own division. (`FUN-USR-01`, `RBAC-001`) | **P0** |
| **Division Management** | Dynamic sheet initialization / deactivation via `backend/11_DivisionService.js`. | `PRESERVE_WITH_IMPROVEMENT` | Relational `divisions` management. Status toggle (active/inactive) with user session invalidation. (`FUN-DIV-01`, `BR-TEN-003`) | **P1** |
| **Monthly Analytics Charts** | Fictitious monthly distribution calculated using hardcoded multipliers (`[0.1, 0.15, 0.2, 0.18, 0.22]`) in `dashboard.html`. | `DEPRECATED_DO_NOT_PORT` | Real aggregation queries (`SELECT MONTH(tanggal_surat), COUNT(*) ... GROUP BY MONTH`) rendered accurately. (`FUN-DASH-01`) | **P1** |
| **Advanced Archive Search** | Client-side array filtering across cached sheet dumps in `cari.html`. | `PRESERVE_WITH_IMPROVEMENT` | Server-side database search using indexed SQL `LIKE` or Fulltext queries with pagination. (`FUN-SRC-01`) | **P1** |
| **Recap & Export** | Client-side CSV/XLS generation in `laporan.html`. Vulnerable to formula injection. | `PRESERVE_WITH_IMPROVEMENT` | Server-side Excel/PDF streaming using PhpSpreadsheet / dompdf with formula injection sanitization. (`FUN-REP-01`, `SEC-004`) | **P1** |
| **Audit Logging** | Flat row append to `db_audit_log` Google Sheet (`backend/06_Logger.js`). Missing IP/UA. | `PRESERVE_WITH_IMPROVEMENT` | Immutable `audit_logs` table capturing user ID, event code, IP address, user agent, and JSON attribute diffs. (`AUD-001`, `SYS-002`) | **P0** |
| **JSON Origin Check** | Inspected `params.origin` inside JSON request body (`backend/01_Config.js`). | `DEPRECATED_DO_NOT_PORT` | Trusted HTTP header evaluation via Laravel `CorsService` middleware. (`SEC-005`) | **P0** |
| **Cartesian Cache Invalidation** | Generated and evicted up to 1,650 permutations of CacheService keys on every write (`backend/08_RecordService.js`). | `DEPRECATED_DO_NOT_PORT` | Tagged caching or targeted key invalidation via Laravel `Cache` facade. (`PERF-001`) | **P1** |
| **Static `db_summary` Sheet** | Stored redundant counters (`total_surat_masuk`, etc.) updated via scheduled triggers. | `DEPRECATED_DO_NOT_PORT` | Dynamic queries with optional cached counts via `Cache::remember()`. (`DB-003`) | **P2** |
