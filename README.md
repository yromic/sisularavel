# SISURAT — Self-Contained Laravel Migration Contract Pack

> **Package Version:** 1.3.0  
> **Status:** RATIFIED BASELINE CONTRACT (Ready for Laravel 12 Contract Intake)  
> **Target Framework:** Laravel ^12.0 (Initial Implementation Baseline)  
> **Target PHP Runtime:** PHP ^8.2 (Supported: PHP 8.2–8.5; Local Baseline: XAMPP PHP 8.2)  
> **Target Database:** MySQL 8.0+ / MariaDB 10.4+ (InnoDB, strict mode)  
> **Target Environment:** Shared Hosting Friendly (cPanel / Apache / LiteSpeed) & VPS Ready  
> **Target Organization:** Disdikbudpora Kabupaten Semarang (Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga)  
> **Date:** September 2026  

---

> [!WARNING]
> **Target Framework Lifecycle Notice:**  
> Laravel 12 was selected for initial SISURAT development because the current local XAMPP environment is limited to PHP 8.2, whereas Laravel 13 requires PHP 8.3+. According to the official Laravel support policy, Laravel 12 bug fixes conclude on August 13, 2026, and security fixes end on February 24, 2027. Upgrading the runtime to PHP 8.3 or newer and migrating SISURAT to Laravel 13 must be treated as a planned post-foundation maintenance milestone once the runtime environment is upgraded.

---

## 1. Executive Overview & Purpose

This contract package is a **complete, self-contained, implementation-ready architectural and functional contract** designed to guide the ground-up development of **SISURAT** on Laravel 12. Contract version 1.3.0 establishes the verified local baseline (Laravel ^12.0 / PHP ^8.2) following the v1.2.0 reconciliation of letter numbering uniqueness, password deprecation and activation, durable missing legacy attachments, single-division ownership, and server-side request idempotency.

### Critical Operating Context for Implementers & AI Agents
1. **Zero Legacy Access:** The implementing engineer or AI agent **WILL NOT** have access to the legacy Google Apps Script (GAS) codebase, the historical Google Sheets (`db.txt`), Google Drive configurations, or the legacy frontend scripts.
2. **Autonomous Reconstructibility:** Every business rule, entity attribute, relationship, workflow step, authorization condition, validation pattern, and UI expectation has been extracted, normalized, and codified inside this package.
3. **Evidence-Based & Non-Speculative:** Every requirement states its legacy provenance (`VERIFIED`, `INFERRED`, `USER_DECISION_REQUIRED`, etc.). Implementers must **never guess** missing logic or apply generic ERP/archival patterns without consulting this contract.

---

## 2. Authoritative Source Order

When resolving conflicts or interpreting specifications during development, the following hierarchy of authority **MUST** be strictly observed:

1. **Tier 1: Explicitly Approved Decisions**  
   [`docs/24-DECISION-LOG.md`](docs/24-DECISION-LOG.md) (Overrides all other documents when status is `APPROVED`).
2. **Tier 2: Normative Documentation & Specification Contracts**  
   [`docs/00-SYSTEM-CONTEXT.md`](docs/00-SYSTEM-CONTEXT.md) through [`docs/23-IMPLEMENTATION-SEQUENCE.md`](docs/23-IMPLEMENTATION-SEQUENCE.md).
3. **Tier 3: Machine-Readable Schema Contracts**  
   [`contracts/requirements.yaml`](contracts/requirements.yaml), [`contracts/roles-permissions.yaml`](contracts/roles-permissions.yaml), [`contracts/entities.yaml`](contracts/entities.yaml), [`contracts/validation-rules.yaml`](contracts/validation-rules.yaml), [`contracts/status-enums.yaml`](contracts/status-enums.yaml), and [`contracts/legacy-data-map.yaml`](contracts/legacy-data-map.yaml).
4. **Tier 4: Concrete Reference Examples & Scenarios**  
   [`examples/anonymized-record-examples.md`](examples/anonymized-record-examples.md), [`examples/workflow-scenarios.md`](examples/workflow-scenarios.md), and [`examples/acceptance-scenarios.md`](examples/acceptance-scenarios.md).
5. **Tier 5: Historical Evidence & Traceability Indexes**  
   [`docs/26-LEGACY-EVIDENCE-INDEX.md`](docs/26-LEGACY-EVIDENCE-INDEX.md) and [`docs/27-TRACEABILITY-MATRIX.md`](docs/27-TRACEABILITY-MATRIX.md).

> [!IMPORTANT]
> If any conflict is discovered between two documents of equal tier, the matter **MUST** be logged in [`docs/25-OPEN-QUESTIONS.md`](docs/25-OPEN-QUESTIONS.md) and human stakeholder input requested. Never silently improvise.

---

## 3. Mandatory Reading Order for Implementers and Agents

Any AI agent or software engineer assigned to build SISURAT on Laravel **MUST** read the documentation package in this exact sequence before writing any code:

```text
  1. README.md                        (Package scope, authority, reading sequence)
  2. AGENTS.md                        (Mandatory AI agent instructions & boundaries)
  3. docs/00-SYSTEM-CONTEXT.md        (Organizational mission, stakeholders, motivations)
  4. docs/01-SCOPE-AND-BOUNDARIES.md  (What is in scope, what is out of scope)
  5. docs/02-GLOSSARY.md              (Canonical terminology, definitions, alias mappings)
  6. docs/03-FEATURE-CATALOG.md       (Complete functional inventory of all features)
  7. docs/04-BUSINESS-RULES.md        (Domain rules, tenancy isolation, numbering rules)
  8. docs/06-DOMAIN-MODEL.md          (Logical entity models, relationships, invariants)
  9. docs/07-DATABASE-CONTRACT.md      (Normalized relational tables, keys, indexes, types)
 10. docs/08-RBAC-AND-DIVISION-SCOPE.md(Roles, permissions, division scoping enforcement)
 11. docs/05-WORKFLOWS.md             (Step-by-step transaction flows & state transitions)
 12. docs/09-VALIDATION-CONTRACT.md   (Field-level and cross-field input validations)
 13. docs/10-FILE-STORAGE-CONTRACT.md (Private storage, attachment streaming, MIME rules)
 14. docs/12-SECURITY-CONTRACT.md     (Authentication, CSRF, XSS, rate limiting, hashing)
 15. docs/14-ERROR-HANDLING-CONTRACT.md(Standardized error codes, messages, and handling)
 16. docs/20-TEST-AND-ACCEPTANCE-CONTRACT.md (Acceptance scenarios & test criteria)
 17. docs/21-SHARED-HOSTING-CONSTRAINTS.md   (PHP/cPanel limits, symlinks, crons, workers)
 18. docs/22-TARGET-ARCHITECTURE-BOUNDARIES.md (Layer boundaries: controllers, services, models)
 19. docs/23-IMPLEMENTATION-SEQUENCE.md     (Phased engineering roadmap)
 20. docs/25-OPEN-QUESTIONS.md        (Known ambiguities and blocked architectural items)
 21. docs/27-TRACEABILITY-MATRIX.md   (Full cross-verification traceability)
```

---

## 4. Package Directory Map

```text
sisurat-laravel-contract/
├── README.md                          # Master overview, authority hierarchy, reading order
├── AGENTS.md                          # AI agent governance, implementation boundaries, constraints
├── CHANGELOG.md                       # Contract version history and revision tracking
├── docs/
│   ├── 00-SYSTEM-CONTEXT.md           # Background, organization, infrastructure rationale
│   ├── 01-SCOPE-AND-BOUNDARIES.md     # In-scope vs out-of-scope functional boundaries
│   ├── 02-GLOSSARY.md                 # Canonical vocabulary, acronyms, and concept dictionary
│   ├── 03-FEATURE-CATALOG.md          # Exhaustive catalog of all functional capabilities
│   ├── 04-BUSINESS-RULES.md           # Business logic, tenancy rules, data constraints
│   ├── 05-WORKFLOWS.md                # End-to-end execution workflows and sequence diagrams
│   ├── 06-DOMAIN-MODEL.md             # Logical entity model, attributes, lifecycle invariants
│   ├── 07-DATABASE-CONTRACT.md        # Relational schema specification, indexes, foreign keys
│   ├── 08-RBAC-AND-DIVISION-SCOPE.md  # Authorization matrix, division context, security scopes
│   ├── 09-VALIDATION-CONTRACT.md      # Field validation rules, regex patterns, constraints
│   ├── 10-FILE-STORAGE-CONTRACT.md    # Private attachment storage, MIME types, streaming
│   ├── 11-UI-UX-BEHAVIOR-CONTRACT.md  # Page-level behavior, interactions, state transitions
│   ├── 12-SECURITY-CONTRACT.md        # Security defenses, password hashing, origin checks
│   ├── 13-AUDIT-LOG-CONTRACT.md       # Audit logging requirements, events, schema, retention
│   ├── 14-ERROR-HANDLING-CONTRACT.md  # Standardized error codes, failure responses, messages
│   ├── 15-LEGACY-DATA-MAPPING.md      # Field-by-field legacy Google Sheets to MySQL mapping
│   ├── 16-DATA-MIGRATION-STRATEGY.md  # ETL migration sequence, reconciliation, file transfers
│   ├── 17-FEATURE-PARITY-MATRIX.md    # Parity status (preserved, improved, deprecated)
│   ├── 18-NON-FUNCTIONAL-REQUIREMENTS.md # Reliability, performance, availability, hosting NFRs
│   ├── 19-PERFORMANCE-BASELINE.md     # Legacy empirical benchmarks & target benchmark plan
│   ├── 20-TEST-AND-ACCEPTANCE-CONTRACT.md # Acceptance test scenarios (Given/When/Then)
│   ├── 21-SHARED-HOSTING-CONSTRAINTS.md  # Shared hosting compatibility constraints & workarounds
│   ├── 22-TARGET-ARCHITECTURE-BOUNDARIES.md # Layer responsibilities, design patterns, service boundaries
│   ├── 23-IMPLEMENTATION-SEQUENCE.md  # 14-phase recommended implementation roadmap
│   ├── 24-DECISION-LOG.md             # ADR-style register of approved architectural decisions
│   ├── 25-OPEN-QUESTIONS.md           # Prioritized decision backlog for human stakeholders
│   ├── 26-LEGACY-EVIDENCE-INDEX.md    # Catalog of all referenced legacy source code artifacts
│   └── 27-TRACEABILITY-MATRIX.md      # Complete end-to-end requirement traceability index
├── contracts/
│   ├── requirements.yaml              # Machine-readable normative requirements catalog
│   ├── roles-permissions.yaml         # Machine-readable role-permission and scope definitions
│   ├── entities.yaml                  # Machine-readable domain entities and attributes
│   ├── validation-rules.yaml          # Machine-readable field validation rules
│   ├── status-enums.yaml              # Machine-readable enumerated status dictionaries
│   └── legacy-data-map.yaml           # Machine-readable legacy field to SQL column mappings
├── examples/
│   ├── anonymized-record-examples.md  # Anonymized sample data records across all entities
│   ├── workflow-scenarios.md          # Realistic transaction execution narratives
│   └── acceptance-scenarios.md        # End-to-end integration test acceptance walkthroughs
└── reviews/
    ├── CRITICAL-CONTRACT-REVIEW.md    # Critical contract defect & risk analysis
    ├── CONTRACT-DEFECT-REGISTER.md    # Defect tracking and reconciliation status register
    ├── CONTRACT-COVERAGE-REPORT.md    # Bounded catalog and schema coverage report
    ├── PRE-IMPLEMENTATION-GATE.md     # Gate criteria & readiness determination
    ├── V1.2.0-RECONCILIATION-REPORT.md# Re-audit and pre-handover reconciliation report
    └── V1.3.0-LARAVEL-12-TARGET-CORRECTION.md # Framework baseline & runtime correction report
```

---

## 5. Requirement Identifier Standards

All requirements within this contract pack carry immutable, structured identifiers:

| Prefix | Category | Example |
|---|---|---|
| `SYS` | System-wide & Architecture Requirement | `SYS-001` |
| `FUN` | Functional Capability Requirement | `FUN-001` |
| `BR` | Domain Business Rule | `BR-001` |
| `WF` | Execution Workflow Specification | `WF-001` |
| `DM` | Domain Data Model Invariant | `DM-001` |
| `DB` | Relational Database Schema / Constraint | `DB-001` |
| `AUTH`| Authentication Requirement | `AUTH-001` |
| `RBAC`| Authorization / Role-Based Access Control | `RBAC-001` |
| `TEN` | Multi-Tenant Division Isolation Rule | `TEN-001` |
| `VAL` | Input Validation Rule | `VAL-001` |
| `FILE`| Binary File / Attachment Storage Rule | `FILE-001` |
| `SEC` | Security & Cryptography Requirement | `SEC-001` |
| `UI` | User Interface & Client Experience Rule | `UI-001` |
| `ERR` | Error Handling & Exception Rule | `ERR-001` |
| `AUD` | Audit Trail & Event Logging Rule | `AUD-001` |
| `MIG` | Legacy Data Migration Requirement | `MIG-001` |
| `PERF`| Performance & Latency Benchmark Target | `PERF-001` |
| `NFR` | Non-Functional Quality Attribute | `NFR-001` |
| `TEST`| Acceptance Criteria & Test Scenario | `TEST-001` |
| `DEC` | Architectural / Product Decision | `DEC-001` |

---

## 6. What Is Included vs. Intentionally Excluded

### Included
* Full behavioral specifications for Incoming Letters, Outgoing Letters, Piagam (Awards), Schools Reference, Competition References, Division Management, User Management, Public Piagam Collection, Canvas Signatures, Dashboard Metrics, and Audit Logging.
* Normalized relational MySQL/MariaDB database contracts replacing legacy Google Sheets tab-sharding.
* Private file storage contract replacing insecure public Google Drive links.
* Field-level validation matrices and error code standards.
* Testable Given/When/Then acceptance criteria for every functional domain.
* Shared-hosting operational rules (cPanel, cron, symlinks, PHP limits).

### Intentionally Excluded
* **No Laravel Application Source Code:** No PHP classes, controllers, models, migrations, Blade templates, routes, or Composer packages.
* **No Live Production Secrets:** All API keys, tokens, session hashes, folder IDs, and spreadsheet IDs are strictly redacted.
* **No Real Personal Identifiable Information (PII):** All student, teacher, and employee data has been replaced with synthetic, realistic anonymized examples.
* **No Arbitrary Implementation Choices:** When multiple valid technical approaches exist, the contract establishes constraints and acceptable options, logging unresolved points in `docs/25-OPEN-QUESTIONS.md`.
