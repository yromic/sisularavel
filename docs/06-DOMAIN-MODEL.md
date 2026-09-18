# 06 — Logical Domain Model & Entity Invariants

> **Requirement Prefix:** `DM`  
> **Status:** RATIFIED BASELINE v1.3.0  
> **Target Audience:** Database Architects, Backend Implementers, Domain Analysts  

---

## 1. Domain Class Diagram (Target Relational Architecture)

```mermaid
classDiagram
    class Division {
        +bigint id
        +string code
        +string name
        +enum status
        +string legacy_drive_folder_id
        +datetime created_at
        +datetime updated_at
        +is_active() bool
    }

    class User {
        +bigint id
        +string username
        +string name
        +string email
        +string password
        +enum role
        +bigint division_id
        +boolean is_active
        +boolean must_reset_password
        +datetime created_at
        +datetime updated_at
        +is_super_admin() bool
        +belongs_to_division(id) bool
    }

    class IncomingLetter {
        +bigint id
        +uuid uuid
        +bigint division_id
        +string letter_number
        +string origin_agency
        +date letter_date
        +date received_date
        +text subject
        +bigint created_by_user_id
        +datetime created_at
        +datetime updated_at
        +datetime deleted_at
    }

    class OutgoingLetter {
        +bigint id
        +uuid uuid
        +bigint division_id
        +smallint letter_year
        +string letter_number
        +date letter_date
        +date disseminated_date
        +text subject
        +bigint created_by_user_id
        +datetime created_at
        +datetime updated_at
        +datetime deleted_at
    }

    class Piagam {
        +bigint id
        +uuid uuid
        +bigint division_id
        +string student_name
        +bigint school_id
        +bigint competition_category_id
        +year competition_year
        +bigint pickup_category_id
        +string recipient_name
        +string recipient_position
        +string recipient_unit
        +datetime created_at
        +datetime updated_at
        +datetime deleted_at
    }

    class School {
        +bigint id
        +string npsn
        +string name
        +boolean is_active
        +datetime created_at
        +datetime updated_at
    }

    class Attachment {
        +bigint id
        +uuid uuid
        +bigint division_id
        +string attachable_type
        +bigint attachable_id
        +string file_path
        +string original_name
        +bigint file_size_bytes
        +string mime_type
        +string file_hash
        +boolean is_missing
        +enum migration_status
        +string missing_reason
        +datetime created_at
        +datetime deleted_at
    }

    class IdempotencyKey {
        +bigint id
        +string key
        +bigint user_id
        +bigint division_id
        +string request_hash
        +int response_code
        +string response_body
        +datetime created_at
        +datetime expires_at
    }

    Division "1" --> "0..*" User : employs
    Division "1" --> "0..*" IncomingLetter : owns
    Division "1" --> "0..*" OutgoingLetter : owns
    Division "1" --> "0..*" Piagam : manages
    School "1" --> "0..*" Piagam : references
    IncomingLetter "1" --> "0..*" Attachment : has
    OutgoingLetter "1" --> "0..*" Attachment : has
    Piagam "1" --> "0..*" Attachment : has
```

---

## 2. Core Entity Invariants & Lifecycle Rules

### 2.1 Division
* **Invariant DM-DIV-01:** `code` is immutable once provisioned, uppercase alphanumeric (e.g. `DIKDAS`).
* **Invariant DM-DIV-02:** When `status` transitions to `inactive`, all non-super-admin user sessions are invalidated immediately, and records become read-only.

### 2.2 User
* **Invariant DM-USR-01:** `username` is unique and normalized lowercase.
* **Invariant DM-USR-02:** Division Admins and Operators must have an immutable `division_id`. Super Admins have `division_id = NULL`.
* **Invariant DM-USR-03:** Insecure legacy passwords are NOT migrated. Migrated users have `must_reset_password = true` requiring one-time activation via Path A (email token) or Path B (out-of-band administrative activation).

### 2.3 Incoming Letter
* **Invariant DM-SM-01 (DEC-TEN-001):** Strictly single-division ownership in v1. Exactly one owning `division_id`. No inter-division routing exists in v1.
* **Invariant DM-SM-02 (DEC-LET-001):** The database primary key `id` is authoritative. External `letter_number` (`nomor_surat`) alone is NOT a unique key; external collisions from different senders are valid.
* **Invariant DM-SM-03:** `received_date` >= `letter_date`.

### 2.4 Outgoing Letter
* **Invariant DM-SK-01 (DEC-TEN-001):** Strictly single-division ownership in v1. Exactly one owning `division_id`.
* **Invariant DM-SK-02 (DEC-LET-002):** Enforced unique per division and calendar year: `UNIQUE (division_id, letter_year, letter_number)`. `letter_year` is derived deterministically from `letter_date`.
* **Invariant DM-SK-03:** `disseminated_date` >= `letter_date`.

### 2.5 Attachment
* **Invariant DM-ATT-01 (DEC-FILE-001):** Private storage by default. No public URLs. Files accessed via authorized streaming.
* **Invariant DM-ATT-02 (DEC-FILE-003):** Letters with missing legacy Drive files have durable `attachments` rows with `is_missing = true`, `file_path = NULL`, and explicit `migration_status`. Letter detail views load safely with a non-fatal warning badge.

### 2.6 Idempotency Key
* **Invariant DM-IDEM-01 (DEC-SYS-001):** Replays with matching key and request hash return cached responses. Replays with matching key but altered payload return HTTP 409 Conflict. Keys expire after 24 hours.
