# 05 — End-to-End Workflow Contracts

> **Requirement Prefix:** `WF`  
> **Status:** RATIFIED BASELINE  
> **Target Audience:** Developers, QA Architects, UI Engineers  

---

## 1. Authentication & Division Context Workflows

### WF-AUTH-01: User Login Workflow

```mermaid
sequenceDiagram
    autonumber
    actor User as Staff / Admin
    participant Client as Browser (Login Page)
    participant AuthCtrl as AuthController
    participant Throttle as RateLimiter
    participant UserDB as Database (users)
    participant Audit as AuditLogService

    User->>Client: Enters username & password
    Client->>AuthCtrl: POST /login (username, password)
    AuthCtrl->>Throttle: Check attempts (max 5 / 15m)
    alt Rate limit exceeded
        Throttle-->>AuthCtrl: Blocked
        AuthCtrl-->>Client: 429 Too Many Requests (ERR_429_TOO_MANY_ATTEMPTS)
    end

    AuthCtrl->>UserDB: Query user by username
    alt User not found or is_active = false
        UserDB-->>AuthCtrl: null or inactive
        AuthCtrl->>Audit: Record LOGIN_FAILED
        AuthCtrl-->>Client: 401 Unauthorized (ERR_401_INVALID_CREDENTIALS)
    end

    AuthCtrl->>AuthCtrl: Verify password (Bcrypt Hash::check)
    alt Password invalid
        AuthCtrl->>Audit: Record LOGIN_FAILED
        AuthCtrl-->>Client: 401 Unauthorized (ERR_401_INVALID_CREDENTIALS)
    end

    alt User has must_reset_password == true
        AuthCtrl->>Audit: Record PASSWORD_RESET_REQUIRED
        AuthCtrl-->>Client: 403 Forbidden / Redirect /password/activate (ERR_403_PASSWORD_RESET_REQUIRED)
    end

    alt User assigned to division AND division.status == 'inactive'
        AuthCtrl->>Audit: Record LOGIN_DENIED_INACTIVE_DIVISION
        AuthCtrl-->>Client: 403 Forbidden (ERR_403_DIVISION_INACTIVE)
    end

    AuthCtrl->>Throttle: Clear attempts
    AuthCtrl->>AuthCtrl: Regenerate session & establish sliding expiration
    alt Role == 'super_admin'
        AuthCtrl->>AuthCtrl: Set active_division_id = null (default global)
    else
        AuthCtrl->>AuthCtrl: Set active_division_id = user.division_id
    end

    AuthCtrl->>Audit: Record LOGIN_SUCCESS
    AuthCtrl-->>Client: 200 OK / Redirect to /dashboard
```

* **Idempotency & Concurrency:** Login is stateful (session creation) and protected by IP/username throttling.
* **Failure Handling:** Generic error message displayed to prevent username enumeration: *"Username atau password salah."*

---

### WF-AUTH-02: Migrated Account Password Activation & Reset Workflow

```mermaid
sequenceDiagram
    autonumber
    actor User as Migrated Staff
    participant Client as Browser (Reset Page)
    participant ResetCtrl as PasswordResetController
    participant UserDB as Database (users)
    participant TokenDB as Database (password_reset_tokens)
    participant Audit as AuditLogService

    alt Path A: Self-Service Activation via Verified Email
        User->>Client: Enters registered email on /password/forgot
        Client->>ResetCtrl: POST /password/email { email }
        ResetCtrl->>UserDB: Lookup user by email
        alt User exists & is_active = true
            ResetCtrl->>TokenDB: Create secure random token (expires in 60 min)
            ResetCtrl->>ResetCtrl: Dispatch PasswordResetNotification (Email)
            ResetCtrl->>Audit: Record PASSWORD_RESET_REQUESTED (without token value)
        end
        ResetCtrl-->>Client: 200 OK (Generic message: "Tautan reset telah dikirim jika email terdaftar")
        User->>Client: Clicks link from email: /password/reset/{token}
        User->>Client: Submits new password (min 12 chars)
        Client->>ResetCtrl: POST /password/reset { token, email, password }
        ResetCtrl->>TokenDB: Verify token & expiration
        ResetCtrl->>UserDB: UPDATE users SET password = Hash::make(new), must_reset_password = false
        ResetCtrl->>TokenDB: Invalidate/DELETE used token
        ResetCtrl->>Audit: Record PASSWORD_RESET_COMPLETED
        ResetCtrl-->>Client: 200 OK (Password berhasil diperbarui, silakan login)
    else Path B: Admin-Assisted Activation (No Reliable Email)
        actor Admin as Division Admin / Super Admin
        Admin->>Client: Clicks "Generate Kredensial Aktivasi" on user management
        Client->>ResetCtrl: POST /admin/users/{id}/activation-secret
        ResetCtrl->>ResetCtrl: Check Policy: Admin authorized for user's division
        ResetCtrl->>UserDB: Generate cryptographically random 16-char temporary secret
        ResetCtrl->>UserDB: UPDATE users SET password = Hash::make(secret), must_reset_password = true
        ResetCtrl->>Audit: Record ADMIN_ISSUED_ACTIVATION_CREDENTIAL (actor_id, target_user_id, NO secret logged)
        ResetCtrl-->>Client: 200 OK (Display secret ONCE via secure modal with warning)
        Admin->>User: Transmits secret via out-of-band secure channel (in-person / internal memo)
        User->>Client: Logs in with temporary secret on /login
        Client->>ResetCtrl: POST /login
        ResetCtrl->>ResetCtrl: Detects must_reset_password == true
        ResetCtrl-->>Client: 403 / Force Redirect to /password/activate
        User->>Client: Submits new permanent password
        Client->>ResetCtrl: POST /password/activate { current_temp_secret, new_password }
        ResetCtrl->>UserDB: UPDATE users SET password = Hash::make(new), must_reset_password = false
        ResetCtrl->>Audit: Record ACCOUNT_ACTIVATED_VIA_ADMIN_SECRET
        ResetCtrl-->>Client: 200 OK / Authenticated Session Established
    end
```

* **Security Guarantees:** Legacy weak hashes and plaintext credentials are never accepted. Secrets and tokens are never logged. Generic enumeration shielding applies to all public endpoints.

---

### WF-TEN-01: Super Admin Division Context Switch

```mermaid
sequenceDiagram
    autonumber
    actor SA as Super Admin
    participant Client as Browser (Sidebar Switcher)
    participant TenCtrl as DivisionContextController
    participant DivDB as Database (divisions)

    SA->>Client: Selects division from dropdown (e.g. DIKDAS)
    Client->>TenCtrl: POST /admin/context/division { division_id: 2 }
    TenCtrl->>TenCtrl: Check Policy: user is super_admin
    TenCtrl->>DivDB: Find division where id = 2 AND status = 'active'
    alt Division not found or inactive
        DivDB-->>TenCtrl: null
        TenCtrl-->>Client: 404 Not Found (ERR_404_DIVISION_NOT_FOUND)
    end

    TenCtrl->>TenCtrl: Session::put('active_division_id', 2)
    TenCtrl-->>Client: 200 OK { active_division: 'DIKDAS' }
    Client->>Client: Reload view or update UI active division badge
```

---

## 2. Letter Management Workflows

### WF-SM-01: Create Incoming Letter (Surat Masuk)

```mermaid
sequenceDiagram
    autonumber
    actor Operator as Division Operator / Admin
    participant Client as Browser Form
    participant IdemMid as IdempotencyMiddleware
    participant LetterCtrl as IncomingLetterController
    participant Policy as LetterPolicy
    participant Storage as PrivateDisk (Storage::disk)
    participant DB as MySQL Transaction
    participant Audit as AuditLogService

    Operator->>Client: Fills form (nomor, asal, tanggal, perihal) & attaches PDF
    Client->>Client: Generate UUIDv4 for Idempotency-Key
    Client->>IdemMid: POST /letters/incoming (Header: Idempotency-Key: {uuid})
    
    IdemMid->>DB: Query idempotency_keys WHERE key = {uuid} AND user_id = {auth_id}
    alt Key exists with matching request_hash AND status = 'completed'
        IdemMid-->>Client: Replay cached HTTP 201 Response
    else Key exists with DIFFERENT request_hash
        IdemMid-->>Client: 409 Conflict (ERR_409_IDEMPOTENCY_CONFLICT)
    else Key exists with status = 'in_progress'
        IdemMid-->>Client: 409 Conflict (ERR_409_CONCURRENT_REQUEST)
    end

    IdemMid->>DB: INSERT into idempotency_keys (key, user_id, status='in_progress', hash)
    IdemMid->>LetterCtrl: Pass request to controller
    LetterCtrl->>Policy: authorize('create', IncomingLetter::class)
    LetterCtrl->>LetterCtrl: Validate request (StoreLetterRequest)

    LetterCtrl->>DB: Check potential duplicate (division_id, asal_surat, nomor_surat, tanggal_surat)
    opt Potential Duplicate Found
        Note over LetterCtrl: Record advisory flag; does NOT block legitimate save
    end

    LetterCtrl->>DB: Begin DB::transaction()
    LetterCtrl->>Storage: Stage file to private/tmp/{uuid}.pdf
    LetterCtrl->>DB: INSERT into incoming_letters (uuid, division_id, ...)
    LetterCtrl->>Storage: Move to private/attachments/{division_id}/{uuid}.pdf
    LetterCtrl->>DB: INSERT into attachments (attachable_id, file_path, ...)
    LetterCtrl->>DB: UPDATE idempotency_keys SET status='completed', response_code=201, response_body=...
    LetterCtrl->>Audit: Record LETTER_INCOMING_CREATED
    DB-->>LetterCtrl: Commit transaction

    alt Any error during DB insertion
        LetterCtrl->>Storage: Delete staged file
        LetterCtrl->>DB: Rollback transaction (Idempotency key deleted or marked failed)
        LetterCtrl-->>Client: 500 Internal Server Error (ERR_500_SAVE_FAILED)
    end

    LetterCtrl-->>Client: 201 Created (JSON / Redirect with toast)
```

* **Data Mutations:** Inserts 1 row in `incoming_letters`, 1 row in `attachments`, 1 row in `idempotency_keys`, 1 row in `audit_logs`.
* **File Mutations:** Writes 1 file into `storage/app/private/attachments/{division_id}/`.
* **Atomicity & Consistency:** If database insert throws exception, the compensating cleanup action immediately deletes the staged physical file from disk.
* **Server-Side Idempotency:** Double-tap submissions or retried requests are trapped by `IdempotencyMiddleware` before any database or filesystem side-effects occur.
* **Duplicate Letter Handling:** Incoming letter collisions from different senders are permitted; database primary key `id` is authoritative.

---

### WF-SM-02: Update Incoming Letter with Attachment Replacement

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Division Admin
    participant LetterCtrl as IncomingLetterController
    participant Storage as PrivateDisk
    participant DB as MySQL Transaction
    participant Audit as AuditLogService

    Admin->>LetterCtrl: PUT /letters/incoming/{id} (metadata + new file optional)
    LetterCtrl->>LetterCtrl: Policy check & FormRequest validation
    LetterCtrl->>DB: Begin DB::transaction()
    LetterCtrl->>DB: UPDATE incoming_letters SET nomor_surat = ..., perihal = ...

    alt New attachment file provided
        LetterCtrl->>Storage: Save new file to private/attachments/{division_id}/{new_uuid}.pdf
        LetterCtrl->>DB: UPDATE attachments SET file_path = new_path, file_name = ...
        LetterCtrl->>Storage: Delete old file from disk (Storage::delete(old_path))
    end

    LetterCtrl->>Audit: Record LETTER_INCOMING_UPDATED
    DB-->>LetterCtrl: Commit transaction
    LetterCtrl-->>Admin: 200 OK (Data berhasil diperbarui)
```

* **Compensation Logic:** Old physical file is deleted **only after** the database update transaction confirms commitment. If transaction fails, the newly uploaded temporary file is purged, leaving the original attachment intact.

---

## 3. Piagam & Digital Signature Workflows

### WF-PG-02: Public Piagam Collection with Canvas Signature

```mermaid
sequenceDiagram
    autonumber
    actor Public as School Representative / Winner
    participant Portal as Public Web Portal (/piagam-penghargaan)
    participant PiagamCtrl as PublicPiagamController
    participant Storage as PrivateDisk
    participant DB as MySQL Transaction
    participant Audit as AuditLogService

    Public->>Portal: Selects division, selects school (NPSN auto-fills)
    Public->>Portal: Enters student name, competition year & category
    Public->>Portal: Signs on HTML5 Canvas signature pad
    Public->>Portal: Clicks "Simpan Piagam"
    Portal->>Portal: Export canvas to PNG Data URL (ttd_base64)
    Portal->>PiagamCtrl: POST /api/public/piagam (JSON payload)

    PiagamCtrl->>PiagamCtrl: Throttle check (max 10 / hour per IP)
    PiagamCtrl->>PiagamCtrl: Validate FormRequest (school exists, division active, valid base64)
    PiagamCtrl->>PiagamCtrl: Decode Base64 PNG to binary image
    
    PiagamCtrl->>DB: Begin DB::transaction()
    PiagamCtrl->>Storage: Save binary to private/signatures/{division_id}/{uuid}.png
    PiagamCtrl->>DB: INSERT into piagams (division_id, school_id, signature_path, submitter_type='public')
    PiagamCtrl->>Audit: Record PIAGAM_PUBLIC_SUBMITTED (actor='public', ip=request.ip)
    DB-->>PiagamCtrl: Commit transaction
    PiagamCtrl-->>Portal: 201 Created { status: 'success', message: 'Otentikasi Piagam Berhasil Disimpan!' }
    Portal->>Portal: Clear form & clear canvas pad
```

---

## 4. File Streaming & Download Workflow

### WF-FILE-01: Authenticated Attachment Download

```mermaid
sequenceDiagram
    autonumber
    actor User as Authenticated Staff
    participant StreamCtrl as DocumentStreamController
    participant Policy as AttachmentPolicy
    participant Storage as PrivateDisk
    participant Audit as AuditLogService

    User->>StreamCtrl: GET /attachments/{uuid}/download
    StreamCtrl->>StreamCtrl: Look up attachment by UUID
    StreamCtrl->>Policy: authorize('view', attachment.attachable)
    alt Cross-division attempt by operator/admin
        Policy-->>StreamCtrl: Denied
        StreamCtrl->>Audit: Record UNAUTHORIZED_FILE_ACCESS
        StreamCtrl-->>User: 403 Forbidden (ERR_403_DIVISI)
    end

    alt Attachment has is_missing == true (Legacy Drive File Missing)
        StreamCtrl->>Audit: Record LEGACY_ATTACHMENT_UNAVAILABLE
        StreamCtrl-->>User: 404 Not Found (ERR_404_ATTACHMENT_MISSING: Berkas warisan tidak ditemukan)
    end

    StreamCtrl->>Storage: Check file exists: Storage::disk('private')->exists(file_path)
    alt File missing on physical disk
        StreamCtrl->>Audit: Record FILE_MISSING_ON_DISK
        StreamCtrl-->>User: 404 Not Found (ERR_404_FILE_NOT_FOUND)
    end

    StreamCtrl->>Audit: Record FILE_DOWNLOADED
    StreamCtrl-->>User: Binary Stream response (Content-Type, Content-Disposition)
```

---

## 5. Reporting & Analytics Workflow

### WF-REP-01: Monthly Recap & Report Generation

```mermaid
sequenceDiagram
    autonumber
    actor User as Division Admin / Super Admin
    participant RepCtrl as ReportController
    participant DB as MySQL Database
    participant Exporter as CsvStream / PdfGenerator

    User->>RepCtrl: GET /reports?type=all&from=2026-01-01&to=2026-06-30
    RepCtrl->>RepCtrl: Apply active division tenant scope
    RepCtrl->>DB: Query letters & piagams within date range
    DB-->>RepCtrl: Result collection

    alt User requested CSV Export
        RepCtrl->>Exporter: Stream CSV headers & rows (PHP php://output)
        RepCtrl-->>User: CSV Download Stream
    else User requested PDF Export
        RepCtrl->>Exporter: Compile DOMPDF / Blade template with official header
        RepCtrl-->>User: PDF Download Stream
    else User requested Web Preview
        RepCtrl-->>User: 200 OK (Rendered Paginated Preview Table)
    end
```
