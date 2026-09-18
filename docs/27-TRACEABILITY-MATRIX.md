# 27. TRACEABILITY MATRIX

## 1. Scope & Traceability Verification

This matrix establishes complete forward and backward traceability connecting legacy evidence and approved architectural decisions to business rules, functional requirements, relational entities, access control boundaries, acceptance tests, and migration rules (v1.3.0).

Every requirement in the system is verified to possess empirical evidence or an explicit architectural decision, explicit acceptance scenarios, and defined migration pathways.

---

## 2. Master Traceability Table

| Requirement ID | Source / Evidence | Feature | Business Rules | Domain Entities | RBAC / Tenant Rules | Acceptance Test IDs | Migration Rules | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **SYS-001** | `EVID-14`, `DEC-TECH-002` | System Foundation (Laravel 12 / PHP 8.2) | `BR-TEN-001` | All | `RBAC-001`, `TEN-001` | `TEST-AUTH-001` | `MIG-001` | `VERIFIED_RECONCILED` |
| **SYS-002** | `EVID-07` | Operational Audit Trail | `SYS-002` | `AuditLog` | Append-only; Super Admin | `TEST-AUTH-001`, `TEST-LET-001` | `MIG-010` | `VERIFIED` |
| **AUTH-001** | `EVID-04`, `DEC-AUTH-001` | Authentication & Password Activation | `BR-USER-001`, `BR-USER-003` | `User` | Authenticated session required; activation required for migrated users | `TEST-AUTH-001`, `TEST-AUTH-005`, `TEST-AUTH-006` | `MIG-002` (Random hash + must_reset) | `VERIFIED_RECONCILED` |
| **TEN-001** | `EVID-13`, `EVID-15`, `DEC-TEN-001` | Division Multi-Tenancy & Single Ownership | `BR-TEN-001` to `005` | `Division`, `IncomingLetter`, `OutgoingLetter`, `Piagam` | Strict single-division isolation; Super Admin cross-division override; inter-division routing deferred | `TEST-TEN-001`, `TEST-TEN-002` | `MIG-003` (Sheet-to-FK) | `VERIFIED_RECONCILED` |
| **TEN-002** | `EVID-14` | Super Admin Scope | `BR-TEN-002` | `Division`, `User` | Dynamic active division context | `TEST-TEN-001` | (Context state) | `VERIFIED` |
| **RBAC-001** | `EVID-03`, `EVID-14` | Role-Based Access Control | `BR-USER-002` | `User`, `Role` | 3 tiers: Super Admin, Division Admin, Operator | `TEST-TEN-001` | `MIG-004` (Role map) | `VERIFIED` |
| **FUN-SM-01** | `EVID-01`, `EVID-09`, `DEC-LET-001` | Incoming Letters CRUD & Non-Uniqueness | `BR-SM-001`, `BR-SM-003`, `BR-LET-001` to `002` | `IncomingLetter`, `Attachment` | PK authoritative; external collisions allowed; advisory duplicate warning | `TEST-LET-001`, `TEST-LET-003`, `TEST-LET-004` | `MIG-005` | `VERIFIED_RECONCILED` |
| **FUN-SK-01** | `EVID-01`, `EVID-09`, `DEC-LET-002` | Outgoing Letters CRUD & Annual Scope | `BR-SK-001`, `BR-SK-002`, `BR-LET-001` to `002` | `OutgoingLetter`, `Attachment` | Read/Create (All roles); explicit letter_year; annual unique constraint | `TEST-LET-001`, `TEST-LET-005`, `TEST-LET-006` | `MIG-006` | `VERIFIED_RECONCILED` |
| **FUN-PG-01** | `EVID-01`, `EVID-10` | Piagam Management | `BR-PG-001` to `002` | `Piagam`, `School`, `Attachment` | Read/Create (All roles), Update/Delete (Admin+) | `TEST-PG-001` | `MIG-007` | `VERIFIED` |
| **FUN-PG-02** | `EVID-10` | Public Piagam Claim | `BR-PG-001` | `Piagam`, `Attachment` | Public / Guest unauthenticated (Rate-limited) | `TEST-PG-001` | `MIG-007` | `VERIFIED` |
| **FILE-001** | `EVID-06`, `EVID-12` | Private Attachment Storage | `BR-FILE-001` | `Attachment` | Private by default; authenticated streaming only | `TEST-FILE-001` | `MIG-008` (Drive download) | `VERIFIED` |
| **FILE-002** | `EVID-11`, `EVID-12` | Standard Multipart Upload | `BR-FILE-002` | `Attachment` | Authenticated operators/admins; max 10MB | `TEST-LET-001` | (Replaces chunking) | `VERIFIED` |
| **FILE-003** | `EVID-06`, `DEC-FILE-003` | Durable Missing Attachment Handling | `BR-FILE-003` | `Attachment` | Stored with is_missing=true, migration_status; non-fatal UI warning & 404 response | `TEST-FILE-002`, `TEST-FILE-003` | `MIG-008` (Preserves letter) | `VERIFIED_RECONCILED` |
| **IDEM-001** | `EVID-17`, `DEC-SYS-001` | Server-Side Mutation Idempotency | `BR-IDEM-001` | `IdempotencyKey` | Enforced via Idempotency-Key header on state-mutating endpoints; 24h retention | `TEST-IDEM-001`, `TEST-IDEM-002` | N/A (Runtime security) | `VERIFIED_RECONCILED` |
| **FUN-REF-01** | `EVID-01` | School Reference Master | `BR-TEN-004` | `School` | Read (All), Write (Super Admin) | `TEST-PG-001` | `MIG-009` | `VERIFIED` |
| **FUN-USR-01** | `EVID-01`, `EVID-08` | User Administration | `BR-USER-001` to `003` | `User` | Super Admin (Global), Division Admin (Own division) | `TEST-AUTH-001`, `TEST-AUTH-006` | `MIG-004` | `VERIFIED` |
| **FUN-DIV-01** | `EVID-01`, `EVID-13` | Division Management | `BR-TEN-003` | `Division` | Super Admin Only | `TEST-TEN-001` | `MIG-003` | `VERIFIED` |
| **FUN-DASH-01**| `EVID-16` | Dashboard Analytics | (Dynamic SQL aggregate) | All document tables | Scoped to active division (Global for Super Admin) | `TEST-LET-001` | (Replaces fake multipliers) | `VERIFIED` |
| **FUN-SRC-01** | `EVID-14` | Advanced Archive Search | `BR-TEN-001` | All document tables | Scoped to active division | `TEST-TEN-001` | `MIG-005`, `MIG-006` | `VERIFIED` |
| **FUN-REP-01** | `EVID-01` | Recap & Export | `SEC-004` | All document tables | Scoped to active division | `TEST-LET-001` | (Sanitized export) | `VERIFIED` |
| **AUD-001** | `EVID-07` | Immutable Audit Logging | `SYS-002` | `AuditLog` | Append-only; viewable only by Super Admin | `TEST-AUTH-001`, `TEST-LET-001` | `MIG-010` | `VERIFIED` |
| **SEC-001** | `EVID-04`, `EVID-05` | Credential & Session Security | `BR-USER-001` | `User` | Bcrypt, HttpOnly cookies, brute force lockout | `TEST-AUTH-001` | `MIG-002` | `VERIFIED` |
| **SEC-002** | `EVID-06` | Document Protection | `BR-FILE-001` | `Attachment` | Rejects public URLs; authorizes before streaming | `TEST-FILE-001` | `MIG-008` | `VERIFIED` |
| **UI-001** | `DEC-UI-001` | Mobile-First Responsive Architecture | `BR-TEN-001` | All user-facing views | All authenticated & guest actors | `TEST-UI-MOB-001`, `TEST-UI-MOB-004` | (Presentation layer) | `APPROVED_TARGET_DECISION` |
| **UI-002** | `DEC-UI-001` | Touch-Friendly Controls (>= 44px) | N/A | All user-facing controls | Zero hover dependence | `TEST-UI-MOB-001`, `TEST-UI-MOB-005` | (Interaction layer) | `APPROVED_TARGET_DECISION` |
| **UI-003** | `DEC-UI-002` | Functional Restrained Styling | N/A | All views | Clean, high-contrast institutional aesthetic | `TEST-UI-MOB-001` | (Design tokens) | `APPROVED_TARGET_DECISION` |
| **UI-004** | `DEC-UI-003` | Unified SVG Icon System | N/A | All icon elements | Accessible labels; no emoji or platform symbols | `TEST-UI-MOB-006` | (Icon asset map) | `APPROVED_TARGET_DECISION` |
| **UI-005** | `DEC-UI-001` | Inline Validation & Form State Retention | `VAL-001` | All FormRequests | Form state preservation on HTTP 422 | `TEST-UI-MOB-002` | (Client error state) | `APPROVED_TARGET_DECISION` |
| **UI-006** | `DEC-UI-001` | Duplicate Submission Shield | `NFR-UI-04` | All Form submissions | Disable submit button on initial tap | `TEST-UI-MOB-003` | (Client submit state) | `APPROVED_TARGET_DECISION` |
| **UI-007** | `DEC-UI-004` | Desktop Dense Management Grid | `BR-TEN-001` | Dense tables (>= 1024px) | Parity with mobile data model & permissions | `TEST-UI-DSK-001` | (Responsive desktop view) | `APPROVED_TARGET_DECISION` |

---

## 3. Gap Analysis & Traceability Audit

1. **Requirements with No Evidence or Decision:** `0` (All requirements trace to legacy evidence or formally approved decisions `DEC-UI-001` to `004`, `DEC-LET-001` to `002`, `DEC-AUTH-001`, `DEC-FILE-003`, `DEC-TEN-001`, `DEC-SYS-001`).
2. **Features with No Acceptance Test:** `0` (Every P0/P1 feature maps to an explicit scenario in `20-TEST-AND-ACCEPTANCE-CONTRACT.md`).
3. **Entities with No Owning Feature:** `0` (All 12 relational tables, including `idempotency_keys`, have functional owners).
4. **Permissions with No Negative Security Test:** `0` (Negative tests exist for unauthenticated access, cross-division access, unauthorized role elevation, and payload tampering).
5. **Mobile Requirements with No Acceptance Coverage:** `0` (All mobile-first requirements map directly to `TEST-UI-MOB-001` through `006`).
6. **Audit Boundary Verification:** Traceability is verified against the identified inventory of 28 functional features and 6 reconciled architectural requirements. Stakeholder validation may identify additional post-v1 capabilities.

---

## 4. Implementation Traceability Addendum

| Requirement ID | Implementation Evidence | Test Evidence | Status |
| :--- | :--- | :--- | :--- |
| `SYS-001` | Laravel Framework `12.69.2`; PHP `8.2.12`; `gd` and required extensions loaded; `/health` route in `routes/web.php`; local MariaDB database `sisurat_dev`; Vite asset build | `tests/Feature/HealthCheckTest.php`; `php artisan test` = 3 tests / 4 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_1` |
| `DB-001` | `database/migrations/0001_01_01_000000_create_users_table.php`; `database/migrations/0001_01_01_000003_create_sisurat_domain_tables.php`; model classes under `app/Models`; base factories under `database/factories`; PHPUnit uses MariaDB test database `sisurat_test` | `tests/Feature/Database/SchemaContractTest.php`; `tests/Feature/Database/ModelFoundationTest.php`; `php artisan test` = 10 tests / 66 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_2` |
| `AUTH-001` | `app/Http/Controllers/Auth/AuthenticatedSessionController.php`; `app/Http/Controllers/Auth/PasswordActivationController.php`; `app/Http/Middleware/EnsurePasswordIsActivated.php`; auth routes in `routes/web.php` | `tests/Feature/Auth/AuthenticationContractTest.php`; `tests/Feature/Auth/PasswordActivationContractTest.php`; `php artisan test` = 19 tests / 114 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_3` |
| `SEC-001` | Login rate limiter, inactive-account rejection, session regeneration/invalidation, hashed reset tokens, Bcrypt activation secrets, redacted audit logging in `app/Services/AuditLogger.php` | `tests/Feature/Auth/AuthenticationContractTest.php`; `tests/Feature/Auth/PasswordActivationContractTest.php`; `vendor\bin\pint --test` passed | `IMPLEMENTED_VERIFIED_PHASE_3` |
| `TEN-001` | `app/Services/DivisionContext.php`; `app/Http/Middleware/EnsureActiveAccount.php`; division-scoped incoming letter routes/controllers; cross-division audit logging | `tests/Feature/Tenancy/DivisionIsolationContractTest.php`; `php artisan test` = 24 tests / 130 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_4` |
| `TEN-002` | Super-admin session `active_division_id` switching in `app/Http/Controllers/Api/DivisionContextController.php` | `tests/Feature/Tenancy/DivisionIsolationContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_4` |
| `RBAC-001` | Role checks for super-admin division switching, division-scoped access, and division-admin same-division activation-secret authorization | `tests/Feature/Tenancy/DivisionIsolationContractTest.php`; `tests/Feature/Auth/PasswordActivationContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_4` |
| `FUN-REF-01` | `app/Http/Controllers/Api/SchoolController.php`; `app/Http/Controllers/Api/ReferenceCategoryController.php`; `GET/POST /api/v1/schools`; division-scoped pickup and competition category routes | `tests/Feature/Reference/MasterReferenceDataContractTest.php`; `php artisan test` = 30 tests / 156 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_5` |
| `FUN-USR-01` | `app/Http/Controllers/Api/UserManagementController.php`; `POST /api/v1/users`; division-admin same-division user creation and role-escalation denial | `tests/Feature/Reference/MasterReferenceDataContractTest.php`; `tests/Feature/Auth/PasswordActivationContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_5` |
| `FUN-DIV-01` | `app/Http/Controllers/Api/DivisionManagementController.php`; `POST /api/v1/divisions`; `PATCH /api/v1/divisions/{division}/status` | `tests/Feature/Reference/MasterReferenceDataContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_5` |
| `FUN-SET-01` | `app/Http/Controllers/Api/SettingController.php`; `PUT /api/v1/settings/{key}` | `tests/Feature/Reference/MasterReferenceDataContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_5` |
| `FUN-SM-01` | `app/Http/Controllers/Api/IncomingLetterController.php`; `app/Http/Requests/StoreIncomingLetterRequest.php`; incoming create/update/delete/restore routes; duplicate advisory and immutable ownership enforcement | `tests/Feature/Incoming/IncomingLetterWorkflowContractTest.php`; `tests/Feature/Tenancy/DivisionIsolationContractTest.php`; `php artisan test` = 36 tests / 206 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_6` |
| `IDEM-001` | `app/Http/Middleware/EnsureIdempotentMutation.php`; `idempotent` route middleware alias in `bootstrap/app.php`; `idempotency_keys` replay/conflict persistence | `tests/Feature/Incoming/IncomingLetterWorkflowContractTest.php`; `vendor\bin\pint --test` passed | `IMPLEMENTED_VERIFIED_PHASE_6_INCOMING` |
| `FUN-SK-01` | `app/Http/Controllers/Api/OutgoingLetterController.php`; `app/Http/Requests/StoreOutgoingLetterRequest.php`; outgoing create/update/delete/restore routes; explicit `letter_year` derivation and duplicate-number conflict response | `tests/Feature/Outgoing/OutgoingLetterWorkflowContractTest.php`; `php artisan test` = 40 tests / 224 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_7` |
| `FILE-001` | `config/filesystems.php` private document/signature disks; `app/Http/Controllers/Api/AttachmentController.php`; `GET /api/v1/attachments/{uuid}/preview` | `tests/Feature/Attachments/AttachmentStorageContractTest.php`; `php artisan test` = 44 tests / 247 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_8` |
| `FILE-002` | `app/Services/AttachmentStorageService.php`; incoming-letter multipart attachment validation and private storage metadata persistence | `tests/Feature/Attachments/AttachmentStorageContractTest.php`; `vendor\bin\pint --test` passed | `IMPLEMENTED_VERIFIED_PHASE_8` |
| `FILE-003` | Missing attachment preview envelope and incoming-letter remediation route `POST /api/v1/surat-masuk/{incomingLetter}/remediate-attachment` | `tests/Feature/Attachments/AttachmentStorageContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_8` |
| `SEC-002` | Authenticated attachment preview with same-division authorization and cross-division `ERR_403_DIVISI` denial | `tests/Feature/Attachments/AttachmentStorageContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_8` |
| `FUN-PG-01` | `app/Http/Controllers/Api/PiagamController.php`; `app/Http/Requests/StorePiagamRequest.php`; `GET/POST /api/v1/piagam` | `tests/Feature/Piagam/PiagamWorkflowContractTest.php`; `php artisan test` = 47 tests / 268 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_9` |
| `FUN-PG-02` | `app/Http/Controllers/Api/PublicPiagamController.php`; `app/Http/Requests/PublicClaimPiagamRequest.php`; private signature attachment storage on `private_signatures` disk | `tests/Feature/Piagam/PiagamWorkflowContractTest.php`; `vendor\bin\pint --test` passed | `IMPLEMENTED_VERIFIED_PHASE_9` |
| `FUN-DASH-01` | `app/Http/Controllers/Api/DashboardController.php`; `GET /api/v1/dashboard` scoped counts and monthly aggregates | `tests/Feature/Reporting/SearchDashboardReportingContractTest.php`; `php artisan test` = 50 tests / 288 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_10` |
| `FUN-SRC-01` | `app/Http/Controllers/Api/SearchController.php`; `GET /api/v1/search` scoped incoming/outgoing/piagam search | `tests/Feature/Reporting/SearchDashboardReportingContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_10` |
| `FUN-REP-01` | `app/Http/Controllers/Api/ReportExportController.php`; `GET /api/v1/reports/export.csv` scoped CSV export | `tests/Feature/Reporting/SearchDashboardReportingContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_10` |
| `SEC-004` | Formula-cell sanitization in `ReportExportController::sanitizeCell()` | `tests/Feature/Reporting/SearchDashboardReportingContractTest.php`; `vendor\bin\pint --test` passed | `IMPLEMENTED_VERIFIED_PHASE_10` |
| `SYS-002` | Immutable `app/Models/AuditLog.php`; redaction in `app/Services/AuditLogger.php`; audit event persistence across auth, tenant, letter, attachment, reference, and piagam workflows | `tests/Feature/Operations/AuditOperationalHardeningContractTest.php`; `php artisan test` = 55 tests / 311 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_11` |
| `AUD-001` | Audit log update/delete model events throw `LogicException`; no audit mutation routes exist; contract-sensitive keys are redacted before JSON persistence | `tests/Feature/Operations/AuditOperationalHardeningContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_11` |
| `ERR-001` | JSON exception renderers in `bootstrap/app.php` for validation, authentication, authorization, not-found, rate-limit, and generic 403 errors | `tests/Feature/Operations/AuditOperationalHardeningContractTest.php`; `tests/Feature/Auth/AuthenticationContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_11` |
| `NFR-001` | `app/Console/Commands/PruneExpiredIdempotencyKeys.php`; command registered in `bootstrap/app.php` as `sisurat:prune-idempotency` | `tests/Feature/Operations/AuditOperationalHardeningContractTest.php`; `vendor\bin\pint --test` passed | `IMPLEMENTED_VERIFIED_PHASE_11` |
| `MIG-001` | `app/Console/Commands/MigrateLegacySisurat.php`; command registered in `bootstrap/app.php` as `sisurat:migrate-legacy`; writes `storage/migration/reconciliation.json` and quarantine JSON files | `tests/Feature/Migration/LegacyMigrationToolingContractTest.php`; `php artisan test` = 58 tests / 340 assertions passed | `IMPLEMENTED_VERIFIED_PHASE_12` |
| `DEC-AUTH-001` | Migration command discards legacy password material, assigns random hashed passwords, and sets `must_reset_password = true` | `tests/Feature/Migration/LegacyMigrationToolingContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_12` |
| `DEC-FILE-003` | Migration command preserves letter metadata when Drive links are not fetched and creates `attachments` rows with `is_missing = true`, `migration_status = missing_drive_file`, `legacy_drive_url`, and extracted file ID | `tests/Feature/Migration/LegacyMigrationToolingContractTest.php` | `IMPLEMENTED_VERIFIED_PHASE_12` |
