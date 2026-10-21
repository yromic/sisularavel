# SISURAT Laravel Migration Contract Pack — Changelog

All notable changes, version ratifications, and schema revisions to the **SISURAT Laravel Migration Contract Pack** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.6.1] — 2026-10-21 — Non-Mutating Bulk Restore Preview & Idempotency Decoupling

### Fixed
* **Bulk Restore Preview 422 Error (`DEC-BULK-002`):** Resolved `"The given data was invalid."` error when previewing restore operations from Trash views. Decoupled read-only dry-run preview endpoints (`/bulk-restore/preview`) from state-mutating `EnsureIdempotentMutation` middleware, eliminating premature rejection caused by missing `Idempotency-Key` headers during impact analysis.
* **Confirmation Phrase Bypassing on Restore:** Updated `BulkDeleteRequest::withValidator` to skip the destructive `HAPUS {N}` confirmation phrase requirement when executing restore operations (`$request->isRestore()`).
* **Restore Impact Preview Routing:** Added explicit `/bulk-restore/preview` routes across all entities (Users, Incoming Letters, Outgoing Letters, Schools, Categories), wired `previewRestore()` in controllers, and updated JavaScript `BulkSelectEngine` to dynamically render emerald badges and `"Dapat Dipulihkan"` labels.

## [1.6.0] — 2026-10-21 — Reference Master Soft Delete & Bulk Operations (Tahap 3)

### Added
* **Reference Master Soft Deletes (`DEC-REF-001`):** Enabled `SoftDeletes` on `schools`, `competition_categories`, `pickup_categories`, and `work_units` tables.
* **NPSN Integrity & Restore Guidance Strategy (Option A):** Preserved strict physical `UNIQUE(npsn)` constraint in MySQL. Attempting to create a school with an NPSN residing in Trash returns HTTP 422 with an explicit instruction to restore the existing record rather than corrupting history.
* **Bulk Reference Operations:** Added dry-run preview, bulk delete, and bulk restore endpoints and UI for Schools, Competition Categories, Pickup Categories, and Work Units.
* **Trash & Restore UI for Reference Masters:** Added "Tempat Sampah" tabs, restore modal confirmations, and status filters on `/master/sekolah` and `/master/kategori`.
* **Category Division Tenancy Scoping:** Strictly enforced division tenancy on category bulk operations; cross-division modification attempts trigger instant HTTP 403 Forbidden.

## [1.5.0] — 2026-10-21 — Bulk Operations Engine & School Foreign Key Safety

### Added
* **Bulk Delete & Restore Engine (`DEC-BULK-001`, `UX-BULK-001`):** High-efficiency multi-record operations for Users, Incoming Letters, and Outgoing Letters. Features:
  * Two-level selection (page-level checkboxes and all-filtered-records toggle).
  * Server-side dynamic query re-verification protecting against mid-session dataset drift (HTTP 409 Conflict).
  * Pre-execution dry-run impact preview (`/preview`) detailing deletable vs. protected/skipped records.
  * Progressive cognitive friction requiring explicit confirmation phrase (`HAPUS {N}`) for high-volume operations (>= 50 items).
  * Resilient chunked database transactions (`DB::transaction`) with partial execution reporting and client CSV export of skipped items.
  * Server-side mutation idempotency via `Idempotency-Key` headers preventing double deletion upon network lag or double-clicking.
  * Rate limiting (`throttle:bulk`, 30 requests/minute) and summary audit logging (`USER_BULK_DELETED`, `LETTER_INCOMING_BULK_DELETED`, etc.).
  * Accessible floating action toolbar, WAI-ARIA dialogs with keyboard trap, and Escape dismissal.

### Fixed
* **School Delete Foreign Key Safety (`DEC-SCH-001`, `BUG-SCH-001`):** Corrected `SchoolController::destroy` to check for linked piagams using `Piagam::withTrashed()`. Prevents MySQL 1451 RESTRICT integrity violations and 500 server crashes when a school's linked piagam is soft-deleted; safely marks the school as inactive with HTTP 422 warning.

---

## [1.4.0] — 2026-10-15 — Modernized UI, Digital Handover Rework, Unified Workspace & Smart Search

### Added
* **Buku Ekspedisi Digital Pengambilan Piagam:** Reworked Piagam module into single-action digital handover receipt book with touchscreen signature canvas, public counter kiosk, and master work units (`DEC-PG-001`, `PG-HANDOVER-01`, `PG-REF-FORM-01`).
* **Multi-Photo Camera Capture & PDF Auto-Merge:** Direct sequential camera capture compiling smartphone document photos into single standard A4 PDF via DomPDF (`DEC-FILE-004`, `DOC-MULTI-PHOTO-01`).
* **In-Place Modal CRUD & Split View Architecture:** Full in-place Create, Edit, Delete confirmation, and responsive Split Modal Document Previews for letters and certificates (`DEC-UI-005`, `DEC-UI-006`).
* **Unified Single-Page Workspace (`/persuratan`):** Zero-reload tabbed operational desk unifying Surat Masuk, Surat Keluar, and Piagam into one seamless interface (`DEC-UI-007`, `UI-UNIFIED-WORKSPACE-01`).
* **Smart Search Unified Stream & Dual-View Mode (`/laporan`):** Repositioned core search directly below Dashboard on sidebar; unified mixed chronological feed; pure SVG Grid vs. Dense List view switchers; zero OS unicode symbols (`DEC-UI-008`, `UI-SMART-SEARCH-01`).

### Fixed
* **Session Auth & Web Redirection:** Corrected `RedirectResponse` return types across web auth routes and preserved form state upon validation error.
* **Master User & Reference Soft Deletions:** Added soft-delete and restore capabilities for master users and categories without foreign key violations.
* **Storage Path Resolution:** Hardened private disk storage paths against double-nesting anomalies.

---

## [1.3.0] — 2026-09-18 — Initial Framework Baseline Changed to Laravel 12

### Changed
* **Target Framework Baseline:** Corrected initial implementation target from `Laravel ^13.0` to `Laravel ^12.0`.
* **Minimum PHP Runtime:** Adjusted minimum runtime requirement from `PHP >= 8.3` to `PHP >= 8.2` (Composer requirement: `^8.2`; supported Laravel 12 range: PHP 8.2–8.5) to align with observed local development runtime on XAMPP (PHP 8.2).
* **Implementation Phase 1:** Updated `Phase 1 — Laravel 12 / PHP 8.2 Environment Verification and Foundation` with explicit exit criteria (`php -v` check >= 8.2, Composer runtime check, resolution to Laravel 12.x, boot test).
* **Maintenance Roadmap:** Added `Post-Foundation Maintenance Milestone: PHP >= 8.3 Runtime Upgrade & Laravel 13 Migration` prior to the Laravel 12 security-support conclusion (February 24, 2027).
* **Decision Log:** Ratified `DEC-TECH-002` (Initial Framework Baseline: Laravel 12 / PHP 8.2); marked `DEC-001` as `SUPERSEDED`.
* **Agent Governance:** Updated `AGENTS.md` with explicit pre-flight verification directives (Rules 6–9: PHP >= 8.2, Composer runtime validation, `--ignore-platform-reqs` ban, `^12.0` initialization constraint).
* **Shared-Hosting Constraints:** Formally distinguished Initial Deployment Compatibility (Laravel 12 / PHP >= 8.2) from Future Upgrade Compatibility (Laravel 13 / PHP >= 8.3).
* **Machine-Readable Contracts:** Updated `requirements.yaml` (`SYS-001`), `entities.yaml`, `roles-permissions.yaml`, `status-enums.yaml`, `validation-rules.yaml`, and `legacy-data-map.yaml` to v1.3.0.

### Unchanged
* **Domain Model:** All core business entities, aggregate boundaries, and lifecycle states remain unchanged.
* **Database Business Constraints:** All 12 relational tables, authoritative PKs, unique constraints, and schema structures remain unchanged.
* **RBAC & Authorization:** Four-role matrix (`admin`, `kepala_dinas`, `operator`, `user`) and division-isolated data access remain unchanged.
* **Division Isolation:** Strict single-division ownership and query scoping via global scopes remain unchanged.
* **Mobile-First UI Requirements:** Ergonomic touch targets, mobile-first layouts, and restricted visual design remain unchanged.
* **File Security:** Private local disk storage, SHA-256 integrity verification, safe download streaming, and MIME validation remain unchanged.
* **Data Migration Strategy:** Staged CSV migration, `db.txt` field mapping, random unusable password hashes, and durable missing attachment tracking remain unchanged.
* **Acceptance Requirements:** All functional and non-functional acceptance scenarios unrelated to framework runtime compatibility remain unchanged.

---

## [1.2.0] — 2026-09-18

### Added
* `DEC-LET-001`: Authoritative database PK `id` for incoming letters; external letter number collisions permitted; non-blocking duplicate advisory check against `(division_id, asal_surat, nomor_surat, tanggal_surat)`.
* `DEC-LET-002`: Internal outgoing letter numbering scoped by division and explicit `letter_year` column; cross-database unique constraint `UNIQUE (division_id, letter_year, nomor_surat)`.
* `DEC-AUTH-001`: Complete deprecation of legacy password formats; migrated users assigned random unusable hashes with `must_reset_password = true`; Path A (email token) and Path B (admin-issued one-time secret) activation workflows.
* `DEC-FILE-003`: Durable missing legacy attachment handling; records preserved with `is_missing = true` and `migration_status` ENUM (`missing_drive_file`, `inaccessible_drive_file`, etc.); graceful UI badge and safe 404 streaming response.
* `DEC-TEN-001`: Strict single-division ownership ratified for v1 (`division_id`); cross-division forwarding and inter-division routing formally classified as `DEFERRED_FUTURE_FEATURE`.
* `DEC-SYS-001`: Mandatory server-side mutation idempotency; `Idempotency-Key` header with 24-hour deduplication tracking in `idempotency_keys` table.
* `DEC-DOC-001`: Scoped coverage semantics; bounded reporting against identified feature catalog (28 features) and inspected `db.txt` schema snapshot (62 columns).
* Database table `idempotency_keys` (Table 12 in Database Contract).
* Standardized error codes `ERR_409_IDEMPOTENCY_CONFLICT`, `ERR_409_CONCURRENT_REQUEST`, `ERR_404_ATTACHMENT_MISSING`, and `WARN_POTENTIAL_DUPLICATE_LETTER`.
* Comprehensive test scenarios `TEST-LET-003` to `TEST-LET-006`, `TEST-AUTH-005` to `TEST-AUTH-006`, `TEST-FILE-002` to `TEST-FILE-003`, and `TEST-IDEM-001` to `TEST-IDEM-002`.
* Comprehensive pre-handover review artifact: `reviews/V1.2.0-RECONCILIATION-REPORT.md`.

### Changed
* Updated `docs/04-BUSINESS-RULES.md`, `docs/06-DOMAIN-MODEL.md`, `docs/07-DATABASE-CONTRACT.md`, `docs/08-RBAC-AND-DIVISION-SCOPE.md`, `docs/09-VALIDATION-CONTRACT.md`, `docs/10-FILE-STORAGE-CONTRACT.md`, `docs/12-SECURITY-CONTRACT.md`, `docs/14-ERROR-HANDLING-CONTRACT.md`, `docs/15-LEGACY-DATA-MAPPING.md`, `docs/16-DATA-MIGRATION-STRATEGY.md`, `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md`, `docs/22-TARGET-ARCHITECTURE-BOUNDARIES.md`, `docs/23-IMPLEMENTATION-SEQUENCE.md`, `docs/24-DECISION-LOG.md`, `docs/26-LEGACY-EVIDENCE-INDEX.md`, and `docs/27-TRACEABILITY-MATRIX.md`.
* Updated machine-readable YAML contracts: `contracts/requirements.yaml`, `contracts/entities.yaml`, `contracts/validation-rules.yaml`, `contracts/status-enums.yaml`, and `contracts/legacy-data-map.yaml` to version 1.2.0.
* Updated `examples/workflow-scenarios.md` and `examples/acceptance-scenarios.md` to demonstrate idempotency, collision handling, and missing attachments.
* Updated `AGENTS.md` with rules 26 through 30 for AI agent governance.

### Corrected
* **Conflated Letter Uniqueness:** Removed universal `UNIQUE (division_id, YEAR(tanggal_surat), nomor_surat)` constraint that previously broke incoming letters from external senders.
* **Weak Legacy Passwords:** Eradicated recommendations to preserve or auto-upgrade legacy plaintext and salted SHA-256 hashes; replaced with strict zero-trust account activation.
* **Ephemeral Missing Files:** Replaced `missing_attachments.json`-only tracking with durable, database-backed `is_missing` and `migration_status` attributes in the `attachments` table.
* **Client-Only Idempotency:** Corrected mobile contract to distinguish client-side button disabling as an ergonomic shield from server-side database idempotency.
* **Review Contradictions:** Reconciled contradictory review claims ("Zero P0 Blockers" alongside open P0 decisions); all identified P0 items now possess explicit, ratified decisions.
* **Overbroad Coverage Claims:** Scoped all "100% complete" and "zero gaps" language to explicitly defined denominators and audit boundaries.

### Resolved
* `Q-01A`: Incoming-letter duplicate policy resolved via `DEC-LET-001` (PK authoritative, advisory check, no unique constraint).
* `Q-01B`: Outgoing-letter numbering uniqueness scope resolved via `DEC-LET-002` (annual division scope).
* `Q-01C`: Definition of numbering year/period resolved via `DEC-LET-002` (explicit `letter_year` derived from `tanggal_surat`).
* `Q-02`: Legacy credential migration resolved via `DEC-AUTH-001` (passwords deprecated, activation/reset enforced).
* `Q-03`: Legacy Drive missing attachments resolved via `DEC-FILE-003` (durable database representation, letter preserved).
* `Q-04`: Single-division ownership for v1 resolved via `DEC-TEN-001` (single owner, cross-division routing deferred).

### Still Open (Non-Blocking Backlog)
* `Q-01D`: Agenda-number uniqueness scope (Deferred to Phase 6; independent of letter number).
* `Q-05`: Production SMTP vs Out-of-band Admin Dispatch Channel (Operational decision for Phase 3 deployment).

---

## [1.1.0] — 2026-09-18

### Added
* Formal incorporation of user-approved UI decisions:
  * `DEC-UI-001` (Mobile-First Primary Experience, operational justification: letter-entry primarily conducted via smartphone).
  * `DEC-UI-002` (Functional, Restrained Visual Design without excessive gradients or non-functional glassmorphism).
  * `DEC-UI-003` (Consistent Application Icon Library using SVG, prohibiting emoji and OS-specific symbols).
  * `DEC-UI-004` (Dual-Tier Parity: Mobile input prioritized, desktop management fully supported without fragmented business logic).
* Added normative requirements `UI-001` through `UI-007` to `contracts/requirements.yaml`.
* Added 7 test scenarios in `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` and `examples/acceptance-scenarios.md` (`TEST-UI-MOB-001` to `006`, `TEST-UI-DSK-001`) covering 360px viewport forms, touch targets >= 44px, form state retention, and duplicate submission prevention.
* Created formal Quality Gate and Review artifacts in `reviews/`:
  * `reviews/CRITICAL-CONTRACT-REVIEW.md`
  * `reviews/CONTRACT-DEFECT-REGISTER.md`
  * `reviews/CONTRACT-COVERAGE-REPORT.md`
  * `reviews/PRE-IMPLEMENTATION-GATE.md`

### Changed
* Enhanced `docs/11-UI-UX-BEHAVIOR-CONTRACT.md` with complete mobile form layout rules, responsive breakpoint tables, card summary mobile list models, and zero-hover touch action menus.
* Enhanced `docs/18-NON-FUNCTIONAL-REQUIREMENTS.md` with mobile-first usability, touch-target area minimums, and accessibility standards.
* Updated `docs/27-TRACEABILITY-MATRIX.md` to reflect complete forward and backward traceability for all UI requirements and mobile tests.

---

## [1.0.0] — 2026-09-18

### Added
* Initial ratified release of the self-contained **SISURAT Laravel Migration Contract Pack**.
* Complete normative specifications across 28 documentation modules (`docs/00-SYSTEM-CONTEXT.md` through `docs/27-TRACEABILITY-MATRIX.md`).
* Machine-readable YAML contracts for requirements, roles/permissions, entities, validation rules, status enums, and legacy data mappings (`contracts/`).
* Anonymized realistic record examples, end-to-end workflow scenarios, and integration acceptance scenarios (`examples/`).
* Mandatory AI implementation governance instructions (`AGENTS.md`).
* Exhaustive legacy evidence index and cross-traceability matrix connecting legacy artifacts to Laravel target specifications.
