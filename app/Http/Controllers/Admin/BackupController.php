<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupRun;
use App\Services\AuditLogger;
use App\Services\Backup\BackupOrchestrator;
use App\Services\Backup\BackupPreflightService;
use App\Services\Backup\BackupRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /**
     * Display the backup and disaster recovery dashboard.
     */
    public function index(Request $request, BackupPreflightService $preflight)
    {
        abort_unless($request->user()?->role === 'super_admin', 403, 'Akses terbatas untuk Super Admin.');

        // 1. Evaluate storage metrics
        $freeBytes = @disk_free_space(storage_path()) ?: 0;
        $totalBytes = @disk_total_space(storage_path()) ?: 1;
        $usedBytes = max(0, $totalBytes - $freeBytes);
        $usedPercent = round(($usedBytes / $totalBytes) * 100, 1);

        $diskCritical = (float) config('backup.thresholds.disk_critical_percent', 90.0);
        $diskWarning = (float) config('backup.thresholds.disk_warning_percent', 75.0);

        $diskStatus = 'normal';
        if ($usedPercent >= $diskCritical || $freeBytes < config('backup.thresholds.min_free_bytes', 104857600)) {
            $diskStatus = 'critical';
        } elseif ($usedPercent >= $diskWarning) {
            $diskStatus = 'warning';
        }

        $storageMetrics = [
            'total_bytes' => $totalBytes,
            'total_formatted' => round($totalBytes / (1024 * 1024 * 1024), 2).' GB',
            'used_bytes' => $usedBytes,
            'used_formatted' => round($usedBytes / (1024 * 1024 * 1024), 2).' GB',
            'free_bytes' => $freeBytes,
            'free_formatted' => round($freeBytes / (1024 * 1024 * 1024), 2).' GB',
            'used_percent' => $usedPercent,
            'status' => $diskStatus,
        ];

        // 2. Off-site Google Drive metrics
        $remoteConfig = [
            'enabled' => (bool) config('backup.remote.enabled', false),
            'provider' => config('backup.remote.provider', 'google_drive'),
            'folder_id' => config('backup.remote.google_drive.folder_id'),
            'has_client_id' => ! empty(config('backup.remote.google_drive.client_id')),
            'has_refresh_token' => ! empty(config('backup.remote.google_drive.refresh_token')),
        ];

        $lastRemoteRun = BackupRun::query()
            ->where('status', 'completed')
            ->whereNotNull('remote_file_id')
            ->latest('started_at')
            ->first();

        // 3. Local archives inventory
        $backupsDir = storage_path('app/private/backups');
        $localFiles = File::exists($backupsDir) ? File::files($backupsDir) : [];
        $localCount = count($localFiles);
        $localTotalBytes = array_sum(array_map(fn ($f) => $f->getSize(), $localFiles));

        // 4. Aggregate stats
        $stats = [
            'total_completed' => BackupRun::query()->where('status', 'completed')->count(),
            'total_failed' => BackupRun::query()->where('status', 'failed')->count(),
            'last_backup' => BackupRun::query()->where('status', 'completed')->latest('started_at')->first(),
            'latest_failed' => BackupRun::query()->where('status', 'failed')->latest('started_at')->first(),
            'local_archives_count' => $localCount,
            'local_archives_bytes' => $localTotalBytes,
            'local_archives_formatted' => round($localTotalBytes / (1024 * 1024), 2).' MB',
        ];

        // 5. Paginated backup runs
        $backups = BackupRun::query()
            ->with('requestedBy')
            ->orderByDesc('started_at')
            ->paginate(15)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'storage' => $storageMetrics,
                'remote' => $remoteConfig,
                'stats' => $stats,
                'backups' => $backups,
            ]);
        }

        return view('admin.backups.index', [
            'storageMetrics' => $storageMetrics,
            'remoteConfig' => $remoteConfig,
            'lastRemoteRun' => $lastRemoteRun,
            'stats' => $stats,
            'backups' => $backups,
        ]);
    }

    /**
     * Trigger an on-demand manual backup cycle.
     */
    public function store(Request $request, BackupOrchestrator $orchestrator, AuditLogger $auditLogger)
    {
        abort_unless($request->user()?->role === 'super_admin', 403, 'Akses terbatas untuk Super Admin.');

        $validated = $request->validate([
            'type' => ['sometimes', 'string', 'in:full,database,files'],
            'upload' => ['sometimes', 'boolean'],
        ]);

        $type = $validated['type'] ?? 'full';
        $upload = array_key_exists('upload', $validated)
            ? (bool) $validated['upload']
            : (bool) config('backup.remote.enabled', false);

        try {
            $run = $orchestrator->run([
                'type' => $type,
                'upload' => $upload,
                'trigger' => 'manual',
                'requested_by' => $request->user()->id,
            ]);

            $auditLogger->userEvent(
                'BACKUP_MANUAL_TRIGGERED',
                $request->user(),
                $request->user(),
                "Memicu pencadangan manual tipe {$type} (ID: {$run->backup_id})"
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'backup_id' => $run->backup_id,
                    'type' => $run->type,
                    'trigger_type' => $run->trigger,
                    'status' => $run->status,
                    'archive_size_bytes' => $run->archive_size_bytes,
                ], 201);
            }

            $sizeFormatted = $run->archive_size_bytes ? round($run->archive_size_bytes / (1024 * 1024), 2).' MB' : '0 MB';

            return redirect()
                ->route('admin.backups.index')
                ->with('success', "Pencadangan manual berhasil diselesaikan. ID: {$run->backup_id} ({$sizeFormatted}).");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return redirect()
                ->route('admin.backups.index')
                ->with('error', "Gagal menjalankan pencadangan manual: {$e->getMessage()}");
        }
    }

    /**
     * Authenticated streaming gateway for encrypted backup archives.
     */
    public function download(Request $request, string $backupId, AuditLogger $auditLogger): BinaryFileResponse
    {
        abort_unless($request->user()?->role === 'super_admin', 403, 'Akses terbatas untuk Super Admin.');

        abort_unless(
            preg_match('/^bkp-\d{8}-\d{6}$/', $backupId),
            400,
            'Format ID pencadangan tidak valid.'
        );

        $run = BackupRun::query()->where('backup_id', $backupId)->firstOrFail();

        $localPath = storage_path("app/private/backups/{$backupId}.enc");

        abort_unless(
            File::exists($localPath),
            404,
            "File arsip lokal ({$backupId}.enc) tidak ditemukan atau telah dibersihkan oleh siklus retensi."
        );

        $auditLogger->userEvent(
            'BACKUP_ARCHIVE_DOWNLOADED',
            $request->user(),
            $request->user(),
            "Mengunduh arsip cadangan terenkripsi {$backupId}.enc"
        );

        return response()->download($localPath, "{$backupId}.enc", [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$backupId}.enc\"",
        ]);
    }

    /**
     * Trigger manual rolling retention pruning.
     */
    public function prune(Request $request, BackupRetentionService $retentionService, AuditLogger $auditLogger)
    {
        abort_unless($request->user()?->role === 'super_admin', 403, 'Akses terbatas untuk Super Admin.');

        $dryRun = $request->boolean('dry_run', false);
        $result = $retentionService->prune($dryRun);

        $auditLogger->userEvent(
            'BACKUP_RETENTION_PRUNED',
            $request->user(),
            $request->user(),
            "Menjalankan pembersihan retensi: {$result['pruned_count']} arsip dihapus."
        );

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['pruned_count'] === 0) {
            $msg = 'Tidak ada arsip yang dibersihkan. '.($result['reason'] ?? 'Seluruh cadangan berada dalam kuota retensi.');

            return redirect()->route('admin.backups.index')->with('info', $msg);
        }

        $mbReclaimed = round($result['reclaimed_bytes'] / (1024 * 1024), 2);
        $msg = "Pembersihan retensi selesai: {$result['pruned_count']} arsip lama berhasil dihapus ({$mbReclaimed} MB ruang disk dibebaskan).";

        return redirect()->route('admin.backups.index')->with('success', $msg);
    }
}
