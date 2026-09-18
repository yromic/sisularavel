# Technical Decisions

| Decision | Status | Rationale | Date |
| --- | --- | --- | --- |
| Treat root `docs/`, `contracts/`, `examples/`, and `reviews/` as the contract pack | ACTIVE | The repository contains the v1.3.0 contract files at root rather than under `sisurat-laravel-contract/`; README and AGENTS identify the same package content and authority. | 2026-09-18 |
| Use server-rendered Blade + Tailwind CSS + minimal Alpine.js | PLANNED | Matches contract preference for shared-hosting-friendly standard web application; avoids SPA/runtime production dependency. | 2026-09-18 |
| Use PHPUnit first because Laravel skeleton is currently PHPUnit-based | PLANNED | Existing baseline uses PHPUnit. Pest can be added later only if it provides clear value without disrupting shared-hosting goals. | 2026-09-18 |
| Keep `QUEUE_CONNECTION=sync` for initial shared-hosting compatibility | PLANNED | Shared hosting may lack daemon workers; cron-compatible commands are acceptable for maintenance tasks. | 2026-09-18 |
