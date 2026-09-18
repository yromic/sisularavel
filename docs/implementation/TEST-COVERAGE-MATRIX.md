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
| TEST-LET-IN-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Incoming feature test | NOT_STARTED | Phase 6 |
| TEST-LET-IN-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Incoming duplicate advisory feature test | NOT_STARTED | Phase 6 |
| TEST-LET-OUT-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Outgoing duplicate rejection test | NOT_STARTED | Phase 7 |
| TEST-LET-OUT-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Outgoing cross-division/year allowance test | NOT_STARTED | Phase 7 |
| TEST-LET-OUT-003 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Outgoing year recalculation test | NOT_STARTED | Phase 7 |
| TEST-FILE-MISS-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Missing attachment detail/404 test | NOT_STARTED | Phase 8 |
| TEST-FILE-MISS-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Missing attachment remediation test | NOT_STARTED | Phase 8 |
| TEST-TEN-OWN-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Division ownership tamper test | NOT_STARTED | Phase 4 |
| TEST-TEN-OWN-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Super admin ownership preservation test | NOT_STARTED | Phase 4 |
| TEST-IDEM-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Idempotent replay test | NOT_STARTED | Phase 6 |
| TEST-IDEM-002 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Idempotency conflict test | NOT_STARTED | Phase 6 |
| TEST-UI-MOB-001 to 006 | `examples/acceptance-scenarios.md` | Browser/responsive tests | NOT_STARTED | Phase 13 |
| TEST-UI-DSK-001 | `examples/acceptance-scenarios.md` | Desktop grid browser test | NOT_STARTED | Phase 13 |
| TEST-PG-001 | `examples/acceptance-scenarios.md` | Public piagam claim feature/browser test | NOT_STARTED | Phase 9 |
| TEST-MIG-001 | `docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md` | Migration dry-run reconciliation test | NOT_STARTED | Phase 12 |
