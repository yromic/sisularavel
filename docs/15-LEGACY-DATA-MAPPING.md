# 15. LEGACY DATA MAPPING

## 1. Scope & Objective

Legacy SISURAT persists all data in Google Sheets (`db.txt`). Multi-tenancy was simulated by dynamically provisioning sets of 5 sheets per division prefixed with `{KODE}_` (e.g. `DIKDAS_surat_masuk`, `PAUD_surat_masuk`). This caused extreme schema fragmentation, non-normalized references, unindexed row-order scans, and inconsistent column data types.

This document defines the field-by-field mapping, data type conversions, transformation logic, and anomaly remediation required to migrate legacy Google Sheets data into the normalized MySQL 8.0 / MariaDB schema specified in `07-DATABASE-CONTRACT.md`.

---

## 2. Table-by-Table Field Mapping Matrix

### 2.1 Divisions Mapping (`db_divisi` → `divisions`)

| Legacy Header | Meaning | Observed Format | Target Table | Target Column | Transformation Rule | Migration Risk |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | Legacy identifier | Numeric string (`"1"`, `"2"`) | `divisions` | (Discard / Reference) | Replaced with auto-increment `BIGINT` PK | Low |
| `kode_divisi` | Short code / Prefix | Uppercase string (`"DIKDAS"`, `"PAUD"`) | `divisions` | `code` | `TRIM(UPPER(kode_divisi))`, unique constraint | Low |
| `nama_divisi` | Full official name | String (`"Bidang Pembinaan SD"`) | `divisions` | `name` | `TRIM(nama_divisi)` | Low |
| `status` | Division status | String (`"active"`, `"inactive"`) | `divisions` | `status` | Normalized to lowercase string enum (`'active'`, `'inactive'`) | Low |
| `drive_folder_id` | Legacy Drive folder | Alphanumeric string | `divisions` | `legacy_drive_folder_id` | Preserved for archival reconciliation; unused in target file storage | Medium |
| `created_at` | Creation timestamp | Date string or empty | `divisions` | `created_at` | Parse to `TIMESTAMP` (`Y-m-d H:i:s`), default to migration time if blank | Low |
| `created_by` | Author username | String or empty | `divisions` | `created_by_user_id` | Map to `users.id` lookup by username, else `NULL` | Low |

---

### 2.2 Users Mapping (`db_users` → `users`)

| Legacy Header | Meaning | Observed Format | Target Table | Target Column | Transformation Rule | Migration Risk |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `username` | Unique username | Lowercase string (`"miftah"`) | `users` | `username` | `TRIM(LOWER(username))`, unique | Low |
| `password` | Legacy plain password | Text or `"__MIGRATED__"` | (Internal) | `password` | **NOT MIGRATED (DEC-AUTH-001)**. Generate random unguessable hash; set `must_reset_password = 1` | Critical Security |
| `password_hash`| SHA-256 hash | Hex string or empty | (Discard) | (Discard) | **NOT MIGRATED**. Insecure legacy format rejected | Critical Security |
| `password_salt`| Hash salt | Hex string or empty | (Discard) | (Discard) | Deprecated, do not port | Critical Security |
| `password_v` | Password version | `"1"`, `"2"`, or empty | (Discard) | (Discard) | Deprecated, do not port | Low |
| `nama` | Full name | String (`"Miftah Fauzi"`) | `users` | `name` | `TRIM(nama)` | Low |
| `email` | User email | Email string or empty | `users` | `email` | `TRIM(LOWER(email))`. Nullable if absent | Medium |
| `role` | Role title | String (`"super_admin"`, `"operator"`, `"admin"`) | `users` | `role` | Normalize: `"super_admin"` → `'super_admin'`, `"admin_divisi"` / `"admin"` → `'division_admin'`, `"operator"` → `'operator'` | Medium |
| `aktif` | Active status | Boolean / String (`TRUE`, `FALSE`, `"1"`, `"0"`) | `users` | `is_active` | Coerce to boolean (`true` / `false`) | Low |
| `divisi_id` | Division code | String (`"DIKDAS"`, `"PAUD"`, empty) | `users` | `division_id` | Foreign key lookup: match `kode_divisi` in `divisions.id`. Null for Super Admin | High (Orphan risk) |
| `scope` | Legacy permission | String or empty | (Discard) | (Discard) | Replaced with standard role-based capabilities | Low |

---

### 2.3 Schools Reference Mapping (`ref_sekolah` → `schools`)

| Legacy Header | Meaning | Observed Format | Target Table | Target Column | Transformation Rule | Migration Risk |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | Legacy identifier | Numeric string | `schools` | (Discard) | Auto-increment PK | Low |
| `nama_sekolah` | Official school name | String (`"SMP Negeri 1 Ungaran"`) | `schools` | `name` | `TRIM(nama_sekolah)` | Low |
| `npsn` | National school code | 8-digit numeric string | `schools` | `npsn` | Clean non-digit characters. Pad leading zeros to 8 digits if needed. Unique constraint | Medium (Duplicate NPSN) |
| `aktif` | Active status | Boolean / String | `schools` | `is_active` | Coerce to boolean | Low |

---

### 2.4 Incoming Letters Mapping (`{KODE}_surat_masuk` → `incoming_letters` & `attachments`)

*Source note: All `{KODE}_surat_masuk` sheets are consolidated into a single unified `incoming_letters` table.*

| Legacy Header | Meaning | Observed Format | Target Table | Target Column | Transformation Rule | Migration Risk |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | Legacy row ID | Numeric or UUID string | `incoming_letters`| `legacy_id` | Preserved in legacy metadata column | Low |
| `{KODE}` (Sheet) | Owning division | Sheet prefix (`"DIKDAS_"`) | `incoming_letters`| `division_id` | Look up `divisions.id` matching `{KODE}`. Single division ownership in v1 | Critical (Must match) |
| `divisi_id` | Division code in row | String or empty | `incoming_letters`| `division_id` | Fallback if sheet prefix is ambiguous | Low |
| `nomor_surat` | Official letter number| String (`"421.3/012/2026"`) | `incoming_letters`| `letter_number` | `TRIM(nomor_surat)`. **NO UNIQUE CONSTRAINT**. External numbers legitimately collide | Medium (Collisions expected) |
| `asal_surat` | Letter origin / sender| String (`"Bupati Semarang"`) | `incoming_letters`| `origin_agency` | `TRIM(asal_surat)` | Low |
| `tanggal_surat`| Date on the letter | Date string (`"2026-01-15"`, `"15/01/2026"`) | `incoming_letters`| `letter_date` | Normalize to ISO format (`YYYY-MM-DD`) | Medium (Format inconsistency) |
| `tanggal_diterima` | Date received | Date string | `incoming_letters`| `received_date`| Normalize to ISO format (`YYYY-MM-DD`) | Medium |
| `perihal` | Subject / Description | Text | `incoming_letters`| `subject` | `TRIM(perihal)` | Low |
| `email_address`| Submitter email | String or empty | (Discard) | (Discard) | Redundant with `created_by_user_id` | Low |
| `nama_pengupload` | Submitter name | String or empty | (Discard) | (Discard) | Denormalized string; map to `created_by_user_id` | Low |
| `upload_file` | Drive attachment link | URL string (`"https://drive.google.com/..."`) | `attachments` | `file_path`, `is_missing`, `migration_status`, `legacy_drive_url` | Extract Drive ID. If download succeeds: store on private disk, hash SHA-256. If Drive returns 404/deleted: set `is_missing = 1`, `migration_status = 'missing_drive_file'`, `file_path = NULL` | High (Missing Drive files) |
| `is_deleted` | Soft delete flag | Boolean / String (`TRUE`, `FALSE`) | `incoming_letters`| `deleted_at` | If `TRUE`, set `deleted_at = timestamp` or migration date; else `NULL` | Low |
| `deleted_at` | Deletion timestamp | Date string or empty | `incoming_letters`| `deleted_at` | Parse to `TIMESTAMP` if present | Low |
| `deleted_by` | Deleting user | Username or empty | `incoming_letters`| `deleted_by_user_id` | Look up `users.id` by username | Low |
| `timestamp` | Submission time | Date string | `incoming_letters`| `created_at` | Parse to `TIMESTAMP` | Low |

---

### 2.5 Outgoing Letters Mapping (`{KODE}_surat_keluar` → `outgoing_letters` & `attachments`)

*Source note: All `{KODE}_surat_keluar` sheets are consolidated into a single unified `outgoing_letters` table.*

| Legacy Header | Meaning | Observed Format | Target Table | Target Column | Transformation Rule | Migration Risk |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | Legacy row ID | Numeric or UUID string | `outgoing_letters`| `legacy_id` | Preserved in legacy metadata | Low |
| `{KODE}` (Sheet) | Owning division | Sheet prefix | `outgoing_letters`| `division_id` | Look up `divisions.id` matching `{KODE}` | Critical |
| `nomor_surat` | Letter number | String | `outgoing_letters`| `letter_number` | `TRIM(nomor_surat)`. Enforced unique on `(division_id, letter_year, letter_number)` | High (Duplicates in division) |
| `tanggal_surat`| Letter date | Date string | `outgoing_letters`| `letter_date`, `letter_year` | Normalize to `YYYY-MM-DD` for `letter_date`; extract `YEAR(tanggal_surat)` for `letter_year` | Medium |
| `tanggal_share`| Dissemination date | Date string or empty | `outgoing_letters`| `disseminated_date` | Normalize to `YYYY-MM-DD`, nullable | Medium |
| `perihal` | Subject | Text | `outgoing_letters`| `subject` | `TRIM(perihal)` | Low |
| `upload_file` | Drive attachment link | URL string | `attachments` | (Polymorphic) | Extract Drive ID. If missing: `is_missing = 1`, `migration_status = 'missing_drive_file'`, `file_path = NULL` | High |
| `is_deleted` | Soft delete flag | Boolean / String | `outgoing_letters`| `deleted_at` | If `TRUE`, set `deleted_at` timestamp | Low |
| `deleted_at` | Deletion timestamp | Date string or empty | `outgoing_letters`| `deleted_at` | Parse to `TIMESTAMP` | Low |
| `deleted_by` | Deleting user | Username or empty | `outgoing_letters`| `deleted_by_user_id` | Look up `users.id` by username | Low |
| `timestamp` | Creation time | Date string | `outgoing_letters`| `created_at` | Parse to `TIMESTAMP` | Low |

---

### 2.6 Piagam Mapping (`{KODE}_piagam` → `piagams` & `attachments`)

| Legacy Header | Meaning | Observed Format | Target Table | Target Column | Transformation Rule | Migration Risk |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | Legacy row ID | Numeric or UUID string | `piagams` | `legacy_id` | Preserved in legacy metadata | Low |
| `{KODE}` (Sheet) | Owning division | Sheet prefix | `piagams` | `division_id` | Look up `divisions.id` matching `{KODE}` | Critical |
| `nama_siswa` | Awardee student name | String (`"Ahmad Zaki"`) | `piagams` | `student_name` | `TRIM(nama_siswa)` | Low |
| `asal_sekolah` | School name | String | `piagams` | `school_id` | Primary lookup via `npsn` in `schools`. Fallback: name matching | Medium (Unmatched school) |
| `npsn` | School NPSN | 8-digit numeric string | `piagams` | `school_id` | Look up `schools.id` where `npsn = cleaned_npsn` | Medium |
| `jenis_perlombaan` | Competition type | String (`"FLS2N"`, `"O2SN"`) | `piagams` | `competition_category_id` | Match against `competition_categories.name` | Medium |
| `tahun_perlombaan` | Competition year | 4-digit year string (`"2026"`) | `piagams` | `competition_year` | Cast to integer (`YEAR`) | Low |
| `pengambilan` | Delivery/pickup status | String (`"Diambil Langsung"`) | `piagams` | `pickup_category_id` | Match against `pickup_categories.name` | Medium |
| `nama_pengambil` | Recipient claimant | String or empty | `piagams` | `recipient_name`| `TRIM(nama_pengambil)`, nullable | Low |
| `jabatan` | Claimant position | String or empty | `piagams` | `recipient_position` | `TRIM(jabatan)`, nullable | Low |
| `unit_kerja` | Claimant department | String or empty | `piagams` | `recipient_unit`| `TRIM(unit_kerja)`, nullable | Low |
| `ttd_pengambil` | Signature image link | Google Drive URL | `attachments` | (Polymorphic) | Extract Drive ID, fetch image, save to private disk. If missing, set `is_missing = 1` | High |
| `is_deleted` | Soft delete flag | Boolean / String | `piagams` | `deleted_at` | If `TRUE`, set `deleted_at` timestamp | Low |
| `timestamp` | Claim / creation date | Date string | `piagams` | `created_at` | Parse to `TIMESTAMP` | Low |
