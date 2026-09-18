# 26. LEGACY EVIDENCE INDEX

## 1. Scope & Objective

This index documents every legacy file, function, schema artifact, and benchmark report referenced as empirical evidence in this contract pack (v1.3.0). All sensitive identifiers (Google Drive folder IDs, deployment URLs) have been redacted in compliance with security guidelines.

---

## 2. Evidence Register

| Evidence ID | Legacy File Path | Function / Section | Verified Behavior Summary | Related Requirements & Decisions | Certainty | Sensitivity Note |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **EVID-01** | `db.txt` | Entire file (lines 1-37) | Master schema definition for `db_divisi`, `db_users`, `db_audit_log`, `db_summary`, `db_config`, `ref_sekolah`, and `{KODE}_...` sheets. | `DM-001`, `DB-001`, `DB-002`, `DEC-LET-001` | `VERIFIED` | Clean schema definition. No PII. |
| **EVID-02** | `backend/01_Config.js` | Lines 53-68 (`ALLOWED_ORIGINS`) | Origin check configured for local dev and production Vercel frontend. | `SEC-005` | `VERIFIED` | Production URL redacted. |
| **EVID-03** | `backend/01_Config.js` | Lines 76-126 (`RBAC_RULES`) | Mapping of actions/tables to permitted roles (`super_admin`, `admin_divisi`, `admin`, `operator`). | `RBAC-001`, `AUTH-001` | `VERIFIED` | Public code logic. |
| **EVID-04** | `backend/02_Auth.js` | Lines 227-309 (`cekLogin`) | Session generation, 15-minute lockout after 5 failed attempts, password verification, division status check. | `AUTH-001`, `BR-USER-001`, `SEC-001` | `VERIFIED` | Public code logic. |
| **EVID-05** | `backend/02_Auth.js` | Lines 205-225 (`_upgradePasswordIfNeeded`) | Retrofitted salted SHA-256 password migration routine with `__MIGRATED__` marker. Confirms legacy crypto is weak and must be deprecated. | `SEC-001`, `MIG-001`, `BR-USER-003`, `DEC-AUTH-001` | `VERIFIED` | Insecure legacy crypto. |
| **EVID-06** | `backend/05_Drive.js` | Lines 16-41 (`uploadFileToDrive`) | Base64 decoding, folder lookup, Drive file creation, and public access `ANYONE_WITH_LINK, VIEW`. Drive files subject to 404 missing state. | `FILE-001`, `SEC-002`, `BR-FILE-003`, `DEC-FILE-003` | `VERIFIED` | Folder IDs redacted. |
| **EVID-07** | `backend/06_Logger.js` | Lines 5-25 (`writeAuditLog`) | Appends audit row to `db_audit_log` (`timestamp, actor, role, divisi_id, action, table_name, record_id, detail`). | `AUD-001` | `VERIFIED` | Flat logging logic. |
| **EVID-08** | `backend/07_UserService.js` | Lines 50-180 (`manageUser`) | User mutations performed by physical spreadsheet row numbers (`sheet.getRange(rowNumber, ...)`). | `BR-USER-002`, `DEC-008` | `VERIFIED` | Fragile legacy index targeting. |
| **EVID-09** | `backend/08_RecordService.js`| Lines 60-150 (`simpanRecord`) | Resolves division table, checks duplicates, writes row, and triggers Cartesian cache invalidation. Confirms no DB uniqueness constraint. | `FUN-SM-01`, `FUN-SK-01`, `BR-SM-003`, `BR-SK-002`, `DEC-LET-001`, `DEC-LET-002` | `VERIFIED` | Public code logic. |
| **EVID-10** | `backend/09_PiagamService.js`| Lines 15-70 (`simpanPiagam`) | Unauthenticated or operator submission of piagam, decoding Base64 canvas signature, saving to Drive. | `FUN-PG-01`, `FUN-PG-02`, `BR-PG-001` | `VERIFIED` | Public code logic. |
| **EVID-11** | `backend/10_UploadService.js`| Lines 5-34 (`handleUploadChunk`) | 72KB Base64 chunk storage in `CacheService` with 1-hour TTL. | `FILE-002`, `PERF-001` | `VERIFIED` | Deprecated workaround. |
| **EVID-12** | `backend/10_UploadService.js`| Lines 36-118 (`finalizeUpload`) | Joins chunks from cache, checks 10MB byte ceiling, writes blob to Drive, returns public URL. | `FILE-001`, `FILE-002`, `BR-FILE-003`, `DEC-FILE-003` | `VERIFIED` | Deprecated workaround. |
| **EVID-13** | `backend/11_DivisionService.js`| Lines 25-110 (`initDivisi`) | Provisions 5 separate sheets (`{KODE}_surat_masuk`, etc.) and a dedicated Drive folder per division. Confirms strict single-division separation. | `TEN-001`, `FUN-DIV-01`, `BR-TEN-005`, `DEC-TEN-001` | `VERIFIED` | Deprecated sheet partitioning. |
| **EVID-14** | `backend/98_Router.js` | Lines 5-294 (`routeAction`) | Central dispatching router; extracts session token, resolves division tables, evaluates permissions. | `SYS-001`, `RBAC-001` | `VERIFIED` | Public code logic. |
| **EVID-15** | `laporan_pengujian_bab43.json` | Lines 1-79 (TC-01 to TC-06) | Empirical benchmark timings: Login 7.7s, Cross-division check 11.1s, Chunks 5.0s/4.2s, Finalize 10.2s. | `PERF-001`, `NFR-001` | `VERIFIED` | Empirical test artifact. |
| **EVID-16** | `dashboard.html` | Lines 140-180 (`renderCharts`) | Hardcoded multipliers (`[0.1, 0.15, 0.2, 0.18, 0.22]`) used to generate fictitious monthly trends. | `FUN-DASH-01` | `VERIFIED` | Deprecated frontend artifact. |
| **EVID-17** | `master.js` | Lines 950-1058 (`submitForm`) | Client-side button disabling upon form submission to shield against double clicks; lacks server-side idempotency protection. | `IDEM-001`, `BR-IDEM-001`, `DEC-SYS-001` | `VERIFIED` | Ergonomic client control only. |
| **EVID-18** | `db.txt` | Lines 17-27 (`DIKDAS_surat_masuk`) | Confirms external `nomor_surat` has no uniqueness constraint, while internal `DIKDAS_surat_keluar` is reset annually per division. | `DEC-LET-001`, `DEC-LET-002`, `BR-SM-003`, `BR-SK-002` | `VERIFIED` | Clean schema definition. |
