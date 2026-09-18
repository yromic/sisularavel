# Test Coverage Matrix

| Acceptance / Test ID | Contract source | Planned automated test | Status | Notes |
| --- | --- | --- | --- | --- |
| Baseline Laravel boot | Laravel skeleton | `tests/Feature/ExampleTest.php` | PASSED | Existing test passed |
| Baseline unit runner | Laravel skeleton | `tests/Unit/ExampleTest.php` | PASSED | Existing test passed |
| Phase 1 health endpoint | `docs/23-IMPLEMENTATION-SEQUENCE.md` | `tests/Feature/HealthCheckTest.php` | PASSED | TDD red-green verified; `/health` returns status JSON |
| Phase 2 schema contract | `docs/07-DATABASE-CONTRACT.md` | `tests/Feature/Database/SchemaContractTest.php` | PASSED | 4 tests / 40 assertions |
| Phase 2 model foundation | `contracts/entities.yaml` | `tests/Feature/Database/ModelFoundationTest.php` | PASSED | 3 tests / 22 assertions |
| TEST-AUTH-SEC-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Auth/AuthenticationContractTest.php` | PASSED | Legacy password rejected; inactive account rejected |
| TEST-AUTH-ACT-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Auth/PasswordActivationContractTest.php` | PASSED | Hashed 64-character token stored; reset clears activation state |
| TEST-AUTH-ACT-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Auth/PasswordActivationContractTest.php` | PASSED | Admin-issued secret displayed once in response, stored hashed, password gate enforced |
| TEST-TEN-OWN-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Tenancy/DivisionIsolationContractTest.php` | PASSED | Cross-division route tampering denied with `ERR_403_DIVISI` and audit |
| TEST-TEN-OWN-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Tenancy/DivisionIsolationContractTest.php` | PASSED | Super-admin context switch scopes reads without ownership mutation |
| Phase 5 reference data | `docs/03-FEATURE-CATALOG.md`, `docs/23-IMPLEMENTATION-SEQUENCE.md` | `tests/Feature/Reference/MasterReferenceDataContractTest.php` | PASSED | 6 tests / 26 assertions cover school search/write boundary, category division scoping, division/settings super-admin boundary, scoped user creation, and operator denial |
| TEST-LET-IN-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Incoming/IncomingLetterWorkflowContractTest.php` | PASSED | External number collision from different sender allowed; no incoming unique number rejection |
| TEST-LET-IN-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Incoming/IncomingLetterWorkflowContractTest.php` | PASSED | Exact duplicate returns advisory without insert; confirmed duplicate inserts and audits |
| TEST-LET-OUT-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Outgoing/OutgoingLetterWorkflowContractTest.php` | PASSED | Duplicate outgoing number within same division/year returns `ERR_409_DUPLICATE_NUMBER` |
| TEST-LET-OUT-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Outgoing/OutgoingLetterWorkflowContractTest.php` | PASSED | Same outgoing number permitted across divisions or different derived years |
| TEST-LET-OUT-003 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Outgoing/OutgoingLetterWorkflowContractTest.php` | PASSED | Updating `tanggal_surat` recalculates `letter_year` and checks uniqueness |
| TEST-FILE-MISS-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Attachments/AttachmentStorageContractTest.php` | PASSED | Missing legacy attachment preview returns safe `ERR_404_ATTACHMENT`; metadata records remain usable |
| TEST-FILE-MISS-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Attachments/AttachmentStorageContractTest.php` | PASSED | Division admin remediation stores private PDF, clears missing flag/status, and audits `ATTACHMENT_UPLOADED` |
| Attachment private streaming | `docs/10-FILE-STORAGE-CONTRACT.md`, `BR-FILE-001`, `SEC-002` | `tests/Feature/Attachments/AttachmentStorageContractTest.php` | PASSED | Authorized same-division preview streams from private disk; cross-division preview returns `ERR_403_DIVISI` |
| Attachment upload validation | `docs/10-FILE-STORAGE-CONTRACT.md`, `BR-FILE-002` | `tests/Feature/Attachments/AttachmentStorageContractTest.php` | PASSED | `.exe`/unapproved MIME upload rejected with HTTP 422 and no letter/attachment rows |
| TEST-IDEM-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Incoming/IncomingLetterWorkflowContractTest.php` | PASSED | Same incoming create payload/key replays identical JSON response with one letter and one audit |
| TEST-IDEM-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | `tests/Feature/Incoming/IncomingLetterWorkflowContractTest.php` | PASSED | Same key with altered payload returns `ERR_409_IDEMPOTENCY_CONFLICT` without insertion |
| TEST-UI-MOB-001 to 006 | `examples/acceptance-scenarios.md` | Browser/responsive tests | NOT_STARTED | Phase 13 |
| TEST-UI-DSK-001 | `examples/acceptance-scenarios.md` | Desktop grid browser test | NOT_STARTED | Phase 13 |
| TEST-PG-001 | `examples/acceptance-scenarios.md` | Public piagam claim feature/browser test | NOT_STARTED | Phase 9 |
| TEST-MIG-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Migration dry-run reconciliation test | NOT_STARTED | Phase 12 |
