# 23. IMPLEMENTATION SEQUENCE

## 1. Scope & Execution Principles

This document defines the chronological, dependency-ordered implementation roadmap for the SISURAT development phase on Laravel 12 (PHP ^8.2). Each phase specifies its objective, prerequisites, requirement coverage, deliverables, and strict exit criteria.

> [!WARNING]
> **Runtime Lifecycle & Maintenance Requirement:**  
> Initial development targets **Laravel ^12.0 on PHP ^8.2** to match the local XAMPP environment. Upgrading to PHP >= 8.3 and evaluating migration to Laravel 13 is recorded as an independent post-foundation maintenance milestone prior to February 2027.

No phase may begin until the preceding phase's exit criteria have been validated.

---

## 2. High-Level Phased Roadmap

```text
Phase 0: Contract Review & Stakeholder Decision Approval
  └── Phase 1: Laravel 12 / PHP 8.2 Environment Verification & Foundation
        └── Phase 2: Database Schema & Relational Foundation
              └── Phase 3: Authentication & Session Infrastructure
                    └── Phase 4: RBAC & Division Isolation Engine
                          └── Phase 5: Master Reference Data
                                └── Phase 6: Incoming Letter Workflows
                                      └── Phase 7: Outgoing Letter Workflows
                                            └── Phase 8: Private File Storage & Streaming
                                                  └── Phase 9: Piagam & Public Claim Workflows
                                                        └── Phase 10: Search, Analytics & Reporting
                                                              └── Phase 11: Audit Logging & Security Hardening
                                                                    └── Phase 12: Legacy Migration Tooling
                                                                          └── Phase 13: E2E Verification & Benchmarking
                                                                                └── Phase 14: UAT & Production Cutover
                                                                                      └── Post-Foundation Maintenance: PHP 8.3+ & Laravel 13 Migration
```

---

## 3. Phase Specifications

### Phase 0: Contract Review & Human Approval
- **Objective:** Secure formal user sign-off on open architecture and product questions (`25-OPEN-QUESTIONS.md`).
- **Dependencies:** Completion of contract pack.
- **Requirement Groups:** `DEC-*`
- **Deliverables:** Approved entries in `24-DECISION-LOG.md`.
- **Exit Criteria:** All `BLOCKS_SCHEMA` and `BLOCKS_SECURITY` questions resolved.

### Phase 1: Laravel 12 / PHP 8.2 Environment Verification and Foundation
- **Objective:** Provision Laravel ^12.0 in a distinct target workspace on PHP ^8.2; verify environment variables, database connectivity, and CI/CD quality linters.
- **Dependencies:** Phase 0 approval.
- **Requirement Groups:** `SYS-001`, `PERF-001`, `DEC-TECH-002`
- **Deliverables:** Working Laravel 12 baseline, `.env.example`, PHPUnit/Pest test runner, pre-flight environment log.
- **Exit Criteria:**
  - PHP runtime version is verified to be at least **8.2** (`php -v`);
  - Composer executes under the expected PHP 8.2 binary;
  - All mandatory extensions (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`/`imagick`, `curl`, `xml`, `ctype`) are active;
  - Laravel framework version resolves strictly to **12.x** (`laravel/framework: ^12.0`);
  - Baseline application boots and returns HTTP 200 on health check;
  - Baseline tests pass (`php artisan test` executes cleanly);
  - MySQL/MariaDB database connection can be configured and connected;
  - Zero application domain features have been implemented prematurely.

### Phase 2: Database Migrations & Relational Foundation
- **Objective:** Construct all 12 relational database migrations, foreign keys, explicit indexes, and constraint definitions.
- **Dependencies:** Phase 1.
- **Requirement Groups:** `DB-001` to `DB-004`, `DM-001`
- **Deliverables:** Laravel migrations for `divisions`, `users` (with `must_reset_password`), `schools`, `incoming_letters` (no unique constraint on number), `outgoing_letters` (with explicit `letter_year` and `UNIQUE (division_id, letter_year, nomor_surat)`), `piagams`, `attachments` (with `is_missing` and `migration_status`), `pickup_categories`, `competition_categories`, `settings`, `audit_logs`, and `idempotency_keys`.
- **Exit Criteria:** `php artisan migrate:fresh` runs without error on MySQL 8.0/MariaDB.

### Phase 3: Authentication & Session Infrastructure
- **Objective:** Implement session-based authentication, password hashing (Bcrypt/Argon2id), rate limiting, CSRF protection, and account activation/reset workflows.
- **Dependencies:** Phase 2.
- **Requirement Groups:** `AUTH-001`, `SEC-001`, `BR-USER-003`, `DEC-AUTH-001`
- **Deliverables:** Auth controllers, session middleware, rate limiter configuration, activation/reset flows (Path A email token, Path B admin one-time secret).
- **Exit Criteria:** `TEST-AUTH-001`, `TEST-AUTH-005`, and `TEST-AUTH-006` pass; legacy passwords strictly cannot authenticate; 5 failed logins triggers HTTP 429 lockout.

### Phase 4: RBAC & Division Isolation Engine
- **Objective:** Implement 3-tier role hierarchy (Super Admin, Division Admin, Operator) and strict division scoping.
- **Dependencies:** Phase 3.
- **Requirement Groups:** `RBAC-001`, `TEN-001`, `BR-TEN-001` to `005`, `DEC-TEN-001`
- **Deliverables:** Division context middleware, Laravel Policies, division switch endpoints. Single-division ownership strictly enforced.
- **Exit Criteria:** `TEST-TEN-001` and `TEST-TEN-002` pass; cross-division access attempts return HTTP 403.

### Phase 5: Master & Reference Data Management
- **Objective:** Implement CRUD and API typeahead for schools (`schools`), pickup categories, competition categories, and global settings.
- **Dependencies:** Phase 4.
- **Requirement Groups:** `FUN-REF-01`, `FUN-DIV-01`, `FUN-SET-01`
- **Deliverables:** Reference controllers, FormRequests, seeders for initial school data.
- **Exit Criteria:** NPSN search returns matching active schools within < 100ms.

### Phase 6: Incoming Letters Workflows & Validation
- **Objective:** Implement full lifecycle for incoming letters (agenda tracking, metadata creation, editing, soft deletion, restoration, duplicate advisory check, and server-side idempotency).
- **Dependencies:** Phase 5.
- **Requirement Groups:** `FUN-SM-01`, `BR-SM-001`, `BR-SM-003`, `VAL-001`, `IDEM-001`, `DEC-LET-001`, `DEC-SYS-001`
- **Deliverables:** `IncomingLetterController`, `IdempotencyMiddleware`, FormRequests, Blade views / API resources.
- **Exit Criteria:** `TEST-LET-001`, `TEST-LET-003`, `TEST-LET-004`, `TEST-IDEM-001` pass; external number collisions are stored cleanly; duplicate advisory triggers appropriately.

### Phase 7: Outgoing Letters Workflows & Validation
- **Objective:** Implement full lifecycle for outgoing letters with explicit `letter_year` calculation and annual uniqueness enforcement.
- **Dependencies:** Phase 6.
- **Requirement Groups:** `FUN-SK-01`, `BR-SK-001`, `BR-SK-002`, `VAL-002`, `IDEM-001`, `DEC-LET-002`
- **Deliverables:** `OutgoingLetterController`, FormRequests, model observer for `letter_year` derivation.
- **Exit Criteria:** `TEST-LET-005`, `TEST-LET-006` pass; duplicate number within same division and year is rejected with HTTP 422.

### Phase 8: Private File Storage & Secure Streaming
- **Objective:** Implement private file storage, MIME validation, generated UUID naming, transactional compensation, durable missing file handling, and authorized streaming controllers.
- **Dependencies:** Phases 6 & 7.
- **Requirement Groups:** `FILE-001`, `FILE-002`, `FILE-003`, `SEC-002`, `DEC-FILE-003`
- **Deliverables:** `StorageService`, `AttachmentController`, streaming routes, missing attachment UI badges.
- **Exit Criteria:** `TEST-FILE-001`, `TEST-FILE-002`, `TEST-FILE-003` pass; missing legacy files return clean 404 without application crash.

### Phase 9: Piagam Workflows & Public Claim Portal
- **Objective:** Implement certificate CRUD, public claim form with HTML5 Canvas signature pad, and certificate preview.
- **Dependencies:** Phase 8.
- **Requirement Groups:** `FUN-PG-01`, `FUN-PG-02`, `BR-PG-001`, `VAL-003`, `IDEM-001`
- **Deliverables:** `PiagamController`, `PublicPiagamController`, canvas signature parser.
- **Exit Criteria:** `TEST-PG-001` passes.

### Phase 10: Advanced Search, Reporting & Analytics
- **Objective:** Implement cross-archive multi-criteria search, Excel/PDF recap export, and live SQL-aggregated dashboard analytics.
- **Dependencies:** Phase 9.
- **Requirement Groups:** `FUN-SRC-01`, `FUN-REP-01`, `FUN-DASH-01`
- **Deliverables:** Search engine, PhpSpreadsheet export service, dashboard controllers.
- **Exit Criteria:** True monthly aggregates match database record counts; exports sanitized.

### Phase 11: Audit Logging & Operational Hardening
- **Objective:** Complete immutable audit log generation, error handler customization, and shared hosting optimization (`.htaccess`).
- **Dependencies:** Phase 10.
- **Requirement Groups:** `AUD-001`, `ERR-001`, `NFR-001`
- **Deliverables:** Custom exception handler, audit event listeners, artisan maintenance tasks.
- **Exit Criteria:** All auditable actions record IP, user, and attribute diffs.

### Phase 12: Legacy Data Migration Tooling
- **Objective:** Build and validate ETL artisan commands (`sisurat:migrate-legacy`).
- **Dependencies:** Phase 11.
- **Requirement Groups:** `MIG-001`, `BR-FILE-003`, `DEC-AUTH-001`
- **Deliverables:** Artisan migration commands, reconciliation validators, quarantine handlers, missing attachment durable logger.
- **Exit Criteria:** `TEST-MIG-001`, `TEST-MIG-002` dry runs achieve 100% metadata ingestion against `db.txt` snapshot without dropping records on Drive 404s.

### Phase 13: E2E Verification & Performance Benchmarking
- **Objective:** Execute full automated test suite and conduct performance benchmarks against legacy baseline.
- **Dependencies:** Phase 12.
- **Requirement Groups:** `PERF-001`, `TEST-001`
- **Deliverables:** Benchmark report comparing results to `19-PERFORMANCE-BASELINE.md`.
- **Exit Criteria:** 100% test pass rate; file upload and list latency meet target objectives.

### Phase 14: User Acceptance Testing (UAT) & Production Cutover
- **Objective:** Final operator training, staging verification, DNS cutover, and legacy write freeze.
- **Dependencies:** Phase 13.
- **Deliverables:** Production deployment, cutover execution.
- **Exit Criteria:** Formal stakeholder sign-off and smooth system adoption.

---

## 4. Post-Foundation Maintenance Milestone

### Milestone: PHP >= 8.3 Runtime Upgrade & Laravel 13 Migration
- **Objective:** Upgrade the local development and production hosting runtimes to PHP >= 8.3 and evaluate migration from Laravel 12 to Laravel 13 before Laravel 12 security support expires (February 24, 2027).
- **Dependencies:** Successful completion of Phase 14; availability of PHP 8.3+ runtime in the hosting environment.
- **Scope:**
  - Verify PHP >= 8.3 runtime availability on local and server environments;
  - Update `composer.json` platform requirement to `"php": "^8.3"`;
  - Run `composer update laravel/framework:^13.0` and audit breaking changes in third-party packages;
  - Run full test suite (`php artisan test`) and execute automated regression tests;
  - Deploy upgraded runtime and application bundle to staging and production.
- **Strict Boundary:** This milestone is independent and separate from the initial SISURAT v1 release. It must not block or complicate the execution of Phases 1 through 14 under the approved Laravel 12 / PHP 8.2 baseline.
