# 25. OPEN QUESTIONS & DECISION BACKLOG

## 1. Scope & Decision Hygiene

This document tracks unresolved product decisions, architectural trade-offs, and operational choices. Following the v1.2.0 targeted reconciliation, issues resolved by ratified architectural decisions have been formally concluded. Only genuine, non-blocking operational choices remain open.

Prioritization Tags:
- **`BLOCKS_SCHEMA`:** Prevents finalization of database migrations.
- **`BLOCKS_SECURITY`:** Affects authentication, credential policies, or permission boundaries.
- **`BLOCKS_WORKFLOW`:** Blocks business flow or controller implementation.
- **`BLOCKS_MIGRATION`:** Blocks legacy data ETL or reconciliation rules.
- **`CAN_BE_DEFERRED`:** Non-blocking operational refinement that can be decided in later implementation iterations.
- **`RESOLVED_IN_V1.2`:** Formally settled by ratified architectural decisions.

---

## 2. Reconciled Status of Prior Questions

| Question ID | Title | Previous Status | Reconciled v1.2 Status | Ratified Decision / Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Q-01A** | Incoming Letter Duplicate Policy | `BLOCKS_SCHEMA` | **`RESOLVED_IN_V1.2`** | **`DEC-LET-001`**: No universal DB unique constraint on incoming `nomor_surat`. Duplicate detection implemented as advisory check on `(division_id, asal_surat, nomor_surat, tanggal_surat)`. |
| **Q-01B** | Outgoing Letter Number Uniqueness Scope | `BLOCKS_SCHEMA` | **`RESOLVED_IN_V1.2`** | **`DEC-LET-002`**: Outgoing numbers enforced unique per division and calendar year via an explicit `letter_year` column. |
| **Q-01C** | Definition of Numbering Year/Period | `BLOCKS_SCHEMA` | **`RESOLVED_IN_V1.2`** | **`DEC-LET-002`**: Derived deterministically from `tanggal_surat` upon creation and update. |
| **Q-02** | Legacy Password Migration & Activation | `BLOCKS_SECURITY` | **`RESOLVED_IN_V1.2`** | **`DEC-AUTH-001`**: Insecure legacy hashes rejected. Migrated users assigned unusable random hash, flagged `must_reset_password = true`, activated via Path A (email token) or Path B (out-of-band single-use temporary credential). |
| **Q-03** | Treatment of 404 / Missing Drive Files | `BLOCKS_MIGRATION` | **`RESOLVED_IN_V1.2`** | **`DEC-FILE-003`**: Letters never discarded. Durable `attachments` table row created with `is_missing = true`, nullable `file_path`, and descriptive `migration_status`. |
| **Q-04** | Cross-Division Letter Disposition | `BLOCKS_WORKFLOW` | **`RESOLVED_IN_V1.2`** | **`DEC-TEN-001`**: Strict single-division ownership in v1. Inter-division routing classified as `DEFERRED FUTURE FEATURE`. |

---

## 3. Active Decision Backlog (Genuine Non-Blocking Items)

### Q-01D: Formal Agenda Numbering Sequence
- **Priority:** `CAN_BE_DEFERRED`
- **Affected Requirements:** `FUN-SM-01`, `DB-001`
- **Target Implementation Phase:** Phase 6 (Incoming Letters)
- **Decision Owner:** Head of General Administration
- **Context & Evidence:**
  In legacy Google Sheets, there was no stored `agenda_number` column; letters were simply displayed with client-side tabular row numbers (`i + 1`). Many government secretariats maintain a continuous annual running agenda number for incoming mail.
- **Options:**
  - *Option A (Recommended for v1):* Maintain incoming letter identification via date, number, and database ID. Display display-order index in UI (preserving legacy behavior).
  - *Option B:* Introduce an explicit auto-incrementing `agenda_number` column reset to 1 at the start of each calendar year per division.
- **Impact on Foundation:** **Zero schema blocker**. Can be added as a nullable column in Phase 6 if required.

---

### Q-05: Digital Signature Delivery & Receipt Stamping
- **Priority:** `CAN_BE_DEFERRED`
- **Affected Requirements:** `FUN-PG-02`, `BR-PG-001`
- **Target Implementation Phase:** Phase 9 (Piagam Workflows)
- **Decision Owner:** Product Owner / Youth & Sports Division
- **Context & Evidence:**
  Legacy stores the digital signature as a flat Base64 PNG image uploaded to Drive.
- **Options:**
  - *Option A (Recommended for v1):* Retain PNG signature image storage on private disk linked to the piagam record (full parity with legacy).
  - *Option B (Enhancement):* Dynamically stamp the digital signature image onto an official PDF certificate receipt with a verification QR code.
- **Impact on Foundation:** **Zero schema blocker**. Option A is fully supported by the current database contract.
