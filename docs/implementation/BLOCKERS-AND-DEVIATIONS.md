# Blockers and Deviations

## BLOCKER-ENV-001

**Affected phase:** Phase 0 - Contract Intake and Environment Verification

**Affected requirement IDs:** `SYS-001`, `DEC-TECH-002`, `FILE-001`, `FILE-002`

**Evidence:**

```text
php -v => PHP 8.2.12 (cli), C:\xampp\php\php.exe
composer --version => Composer 2.9.5 using PHP 8.2.12
php -m => pdo_mysql, mbstring, openssl, fileinfo, curl, xml, ctype present
php -m => gd absent; imagick absent
```

**Why implementation cannot safely continue:**

`AGENTS.md` and `docs/21-SHARED-HOSTING-CONSTRAINTS.md` require `gd` or `imagick` as a mandatory PHP extension. SISURAT must process uploaded images and public piagam signature PNGs, so continuing into domain implementation without an image extension would violate the environment preflight gate.

**Available options:**

- Enable `extension=gd` in the active XAMPP PHP runtime and restart the shell/web server.
- Install and enable `imagick` for the active PHP 8.2 runtime.

**Recommended option:**

Enable `gd` in XAMPP PHP 8.2, then rerun `php -m` and `php artisan test`.

**Safe work that can continue independently:**

Contract intake documentation and implementation planning were completed. Application feature implementation should wait until the extension gate passes.

## Deviations

| Deviation | Status | Justification |
| --- | --- | --- |
| Contract directory is rooted at repository root, not `sisurat-laravel-contract/` | DOCUMENTED | Root files match v1.3.0 contract pack and are authoritative in this checkout. |
