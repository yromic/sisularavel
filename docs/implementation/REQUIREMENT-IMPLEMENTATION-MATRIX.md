# Requirement Implementation Matrix

| Requirement ID | Contract source | Implementation | Test IDs | Status | Notes |
| --- | --- | --- | --- | --- | --- |
| SYS-001 | `contracts/requirements.yaml`, `docs/23-IMPLEMENTATION-SEQUENCE.md` | Laravel 12 baseline installed; `/health` route; local MariaDB `sisurat_dev`; Vite build | Baseline example tests, `HealthCheckTest` | VERIFIED | Laravel 12.69.2, PHP 8.2.12, `gd`, MariaDB 10.4.32 verified |
| SYS-002 | `docs/13-AUDIT-LOG-CONTRACT.md` | Not started | `TEST-AUTH-*`, `TEST-LET-*` | NOT_STARTED | Phase 11 |
| AUTH-001 | `docs/12-SECURITY-CONTRACT.md` | Session login/logout, legacy credential rejection, generic reset request, token reset, admin activation secret, mandatory password activation gate | `AuthenticationContractTest`, `PasswordActivationContractTest` | VERIFIED | Phase 3 |
| TEN-001 | `docs/08-RBAC-AND-DIVISION-SCOPE.md` | Division-owned incoming letter listing/detail scoped to active division; cross-division denial with audit record; inactive account/division gate | `DivisionIsolationContractTest` | VERIFIED | Phase 4 |
| TEN-002 | `docs/08-RBAC-AND-DIVISION-SCOPE.md` | Super admin active-division session switching without ownership transfer | `DivisionIsolationContractTest` | VERIFIED | Phase 4 |
| RBAC-001 | `contracts/roles-permissions.yaml` | Authenticated role checks for super-admin-only division switching and division-admin same-division activation secret boundary | `DivisionIsolationContractTest`, `PasswordActivationContractTest` | VERIFIED | Phase 4 |
| FUN-SM-01 | `docs/04-BUSINESS-RULES.md`, `docs/07-DATABASE-CONTRACT.md` | Incoming letter scoped list/detail plus create/update/soft-delete/restore lifecycle; no unique incoming number constraint; duplicate advisory with confirmation; immutable division ownership | `IncomingLetterWorkflowContractTest`, `DivisionIsolationContractTest` | VERIFIED | Phase 6 API lifecycle; attachment binaries continue in Phase 8 |
| FUN-SK-01 | `docs/04-BUSINESS-RULES.md`, `docs/07-DATABASE-CONTRACT.md` | Outgoing scoped list/detail plus create/update/delete/restore lifecycle; explicit `letter_year`; annual division uniqueness; same number allowed across divisions/years | `OutgoingLetterWorkflowContractTest` | VERIFIED | Phase 7 API lifecycle; attachment binaries continue in Phase 8 |
| FUN-PG-01 | `docs/03-FEATURE-CATALOG.md` | Not started | `TEST-PG-001` | NOT_STARTED | Phase 9 |
| FUN-PG-02 | `docs/03-FEATURE-CATALOG.md` | Not started | Public claim scenario | NOT_STARTED | Phase 9 |
| FUN-REF-01 | `docs/03-FEATURE-CATALOG.md` | Public active-school search and super-admin school creation endpoint; division-scoped pickup/competition category creation | `MasterReferenceDataContractTest` | VERIFIED | Phase 5 |
| FUN-USR-01 | `docs/03-FEATURE-CATALOG.md` | Division-admin same-division operator creation; cross-division and role-escalation denial; super-admin global creation path | `MasterReferenceDataContractTest`, `PasswordActivationContractTest` | VERIFIED | Phase 3/5 |
| FUN-DIV-01 | `docs/03-FEATURE-CATALOG.md` | Super-admin division creation/status management; non-super-admin denial | `MasterReferenceDataContractTest` | VERIFIED | Phase 5 |
| FUN-SET-01 | `docs/03-FEATURE-CATALOG.md` | Super-admin settings update endpoint with audit-safe key/value persistence | `MasterReferenceDataContractTest` | VERIFIED | Phase 5 |
| FUN-DASH-01 | `docs/03-FEATURE-CATALOG.md` | Not started | Aggregate count tests | NOT_STARTED | Phase 10 |
| FUN-SRC-01 | `docs/03-FEATURE-CATALOG.md` | Not started | Scoped search tests | NOT_STARTED | Phase 10 |
| FUN-REP-01 | `docs/03-FEATURE-CATALOG.md` | Not started | Export sanitization tests | NOT_STARTED | Phase 10 |
| FILE-001 | `docs/10-FILE-STORAGE-CONTRACT.md` | Not started | `TEST-FILE-MISS-*`, stream auth tests | NOT_STARTED | Blocked by missing `gd`/`imagick` |
| FILE-002 | `docs/10-FILE-STORAGE-CONTRACT.md` | Not started | Upload validation tests | NOT_STARTED | Phase 8 |
| IDEM-001 | `docs/04-BUSINESS-RULES.md`, `DEC-SYS-001` | `Idempotency-Key` UUIDv4 route middleware, request fingerprinting, cached JSON replay, same-key/different-payload conflict, 24-hour expiration | `IncomingLetterWorkflowContractTest`, `OutgoingLetterWorkflowContractTest` | VERIFIED_PARTIAL | Verified for incoming and outgoing creates; reuse for piagam in later phase |
| SEC-001 | `docs/12-SECURITY-CONTRACT.md` | Bcrypt password verification, inactive-account rejection, login rate limiting, session regeneration/invalidation, reset token hashing | `AuthenticationContractTest`, `PasswordActivationContractTest` | VERIFIED | Phase 3 |
| SEC-002 | `docs/12-SECURITY-CONTRACT.md` | Not started | Attachment authorization tests | NOT_STARTED | Phase 8 |
| SEC-004 | `contracts/requirements.yaml` | Not started | CSV formula injection tests | NOT_STARTED | Phase 10 |
| SEC-005 | `contracts/requirements.yaml` | Not started | Origin/CORS tests | NOT_STARTED | Phase 11 |
| DB-001 | `docs/07-DATABASE-CONTRACT.md` | Contract migrations for 12 SISURAT tables, constraints, indexes, foreign keys, model foundations, and factories | `SchemaContractTest`, `ModelFoundationTest` | VERIFIED | Phase 2 rollback/remigration verified |
| PERF-001 | `docs/19-PERFORMANCE-BASELINE.md` | Not started | Benchmark suite | NOT_STARTED | Phase 13 |
| UI-001 | `docs/11-UI-UX-BEHAVIOR-CONTRACT.md` | Not started | `TEST-UI-MOB-001` | NOT_STARTED | Phase 6+ |
| UI-002 | `docs/11-UI-UX-BEHAVIOR-CONTRACT.md` | Not started | `TEST-UI-MOB-005` | NOT_STARTED | Phase 6+ |
| UI-003 | `docs/11-UI-UX-BEHAVIOR-CONTRACT.md` | Not started | Visual review/browser tests | NOT_STARTED | Phase 6+ |
| UI-004 | `docs/11-UI-UX-BEHAVIOR-CONTRACT.md` | Not started | `TEST-UI-MOB-006` | NOT_STARTED | Phase 6+ |
| UI-005 | `docs/11-UI-UX-BEHAVIOR-CONTRACT.md` | Not started | `TEST-UI-MOB-002` | NOT_STARTED | Phase 6+ |
| UI-006 | `docs/11-UI-UX-BEHAVIOR-CONTRACT.md`, `DEC-SYS-001` | Server-side duplicate submission shield implemented for incoming letter create via idempotency middleware | `IncomingLetterWorkflowContractTest` | VERIFIED_PARTIAL | Browser/mobile button behavior deferred to UI verification |
| UI-007 | `docs/11-UI-UX-BEHAVIOR-CONTRACT.md` | Not started | `TEST-UI-DSK-001` | NOT_STARTED | Phase 6+ |
