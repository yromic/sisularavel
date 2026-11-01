<x-layouts.app title="Cadangan Data & Pemulihan">
    <section class="px-4 py-4 md:px-8">
        <!-- Header Page (Standar SISURAT) -->
        <x-page-header
            badge="Keamanan Data"
            badge-icon="shield-check"
            meta="Kewenangan: Super Admin"
            title="Cadangan Data & Pemulihan"
            subtitle="Simpan salinan data surat agar tetap aman jika terjadi kerusakan atau kehilangan data."
        >
            <x-slot:actions>
                <button type="button" 
                        onclick="document.getElementById('modal-prune-retensi').classList.toggle('hidden')" 
                        class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="trash" class="h-4 w-4 text-slate-500" />
                    <span>Bersihkan Cadangan Lama</span>
                </button>
                <button type="button" 
                        onclick="document.getElementById('modal-backup-manual').classList.toggle('hidden')" 
                        class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>Buat Cadangan Sekarang</span>
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
                    <span>Permintaan belum bisa diproses:</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- HERO CARD: Salinan data dalam Excel & Google Drive -->
        <div class="mb-6 rounded-2xl border-2 border-emerald-200 bg-gradient-to-br from-emerald-50/90 via-teal-50/50 to-white p-5 md:p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 border border-emerald-300 mb-2">
                        <x-icon name="check" class="h-3.5 w-3.5 text-emerald-600" />
                        <span>Mudah dibuka di Excel</span>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 md:text-xl">
                        Salinan Data Surat (Excel & Google Drive)
                    </h2>
                    <p class="text-xs text-slate-600 md:text-sm mt-1">
                        Unduh salinan lengkap data surat dalam bentuk Excel. Setiap baris dilengkapi <strong class="text-slate-800">tautan Google Drive</strong> yang bisa diklik untuk melihat atau mencetak berkas aslinya.
                    </p>
                    
                    <!-- Sync status pill & counter -->
                    <div class="mt-3.5 flex flex-wrap items-center gap-2.5 text-xs">
                        <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700 bg-white/90 border border-slate-200 rounded-lg px-2.5 py-1">
                            <x-icon name="cloud" class="h-4 w-4 text-sky-600" />
                            <span>Google Drive: <strong>{{ $remoteConfig['has_refresh_token'] ? 'Terhubung ke Akun Dinas' : 'Belum Terhubung' }}</strong></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700 bg-white/90 border border-slate-200 rounded-lg px-2.5 py-1">
                            <span>Berkas di Google Drive: <strong>{{ $syncStats['synced_files'] ?? 0 }} / {{ $syncStats['total_files'] ?? 0 }} berkas ({{ $syncStats['percentage'] ?? 0 }}%)</strong></span>
                        </span>
                        @if (($syncStats['pending_files'] ?? 0) === 0 && ($syncStats['total_files'] ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-200">
                                <x-icon name="check" class="h-3 w-3" />
                                Semua berkas sudah tersimpan
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
                            <span>Unduh Data ke Excel</span>
                            <span class="block text-[10px] font-normal text-emerald-100">Lengkap dengan tautan Google Drive</span>
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
                                <span class="block text-[10px] font-normal text-sky-600">Lihat berkas & tanda tangan asli</span>
                            </span>
                        </a>
                    @endif

                    <!-- Tombol simpan manual jika ada berkas yang belum tersimpan -->
                    @if (($syncStats['pending_files'] ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.backups.sync-drive') }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="w-full inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-800 shadow-xs hover:bg-amber-100 transition-colors">
                                <x-icon name="refresh-cw" class="h-4 w-4 text-amber-600 shrink-0" />
                                <span class="text-left">
                                    <span>Simpan ke Google Drive</span>
                                    <span class="block text-[10px] font-normal text-amber-600">({{ $syncStats['pending_files'] }} berkas belum tersimpan)</span>
                                </span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Ringkasan Kondisi -->
        <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <!-- 1. Ruang penyimpanan server -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                            <x-icon name="hard-drive" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Ruang Penyimpanan Server</h2>
                            <p class="text-sm font-bold text-slate-900">{{ $storageMetrics['used_formatted'] }} / {{ $storageMetrics['total_formatted'] }}</p>
                        </div>
                    </div>
                    @if ($storageMetrics['status'] === 'critical')
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800 border border-rose-300">
                            Hampir Penuh (cadangan dihentikan)
                        </span>
                    @elseif ($storageMetrics['status'] === 'warning')
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800 border border-amber-300">
                            Mulai Penuh ({{ $storageMetrics['used_percent'] }}%)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-300">
                            Aman ({{ $storageMetrics['used_percent'] }}%)
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
                        <span>Sisa Ruang: <strong class="text-slate-700">{{ $storageMetrics['free_formatted'] }}</strong></span>
                        <span>Aman bila terpakai kurang dari 75%</span>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Cadangan di server ini:</span>
                    <span class="font-semibold text-slate-800">{{ $stats['local_archives_count'] }} berkas ({{ $stats['local_archives_formatted'] }})</span>
                </div>
            </div>

            <!-- 2. Salinan di Google Drive -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                            <x-icon name="cloud" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Salinan di Google Drive</h2>
                            <p class="text-sm font-bold text-slate-900">Akun Google Drive Instansi</p>
                        </div>
                    </div>
                    @if ($remoteConfig['enabled'] && $remoteConfig['has_refresh_token'])
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-300">
                            Aktif & Terhubung
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700 border border-slate-300">
                            Hanya di Server
                        </span>
                    @endif
                </div>

                <div class="mt-4 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Folder Tujuan:</span>
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-800 font-mono">
                            {{ $remoteConfig['folder_id'] ? Str::limit($remoteConfig['folder_id'], 18) : '(Belum dipilih)' }}
                        </code>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Izin Akses Google:</span>
                        <span class="font-medium {{ $remoteConfig['has_refresh_token'] ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ $remoteConfig['has_refresh_token'] ? 'Sudah diberikan' : 'Belum diberikan' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Terakhir Disimpan ke Drive:</span>
                        <span class="font-medium text-slate-800">
                            {{ $lastRemoteRun ? $lastRemoteRun->started_at->format('d/m/Y H:i') : '-' }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Keamanan Cadangan:</span>
                    <span class="font-semibold text-emerald-700">Dikunci dengan sandi</span>
                </div>
            </div>

            <!-- 3. Lama penyimpanan cadangan -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                            <x-icon name="database" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Lama Penyimpanan Cadangan</h2>
                            <p class="text-sm font-bold text-slate-900">Harian, mingguan, dan bulanan</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-[11px] font-bold text-teal-800 border border-teal-300">
                        Cadangan Terlindungi
                    </span>
                </div>

                <div class="mt-4 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Cadangan Harian:</span>
                        <strong class="text-slate-800">7 hari terakhir</strong>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Cadangan Mingguan:</span>
                        <strong class="text-slate-800">4 minggu terakhir</strong>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Cadangan Bulanan:</span>
                        <strong class="text-slate-800">12 bulan terakhir</strong>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Otomatis setiap hari:</span>
                    <span class="font-semibold text-slate-800">02:00 WIB buat cadangan, 03:00 WIB bersihkan yang lama</span>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Cadangan -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Cadangan</h2>
                    <p class="text-xs text-slate-500">Daftar cadangan lengkap yang tersimpan terkunci sandi. Dipakai teknisi bila data perlu dipulihkan.</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span>Berhasil: <strong class="text-emerald-700 font-bold">{{ $stats['total_completed'] }}</strong></span>
                    <span class="text-slate-300">|</span>
                    <span>Gagal: <strong class="{{ $stats['total_failed'] > 0 ? 'text-rose-700 font-bold' : 'text-slate-500' }}">{{ $stats['total_failed'] }}</strong></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                            <th scope="col" class="py-3 px-4">Kode Cadangan</th>
                            <th scope="col" class="py-3 px-4">Jenis & Cara Dibuat</th>
                            <th scope="col" class="py-3 px-4">Waktu & Lama Proses</th>
                            <th scope="col" class="py-3 px-4">Ukuran</th>
                            <th scope="col" class="py-3 px-4">Google Drive</th>
                            <th scope="col" class="py-3 px-4">Status</th>
                            <th scope="col" class="py-3 px-4 text-center">Unduh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($backups as $run)
                            @php
                                $localExists = \Illuminate\Support\Facades\File::exists(storage_path("app/private/backups/{$run->backup_id}.enc"));
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Kode Cadangan -->
                                <td class="py-3 px-4">
                                    <div class="font-mono text-xs font-bold text-slate-900">{{ $run->backup_id }}</div>
                                    @if ($run->sha256_checksum)
                                        <div class="text-[10px] text-slate-400 font-mono" title="Kode pemeriksa keutuhan: {{ $run->sha256_checksum }}">
                                            Kode cek: {{ Str::limit($run->sha256_checksum, 12) }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Jenis & Cara Dibuat -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $run->type === 'full' ? 'bg-teal-100 text-teal-800' : ($run->type === 'database' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                                            {{ $run->type === 'full' ? 'Lengkap' : ($run->type === 'database' ? 'Data saja' : 'Berkas saja') }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            {{ $run->trigger_type === 'scheduled' ? 'Otomatis' : 'Manual' }}
                                        </span>
                                    </div>
                                    @if ($run->requestedBy)
                                        <div class="text-[11px] text-slate-400 mt-0.5">Oleh: {{ $run->requestedBy->name ?? $run->requestedBy->username }}</div>
                                    @endif
                                </td>

                                <!-- Waktu & Lama Proses -->
                                <td class="py-3 px-4">
                                    <div class="text-xs font-semibold text-slate-800">
                                        {{ $run->started_at ? $run->started_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        Lama: {{ $run->duration_seconds !== null ? $run->duration_seconds . ' detik' : '-' }}
                                    </div>
                                </td>

                                <!-- Ukuran -->
                                <td class="py-3 px-4">
                                    <div class="text-xs font-bold text-slate-900">
                                        {{ $run->archive_size_bytes ? round($run->archive_size_bytes / (1024 * 1024), 2) . ' MB' : '-' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        Data: {{ $run->database_size_bytes ? round($run->database_size_bytes / 1024, 1) . ' KB' : '0' }} ·
                                        Berkas: {{ $run->files_size_bytes ? round($run->files_size_bytes / 1024, 1) . ' KB' : '0' }}
                                    </div>
                                </td>

                                <!-- Status Google Drive -->
                                <td class="py-3 px-4">
                                    @if ($run->remote_file_id)
                                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">
                                            <x-icon name="cloud" class="h-3 w-3 text-emerald-600" />
                                            Tersimpan di Drive
                                        </span>
                                    @elseif ($run->status === 'completed')
                                        <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                            Hanya di Server
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
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
                                            {{ $run->status === 'pending' ? 'Menunggu' : 'Sedang diproses' }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Unduh -->
                                <td class="py-3 px-4 text-center">
                                    @if ($run->status === 'completed' && $localExists)
                                        <a href="/admin/backups/{{ $run->backup_id }}/download" 
                                           class="inline-flex min-h-[36px] items-center gap-1.5 rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 border border-teal-200 hover:bg-teal-100 hover:text-teal-800 transition-colors"
                                           title="Unduh cadangan {{ $run->backup_id }} (berkas terkunci sandi)">
                                            <x-icon name="download" class="h-3.5 w-3.5" />
                                            <span>Unduh</span>
                                        </a>
                                    @elseif ($run->status === 'completed')
                                        <span class="inline-flex items-center rounded bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-500" title="Salinan di server sudah dibersihkan karena terlalu lama. Salinan masih ada di Google Drive.">
                                            Hanya di Drive
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
                                    <p class="font-semibold text-slate-600 text-sm">Belum ada cadangan data</p>
                                    <p class="text-xs text-slate-400 mt-1">Klik "Buat Cadangan Sekarang" untuk membuat cadangan pertama.</p>
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

        <!-- Modal Buat Cadangan -->
        <div id="modal-backup-manual" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                            <x-icon name="database" class="h-4 w-4" />
                        </span>
                        <h2 class="text-base font-bold text-slate-900">Buat Cadangan Sekarang</h2>
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
                            <label class="block text-xs font-bold text-slate-700 mb-1">Isi Cadangan *</label>
                            <select name="type" class="w-full min-h-[44px] rounded-lg border border-slate-300 px-3 text-sm focus:border-teal-700 focus:outline-hidden">
                                <option value="full" selected>Lengkap: data surat + dokumen & tanda tangan (Disarankan)</option>
                                <option value="database">Data surat saja</option>
                                <option value="files">Dokumen & tanda tangan saja</option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">Pilihan "Lengkap" menyimpan semua data surat masuk/keluar beserta dokumen dan tanda tangannya. Pilih ini bila ragu.</p>
                        </div>

                        @if ($remoteConfig['enabled'])
                            <div class="rounded-lg border border-sky-200 bg-sky-50/50 p-3">
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="upload" value="1" checked class="mt-0.5 rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                                    <span class="text-xs text-slate-700 leading-relaxed">
                                        <strong>Simpan juga ke Google Drive:</strong> salinan cadangan dikirim otomatis ke Google Drive instansi setelah selesai dibuat.
                                    </span>
                                </label>
                            </div>
                        @else
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                                <em>Google Drive belum diaktifkan. Cadangan hanya akan disimpan di server ini.</em>
                            </div>
                        @endif

                        <div class="rounded-lg border border-teal-200 bg-teal-50/50 p-3 text-xs text-teal-900 leading-relaxed">
                            <strong class="font-bold">Keamanan:</strong> Cadangan otomatis dikunci dengan sandi dan diperiksa keutuhannya. Kunci sandi tidak disimpan bersama cadangan.
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-900 leading-relaxed flex items-start gap-2">
                            <x-icon name="alert-triangle" class="h-4 w-4 text-amber-700 shrink-0 mt-0.5" />
                            <div>
                                <strong class="font-bold">Mohon tunggu:</strong> Proses ini bisa memakan waktu 15–30 detik. Jangan tutup halaman sampai selesai. Jika data sangat besar dan halaman gagal dimuat, andalkan cadangan otomatis setiap pukul 02:00 WIB.
                                <details class="mt-1.5">
                                    <summary class="cursor-pointer font-semibold">Khusus teknisi</summary>
                                    <p class="mt-1">Jalankan lewat terminal server:</p>
                                    <code class="mt-1 block rounded bg-white px-2 py-0.5 font-mono text-[11px] text-amber-950 border border-amber-300">php artisan sisurat:backup --type=full --upload</code>
                                </details>
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
                                onclick="this.disabled=true; this.innerText='Sedang membuat cadangan...'; document.getElementById('form-backup-manual').submit();"
                                class="min-h-[44px] rounded-lg bg-teal-700 px-5 text-xs font-semibold text-white hover:bg-teal-800 transition-colors shadow-xs">
                            Buat Cadangan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Bersihkan Cadangan Lama -->
        <div id="modal-prune-retensi" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                            <x-icon name="trash" class="h-4 w-4" />
                        </span>
                        <h2 class="text-base font-bold text-slate-900">Bersihkan Cadangan Lama</h2>
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
                            Cadangan lama dihapus otomatis agar ruang server tidak penuh. Yang tetap disimpan:
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-slate-700">
                            <li>Cadangan harian selama <strong>7 hari</strong> terakhir.</li>
                            <li>Satu cadangan tiap minggu selama <strong>4 minggu</strong> terakhir.</li>
                            <li>Satu cadangan tiap bulan selama <strong>12 bulan</strong> terakhir.</li>
                        </ul>

                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-emerald-900">
                            <strong class="font-bold">Pengaman:</strong> Tidak ada cadangan yang dihapus jika hanya tersisa 1 cadangan, atau jika cadangan terakhir gagal dibuat.
                        </div>

                        <div class="pt-2">
                            <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-800">
                                <input type="checkbox" name="dry_run" value="1" class="rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                                <span>Hanya periksa dulu (tidak ada yang dihapus)</span>
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
                            Bersihkan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts.app>
