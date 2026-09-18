# 13. AUDIT LOG CONTRACT

## 1. Scope & Objective

In legacy SISURAT, activity logging was implemented via `writeAuditLog` in `backend/06_Logger.js`, which appended flat rows to the `db_audit_log` Google Sheet (`timestamp, actor, role, divisi_id, action, table_name, record_id, detail`). While functional, it lacked IP tracing, user-agent auditing, state change diffs (`before` and `after` attributes), and was vulnerable to manual tampering directly within the Google Sheets UI.

This contract defines an immutable, high-integrity audit logging specification for the Laravel target system (initial baseline: Laravel ^12.0 / PHP ^8.2), establishing end-to-end operational traceability for government archiving compliance.

---

## 2. Event Code Registry

All auditable domain mutations and security events must record one of the following standardized event codes:

| Event Code | Category | Trigger Condition | Severity |
| :--- | :--- | :--- | :--- |
| `AUTH_LOGIN` | Authentication | Successful user authentication | Informational |
| `AUTH_LOGOUT` | Authentication | Explicit user session termination | Informational |
| `AUTH_LOGIN_FAILED` | Authentication | Invalid credentials or inactive account attempt | Warning |
| `AUTH_PASSWORD_RESET` | Authentication | Password reset performed by admin or user | Notice |
| `LETTER_CREATED` | Letters | New incoming or outgoing letter recorded | Informational |
| `LETTER_UPDATED` | Letters | Letter metadata modified | Informational |
| `LETTER_DELETED` | Letters | Letter soft-deleted | Notice |
| `LETTER_RESTORED` | Letters | Soft-deleted letter recovered | Notice |
| `ATTACHMENT_UPLOADED` | Files | Document or signature binary stored on disk | Informational |
| `ATTACHMENT_STREAMED` | Files | Document previewed or downloaded by user | Informational |
| `ATTACHMENT_DELETED` | Files | Attachment purged or unlinked | Notice |
| `PIAGAM_CREATED` | Piagam | New certificate record registered | Informational |
| `PIAGAM_CLAIMED` | Piagam | Public/school guest submits digital signature receipt | Informational |
| `PIAGAM_DELETED` | Piagam | Certificate record deleted | Notice |
| `USER_CREATED` | Administration | New user account provisioned | Notice |
| `USER_UPDATED` | Administration | User role, division, or status altered | Notice |
| `USER_DEACTIVATED` | Administration | User account toggled to inactive | Warning |
| `DIVISION_CREATED` | Administration | New organizational division initialized | Notice |
| `DIVISION_STATUS_CHANGED`| Administration | Division activated or deactivated | Warning |
| `CONFIG_UPDATED` | Administration | System settings / application metadata altered | Notice |
| `SEC_CROSS_DIVISION_BLOCKED` | Security | Unauthorized attempt to access another division's data | Critical |
| `SEC_RATE_LIMIT_EXCEEDED` | Security | Brute force or API abuse threshold reached | Warning |

---

## 3. Database Schema & Payload Architecture

All logs are written to the `audit_logs` table defined in `07-DATABASE-CONTRACT.md`:

```sql
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL COMMENT 'Null for unauthenticated / system events',
    `username` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) NOT NULL,
    `division_id` BIGINT UNSIGNED NULL,
    `event_code` VARCHAR(50) NOT NULL,
    `auditable_type` VARCHAR(191) NULL,
    `auditable_id` BIGINT UNSIGNED NULL,
    `old_values` JSON NULL COMMENT 'State before update/delete (sanitized)',
    `new_values` JSON NULL COMMENT 'State after insert/update (sanitized)',
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `description` VARCHAR(500) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_logs_event` (`event_code`),
    INDEX `idx_audit_logs_user` (`user_id`),
    INDEX `idx_audit_logs_division` (`division_id`),
    INDEX `idx_audit_logs_created_at` (`created_at`),
    INDEX `idx_audit_logs_polymorphic` (`auditable_type`, `auditable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Payload Redaction & Sanitization Contract

Before serializing `old_values` and `new_values` into JSON, the audit listener or service must filter out prohibited sensitive attributes:

- **Redacted Keys:** `password`, `password_hash`, `password_salt`, `remember_token`, `session_token`, `authorization`, `credit_card`.
- **Transformation:** Prohibited keys must be replaced with the string `"[REDACTED]"`.
- **File Payloads:** Uploaded binary payloads or Base64 signature strings must never be dumped into `audit_logs`. Only file metadata (original name, byte size, generated UUID, MIME type) may be stored.

---

## 5. Failure Resilience & Isolation

1. **Transaction Independence:**
   - Audit logging of security violations (`SEC_CROSS_DIVISION_BLOCKED`, `AUTH_LOGIN_FAILED`) must execute regardless of whether the incoming HTTP request is aborted with an exception or HTTP 403/401 status.
   - If writing an audit record fails due to a temporary database issue, the error must be logged to Laravel's file logger (`storage/logs/laravel.log`) without suppressing or crashing the primary business workflow unless strict regulatory compliance explicitly demands transaction rollback.
2. **Immutability Contract:**
   - No update or delete endpoints shall exist for the `audit_logs` table.
   - Access to audit trails in the UI is restricted to Super Admin, with read-only pagination and filtering.

---

## 6. Retention & Pruning Policy

1. **Minimum Legal Retention:** In alignment with administrative archival standards, operational audit logs must be retained for a minimum of 24 months.
2. **Archival & Pruning:**
   - A scheduled artisan command (`php artisan sisurat:prune-audit-logs --days=730`) may archive older logs to compressed JSON cold-storage before purging database rows.
   - Hard deletion without prior export/backup is prohibited.
