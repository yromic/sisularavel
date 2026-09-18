# AGENTS.md — Implementation Governance for Autonomous AI Agents

> **Context:** This document governs any future AI agent or software engineer tasked with building the **SISURAT** system on Laravel 12 (PHP ^8.2). It must be copied to the root directory of the new Laravel project repository upon project initialization.

---

> [!WARNING]
> **Target Framework Lifecycle Notice:**  
> Laravel 12 is selected for the initial SISURAT implementation because the local XAMPP development environment provides PHP 8.2. Laravel 12 bug fixes conclude on August 13, 2026, and security fixes end on February 24, 2027. Upgrading the runtime to PHP 8.3 or newer and migrating SISURAT to Laravel 13 must be treated as a planned post-foundation maintenance milestone once the development and server environments are upgraded.

---

## 1. Prime Directives for Future Implementation Agents

As an autonomous implementation agent, you **MUST** comply with the following 32 governance rules at all times during the development of SISURAT:

1. **Mandatory Contract Review:** Read and internalize the complete contract package in `sisurat-laravel-contract/` (starting with `README.md`) before creating files, running commands, or generating code.
2. **Strict Legacy Isolation:** Treat the legacy Google Apps Script, Google Sheets (`db.txt`), and legacy HTML/JS codebases as **completely unavailable**. Do not prompt the user for legacy source access. Everything you need is codified in this contract pack.
3. **Zero Hallucination of Business Rules:** **NEVER** invent undocumented business rules, fields, statuses, or workflows. If a requirement is not specified in `docs/`, check `docs/25-OPEN-QUESTIONS.md`.
4. **Halt on Material Ambiguity:** When an unresolved question materially affects database schemas, security boundaries, or business logic, **STOP**, record the proposed options in `docs/24-DECISION-LOG.md` and `docs/25-OPEN-QUESTIONS.md`, and request human stakeholder confirmation.
5. **Enforce Requirement Classification:** Strictly distinguish between normative `VERIFIED` business requirements and architectural recommendations. Do not elevate suggestions to immutable rules.
6. **Target Framework Baseline (Laravel 12 / PHP ^8.2):** Build strictly on **Laravel ^12.0** on **PHP ^8.2** (supported range: PHP 8.2–8.5). Do not attempt to initialize or install Laravel 13 during the initial implementation phase (`DEC-TECH-002`).
7. **Environment & Runtime Verification (Pre-Flight Protocol):**
   - Run `php -v` and record the exact PHP runtime version.
   - Verify that PHP is at least **8.2**. If PHP is below 8.2, **STOP IMMEDIATELY** and alert the user.
   - Verify that Composer executes under the intended PHP 8.2+ binary.
   - Verify that all mandatory PHP extensions are enabled: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` (or `imagick`), `curl`, `xml`, and `ctype`.
   - Never use `--ignore-platform-reqs` during dependency installation or project creation.
8. **Framework Version Boundaries:**
   - Initialize Laravel using the explicit `^12.0` version constraint (e.g. `composer create-project laravel/laravel:^12.0 .`).
   - Never silently install Laravel 13.
   - Never silently downgrade below Laravel 12.
   - Do not change the framework major version without explicit user authorization.
   - Use official Laravel 12 conventions, directory structures, and APIs. Do not attempt to backport speculative or hypothetical Laravel 13 features into Laravel 12.
9. **Upgrade Compatibility Mindset:**
   - Retain code structure and idioms compatible with a future Laravel 13 / PHP 8.3+ upgrade where reasonably possible.
   - Avoid undocumented or brittle Laravel 12-specific workarounds; document any version-specific idioms in `docs/24-DECISION-LOG.md`.
   - Ensure third-party package dependencies do not block future Laravel 13 / PHP 8.3 migration.
   - Avoid premature Laravel 13 implementation while the approved environment baseline remains PHP 8.2.
10. **Shared-Hosting Compatibility:** Maintain 100% compatibility with conventional shared hosting (Apache/LiteSpeed, cPanel). Never mandate Redis, Docker, daemon queue workers, root SSH access, or Node.js production runtimes unless explicitly authorized.
11. **Relational Database Integrity:** Implement schemas strictly for **MySQL 8.0+ / MariaDB 10.4+** using InnoDB engines, strict mode, proper foreign key constraints, and performance indexes.
12. **Server-Side Authorization Authority:** Enforce all access control server-side using Laravel Policies, Middleware, and Form Requests. Client-side UI controls are for presentation only and must never be trusted for security.
13. **Explicit Division Tenant Scoping:** Every division-scoped query **MUST** be explicitly scoped to the active division context (via Eloquent Global Scopes or explicit service constraints).
14. **Deny Cross-Division Access by Default:** Users assigned to Division A must be denied access to Division B data with HTTP 403 Forbidden under all circumstances, even if they manipulate URL route IDs or payload parameters.
15. **Controlled Super Admin Switching:** Super Admin cross-division access is permitted only through an explicit, authorized context-switching workflow backed by validated session state.
16. **Private File Storage by Default:** Store all attachments and digital signature images in private disk storage (`storage/app/private`). **NEVER** place sensitive archives in public directories (`public/storage`) or generate unauthenticated direct URLs.
17. **Authorized Streaming Gateways:** Serve file previews and downloads exclusively through authenticated controller streaming routes (`Storage::download()`) protected by Policy authorization.
18. **Mandatory Database Transactions:** Wrap all multi-step and multi-table database mutations inside `DB::transaction()` to ensure atomicity.
19. **Deliberate Storage Consistency:** Implement compensating rollback logic for file uploads: if a database insertion fails, immediately delete the staged binary attachment from disk storage.
20. **Immutable Record Addressing:** Address and mutate records exclusively via immutable primary keys (`id` or `uuid`). **NEVER** address or mutate data by spreadsheet row numbers (`row_number`).
21. **Reject Legacy Workarounds:** Do not port obsolete legacy mechanisms such as 72 KB chunk uploads, tab-partitioned spreadsheets, public Google Drive link sharing, or fake percentage charts.
22. **Test-Driven Traceability:** Every feature, policy, and validation rule must be accompanied by automated tests (Pest / PHPUnit) explicitly linking back to the relevant Requirement ID (`BR-xxx`, `TEN-xxx`, etc.).
23. **Continuous Traceability Updates:** After implementing any requirement, update [`docs/27-TRACEABILITY-MATRIX.md`](docs/27-TRACEABILITY-MATRIX.md) to record the implementing class, migration, and test file.
24. **Record Architectural Decisions (ADR):** Any significant technical or architectural decision made during development must be documented in [`docs/24-DECISION-LOG.md`](docs/24-DECISION-LOG.md) before code completion.
25. **Focused Implementation:** Avoid unrelated refactoring or introducing complex enterprise patterns (CQRS, Event Sourcing, microservices) that exceed the scope of the contract pack.
26. **Acceptance Verification Gate:** Never mark a feature or implementation phase complete without executing and passing all related scenarios defined in [`docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md`](docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md).
27. **Preserve Academic Research Baseline:** Ensure that response times, queries, and resource footprints are benchmarkable to evaluate performance gains against legacy baseline measurements preserved in [`docs/19-PERFORMANCE-BASELINE.md`](docs/19-PERFORMANCE-BASELINE.md).
28. **Default to Standard Web Application:** Do not implement offline sync queues, service workers, or PWA features unless a formal stakeholder requirement is approved and recorded in the decision log.
29. **Separate Letter Numbering Semantics:** Do NOT conflate incoming and outgoing uniqueness rules. Incoming letters from external senders permit number collisions (PK `id` authoritative; duplicate checks are advisory only). Outgoing letters require explicit `letter_year` and annual uniqueness `UNIQUE (division_id, letter_year, nomor_surat)` (`DEC-LET-001`, `DEC-LET-002`).
30. **Deprecate Legacy Password Formats:** Never accept legacy plaintext or salted SHA-256 hashes. Migrated accounts must receive random unusable hashes and `must_reset_password = true`, requiring activation via Path A (Email) or Path B (Admin one-time secret) (`DEC-AUTH-001`).
31. **Durable Missing Attachment Handling:** Missing/404 legacy Drive files must never abort letter ingestion. Persist attachment records with `is_missing = true` and explicit `migration_status` ENUM for operational tracking and graceful UI degradation (`DEC-FILE-003`).
32. **Ratify Single-Division Ownership for v1:** In v1, every letter is owned by exactly one `division_id`. Cross-division transfers or inter-division forwarding are formally deferred to post-v1 (`DEC-TEN-001`).
33. **Server-Side Mutation Idempotency:** State-mutating POST operations must require and enforce `Idempotency-Key` headers backed by the `idempotency_keys` table. Client button disabling is an ergonomic shield, not an integrity guarantee (`DEC-SYS-001`).

---

## 2. Implementation Readiness Verification

Before writing code in any development phase, verify your plan against:
- [`docs/22-TARGET-ARCHITECTURE-BOUNDARIES.md`](docs/22-TARGET-ARCHITECTURE-BOUNDARIES.md) (Architecture structure)
- [`docs/23-IMPLEMENTATION-SEQUENCE.md`](docs/23-IMPLEMENTATION-SEQUENCE.md) (Phase prerequisites & exit criteria)
- [`docs/21-SHARED-HOSTING-CONSTRAINTS.md`](docs/21-SHARED-HOSTING-CONSTRAINTS.md) (Deployment constraints)
- [`docs/24-DECISION-LOG.md`](docs/24-DECISION-LOG.md) (`DEC-TECH-002`: Initial Framework Baseline)
