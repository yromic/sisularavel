<x-layouts.app title="Backup & Disaster Recovery">
    <section class="px-4 py-4 md:px-8">
        <!-- Header Page (Standar SISURAT) -->
        <x-page-header
            badge="Sistem & Ketahanan Data"
            badge-icon="shield-check"
            meta="Kewenangan: Super Admin"
            title="Backup & Disaster Recovery"
            subtitle="Pemantauan snapshot berkala, replikasi off-site cloud, dan pemulihan bencana sistem."
        >
            <x-slot:actions>
                <button type="button" 
                        onclick="document.getElementById('modal-prune-retensi').classList.toggle('hidden')" 
                        class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="trash" class="h-4 w-4 text-slate-500" />
                    <span>Pruning Retensi</span>
                </button>
                <button type="button" 
                        onclick="document.getElementById('modal-backup-manual').classList.toggle('hidden')" 
                        class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>+ Backup Manual Sekarang</span>
                </button>
            </x-slot:actions>
        </x-page-header>

        <!-- Flash Notifications -->
        @if (session('success'))
            <div class="mb-5 flex items-start gap-3 rounded-lg border border-teal-200 bg-teal-50/80 p-4 text-sm font-medium text-teal-900 shadow-xs">
                <x-icon name="check" class="h-5 w-5 shrink-0 text-teal-600 mt-0.5" />
                <div class="flex-1">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('info'))
            <div class="mb-5 flex items-start gap-3 rounded-lg border border-sky-200 bg-sky-50/80 p-4 text-sm font-medium text-sky-900 shadow-xs">
                <x-icon name="clock" class="h-5 w-5 shrink-0 text-sky-600 mt-0.5" />
                <div class="flex-1">{{ session('info') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50/80 p-4 text-sm font-medium text-rose-900 shadow-xs">
                <x-icon name="alert-triangle" class="h-5 w-5 shrink-0 text-rose-600 mt-0.5" />
                <div class="flex-1">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800 shadow-xs">
                <div class="font-bold mb-1 flex items-center gap-1.5">
                    <x-icon name="alert-triangle" class="h-4 w-4 text-rose-600" />
                    <span>Validasi Permintaan Gagal:</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- HERO CARD: ARSIP & CADANGAN RAMAH DINAS (SPREADSHEET-FIRST & GOOGLE DRIVE) -->
        <div class="mb-6 rounded-2xl border-2 border-emerald-200 bg-gradient-to-br from-emerald-50/90 via-teal-50/50 to-white p-5 md:p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 border border-emerald-300 mb-2">
                        <x-icon name="check" class="h-3.5 w-3.5 text-emerald-600" />
                        <span>Format Ramah Pengguna (Human-Readable Archive)</span>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 md:text-xl">
                        Cadangan Data Dinas (Spreadsheet Excel & Google Drive)
                    </h2>
                    <p class="text-xs text-slate-600 md:text-sm mt-1">
                        Unduh salinan lengkap data persuratan dan buku ekspedisi piagam dalam berkas Excel (.xls). Setiap baris dilengkapi <strong class="text-slate-800">tautan langsung Google Drive</strong> yang dapat langsung diklik untuk melihat atau mencetak berkas asli tanpa hambatan izin akses.
                    </p>
                    
                    <!-- Sync status pill & counter -->
                    <div class="mt-3.5 flex flex-wrap items-center gap-2.5 text-xs">
                        <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700 bg-white/90 border border-slate-200 rounded-lg px-2.5 py-1">
                            <x-icon name="cloud" class="h-4 w-4 text-sky-600" />
                            <span>Google Drive: <strong>{{ $remoteConfig['has_refresh_token'] ? 'Terhubung ke Akun Dinas' : 'Belum Terhubung' }}</strong></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700 bg-white/90 border border-slate-200 rounded-lg px-2.5 py-1">
                            <span>Berkas di Cloud: <strong>{{ $syncStats['synced_files'] ?? 0 }} / {{ $syncStats['total_files'] ?? 0 }} berkas ({{ $syncStats['percentage'] ?? 0 }}%)</strong></span>
                        </span>
                        @if (($syncStats['pending_files'] ?? 0) === 0 && ($syncStats['total_files'] ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-200">
                                <x-icon name="check" class="h-3 w-3" />
                                100% Tersinkron
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row lg:flex-col xl:flex-row gap-2.5 shrink-0">
                    <!-- Tombol Unduh Excel Utama -->
                    <a href="{{ route('admin.backups.spreadsheet') }}" 
                       class="inline-flex min-h-[48px] items-center justify-center gap-2.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs md:text-sm font-bold text-white shadow-md hover:bg-emerald-700 hover:shadow-lg transition-all focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-emerald-600">
                        <x-icon name="download" class="h-5 w-5 text-emerald-100 shrink-0" />
                        <span class="text-left">
                            <span>Unduh Rekap Excel (.xls)</span>
                            <span class="block text-[10px] font-normal text-emerald-100">Lengkap Link Google Drive</span>
                        </span>
                    </a>

                    <!-- Tombol Buka Folder Google Drive -->
                    @if ($driveFolderUrl)
                        <a href="{{ $driveFolderUrl }}" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl border border-sky-300 bg-white px-4 py-2.5 text-xs font-bold text-sky-800 shadow-xs hover:bg-sky-50 transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-sky-600">
                            <x-icon name="external-link" class="h-4 w-4 text-sky-600 shrink-0" />
                            <span class="text-left">
                                <span>Buka Google Drive</span>
                                <span class="block text-[10px] font-normal text-sky-600">Lihat File & TTD Asli</span>
                            </span>
                        </a>
                    @endif

                    <!-- Tombol Sinkronisasi Manual jika ada berkas tertunda -->
                    @if (($syncStats['pending_files'] ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.backups.sync-drive') }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="w-full inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-800 shadow-xs hover:bg-amber-100 transition-colors">
                                <x-icon name="refresh-cw" class="h-4 w-4 text-amber-600 shrink-0" />
                                <span class="text-left">
                                    <span>Sinkronkan ke Cloud</span>
                                    <span class="block text-[10px] font-normal text-amber-600">({{ $syncStats['pending_files'] }} tertunda)</span>
                                </span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Metrics Overview Grid -->
        <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <!-- 1. Storage Hosting SSD -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                            <x-icon name="hard-drive" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Kapasitas SSD Hosting</h2>
                            <p class="text-sm font-bold text-slate-900">{{ $storageMetrics['used_formatted'] }} / {{ $storageMetrics['total_formatted'] }}</p>
                        </div>
                    </div>
                    @if ($storageMetrics['status'] === 'critical')
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800 border border-rose-300">
                            Kritis (Auto-Abort)
                        </span>
                    @elseif ($storageMetrics['status'] === 'warning')
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800 border border-amber-300">
                            Waspada ({{ $storageMetrics['used_percent'] }}%)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-300">
                            Normal ({{ $storageMetrics['used_percent'] }}%)
                        </span>
                    @endif
                </div>

                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-2.5 rounded-full {{ $storageMetrics['status'] === 'critical' ? 'bg-rose-500' : ($storageMetrics['status'] === 'warning' ? 'bg-amber-500' : 'bg-teal-600') }}" 
                             style="width: {{ min(100, $storageMetrics['used_percent']) }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                        <span>Ruang Kosong: <strong class="text-slate-700">{{ $storageMetrics['free_formatted'] }}</strong></span>
                        <span>Batas Aman: &lt;75%</span>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Arsip Lokal Tersimpan:</span>
                    <span class="font-semibold text-slate-800">{{ $stats['local_archives_count'] }} file ({{ $stats['local_archives_formatted'] }})</span>
                </div>
            </div>

            <!-- 2. Replikasi Off-site Cloud (Google Drive) -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                            <x-icon name="cloud" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Replikasi Off-site Cloud</h2>
                            <p class="text-sm font-bold text-slate-900">Google Drive Instansi</p>
                        </div>
                    </div>
                    @if ($remoteConfig['enabled'] && $remoteConfig['has_refresh_token'])
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-300">
                            Aktif & Terhubung
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700 border border-slate-300">
                            Lokal Saja
                        </span>
                    @endif
                </div>

                <div class="mt-4 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Target Folder ID:</span>
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-800 font-mono">
                            {{ $remoteConfig['folder_id'] ? Str::limit($remoteConfig['folder_id'], 18) : '(Belum Disetel)' }}
                        </code>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Status OAuth Token:</span>
                        <span class="font-medium {{ $remoteConfig['has_refresh_token'] ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ $remoteConfig['has_refresh_token'] ? 'Terkonfigurasi' : 'Belum Ada Token' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Sinkronisasi Terakhir:</span>
                        <span class="font-medium text-slate-800">
                            {{ $lastRemoteRun ? $lastRemoteRun->started_at->format('d/m/Y H:i') : '-' }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Enkripsi Payload:</span>
                    <span class="font-semibold text-emerald-700">AES-256-GCM (Terautentikasi)</span>
                </div>
            </div>

            <!-- 3. Jadwal & Kebijakan Retensi GFS -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                            <x-icon name="database" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Kebijakan Retensi GFS</h2>
                            <p class="text-sm font-bold text-slate-900">Grandfather-Father-Son</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-[11px] font-bold text-teal-800 border border-teal-300">
                        Golden Rule Aktif
                    </span>
                </div>

                <div class="mt-4 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Penyimpanan Harian (Daily):</span>
                        <strong class="text-slate-800">7 Hari Terakhir</strong>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Penyimpanan Mingguan (Weekly):</span>
                        <strong class="text-slate-800">4 Titik Akhir Pekan</strong>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Penyimpanan Bulanan (Monthly):</span>
                        <strong class="text-slate-800">12 Titik Akhir Bulan</strong>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Jadwal Otomatis:</span>
                    <span class="font-semibold text-slate-800">02:00 WIB (Backup) · 03:00 WIB (Prune)</span>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Pencadangan -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Pencadangan Teknis (Disaster Recovery Safeguard)</h2>
                    <p class="text-xs text-slate-500">Daftar arsip cadangan biner terenkripsi AES-256-GCM dan SQL dump untuk pemulihan darurat sistem oleh developer.</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span>Total Selesai: <strong class="text-emerald-700 font-bold">{{ $stats['total_completed'] }}</strong></span>
                    <span class="text-slate-300">|</span>
                    <span>Gagal: <strong class="{{ $stats['total_failed'] > 0 ? 'text-rose-700 font-bold' : 'text-slate-500' }}">{{ $stats['total_failed'] }}</strong></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                            <th scope="col" class="py-3 px-4">ID Pencadangan</th>
                            <th scope="col" class="py-3 px-4">Tipe & Pemicu</th>
                            <th scope="col" class="py-3 px-4">Waktu & Durasi</th>
                            <th scope="col" class="py-3 px-4">Ukuran Arsip</th>
                            <th scope="col" class="py-3 px-4">Google Drive</th>
                            <th scope="col" class="py-3 px-4">Status</th>
                            <th scope="col" class="py-3 px-4 text-center">Aksi Unduh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($backups as $run)
                            @php
                                $localExists = \Illuminate\Support\Facades\File::exists(storage_path("app/private/backups/{$run->backup_id}.enc"));
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- ID Pencadangan -->
                                <td class="py-3 px-4">
                                    <div class="font-mono text-xs font-bold text-slate-900">{{ $run->backup_id }}</div>
                                    @if ($run->sha256_checksum)
                                        <div class="text-[10px] text-slate-400 font-mono" title="{{ $run->sha256_checksum }}">
                                            SHA: {{ Str::limit($run->sha256_checksum, 12) }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Tipe & Pemicu -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $run->type === 'full' ? 'bg-teal-100 text-teal-800' : ($run->type === 'database' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                                            {{ $run->type }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            {{ $run->trigger_type === 'scheduled' ? 'Otomatis' : 'Manual' }}
                                        </span>
                                    </div>
                                    @if ($run->requestedBy)
                                        <div class="text-[11px] text-slate-400 mt-0.5">Oleh: {{ $run->requestedBy->name ?? $run->requestedBy->username }}</div>
                                    @endif
                                </td>

                                <!-- Waktu & Durasi -->
                                <td class="py-3 px-4">
                                    <div class="text-xs font-semibold text-slate-800">
                                        {{ $run->started_at ? $run->started_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        Durasi: {{ $run->duration_seconds !== null ? $run->duration_seconds . ' detik' : '-' }}
                                    </div>
                                </td>

                                <!-- Ukuran Arsip -->
                                <td class="py-3 px-4">
                                    <div class="text-xs font-bold text-slate-900">
                                        {{ $run->archive_size_bytes ? round($run->archive_size_bytes / (1024 * 1024), 2) . ' MB' : '-' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        DB: {{ $run->database_size_bytes ? round($run->database_size_bytes / 1024, 1) . ' KB' : '0' }} ·
                                        Files: {{ $run->files_size_bytes ? round($run->files_size_bytes / 1024, 1) . ' KB' : '0' }}
                                    </div>
                                </td>

                                <!-- Status Google Drive -->
                                <td class="py-3 px-4">
                                    @if ($run->remote_file_id)
                                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">
                                            <x-icon name="cloud" class="h-3 w-3 text-emerald-600" />
                                            Tersinkron
                                        </span>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5" title="{{ $run->remote_file_id }}">
                                            ID: {{ Str::limit($run->remote_file_id, 10) }}
                                        </div>
                                    @elseif ($run->status === 'completed')
                                        <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                            Lokal Saja
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Status Siklus -->
                                <td class="py-3 px-4">
                                    @if ($run->status === 'completed')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800">
                                            <x-icon name="check" class="h-3 w-3 text-emerald-600" />
                                            Selesai
                                        </span>
                                    @elseif ($run->status === 'failed')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800" title="{{ $run->error_message }}">
                                            <x-icon name="alert-triangle" class="h-3 w-3 text-rose-600" />
                                            Gagal
                                        </span>
                                        @if ($run->error_message)
                                            <div class="text-[10px] text-rose-600 mt-0.5 max-w-[180px] truncate" title="{{ $run->error_message }}">
                                                {{ $run->error_message }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-0.5 text-[11px] font-bold text-sky-800">
                                            <x-icon name="loader" class="h-3 w-3 text-sky-600 animate-spin" />
                                            {{ ucfirst($run->status) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi Unduh -->
                                <td class="py-3 px-4 text-center">
                                    @if ($run->status === 'completed' && $localExists)
                                        <a href="/admin/backups/{{ $run->backup_id }}/download" 
                                           class="inline-flex min-h-[36px] items-center gap-1.5 rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 border border-teal-200 hover:bg-teal-100 hover:text-teal-800 transition-colors"
                                           title="Unduh arsip terenkripsi {{ $run->backup_id }}.enc">
                                            <x-icon name="download" class="h-3.5 w-3.5" />
                                            <span>Unduh .enc</span>
                                        </a>
                                    @elseif ($run->status === 'completed')
                                        <span class="inline-flex items-center rounded bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-500" title="File lokal telah dibersihkan oleh siklus retensi">
                                            Dipangkas (Cloud)
                                        </span>
                                    @else
                                        <span class="text-slate-300 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <x-icon name="database" class="mx-auto h-10 w-10 text-slate-300 mb-2" />
                                    <p class="font-semibold text-slate-600 text-sm">Belum ada riwayat pencadangan data</p>
                                    <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Backup Manual Sekarang" untuk membuat titik pemulihan pertama.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            @if ($backups->hasPages())
                <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
                    {{ $backups->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Pemicu Backup Manual -->
        <div id="modal-backup-manual" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                            <x-icon name="database" class="h-4 w-4" />
                        </span>
                        <h2 class="text-base font-bold text-slate-900">Picu Pencadangan Manual</h2>
                    </div>
                    <button type="button" 
                            onclick="document.getElementById('modal-backup-manual').classList.add('hidden')"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <form method="post" action="/admin/backups" id="form-backup-manual">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Pencadangan *</label>
                            <select name="type" class="w-full min-h-[44px] rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-700 focus:outline-hidden">
                                <option value="full" selected>Full Backup (Database + Dokumen Surat & Tanda Tangan) [Disarankan]</option>
                                <option value="database">Database SQL Sahaja</option>
                                <option value="files">Berkas Dokumen & Tanda Tangan Sahaja</option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">Full Backup mencakup seluruh relasi, skema, dokumen surat masuk/keluar, dan tanda tangan digital piagam.</p>
                        </div>

                        @if ($remoteConfig['enabled'])
                            <div class="rounded-lg border border-sky-200 bg-sky-50/50 p-3">
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="upload" value="1" checked class="mt-0.5 rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                                    <span class="text-xs text-slate-700 leading-relaxed">
                                        <strong>Unggah ke Google Drive Instansi:</strong> Secara otomatis kirimkan salinan arsip terenkripsi ke Google Drive setelah proses pencadangan lokal selesai.
                                    </span>
                                </label>
                            </div>
                        @else
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                                <em>Google Drive off-site belum diaktifkan pada konfigurasi sistem. Backup akan disimpan di direktori privat server lokal.</em>
                            </div>
                        @endif

                        <div class="rounded-lg border border-teal-200 bg-teal-50/50 p-3 text-xs text-teal-900 leading-relaxed">
                            <strong class="font-bold">Keamanan & Integritas:</strong> Arsip otomatis dienkripsi dengan standar AES-256-GCM dan diverifikasi oleh hash SHA-256. Kunci dekripsi tidak pernah disimpan di dalam file arsip cadangan.
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-900 leading-relaxed flex items-start gap-2">
                            <x-icon name="alert-triangle" class="h-4 w-4 text-amber-700 shrink-0 mt-0.5" />
                            <div>
                                <strong class="font-bold">Catatan Lingkungan Shared Hosting:</strong> Pencadangan manual via web berjalan sinkron dan dapat memerlukan 15–30 detik. Untuk menghindari batas waktu <em>Gateway Timeout (504)</em> pada arsip besar, disarankan menggunakan cron terjadwal pukul 02:00 WIB atau jalankan langsung via Terminal cPanel:
                                <code class="mt-1 block rounded bg-white px-2 py-0.5 font-mono text-[11px] text-amber-950 border border-amber-300">php artisan sisurat:backup --type=full --upload</code>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" 
                                onclick="document.getElementById('modal-backup-manual').classList.add('hidden')"
                                class="min-h-[44px] rounded-lg border border-slate-300 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                id="btn-submit-backup"
                                onclick="this.disabled=true; this.innerText='Memproses Pencadangan...'; document.getElementById('form-backup-manual').submit();"
                                class="min-h-[44px] rounded-lg bg-teal-700 px-5 text-xs font-semibold text-white hover:bg-teal-800 transition-colors shadow-xs">
                            Mulai Pencadangan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Pruning Retensi -->
        <div id="modal-prune-retensi" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                            <x-icon name="trash" class="h-4 w-4" />
                        </span>
                        <h2 class="text-base font-bold text-slate-900">Pembersihan Retensi (Pruning)</h2>
                    </div>
                    <button type="button" 
                            onclick="document.getElementById('modal-prune-retensi').classList.add('hidden')"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <form method="post" action="/admin/backups/prune">
                    @csrf
                    <div class="space-y-4 text-xs text-slate-600 leading-relaxed">
                        <p>
                            Pembersihan retensi menerapkan rotasi <strong>Grandfather-Father-Son (GFS)</strong>:
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-slate-700">
                            <li>Mempertahankan cadangan harian selama <strong>7 hari</strong> terakhir.</li>
                            <li>Mempertahankan titik akhir pekan selama <strong>4 minggu</strong> terakhir.</li>
                            <li>Mempertahankan titik akhir bulan selama <strong>12 bulan</strong> terakhir.</li>
                        </ul>

                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-emerald-900">
                            <strong class="font-bold">Golden Rule Safeguard:</strong> Sistem menolak menghapus arsip apapun jika total backup tersimpan $\le 1$ atau jika pencadangan terakhir mengalami kegagalan.
                        </div>

                        <div class="pt-2">
                            <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-800">
                                <input type="checkbox" name="dry_run" value="1" class="rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                                <span>Simulasi Sahaja (Dry-Run: periksa tanpa menghapus file fisik)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" 
                                onclick="document.getElementById('modal-prune-retensi').classList.add('hidden')"
                                class="min-h-[44px] rounded-lg border border-slate-300 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                class="min-h-[44px] rounded-lg bg-slate-800 px-5 text-xs font-semibold text-white hover:bg-slate-900 transition-colors shadow-xs">
                            Jalankan Pembersihan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts.app>
