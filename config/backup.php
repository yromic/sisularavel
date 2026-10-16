<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup & Disaster Recovery Configuration (SISURAT)
    |--------------------------------------------------------------------------
    |
    | Governed by docs/backup.md and AGENTS.md.
    | Designed for shared-hosting (cPanel) compatibility on PHP ^8.2.
    |
    */

    // Path to mysqldump binary (null = auto-detect in PATH and common paths)
    'mysqldump_path' => env('MYSQL_DUMP_PATH', null),

    // Encryption settings
    'encryption' => [
        'cipher' => env('BACKUP_CIPHER', 'aes-256-gcm'),
        'key_id' => env('BACKUP_KEY_ID', 'sisurat-backup-master-2026'),
    ],

    // Storage capacity thresholds (Section 10.4 of docs/backup.md)
    'thresholds' => [
        // Disk usage warning threshold (in percent, e.g. 75%)
        'disk_warning_percent' => (float) env('BACKUP_DISK_WARN_PERCENT', 75.0),

        // Disk usage critical threshold (in percent, e.g. 90% - auto-abort backup)
        'disk_critical_percent' => (float) env('BACKUP_DISK_CRIT_PERCENT', 90.0),

        // Minimum free bytes required before initiating backup (default: 100 MB)
        'min_free_bytes' => (int) env('BACKUP_MIN_FREE_BYTES', 100 * 1024 * 1024),

        // Memory limit minimum recommendation (in megabytes)
        'min_memory_mb' => (int) env('BACKUP_MIN_MEMORY_MB', 128),
    ],

    // Disks to include in file snapshot
    'source_disks' => [
        'documents' => 'private_documents',
        'signatures' => 'private_signatures',
    ],

    // Working directory for staging backups before packaging & encryption
    'temp_path' => storage_path('app/private/backup-temp'),

    // Remote offsite storage
    'remote' => [
        'enabled' => env('BACKUP_REMOTE_ENABLED', false),
        'provider' => env('BACKUP_REMOTE_PROVIDER', 'google_drive'),
        'google_drive' => [
            'client_id' => env('GOOGLE_DRIVE_CLIENT_ID', null),
            'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET', null),
            'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN', null),
            'folder_id' => env('GOOGLE_DRIVE_BACKUP_FOLDER_ID', null),
            // Upload chunk size in bytes (must be multiple of 256KB = 262144 bytes per Google API)
            'chunk_size_bytes' => (int) env('GOOGLE_DRIVE_CHUNK_SIZE', 1024 * 1024), // 1MB default
            'max_retries' => (int) env('GOOGLE_DRIVE_MAX_RETRIES', 3),
            'retry_base_ms' => (int) env('GOOGLE_DRIVE_RETRY_BASE_MS', 100), // ms between retries
        ],
    ],

    // Recovery & extraction settings (Section 11 of docs/backup.md)
    'recovery' => [
        'temp_path' => storage_path('app/private/recovery-temp'),
    ],

    // Rolling retention policy (Section 10.3 of docs/backup.md)
    'retention' => [
        'keep_daily_days' => (int) env('BACKUP_RETENTION_DAILY_DAYS', 7),
        'keep_weekly_weeks' => (int) env('BACKUP_RETENTION_WEEKLY_WEEKS', 4),
        'keep_monthly_months' => (int) env('BACKUP_RETENTION_MONTHLY_MONTHS', 12),
        'delete_remote_on_prune' => (bool) env('BACKUP_PRUNE_REMOTE', true),
    ],
];
