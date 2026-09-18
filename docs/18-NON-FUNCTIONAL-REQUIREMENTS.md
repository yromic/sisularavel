# 18. NON-FUNCTIONAL REQUIREMENTS

## 1. Scope & System Quality Attributes

This document defines the quality attributes, operational constraints, availability requirements, mobile usability standards, and regulatory standards that the SISURAT application (initial baseline: Laravel ^12.0 / PHP ^8.2) must satisfy.

---

## 2. Detailed NFR Specifications

### 2.1 Mobile-First Usability & Accessibility (NFR-UI-01 to 04)
- **Primary Operational Platform:** The system must be fully optimized for smartphone operational use (down to 360px viewport width) where the majority of letter-entry tasks occur.
- **Touch Target Sizing:** All interactive buttons, links, dropdown triggers, and form controls must provide an effective minimum touch target area of **44 × 44 CSS pixels** to prevent mis-taps on mobile touchscreens.
- **Hover-Independence:** No critical workflow, navigation element, or data-action trigger may rely exclusively on mouse hover (`:hover`) states. All actions must be directly accessible via touch tap.
- **Form State Preservation:** In the event of a server validation rejection (HTTP 422) or temporary network timeout, all user-entered inputs must be retained without clearing form fields.
- **Duplicate Submission Prevention:** Submit buttons must disable immediately upon initial user tap with visual progress indication to prevent double-posting over slow mobile networks.
- **Visual Restraint & Legibility:** Minimum text contrast ratio of 4.5:1 (WCAG 2.1 AA). Clean institutional visual language without non-functional decorative animations, excessive gradients, or performance-degrading glassmorphism.
- **Icon Uniformity:** Mandatory adoption of a single, unified web-based SVG icon library. Plain Unicode glyphs, emoji as functional icons, or platform-specific symbols are strictly prohibited.

### 2.2 Maintainability & Code Quality
- **Standard Compliance:** All PHP code must adhere strictly to **PSR-12** code style guidelines.
- **Framework Conventions:** Adhere to standard Laravel directory conventions (`app/Http/Controllers`, `app/Models`, `app/Policies`, `app/Http/Requests`, `app/Services`). Avoid speculative enterprise abstractions (e.g. forced CQRS or superfluous repository layers) that hinder ongoing maintenance by district IT personnel.
- **Type Safety:** Method signatures in controllers and services must utilize strict parameter and return type declarations (`declare(strict_types=1);`).

### 2.3 Reliability & ACID Consistency
- **Transactional Integrity:** All multi-step database mutations (e.g., creating a letter and inserting an attachment record) must be wrapped in database transactions (`DB::transaction()`).
- **Referential Integrity:** Enforce foreign key constraints at the database level (`ON DELETE RESTRICT` or `ON DELETE CASCADE` where specified) to prevent orphan records.
- **Compensating Actions:** In the event of a disk storage failure during file upload, the database transaction must roll back and any partial file written must be purged.

### 2.4 Performance & Response Times
- **Read Latency (API & Web):**
  - Authenticated list queries (`/surat-masuk`, `/surat-keluar`, `/piagam`) must achieve p95 response time < 500 ms under standard dataset loads (up to 50,000 records).
- **Write Latency:**
  - Standard document creation (without file upload): p95 < 400 ms.
  - Document creation with 5 MB PDF upload: p95 < 3.0 seconds (a >98% reduction from legacy GAS 5.5-minute chunked upload duration).
- **Search & Filter:**
  - Text search queries across letter subject and numbers: p95 < 800 ms.

### 2.5 Availability & Operational Uptime
- **Target Availability:** 99.5% uptime during regional government working hours (Monday to Friday, 07:00 – 17:00 WIB).
- **Maintenance Windows:** Planned maintenance or system upgrades must be scheduled outside standard office hours (e.g., weekends or 22:00 – 04:00 WIB) with prior administrative notification.

### 2.6 Observability & Monitoring
- **Logging Standards:** Use Laravel's native `Log` facade with daily rotation (`daily` channel in `config/logging.php`), retaining logs for a minimum of 30 days.
- **Error Tracking:** Unhandled exceptions (HTTP 500) must be logged with request path, authenticated user ID, and stack trace (excluding sensitive parameters).
- **Audit Traceability:** All business mutations and security violations must be recorded in the immutable `audit_logs` table.

### 2.7 Backup & Disaster Recovery (DR)
- **Database Backup:** Automated daily MySQL dump (`mysqldump`) executed via cron. Backups must be gzip-compressed and retained off-site or in a secondary storage directory for 30 days.
- **Attachment Backup:** Nightly incremental backup of the private document storage directory (`storage/app/private/`).
- **Recovery Time Objective (RTO):** Full system restoration from cold backup within 4 hours.
- **Recovery Point Objective (RPO):** Maximum allowable data loss window: 24 hours (last nightly backup).

### 2.8 Browser Support & Responsive Fluidity
- **Browser Support:** Fully compatible with evergreen browser releases: Google Chrome, Microsoft Edge, Mozilla Firefox, and Apple Safari (desktop and mobile).
- **Responsive Viewports:** Fluid adaptation across 360px (narrow mobile), 390px (standard mobile), 768px (tablet), and 1024px+ (desktop).

### 2.9 Data Privacy & Legal Compliance
- **Indonesian PDP Law (UU No. 27 Tahun 2022):**
  - Personal data of students (names, schools, competition records) and civil servants (NIP, positions, signatures) must be protected against unauthorized disclosure.
  - Public access to certificate claim endpoints must require school identification (NPSN verification) and rate limiting.
  - Direct public internet indexing of student certificates and signatures via search engines is strictly forbidden (`robots.txt` disallowing `/attachments/`).
