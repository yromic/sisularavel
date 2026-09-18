# 21. SHARED HOSTING CONSTRAINTS

## 1. Scope & Objective

While modern enterprise systems frequently rely on containerization (Docker/Kubernetes) and persistent worker daemons (Supervisor/Redis), municipal and district government departments in Indonesia often deploy information systems to conventional shared cPanel or VPS hosting.

This contract defines technical guardrails to ensure the SISURAT application remains 100% deployable on standard shared cPanel hosting environments without forfeiting architectural integrity or performance.

> [!WARNING]
> **Runtime Lifecycle & Upgrade Compatibility Notice:**  
> Initial deployment baseline is **Laravel ^12.0 on PHP >= 8.2** to maintain full compatibility with the local XAMPP PHP 8.2 development environment. Because Laravel 12 security fixes conclude on February 24, 2027, production hosting should preferably support or provide an upgrade path to **PHP >= 8.3** to facilitate future migration to Laravel 13. Production hosting runtime must be verified independently of local XAMPP.

---

## 2. Shared Hosting Capability Classification Matrix

### 2.1 Initial Deployment Compatibility (Mandatory Baseline)
- **Framework Target:** Laravel ^12.0
- **Runtime Requirement:** PHP >= 8.2 (supported range: PHP 8.2–8.5)
- **Local Dev Baseline:** XAMPP PHP 8.2 (COMPATIBLE)
- **Production Server Verification:** `RUNTIME VALIDATION REQUIRED` (must be confirmed on the actual hosting server prior to cutover)

### 2.2 Future Upgrade Compatibility (Preferred Maintenance Target)
- **Framework Target:** Laravel 13.x
- **Runtime Requirement:** PHP >= 8.3 (supported range: PHP 8.3–8.5)
- **Timeline:** Planned maintenance milestone prior to Laravel 12 security expiration (February 24, 2027).

### 2.3 Detailed Hosting Capability Matrix

| Architecture Component | Target Specification | Hosting Capability Classification | Fallback / Mitigation Strategy |
| :--- | :--- | :--- | :--- |
| **PHP Runtime (Initial)** | PHP >= 8.2 (8.2–8.5) with extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`/`imagick`, `curl`, `xml`, `ctype` | `SHARED_HOSTING_FRIENDLY` | Select PHP 8.2 (or PHP 8.3 if available) via cPanel MultiPHP Manager. Ensure `fileinfo` and `gd` are activated in PHP Extension Selector. |
| **PHP Runtime (Future)** | PHP >= 8.3 for planned Laravel 13 upgrade | `POST_FOUNDATION_MILESTONE` | Verify provider offers PHP 8.3+ toggle before February 2027. |
| **Web Server Document Root** | Web root pointed directly to `/public` directory | `SHARED_HOSTING_FRIENDLY` | In cPanel: Configure addon domain or primary domain document root to point to `sisurat/public`. If root is locked to `public_html`, use root-level `.htaccess` rewrite into `public/`. |
| **Database Engine** | MySQL 8.0+ or MariaDB 10.5+ with InnoDB support | `SHARED_HOSTING_FRIENDLY` | Standard shared cPanel database provisioning. Schema adheres strictly to cross-compatible SQL syntax. |
| **Queue Worker** | Asynchronous job execution | `REQUIRES_FALLBACK_DESIGN` | Supervisor is unavailable on shared hosting. Default `QUEUE_CONNECTION=sync` for initial release. If async background tasks are required, execute via cPanel cron: `php artisan queue:work --stop-when-empty --max-time=50`. |
| **Task Scheduler** | Periodic maintenance (cleaning temp files, pruning logs) | `SHARED_HOSTING_FRIENDLY` | Add standard cPanel Cron Job entry: `* * * * * /usr/local/bin/php /home/user/sisurat/artisan schedule:run >> /dev/null 2>&1`. |
| **Symlink Creation (`storage:link`)**| Expose public assets | `SHARED_HOSTING_FRIENDLY` | In shared hosting, `symlink()` is frequently disabled for security. Because our file architecture uses **private authenticated streaming** through controller routes, `storage:link` is **NOT required** for document attachments. |
| **Cache & Session Drivers** | In-memory key-value stores (Redis / Memcached) | `AVOID_IN_INITIAL_RELEASE` | Shared hosting lacks private Redis daemons. Default `CACHE_STORE=file` (or `database`) and `SESSION_DRIVER=database` (or `file`). |
| **File Upload & Memory Limits** | 10 MB document uploads, PDF parsing | `REQUIRES_PROVIDER_VERIFICATION` | Verify or configure `php.ini` directives in cPanel: `upload_max_filesize = 25M`, `post_max_size = 30M`, `memory_limit = 256M`, `max_execution_time = 120`. |
| **SSH / Composer Access** | Dependency installation and migrations | `REQUIRES_FALLBACK_DESIGN` | If SSH is unavailable on client shared hosting, packaging workflow must produce a pre-built release artifact containing `/vendor` and pre-compiled frontend assets, with a web-based migration runner or database SQL import script. |
| **Real-time WebSockets** | Pusher / Laravel Reverb | `AVOID_IN_INITIAL_RELEASE` | Rely on standard client polling or request refresh for updates; avoid persistent WebSocket server requirements. |

---

## 3. Directory Layout for Shared Hosting

```text
/home/{cpanel_user}/
├── sisurat_app/                 <-- Placed OUTSIDE public web root
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── storage/
│   │   ├── app/private/        <-- Secure private documents
│   │   ├── framework/
│   │   └── logs/
│   ├── vendor/
│   ├── .env                    <-- Environment variables completely isolated
│   └── artisan
│
└── public_html/ (or public/)    <-- Only publicly accessible files
    ├── index.php               <-- Requires ../sisurat_app/public/index.php
    ├── assets/
    ├── robots.txt
    └── .htaccess
```

---

## 4. Web Server `.htaccess` Security Directives

If deployed on shared Apache / LiteSpeed hosting, the root `.htaccess` must enforce:
1. Routing all requests through `public/index.php`.
2. Disallowing directory browsing (`Options -Indexes`).
3. Blocking direct HTTP access to `.env`, `composer.json`, and hidden dotfiles.
4. Enforcing HTTPS redirect if SSL is active.
