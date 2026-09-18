# 02 — Glossary & Canonical Terminology

> **Requirement Prefix:** `SYS`, `DM`  
> **Status:** RATIFIED BASELINE  
> **Target Audience:** All Engineers, Implementers, QA, Product Owners  

---

## 1. Canonical Terminology Mapping

To avoid ambiguities caused by disparate naming across legacy Google Sheets headers, JavaScript variables, and informal user dialogue, the following canonical dictionary **MUST** be used consistently in all code, documentation, database columns, and UI labels:

| Canonical English Term | Canonical Indonesian Term | Legacy Aliases / Deprecated Terms | Strict Definition & Domain Context |
|---|---|---|---|
| **Division** | **Divisi** | *Bidang, Unit, Bagian, Sub-bagian, Tenant* | An autonomous administrative operational branch within Disdikbudpora (e.g., DIKDAS, GTK, PAUD). Serves as the primary tenant isolation boundary. |
| **Incoming Letter** | **Surat Masuk** | *Inbound mail, Surat Diterima, db_surat_masuk* | An official formal document received from an external entity (ministry, school, agency) by a division. |
| **Outgoing Letter** | **Surat Keluar** | *Outbound mail, Surat Dikeluarkan, db_surat_keluar* | An official formal administrative letter, circular, or decree issued by a division to external or internal parties. |
| **Piagam** | **Piagam / Sertifikat Penghargaan** | *Certificate, Piagam Penghargaan, db_piagam* | An official certificate awarded to students or schools for competition achievements, requiring signed handover proof. |
| **Canvas Signature** | **Tanda Tangan Digital (TTD)** | *Signature, TTD Pengambil, ttd_base64* | A raster PNG graphic captured directly in the browser via HTML5 Canvas API representing the physical signature of the certificate collector. |
| **School** | **Sekolah** | *School registry, ref_sekolah, Asal Sekolah* | An accredited educational institution operating in Semarang Regency, uniquely identified nationally by NPSN. |
| **NPSN** | **NPSN** | *Nomor Pokok Sekolah Nasional, No NPSN* | The 8-digit unique National School Identification Number assigned by the Indonesian Ministry of Education. |
| **Super Admin** | **Super Admin Pusat** | *Superadmin, SA, Administrator Global* | The highest privileged role with global scope across all divisions, system settings, and user provisioning. |
| **Division Admin** | **Admin Divisi** | *Admin, Administrator Bidang* | An administrative user scoped to exactly one division with full CRUD authority over that division's documents and operators. |
| **Operator** | **Operator Divisi** | *Staff, Pengupload, Petugas Input* | An operational clerical user scoped to one division with create and read authority only. Prohibited from editing or deleting data. |
| **Public User** | **Pengguna Publik / Pengambil** | *Guest, anonymous, public_user, Siswa/Guru* | An unauthenticated external person accessing the public certificate handover portal (`piagam.html`). |
| **Soft Delete** | **Hapus Sementara (Sampah)** | *Trash, is_deleted, softDelete, recovery bin* | A deletion state where database records remain persisted with `deleted_at IS NOT NULL` and are excluded from default query results. |
| **Hard Delete** | **Hapus Permanen** | *Permanent delete, drop, purge, deleteRow* | The irrevocable physical removal of records or tables from the database and files from disk storage. |
| **Inactive Status** | **Nonaktif** | *Disabled, blocked, deactive, aktif=false* | The suspension state of a user account or division. The entity exists, but login or write access is blocked. |
| **File Attachment** | **Lampiran Berkas** | *Upload file, berkas surat, dokumen fisik, upload_file* | A digital binary asset (PDF, JPG, PNG) supporting an incoming/outgoing letter or certificate handover. |
| **Storage Path** | **Jalur Penyimpanan** | *Drive URL, link Google Drive, uc?export=view* | The internal relative filesystem path on private disk storage where a binary attachment is safely stored. |
| **Audit Log** | **Log Aktivitas / Audit Trail** | *db_audit_log, activity_log, histori aksi* | An append-only chronological record documenting every security, access, and mutation event. |

---

## 2. Acronyms & Abbreviations Dictionary

* **ACID:** Atomicity, Consistency, Isolation, Durability (Database transaction guarantees).
* **ADR:** Architectural Decision Record (Recorded in `docs/24-DECISION-LOG.md`).
* **API:** Application Programming Interface.
* **CORS:** Cross-Origin Resource Sharing.
* **CSRF:** Cross-Site Request Forgery.
* **DISDIKBUDPORA:** Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga Kabupaten Semarang.
* **FLS2N:** Festival dan Lomba Seni Siswa Nasional (Annual national student arts competition).
* **GAS:** Google Apps Script (Legacy serverless execution environment).
* **IDOR:** Insecure Direct Object Reference (Security vulnerability).
* **KURSISSMP:** Seksi Kurikulum dan Penilaian Sekolah Menengah Pertama (Division within Disdikbudpora).
* **MAPSI:** Mata Pelajaran Pendidikan Agama Islam dan Seni Islami (Student religious competition).
* **MIME:** Multipurpose Internet Mail Extensions (File type classification).
* **NPSN:** Nomor Pokok Sekolah Nasional (8-digit national school code).
* **O2SN:** Olimpiade Olahraga Siswa Nasional (Annual national student athletics/sports competition).
* **OSN:** Olimpiade Sains Nasional (Annual national science olympiad).
* **PII:** Personally Identifiable Information.
* **RBAC:** Role-Based Access Control.
* **SPA / MPA:** Single-Page Application / Multi-Page Application.
* **SWR:** Stale-While-Revalidate (Legacy client caching pattern).
* **TTL:** Time-To-Live (Expiration period for cache or sessions).
* **UAT:** User Acceptance Testing.
* **UUID:** Universally Unique Identifier (v4 standard).
* **XSS:** Cross-Site Scripting.

---

## 3. Disambiguation Rules

1. **`Role` vs. `Jabatan`:**
   - `Role` refers strictly to the application security authorization level (`super_admin`, `admin_divisi`, `operator`).
   - `Jabatan` refers exclusively to the external professional job position of a certificate collector (e.g. "Kepala Sekolah", "Guru Pembimbing", "Orang Tua Siswa") captured as metadata in Piagam.
2. **`Division` vs. `Unit Kerja`:**
   - `Division` refers exclusively to an internal department tenant within Disdikbudpora.
   - `Unit Kerja` refers to the external workplace or institutional employer of a certificate recipient.
3. **`Deleted` vs. `Inactive`:**
   - A document is **deleted** when its lifecycle is terminated (`deleted_at` is set).
   - A user or division is **inactive** when its status flag is switched to `inactive` (`is_active = false`), which halts operational usage without deleting the entity.
