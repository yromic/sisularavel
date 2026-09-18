# 08 — Role-Based Access Control (RBAC) & Division Scoping Contract

> **Requirement Prefix:** `RBAC`, `TEN`  
> **Status:** RATIFIED BASELINE v1.3.0  
> **Target Audience:** Security Engineers, Backend Developers, QA Architects  

---

## 1. Role Hierarchy & Conceptual Model

SISURAT enforces a strict 3-tier role hierarchy supplemented by a public guest access role:

```text
[Super Administrator] (super_admin)
   │  └─► Global administrative authority across all organizational divisions
   │
   ▼
[Division Administrator] (division_admin)
   │  └─► Full administrative management strictly within their own assigned division
   │
   ▼
[Operator] (operator)
   │  └─► Data entry and document retrieval strictly within their own assigned division
   │
   ▼
[Public Guest] (guest)
      └─► Unauthenticated access restricted to public certificate claim portal and public references
```

---

## 2. Granular Capability Permission Matrix

| Capability / Operation | Guest | Operator | Division Admin | Super Admin | Evidence / Rule |
|---|:---:|:---:|:---:|:---:|---|
| **Session & Auth** | | | | | |
| `auth:login` | Yes | Yes | Yes | Yes | `02_Auth.js:L227` |
| `auth:logout` | — | Yes | Yes | Yes | `98_Router.js:L32` |
| `auth:reset-password:self` | — | Yes | Yes | Yes | `02_Auth.js:L180` |
| `auth:activate-account` | Yes | Yes | Yes | Yes | `DEC-AUTH-001` |
| **Incoming Letters** | | | | | |
| `letters:incoming:read` | — | Yes (Own Div) | Yes (Own Div) | Yes (Global) | `01_Config.js:L84` |
| `letters:incoming:create`| — | Yes (Own Div) | Yes (Own Div) | Yes (Active Div) | `01_Config.js:L94` |
| `letters:incoming:update`| — | — | Yes (Own Div) | Yes (Global) | `01_Config.js:L100` |
| `letters:incoming:delete`| — | — | Yes (Own Div) | Yes (Global) | `01_Config.js:L106` |
| `letters:incoming:restore`| — | — | Yes (Own Div) | Yes (Global) | `BR-LET-002` |
| **Outgoing Letters** | | | | | |
| `letters:outgoing:read` | — | Yes (Own Div) | Yes (Own Div) | Yes (Global) | `01_Config.js:L85` |
| `letters:outgoing:create`| — | Yes (Own Div) | Yes (Own Div) | Yes (Active Div) | `01_Config.js:L95` |
| `letters:outgoing:update`| — | — | Yes (Own Div) | Yes (Global) | `01_Config.js:L101` |
| `letters:outgoing:delete`| — | — | Yes (Own Div) | Yes (Global) | `01_Config.js:L107` |
| `letters:outgoing:restore`| — | — | Yes (Own Div) | Yes (Global) | `BR-LET-002` |
| **Piagam Certificates** | | | | | |
| `piagam:read` | — | Yes (Own Div) | Yes (Own Div) | Yes (Global) | `01_Config.js:L86` |
| `piagam:create` | — | Yes (Own Div) | Yes (Own Div) | Yes (Active Div) | `01_Config.js:L96` |
| `piagam:update` | — | — | Yes (Own Div) | Yes (Global) | `01_Config.js:L102` |
| `piagam:delete` | — | — | Yes (Own Div) | Yes (Global) | `01_Config.js:L108` |
| `piagam:public-claim` | Yes | — | — | — | `09_PiagamService.js` |
| **Attachments & Storage** | | | | | |
| `attachments:stream` | — | Yes (Own Div) | Yes (Own Div) | Yes (Global) | `DEC-FILE-001` |
| `attachments:upload` | — | Yes (Own Div) | Yes (Own Div) | Yes (Active Div) | `DEC-FILE-002` |
| `attachments:remediate`| — | — | Yes (Own Div) | Yes (Global) | `DEC-FILE-003` |
| **Administration** | | | | | |
| `users:manage` | — | — | Yes (Own Div) | Yes (Global) | `07_UserService.js` |
| `users:reset-password` | — | — | Yes (Own Div) | Yes (Global) | `DEC-AUTH-001` |
| `divisions:manage` | — | — | — | Yes | `11_DivisionService.js` |
| `settings:manage` | — | — | — | Yes | `01_Config.js:L114` |
| `audit-logs:read` | — | — | — | Yes | `01_Config.js:L93` |

---

## 3. Resource Tenancy & Single-Division Ownership Contract (DEC-TEN-001)

In SISURAT v1, every division-owned record has **strictly one owning division (`division_id`)**. Inter-division forwarding, routing, or transfer is classified as a **DEFERRED FUTURE FEATURE** and is not part of v1.

| Resource Entity | Tenancy Scope | Operator / Division Admin Enforcement | Super Admin Context |
|---|---|---|---|
| `incoming_letters`| Division-Owned | Filtered strictly to `user.division_id`. Cross-division queries blocked. Division ownership immutable. | Filtered to `session('active_division_id')` or global view. Viewing does not transfer ownership. |
| `outgoing_letters`| Division-Owned | Scoped strictly to `user.division_id`. Division ownership immutable. | Scoped to active division. |
| `piagams` | Division-Owned | Scoped strictly to `user.division_id`. | Scoped to active division. |
| `attachments` | Inherited Tenancy | Access granted if user is authorized to view parent record in own division. | Access granted across divisions. |
| `pickup_categories`| Division-Owned | Scoped strictly to `user.division_id`. | Scoped to active division. |
| `competition_categories`| Division-Owned | Scoped strictly to `user.division_id`. | Scoped to active division. |
| `schools` | Global Shared | Readable by all authenticated users and public autocomplete. | Managed exclusively by Super Admin. |
| `divisions` | Global System | Read-only listing for dropdowns and badges. | Full lifecycle management. |
| `users` | Scoped to Division | Division Admin manages users strictly within own division. Cannot create `super_admin`. | Full global user management. |
| `audit_logs` | Global System | Access restricted; viewable exclusively by Super Admin. | Full audit trail inspection. |

---

## 4. Server-Side Enforcement Architecture

The contract mandates **explicit, testable server-side authorization** rather than prescribing a specific framework implementation choice (such as a hidden Eloquent global scope):

1. **Authentication & Session Gate:** Verifies active session token, checks that user is active, and verifies that the assigned division status is `'active'`.
2. **Division Context Resolution:** All division-scoped routes must resolve and evaluate `division_id`. For non-super-admins, `division_id` is derived strictly from the authenticated session, ignoring client-injected parameters.
3. **Policy Authorization:** Resource policies evaluate permission capabilities and enforce `record.division_id == user.division_id`.
4. **Deny-by-Default:** Any unauthenticated access to non-public routes, cross-division access attempts, or route tampering aborts with HTTP 401 or HTTP 403 (`ERR_403_DIVISI`) and generates a `SEC_CROSS_DIVISION_BLOCKED` audit record.

---

## 5. Negative Security Test Scenarios

The automated test suite **MUST** validate that the following attack vectors are blocked:

1. **Route ID Tampering:** Operator from Division A attempts `GET /api/v1/surat-masuk/{id}` where letter belongs to Division B. System returns HTTP 403 Forbidden.
2. **Payload Division Injection:** Operator from Division A submits `POST /api/v1/surat-masuk` with `{ "division_id": 2 }`. System enforces Division A ownership or rejects with HTTP 403.
3. **Ownership Alteration (DEC-TEN-001):** Division Admin attempts `PUT /api/v1/surat-masuk/{id}` with `{ "division_id": 2 }` attempting to transfer a letter. System rejects alteration; ownership remains immutable.
4. **Cross-Division Attachment Download:** User from Division A requests `GET /api/v1/attachments/{uuid}/preview` for a Division B file. System returns HTTP 403 and writes `SEC_CROSS_DIVISION_BLOCKED` to audit log.
5. **Super Admin Without Active Context:** Super Admin submits `POST /api/v1/surat-masuk` without specifying target division. System returns HTTP 400 (`ERR_400_DIVISI_REQUIRED`).
6. **Division Admin Privilege Escalation:** Division Admin attempts to create or elevate a user to `role = 'super_admin'`. System rejects with HTTP 403.
7. **Unauthorized Password Reset:** Division Admin attempts to trigger password reset for a user in another division. System returns HTTP 403.
8. **Inactive Account Rejection:** User with `is_active = false` attempts authenticated request. System invalidates session and returns HTTP 401.
