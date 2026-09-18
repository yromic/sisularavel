# 04. BUSINESS RULES

## 1. Division & Multi-Tenancy Rules

```text
ID: BR-TEN-001
Title: Strict Division Data Isolation
Statement: All letter records, piagam records, and document attachments MUST be associated with exactly one organizational division ('division_id'). Users assigned to a specific division MUST NOT be permitted to read, update, or delete records belonging to any other division.
Applies to: incoming_letters, outgoing_letters, piagams, attachments, users
Classification: VERIFIED
Evidence: 08_RecordService.js:L53-58, 08_RecordService.js:L360-366, 11_DivisionService.js:L180-210, laporan_pengujian_bab43.json:L24-33 (TC-02)
Rationale: Prevents unauthorized cross-departmental information leakage and enforces strict operational boundaries.
Exceptions: Super Admin possess global authorization across all divisions.
Related requirements: TEN-001, RBAC-001, SEC-002
Acceptance criteria: An operator from Division A attempting to query or mutate a record in Division B receives HTTP 403 Forbidden with error code "ERR_403_DIVISI".
```

```text
ID: BR-TEN-002
Title: Super Admin Active Division Context
Statement: When performing division-scoped operations (creating letters, uploading division files, or filtering division records), a Super Admin MUST operate within an explicitly declared active division context.
Applies to: incoming_letters, outgoing_letters, piagams, attachments
Classification: VERIFIED
Evidence: 98_Router.js:L58-62, 98_Router.js:L132-136, 10_UploadService.js:L22-24
Rationale: Prevents accidental creation of un-scoped or orphan division records by global administrators.
Exceptions: Global reporting and system-wide user/division management.
Related requirements: TEN-002, RBAC-001
Acceptance criteria: A Super Admin attempting to create a letter without specifying 'division_id' receives HTTP 400 Bad Request with error code "ERR_400_DIVISI_REQUIRED".
```

```text
ID: BR-TEN-003
Title: Deactivated Division Access Lockout
Statement: If an organizational division's status is set to 'inactive', all non-super-admin user accounts belonging to that division MUST be immediately blocked from logging in or performing any data mutations. Existing division records become read-only to Super Admin.
Applies to: divisions, users, incoming_letters, outgoing_letters, piagams
Classification: VERIFIED
Evidence: 02_Auth.js:L116-120, 02_Auth.js:L281-287, 11_DivisionService.js:L180-210
Rationale: Enforces organizational deactivation and prevents modifications to decommissioned administrative units.
Exceptions: Super Admin retains read-only access for archival and auditing purposes.
Related requirements: FUN-DIV-02, AUTH-001
Acceptance criteria: A user belonging to an inactive division attempting to log in receives HTTP 403 Forbidden with error message "Divisi Anda dinonaktifkan. Silakan hubungi Super Admin."
```

```text
ID: BR-TEN-004
Title: Global Reference Data Read Accessibility
Statement: School master data ('schools' / 'ref_sekolah') is globally shared across all divisions. All authenticated operators and public piagam claim workflows have read-only access to the school reference catalog.
Applies to: schools
Classification: VERIFIED
Evidence: 98_Router.js:L41, 01_Config.js:L81
Rationale: Standardized national school registry (NPSN) is common to all education divisions.
Exceptions: Creation and modification of school reference records is restricted to Super Admin.
Related requirements: FUN-REF-01, RBAC-001
Acceptance criteria: An operator from any division can query and autocomplete school names without receiving division access errors.
```

```text
ID: BR-TEN-005
Title: Strict Single-Division Ownership in v1
Statement: In SISURAT v1, every division-owned record (incoming letter, outgoing letter, piagam, attachment) MUST have exactly one immutable owning division ('division_id'). Inter-division forwarding, routing, disposition, or transfer is classified as a DEFERRED FUTURE FEATURE and is NOT permitted in v1. Super Admin viewing a record across divisions does not alter its ownership.
Applies to: incoming_letters, outgoing_letters, piagams, attachments
Classification: VERIFIED (Approved Target Decision DEC-TEN-001)
Evidence: 08_RecordService.js:L256-257, 98_Router.js:L132-136, DEC-TEN-001
Rationale: Preserves clear organizational accountability and audit boundaries without premature workflow complexity.
Exceptions: None in v1.
Related requirements: TEN-001, RBAC-001
Acceptance criteria: A PUT/PATCH request attempting to modify 'division_id' on an existing letter is rejected with HTTP 422 or ignored; division ownership remains immutable.
```

---

## 2. Letter Management Business Rules

```text
ID: BR-SM-001
Title: Incoming Letter Mandatory Attributes
Statement: Every incoming letter record MUST specify an official letter number ('nomor_surat'), sender/issuing agency ('asal_surat'), letter date ('tanggal_surat'), received date ('tanggal_diterima'), and subject ('perihal').
Applies to: incoming_letters
Classification: VERIFIED
Evidence: 01_Config.js:L148, 08_RecordService.js:L306-324, master.js:L120-155
Rationale: Official archival governance requires full traceability of external correspondence origin and dates.
Exceptions: None.
Related requirements: FUN-SM-02, VAL-SM-001
Acceptance criteria: Submission lacking 'asal_surat', 'nomor_surat', 'tanggal_surat', 'tanggal_diterima', or 'perihal' fails validation with HTTP 422 Unprocessable Entity.
```

```text
ID: BR-SM-002
Title: Letter Date Sequence Temporal Consistency
Statement: The received date ('tanggal_diterima') of an incoming letter MUST be greater than or equal to the letter date ('tanggal_surat'). A document cannot legally or physically be received prior to the date it was written by the external sending agency.
Applies to: incoming_letters
Classification: INFERRED (Archival Standard)
Evidence: Domain archival standard; legacy frontend date constraints
Rationale: Preserves temporal integrity of official legal documents.
Exceptions: None.
Related requirements: VAL-SM-002
Acceptance criteria: If tanggal_diterima < tanggal_surat, submission fails with validation error "Tanggal diterima tidak boleh lebih awal dari tanggal surat."
```

```text
ID: BR-SM-003
Title: Incoming Letter Number Non-Uniqueness & Duplicate Advisory
Statement: The database primary key ('id') is the sole authoritative record identifier. An incoming letter number ('nomor_surat') alone is NOT a unique key, as external sending institutions legitimately and frequently issue identical letter numbers (e.g. '005/01/2026'). The system MUST NOT impose a universal UNIQUE constraint on incoming 'nomor_surat'. Instead, the system MUST implement a duplicate advisory check matching (division_id, asal_surat, nomor_surat, tanggal_surat). If an exact match is detected, the system warns the operator ("Surat dengan nomor, asal, dan tanggal yang sama sudah terdaftar"), but permits intentional registration upon explicit operator confirmation.
Applies to: incoming_letters
Classification: VERIFIED (Legacy Code Audit & Approved Decision DEC-LET-001)
Evidence: db.txt:L24 (no unique constraint), 08_RecordService.js:L324 (appendRow with no duplicate check), DEC-LET-001
Rationale: Eliminates false-positive rejection of legitimate external letters while providing operational duplicate awareness.
Exceptions: None.
Related requirements: FUN-SM-01, DB-001, VAL-SM-003
Acceptance criteria: Two incoming letters from different external senders with identical 'nomor_surat' are stored successfully with distinct primary keys. An incoming letter matching an existing record triggers an advisory warning prompt.
```

```text
ID: BR-SK-001
Title: Outgoing Letter Dissemination Date Mandatory
Statement: Every outgoing letter record MUST specify an official letter number ('nomor_surat'), letter date ('tanggal_surat'), dissemination date ('tanggal_share'), and subject ('perihal').
Applies to: outgoing_letters
Classification: VERIFIED
Evidence: 01_Config.js:L149, 08_RecordService.js:L306-324, master.js:L157-175
Rationale: Outgoing letters must track when circulars or decrees were officially disseminated to subordinate institutions.
Exceptions: None.
Related requirements: FUN-SK-02, VAL-SK-001
Acceptance criteria: Submission lacking 'tanggal_share' fails validation with HTTP 422 Unprocessable Entity.
```

```text
ID: BR-SK-002
Title: Outgoing Letter Number Annual Division Uniqueness
Statement: Outgoing letter numbers are generated internally and MUST be unique within the issuing division and calendar year. The system MUST maintain an explicit 'letter_year' column (SMALLINT UNSIGNED) derived deterministically from 'tanggal_surat'. The database MUST enforce an explicit unique constraint: UNIQUE KEY uq_outgoing_letters_division_year_number (division_id, letter_year, nomor_surat). If 'tanggal_surat' is edited, 'letter_year' is recomputed and validated against the unique constraint.
Applies to: outgoing_letters
Classification: VERIFIED (Approved Decision DEC-LET-002)
Evidence: 01_Config.js:L149, DEC-LET-002
Rationale: Prevents duplicate numbering of internal decrees and circulars within the same administrative year.
Exceptions: None.
Related requirements: FUN-SK-01, DB-001, VAL-SK-002
Acceptance criteria: Attempting to create an outgoing letter with a 'nomor_surat' already registered within the same division and year fails with HTTP 409 Conflict ("Nomor surat keluar sudah terdaftar pada divisi dan tahun ini").
```

```text
ID: BR-LET-001
Title: Immutable Letter Creator Audit Attribution
Statement: Upon creation of an incoming or outgoing letter, the system MUST automatically record the creating user's ID ('created_by'), name, and email. These attributes MUST be immutable and protected from alteration during subsequent edits.
Applies to: incoming_letters, outgoing_letters
Classification: VERIFIED
Evidence: 08_RecordService.js:L287-288, 08_RecordService.js:L432-436
Rationale: Maintains legal accountability regarding which staff member registered the document.
Exceptions: None.
Related requirements: AUD-001, SEC-003
Acceptance criteria: An update request attempting to supply a modified 'created_by' is ignored; the original creator is preserved.
```

```text
ID: BR-LET-002
Title: Soft-Deletion and Document Restoration
Statement: Letters deleted by authorized administrators MUST NOT be purged from the database immediately. The record MUST be marked with 'deleted_at' and 'deleted_by'. Soft-deleted letters are excluded from standard list queries but can be restored by Division Admin or Super Admin.
Applies to: incoming_letters, outgoing_letters, piagams
Classification: VERIFIED
Evidence: db.txt:L24, 08_RecordService.js:L465-495
Rationale: Protects against accidental data loss and supports regulatory audit recovery.
Exceptions: Super Admin hard-purge routines.
Related requirements: FUN-SM-04, FUN-SK-04
Acceptance criteria: Issuing DELETE /api/v1/surat-masuk/{id} populates 'deleted_at' timestamp; issuing POST /api/v1/surat-masuk/{id}/restore resets 'deleted_at' to NULL.
```

---

## 3. Certificate (Piagam) Business Rules

```text
ID: BR-PG-001
Title: Public Piagam Claim Digital Signature Requirement
Statement: When a school representative claims a student certificate via the public portal, they MUST provide their full name ('recipient_name'), official position ('recipient_position'), institution/department ('recipient_unit'), and a non-empty digital signature rendered via HTML5 canvas pad.
Applies to: piagams, attachments
Classification: VERIFIED
Evidence: 09_PiagamService.js:L20-45, piagam.html:L120-160
Rationale: Establishes legal proof of physical certificate handover to authorized school personnel.
Exceptions: Internal administrative updates by Super Admin.
Related requirements: FUN-PG-02, VAL-PG-001
Acceptance criteria: A public claim submission with an empty signature canvas is rejected with HTTP 422 Unprocessable Entity ("Tanda tangan digital wajib dibubuhkan").
```

```text
ID: BR-PG-002
Title: School Registration & NPSN Association
Statement: Every piagam record MUST link to a registered school via 'school_id' (derived from 8-digit NPSN). If a school is not registered in 'schools', the submission must be blocked or the school must be registered first by Super Admin.
Applies to: piagams, schools
Classification: VERIFIED
Evidence: db.txt:L30, 08_RecordService.js:L320
Rationale: Prevents certificate issuance under orphaned or unverified school identities.
Exceptions: None.
Related requirements: FUN-PG-01, DB-002
Acceptance criteria: Submitting a piagam claim with an invalid or non-existent NPSN returns HTTP 422 ("NPSN sekolah tidak terdaftar").
```

---

## 4. User & Authentication Business Rules

```text
ID: BR-USER-001
Title: Authentication & Failed Login Rate Limiting
Statement: Accounts with 'is_active = false' or belonging to an 'inactive' division MUST be blocked from authenticating. The system MUST enforce a rate limit of maximum 5 consecutive failed attempts per 15-minute window per IP/username tuple. Exceeding this limit locks the login endpoint with HTTP 429.
Applies to: users, authentication
Classification: VERIFIED
Evidence: 02_Auth.js:L231-241, 02_Auth.js:L277-287
Rationale: Protects administrative accounts against online brute-force credential stuffing attacks.
Exceptions: None.
Related requirements: AUTH-001, SEC-001
Acceptance criteria: The 6th consecutive incorrect login attempt returns HTTP 429 with localized message "Terlalu banyak percobaan login. Coba lagi dalam 15 menit."
```

```text
ID: BR-USER-002
Title: User Administration Scoping
Statement: A Division Admin is strictly authorized to manage user accounts assigned to their OWN division. Only Super Admin can provision accounts across multiple divisions or assign the 'super_admin' role.
Applies to: users
Classification: VERIFIED
Evidence: 07_UserService.js:L50-80, 03_RBAC.js:L30-45
Rationale: Delegates divisional user administration without compromising global security.
Exceptions: Super Admin.
Related requirements: FUN-USR-01, RBAC-001
Acceptance criteria: A Division Admin attempting to update or reset the password of a user in another division receives HTTP 403 Forbidden.
```

```text
ID: BR-USER-003
Title: Deprecation of Insecure Legacy Passwords & Controlled Account Activation
Statement: Legacy plaintext and salted SHA-256 passwords MUST NOT be imported as valid credentials. The target security contract rejects legacy insecure credential formats. Every migrated legacy account MUST be assigned an unguessable randomized hash, flagged with 'must_reset_password = true', and required to establish a new password through one of two authorized paths: (Path A) single-use expiring reset token sent to verified email, or (Path B) single-use temporary credential generated by authorized admin and delivered out-of-band, forcing immediate replacement upon first authentication. Password secrets and reset tokens MUST NEVER be logged.
Applies to: users, authentication, migration
Classification: VERIFIED (Approved Decision DEC-AUTH-001)
Evidence: 02_Auth.js:L205-225, DEC-AUTH-001
Rationale: Eliminates vulnerabilities associated with weak legacy hashes while providing a structured, auditable account activation lifecycle.
Exceptions: None.
Related requirements: AUTH-001, SEC-001, MIG-002
Acceptance criteria: Migrated users cannot log in with their legacy password. Initiating activation via Path A or Path B requires immediate password change before granting dashboard access. Password values never appear in logs.
```

---

## 5. File & Idempotency Business Rules

```text
ID: BR-FILE-001
Title: Private File Storage & Authorized Streaming
Statement: All document attachments and digital signature images MUST be stored in private filesystem disks outside the public document root. Direct permanent public URLs (such as Google Drive ANYONE_WITH_LINK) are strictly prohibited. Access to attachments MUST be mediated through an authorized controller route verifying division scope and user permissions.
Applies to: attachments
Classification: VERIFIED (Approved Decision DEC-FILE-001)
Evidence: 05_Drive.js:L37 (legacy vulnerability rejected), DEC-FILE-001
Rationale: Protects sensitive government correspondence and student data from public internet exposure.
Exceptions: Public certificate verification receipts if explicitly authorized.
Related requirements: FILE-001, SEC-002
Acceptance criteria: Requesting GET /api/v1/attachments/{uuid}/preview without a valid division-authorized session returns HTTP 401 or HTTP 403.
```

```text
ID: BR-FILE-002
Title: Multipart Binary Upload Ceiling & MIME Whitelist
Statement: File uploads must use standard HTTP multipart/form-data with a maximum file size of 10 MB (10,240 KB). Only whitelisted MIME types (application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document, image/jpeg, image/png) are accepted.
Applies to: attachments, uploads
Classification: VERIFIED
Evidence: 10_UploadService.js:L82-86, DEC-FILE-002
Rationale: Protects storage quotas and mitigates malicious executable upload risks.
Exceptions: Canvas signature uploads are restricted to image/png or image/jpeg up to 2 MB.
Related requirements: FILE-002, VAL-FILE-001
Acceptance criteria: Uploading a file with extension .exe or size > 10 MB is rejected with HTTP 415 or HTTP 413.
```

```text
ID: BR-FILE-003
Title: Durable Database Representation of Missing Legacy Attachments
Statement: A legacy letter record MUST NOT be discarded during migration solely because its referenced Google Drive attachment is missing, deleted, or inaccessible (HTTP 404/403). The system MUST create a durable database attachment record linked to the letter with 'file_path = NULL', 'is_missing = true', and 'migration_status' set to a descriptive state ('missing_drive_file', 'inaccessible_drive_file', 'download_failed', 'validation_rejected'). In the UI, letter details remain fully accessible, rendering a clear warning badge ("Lampiran lama tidak tersedia"), disabling the download button, and permitting authorized later re-upload.
Applies to: attachments, migration
Classification: VERIFIED (Approved Decision DEC-FILE-003)
Evidence: db.txt:L24, 05_Drive.js:L43-55, DEC-FILE-003
Rationale: Preserves official archival correspondence metadata while maintaining operational transparency over missing legacy media.
Exceptions: Letters where no attachment was ever uploaded ('upload_file' was empty).
Related requirements: FILE-001, MIG-008
Acceptance criteria: A legacy letter whose Drive link is 404 is imported successfully. Its detail view displays letter metadata without error, flags the missing attachment, and prevents broken streaming requests.
```

```text
ID: BR-IDEM-001
Title: Server-Side Idempotency & Duplicate Submission Protection
Statement: Client-side submit button disabling is a usability convenience, NOT a data integrity guarantee. The target system MUST enforce server-side duplicate-submission protection for all critical create mutations (incoming letters, outgoing letters, piagams, and public piagam claims). Clients MUST transmit an 'Idempotency-Key' header (UUIDv4). The server caches request fingerprints and responses for 24 hours in a database-backed 'idempotency_keys' table. Replays with identical keys and payloads return the cached response; replays with identical keys but altered payloads abort with HTTP 409 Conflict ('ERR_409_IDEMPOTENCY_CONFLICT').
Applies to: incoming_letters, outgoing_letters, piagams, attachments
Classification: VERIFIED (Approved Decision DEC-SYS-001)
Evidence: DEC-SYS-001, NFR-UI-04
Rationale: Prevents duplicate record creation caused by rapid double-taps, network retries, or browser re-submissions over high-latency cellular connections.
Exceptions: Read-only requests (GET, HEAD, OPTIONS).
Related requirements: SYS-001, UI-006
Acceptance criteria: Sending two concurrent identical POST requests with the same Idempotency-Key results in exactly one database record inserted and two identical successful HTTP responses returned.
```
