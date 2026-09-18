# 07. DATABASE CONTRACT

## 1. Relational Database Guidelines

* **Target Database Engines:** MySQL 8.0+ / MariaDB 10.5+
* **Storage Engine:** InnoDB
* **Character Set & Collation:** `utf8mb4` with `utf8mb4_unicode_ci`
* **Naming Conventions:**
  * Tables: plural snake_case (e.g., `incoming_letters`, `users`).
  * Columns: singular snake_case (e.g., `division_id`, `letter_number`).
  * Foreign keys: `fk_{table}_{referenced_table}_{column}`
  * Unique keys: `uq_{table}_{columns}`
  * Indexes: `idx_{table}_{columns}`

---

## 2. Table Specifications

### 2.1 Table: `divisions`
* **Purpose:** Master repository of institutional divisions (e.g., DIKDAS, PAUD).
* **Legacy Source:** `db_divisi`
* **Primary Key:** `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
* **Columns:**

| Column Name | Semantic Type | Nullable | Default | Description & Constraint |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Surrogate primary key. |
| `uuid` | CHAR(36) | No | None | Public immutable UUID v4 identifier. |
| `code` | VARCHAR(20) | No | None | Unique division code (e.g., 'DIKDAS', 'PAUD'). |
| `name` | VARCHAR(150) | No | None | Full legal division name. |
| `status` | VARCHAR(20) | No | 'active' | Status enum ('active', 'inactive'). |
| `legacy_drive_folder_id` | VARCHAR(100) | Yes | NULL | Retained legacy Drive folder ID for audit. |
| `created_by_user_id`| BIGINT UNSIGNED| Yes | NULL | Foreign key to `users(id)`. |
| `created_at` | TIMESTAMP | Yes | NULL | Creation timestamp. |
| `updated_at` | TIMESTAMP | Yes | NULL | Last modification timestamp. |
| `deleted_at` | TIMESTAMP | Yes | NULL | Soft-deletion timestamp. |

* **Foreign Keys:**
  - `fk_divisions_created_by`: `FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL`.
* **Unique Constraints:**
  - `uq_divisions_uuid` ON (`uuid`).
  - `uq_divisions_code` ON (`code`).
* **Indexes:**
  - `idx_divisions_status` ON (`status`).
  - `idx_divisions_deleted_at` ON (`deleted_at`).

---

### 2.2 Table: `users`
* **Purpose:** System actors, operators, and administrative credentials.
* **Legacy Source:** `db_users`
* **Primary Key:** `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
* **Columns:**

| Column Name | Semantic Type | Nullable | Default | Description & Constraint |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| `uuid` | CHAR(36) | No | None | Public immutable UUID v4 identifier. |
| `username` | VARCHAR(50) | No | None | Unique login username (normalized lowercase). |
| `name` | VARCHAR(150) | No | None | Full legal staff name. |
| `email` | VARCHAR(150) | Yes | NULL | Contact email address. |
| `password` | VARCHAR(255) | No | None | Bcrypt password hash (Cost: 12). |
| `role` | VARCHAR(50) | No | 'operator' | Role slug ('super_admin', 'division_admin', 'operator'). |
| `division_id` | BIGINT UNSIGNED | Yes | NULL | Owning division foreign key. NULL for Super Admin. |
| `is_active` | BOOLEAN | No | 1 | Account operational state (1 = active, 0 = disabled). |
| `must_reset_password` | BOOLEAN | No | 0 | Flag requiring user to establish a new password upon login. |
| `password_reset_token` | CHAR(64) | Yes | NULL | SHA-256 hashed single-use activation/reset token. |
| `password_reset_expires_at`| TIMESTAMP | Yes | NULL | Expiration timestamp for password reset token. |
| `created_at` | TIMESTAMP | Yes | NULL | Creation timestamp. |
| `updated_at` | TIMESTAMP | Yes | NULL | Update timestamp. |
| `deleted_at` | TIMESTAMP | Yes | NULL | Soft-deletion timestamp. |

* **Foreign Keys:**
  - `fk_users_division`: `FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT`.
* **Unique Constraints:**
  - `uq_users_uuid` ON (`uuid`).
  - `uq_users_username` ON (`username`).
* **Indexes:**
  - `idx_users_division_role` ON (`division_id`, `role`, `is_active`).
  - `idx_users_reset_token` ON (`password_reset_token`, `password_reset_expires_at`).
  - `idx_users_deleted_at` ON (`deleted_at`).

---

### 2.3 Table: `incoming_letters`
* **Purpose:** Archival registry of official external correspondence received.
* **Legacy Source:** `{KODE}_surat_masuk`
* **Primary Key:** `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
* **Identity Principle:** The database primary key `id` is the authoritative record identifier. An incoming letter number (`letter_number` / `nomor_surat`) alone is NOT a unique key, as external sending institutions legitimately issue colliding numbers. No universal UNIQUE constraint is placed on `letter_number`. Duplicate detection is performed as a business advisory check.
* **Columns:**

| Column Name | Semantic Type | Nullable | Default | Description & Constraint |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| `uuid` | CHAR(36) | No | None | Public immutable UUID v4 identifier. |
| `division_id` | BIGINT UNSIGNED | No | None | Owning division foreign key (Immutable single-division ownership in v1). |
| `letter_number`| VARCHAR(100) | No | None | Official letter number assigned by external sender (`nomor_surat`). |
| `origin_agency`| VARCHAR(255) | No | None | Origin agency or organization (`asal_surat`). |
| `letter_date` | DATE | No | None | Date written on letter (`tanggal_surat`). |
| `received_date`| DATE | No | None | Date received by division (`tanggal_diterima`). |
| `subject` | TEXT | No | None | Letter subject or content summary (`perihal`). |
| `created_by_user_id`| BIGINT UNSIGNED| Yes | NULL | User who registered the letter. |
| `updated_by_user_id`| BIGINT UNSIGNED| Yes | NULL | User who last updated the letter. |
| `deleted_by_user_id`| BIGINT UNSIGNED| Yes | NULL | User who soft-deleted the letter. |
| `created_at` | TIMESTAMP | Yes | NULL | System registration timestamp. |
| `updated_at` | TIMESTAMP | Yes | NULL | System update timestamp. |
| `deleted_at` | TIMESTAMP | Yes | NULL | Soft-deletion timestamp. |

* **Foreign Keys:**
  - `fk_in_letters_division`: `FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT`.
  - `fk_in_letters_created_by`: `FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL`.
* **Unique Constraints:**
  - `uq_in_letters_uuid` ON (`uuid`).
* **Indexes:**
  - `idx_in_letters_div_dates` ON (`division_id`, `letter_date`, `received_date`).
  - `idx_in_letters_div_number` ON (`division_id`, `letter_number`).
  - `idx_in_letters_duplicate_advisory` ON (`division_id`, `letter_number`, `letter_date`, `origin_agency`).
  - `idx_in_letters_deleted_at` ON (`deleted_at`).

---

### 2.4 Table: `outgoing_letters`
* **Purpose:** Archival registry of official outgoing administrative correspondence dispatched.
* **Legacy Source:** `{KODE}_surat_keluar`
* **Primary Key:** `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
* **Uniqueness Principle:** Outgoing letters are generated internally and must be unique per issuing division and calendar year. An explicit `letter_year` column (derived from `letter_date`) is maintained and indexed with an explicit unique constraint.
* **Columns:**

| Column Name | Semantic Type | Nullable | Default | Description & Constraint |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| `uuid` | CHAR(36) | No | None | Public immutable UUID v4 identifier. |
| `division_id` | BIGINT UNSIGNED | No | None | Owning division foreign key (Immutable single-division ownership in v1). |
| `letter_year` | SMALLINT UNSIGNED | No | None | Calendar year derived deterministically from `letter_date` (`tanggal_surat`). |
| `letter_number`| VARCHAR(100) | No | None | Official outgoing letter number (`nomor_surat`). |
| `letter_date` | DATE | No | None | Date written on letter (`tanggal_surat`). |
| `disseminated_date`| DATE | No | None | Date shared or dispatched (`tanggal_share`). |
| `subject` | TEXT | No | None | Letter subject or directive summary (`perihal`). |
| `created_by_user_id`| BIGINT UNSIGNED| Yes | NULL | User who registered the letter. |
| `updated_by_user_id`| BIGINT UNSIGNED| Yes | NULL | User who last updated the letter. |
| `deleted_by_user_id`| BIGINT UNSIGNED| Yes | NULL | User who soft-deleted the letter. |
| `created_at` | TIMESTAMP | Yes | NULL | Registration timestamp. |
| `updated_at` | TIMESTAMP | Yes | NULL | Update timestamp. |
| `deleted_at` | TIMESTAMP | Yes | NULL | Soft-deletion timestamp. |

* **Foreign Keys:**
  - `fk_out_letters_division`: `FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT`.
  - `fk_out_letters_created_by`: `FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL`.
* **Unique Constraints:**
  - `uq_out_letters_uuid` ON (`uuid`).
  - `uq_out_letters_div_year_number` ON (`division_id`, `letter_year`, `letter_number`).
* **Indexes:**
  - `idx_out_letters_div_dates` ON (`division_id`, `letter_date`, `disseminated_date`).
  - `idx_out_letters_div_year_number` ON (`division_id`, `letter_year`, `letter_number`).
  - `idx_out_letters_deleted_at` ON (`deleted_at`).

---

### 2.5 Table: `piagams`
* **Purpose:** Student competition certificates and physical retrieval tracking.
* **Legacy Source:** `{KODE}_piagam`
* **Primary Key:** `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
* **Columns:**

| Column Name | Semantic Type | Nullable | Default | Description & Constraint |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| `uuid` | CHAR(36) | No | None | Public immutable UUID v4 identifier. |
| `division_id` | BIGINT UNSIGNED | No | None | Owning division foreign key. |
| `student_name`| VARCHAR(150) | No | None | Full legal student name (`nama_siswa`). |
| `school_id` | BIGINT UNSIGNED | No | None | Reference to `schools(id)`. |
| `competition_category_id`| BIGINT UNSIGNED| No | None | Competition category (`jenis_perlombaan`). |
| `competition_year` | YEAR | No | None | Year competition was held (`tahun_perlombaan`). |
| `pickup_category_id` | BIGINT UNSIGNED| Yes | NULL | Retrieval classification (`pengambilan`). |
| `recipient_name` | VARCHAR(150) | Yes | NULL | Individual who claimed physical award (`nama_pengambil`). |
| `recipient_position` | VARCHAR(100) | Yes | NULL | Position/Title of claimant (`jabatan`). |
| `recipient_unit` | VARCHAR(150) | Yes | NULL | Department or school unit (`unit_kerja`). |
| `created_by_user_id` | BIGINT UNSIGNED| Yes | NULL | Staff who entered the record. |
| `created_at` | TIMESTAMP | Yes | NULL | Creation timestamp. |
| `updated_at` | TIMESTAMP | Yes | NULL | Update timestamp. |
| `deleted_at` | TIMESTAMP | Yes | NULL | Soft-deletion timestamp. |

* **Foreign Keys:**
  - `fk_piagams_division`: `FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT`.
  - `fk_piagams_school`: `FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE RESTRICT`.
  - `fk_piagams_competition`: `FOREIGN KEY (competition_category_id) REFERENCES competition_categories(id) ON DELETE RESTRICT`.
  - `fk_piagams_pickup`: `FOREIGN KEY (pickup_category_id) REFERENCES pickup_categories(id) ON DELETE RESTRICT`.
* **Unique Constraints:** `uq_piagams_uuid` ON (`uuid`).
* **Indexes:**
  - `idx_piagams_div_school` ON (`division_id`, `school_id`).
  - `idx_piagams_student_search` ON (`student_name`, `competition_year`).

---

### 2.6 Table: `attachments`
* **Purpose:** Polymorphic private file binary metadata, digital signature images, and legacy file reconciliation.
* **Primary Key:** `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
* **Durable Missing Representation:** A legacy letter is never discarded if its Drive file is 404. It receives an `attachments` row with `file_path = NULL`, `is_missing = TRUE`, and an explicit `migration_status`.
* **Columns:**

| Column Name | Semantic Type | Nullable | Default | Description & Constraint |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| `uuid` | CHAR(36) | No | None | Public immutable UUID v4 identifier. |
| `division_id` | BIGINT UNSIGNED | No | None | Owning division foreign key. |
| `attachable_type` | VARCHAR(191) | No | None | Polymorphic parent model (e.g., 'IncomingLetter', 'Piagam'). |
| `attachable_id` | BIGINT UNSIGNED | No | None | Polymorphic parent record ID. |
| `file_path` | VARCHAR(500) | Yes | NULL | Storage path on private disk; NULL if legacy file is missing. |
| `original_name` | VARCHAR(255) | No | None | Original client filename. |
| `file_size_bytes` | BIGINT UNSIGNED | No | 0 | File size in bytes. |
| `mime_type` | VARCHAR(100) | No | 'application/octet-stream' | Verified MIME type. |
| `file_hash` | CHAR(64) | Yes | NULL | SHA-256 checksum. |
| `is_missing` | BOOLEAN | No | 0 | 1 if referenced legacy file is missing/inaccessible. |
| `migration_status`| ENUM | No | 'migrated' | Status: 'migrated', 'missing_drive_file', 'inaccessible_drive_file', 'download_failed', 'validation_rejected', 'no_attachment_expected'. |
| `missing_reason` | VARCHAR(255) | Yes | NULL | Explanatory error details if file is missing. |
| `legacy_drive_file_id` | VARCHAR(100) | Yes | NULL | Original Google Drive file ID. |
| `legacy_drive_url` | TEXT | Yes | NULL | Original Google Drive URL. |
| `created_by_user_id`| BIGINT UNSIGNED| Yes | NULL | User who uploaded the file. |
| `created_at` | TIMESTAMP | Yes | NULL | Upload timestamp. |
| `updated_at` | TIMESTAMP | Yes | NULL | Update timestamp. |
| `deleted_at` | TIMESTAMP | Yes | NULL | Soft-deletion timestamp. |

* **Foreign Keys:**
  - `fk_attachments_division`: `FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT`.
* **Unique Constraints:** `uq_attachments_uuid` ON (`uuid`).
* **Indexes:**
  - `idx_attachments_polymorphic` ON (`attachable_type`, `attachable_id`).
  - `idx_attachments_division` ON (`division_id`).
  - `idx_attachments_missing` ON (`is_missing`, `migration_status`).

---

### 2.7 Table: `schools`
* **Purpose:** Master reference list of educational institutions.
* **Legacy Source:** `ref_sekolah`
* **Primary Key:** `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
* **Columns:**

| Column Name | Semantic Type | Nullable | Default | Description & Constraint |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| `npsn` | CHAR(8) | No | None | 8-digit unique National School Registration Number. |
| `name` | VARCHAR(255) | No | None | Official school name. |
| `is_active` | BOOLEAN | No | 1 | 1 = active, 0 = closed/merged. |
| `created_at` | TIMESTAMP | Yes | NULL | Creation timestamp. |
| `updated_at` | TIMESTAMP | Yes | NULL | Update timestamp. |

* **Unique Constraints:** `uq_schools_npsn` ON (`npsn`).
* **Indexes:** `idx_schools_name` ON (`name`).

---

### 2.8 Table: `pickup_categories`
* **Purpose:** Classification options for piagam delivery/pickup.
* **Legacy Source:** `{KODE}_ref_pengambilan`
* **Columns:** `id`, `division_id`, `name`, `is_active`, `created_at`, `updated_at`.
* **Foreign Keys:** `FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT`.

---

### 2.9 Table: `competition_categories`
* **Purpose:** Classification options for competitions (FLS2N, O2SN, etc.).
* **Legacy Source:** `{KODE}_ref_jenis`
* **Columns:** `id`, `division_id`, `name`, `is_active`, `created_at`, `updated_at`.
* **Foreign Keys:** `FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT`.

---

### 2.10 Table: `settings`
* **Purpose:** Key-value configuration for global branding and defaults.
* **Legacy Source:** `db_config`
* **Columns:** `id`, `key` (VARCHAR(100) UNIQUE), `value` (TEXT NULL), `created_at`, `updated_at`.

---

### 2.11 Table: `audit_logs`
* **Purpose:** Immutable append-only audit trail.
* **Legacy Source:** `db_audit_log`
* **Columns:** `id`, `user_id` (NULL on public events), `username`, `role`, `division_id`, `event_code`, `auditable_type`, `auditable_id`, `old_values` (JSON), `new_values` (JSON), `ip_address`, `user_agent`, `description`, `created_at`.
* **Indexes:** `idx_audit_event_time` ON (`event_code`, `created_at`), `idx_audit_user` ON (`user_id`), `idx_audit_division` ON (`division_id`).

---

### 2.12 Table: `idempotency_keys`
* **Purpose:** Server-side duplicate-submission prevention and response caching for high-risk create operations.
* **Columns:**

| Column Name | Semantic Type | Nullable | Default | Description & Constraint |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key. |
| `key` | VARCHAR(64) | No | None | Client-supplied unique token (`Idempotency-Key` UUIDv4). |
| `user_id` | BIGINT UNSIGNED | Yes | NULL | Authenticated user ID; NULL for public guest submissions. |
| `division_id` | BIGINT UNSIGNED | Yes | NULL | Active division context ID. |
| `request_hash` | CHAR(64) | No | None | SHA-256 fingerprint of HTTP method, route, and normalized JSON payload. |
| `response_code`| SMALLINT UNSIGNED| No | None | Cached HTTP status code (e.g. 201, 422). |
| `response_body`| MEDIUMTEXT | No | None | Cached JSON response body for exact replay. |
| `created_at` | TIMESTAMP | No | CURRENT_TIMESTAMP | Submission timestamp. |
| `expires_at` | TIMESTAMP | No | None | Expiration threshold (default 24 hours). |

* **Unique Constraints:** `uq_idempotency_key_user` ON (`key`, `user_id`).
* **Indexes:** `idx_idempotency_expires` ON (`expires_at`).
