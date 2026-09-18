# SISURAT V1 Implementation Plan

**Goal:** Build the approved SISURAT v1 Laravel 12 application from the v1.3.0 contract pack.

**Architecture:** Laravel Blade, Tailwind CSS, and minimal Alpine.js for a shared-hosting-friendly server-rendered app. Business workflows live in Form Requests, Policies, middleware, and application services, with Eloquent models and private disk storage underneath.

**Tech Stack:** PHP 8.2, Laravel 12, PHPUnit, MySQL 8.0+/MariaDB 10.5+, Blade, Tailwind CSS, Alpine.js, one SVG icon library.

**Spec:** Root contract pack: `README.md`, `AGENTS.md`, `docs/`, `contracts/`, `examples/`, `reviews/`.

## Global Constraints

- Target framework: Laravel `^12.0`; installed baseline verified as Laravel `12.69.2`.
- Target runtime: PHP `^8.2`; local CLI verified as PHP `8.2.12`.
- Mandatory PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` or `imagick`, `curl`, `xml`, `ctype`.
- Target database: MySQL 8.0+ / MariaDB 10.5+, InnoDB, utf8mb4.
- Storage: attachments and signatures must remain private under `storage/app/private`.
- Tenancy: v1 records have exactly one immutable `division_id`; cross-division access is denied by default.
- Security: no legacy password compatibility, no public document URLs, no row-number identity, no `--ignore-platform-reqs`.
- Scope: do not implement `DEFERRED_FUTURE_FEATURE`, `DEPRECATED_DO_NOT_PORT`, or unresolved user-decision items.

---

## Phase 0: Contract Intake and Environment Verification

**Objective:** Validate contract readiness, current Laravel baseline, runtime, tooling, and blockers before domain implementation.

**Requirement IDs:** `SYS-001`, `DEC-TECH-002`, `DEC-DOC-001`.

**Dependencies:** Contract pack present at repository root.

**Expected files/components:** `docs/implementation/*`, phase verification log, blocker record.

**Tests to write first:** None; Phase 0 is read-only validation and control-file setup.

**Implementation steps:**
- Read `AGENTS.md`, root `README.md`, all contract docs, YAML contracts, examples, and review artifacts.
- Validate contract version `1.3.0`, Laravel `^12.0`, PHP `^8.2`, readiness verdict, resolved P0 questions, and YAML parse.
- Run environment preflight: `php -v`, `composer --version`, `git --version`, `node --version`, `npm --version`, `php -m`.
- Run Laravel baseline verification: `php artisan --version`, `composer validate --strict`, `php artisan test`.
- Record blocker if any mandatory extension or infrastructure capability is missing.

**Quality gate:** PASS only when all mandatory runtime extensions are loaded, baseline tests pass, and no active P0 contract blocker remains.

**Rollback considerations:** Documentation-only phase; revert control-file additions if contract intake must be restarted.

**Completion criteria:** Phase 0 is blocked until `gd` or `imagick` is enabled in the active PHP CLI/runtime.

## Phase 1: Laravel Foundation

**Objective:** Finalize Laravel 12 foundation, safe local env, health route, session/cache defaults, logging/error baseline, and project repository checkpoint.

**Requirement IDs:** `SYS-001`, `PERF-001`, `DEC-TECH-002`.

**Dependencies:** Phase 0 verified; `gd` or `imagick` loaded.

**Expected files/components:** `.env.example`, `routes/web.php`, baseline feature tests, config updates, git repository.

**Tests to write first:** Health-check feature test asserting app boots with HTTP 200 and no domain features are present prematurely.

**Quality gate:** `composer validate --strict`, `php artisan test`, Laravel 12.x confirmed, database connection strategy documented.

## Phase 2: Database Schema and Relational Foundation

**Objective:** Implement all 12 contract tables with foreign keys, unique constraints, indexes, models, factories, seeders, and rollback coverage.

**Requirement IDs:** `DB-001`, `DM-001`, `DEC-LET-001`, `DEC-LET-002`, `DEC-FILE-003`, `DEC-SYS-001`.

**Expected migrations:** `divisions`, `users`, `schools`, `incoming_letters`, `outgoing_letters`, `piagams`, `attachments`, `pickup_categories`, `competition_categories`, `settings`, `audit_logs`, `idempotency_keys`.

**Tests to write first:** Migration/schema tests proving incoming numbers are non-unique, outgoing numbers are unique per division/year, and required indexes/foreign keys exist.

**Quality gate:** `php artisan migrate:fresh` and rollback tests pass on the configured local database.

## Phase 3: Authentication and Session Infrastructure

**Objective:** Implement login/logout, rate limiting, secure password hashing, Path A reset, Path B admin activation secret, and mandatory password reset gate.

**Requirement IDs:** `AUTH-001`, `SEC-001`, `DEC-AUTH-001`, `FUN-USR-01`.

**Tests to write first:** Legacy password rejection, reset token lifecycle, admin activation secret one-time display, login rate limit.

**Quality gate:** `TEST-AUTH-SEC-001`, `TEST-AUTH-ACT-001`, `TEST-AUTH-ACT-002` pass.

## Phase 4: RBAC and Division Isolation Engine

**Objective:** Implement role hierarchy, division context middleware, policies, global/super-admin switching, and default-deny cross-division behavior.

**Requirement IDs:** `RBAC-001`, `TEN-001`, `TEN-002`, `DEC-TEN-001`.

**Tests to write first:** Cross-division URL tampering returns 403; super admin context switching scopes queries without ownership mutation.

**Quality gate:** Negative authorization and division ownership tests pass.

## Phase 5: Master and Reference Data

**Objective:** Implement division, school, pickup category, competition category, user-management, and settings screens/services.

**Requirement IDs:** `FUN-REF-01`, `FUN-DIV-01`, `FUN-USR-01`.

**Tests to write first:** NPSN uniqueness/typeahead, scoped category management, admin-only school management.

**Quality gate:** Reference data CRUD and authorization tests pass.

## Phase 6: Incoming Letters

**Objective:** Implement incoming letter create/read/update/delete/restore, validation, duplicate advisory, mobile-first views, audit, idempotency.

**Requirement IDs:** `FUN-SM-01`, `FILE-002`, `IDEM-001`, `DEC-LET-001`, `DEC-SYS-001`.

**Tests to write first:** External number collision allowed; suspected duplicate advisory; idempotent double submit; cross-division denial.

**Quality gate:** Incoming letter acceptance tests pass and Q-01D remains documented as non-blocking unless stakeholder decision arrives.

## Phase 7: Outgoing Letters

**Objective:** Implement outgoing letter workflow with automatic `letter_year` derivation and annual division uniqueness.

**Requirement IDs:** `FUN-SK-01`, `DEC-LET-002`, `IDEM-001`.

**Tests to write first:** Duplicate same division/year rejected; same number across divisions/years allowed; year recalculates on date edit.

**Quality gate:** Outgoing letter acceptance tests pass.

## Phase 8: Private File Storage and Streaming

**Objective:** Implement upload validation, private storage, checksums, authorized streaming, remediation, storage compensation, missing legacy attachment behavior.

**Requirement IDs:** `FILE-001`, `FILE-002`, `FILE-003`, `SEC-002`, `DEC-FILE-003`.

**Tests to write first:** Unauthorized preview denied, missing attachment safe 404, remediation upload clears missing flag, storage failure rolls back database writes.

**Quality gate:** File security and consistency tests pass.

## Phase 9: Piagam and Public Claim Workflows

**Objective:** Implement piagam CRUD, public NPSN lookup, claim flow, PNG signature storage, and receipt view.

**Requirement IDs:** `FUN-PG-01`, `FUN-PG-02`, `VAL-003`.

**Tests to write first:** Public claim with valid signature stores private PNG and updates claimant metadata; blank signature rejected.

**Quality gate:** Piagam public and admin scenarios pass.

## Phase 10: Search, Analytics, and Reporting

**Objective:** Implement scoped search, dashboard aggregates, CSV/Excel/PDF export, and export formula-injection protection.

**Requirement IDs:** `FUN-SRC-01`, `FUN-DASH-01`, `FUN-REP-01`, `SEC-004`, `PERF-001`.

**Tests to write first:** Scoped search excludes other divisions; aggregates match database counts; exported dangerous cell values are escaped.

**Quality gate:** Search/reporting tests pass.

## Phase 11: Audit Logging and Operational Hardening

**Objective:** Complete immutable audit logging, error envelopes, rate-limit audit events, pruning commands, and shared-hosting hardening.

**Requirement IDs:** `AUD-001`, `ERR-001`, `NFR-001`.

**Tests to write first:** Audit redaction, append-only behavior, structured error envelopes, maintenance command behavior.

**Quality gate:** Security-hardening and audit tests pass.

## Phase 12: Legacy Migration Tooling

**Objective:** Build dry-run and import commands using contract-defined mappings and anonymized fixtures only.

**Requirement IDs:** `MIG-001`, `DEC-AUTH-001`, `DEC-FILE-003`.

**Tests to write first:** Dry-run reconciliation counts, migrated users get unusable random hashes and `must_reset_password`, missing Drive files create durable attachment rows.

**Quality gate:** Migration dry-run and reconciliation tests pass without requiring legacy repository access.

## Phase 13: E2E Verification and Benchmarking

**Objective:** Run full suite, negative security coverage, UI/browser checks where available, and benchmark plan against legacy baseline.

**Requirement IDs:** `TEST-*`, `PERF-001`, `UI-001` to `UI-007`.

**Tests to write first:** Browser/responsive tests for 360px and 1280px workflows if Playwright or equivalent is configured.

**Quality gate:** Full relevant automated suite passes and benchmark results are recorded.

## Phase 14: Deployment Readiness and Cutover Package

**Objective:** Complete shared-hosting deployment docs, artifact packaging strategy, UAT checklist, and production cutover runbook.

**Requirement IDs:** `NFR-*`, `SYS-001`, `DEC-DOC-001`.

**Tests to write first:** Configuration/deployment smoke tests where possible.

**Quality gate:** Deployment readiness is documented; production secrets and access remain manual-only.
