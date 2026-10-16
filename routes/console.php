<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Console Commands (Fase 05 Automation & Maintenance)
|--------------------------------------------------------------------------
|
| Governed by docs/backup.md Section 10.2 & 10.3.
| Scheduled jobs run via cPanel cron job executing `php artisan schedule:run`.
|
*/

// 1. Daily atomic full backup with encrypted Google Drive offsite upload at 02:00 WIB
Schedule::command('sisurat:backup --type=full --upload --trigger=scheduled')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// 2. Daily rolling retention pruning (Grandfather-Father-Son rotation) at 03:00 WIB
Schedule::command('sisurat:backup:prune')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// 3. Hourly maintenance pruning of expired idempotency keys
Schedule::command('sisurat:prune-idempotency')
    ->hourly();
