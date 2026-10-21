<x-layouts.app title="Dashboard">
    @php
        $user = auth()->user();
        $isSuperAdmin = $user->role === 'super_admin';
        $isDivisionAdmin = $user->role === 'division_admin';
        $isOperator = $user->role === 'operator';
    @endphp

    <section class="px-4 py-4 sm:px-6 sm:py-6 md:px-8 flex flex-col gap-4 sm:gap-5">
        <!-- ============================================================ -->
        <!-- 1. WORKSPACE HEADER & PRIMARY ACTIONS                        -->
        <!-- ============================================================ -->
        <x-page-header class="mb-0"
            :badge="$activeDivision ? $activeDivision->code . ' — ' . $activeDivision->name : 'Semua Divisi (Konteks Global)'"
            :meta="'Peran: ' . ucwords(str_replace('_', ' ', $user->role))"
            title="Dashboard Pengarsipan Surat"
            subtitle="Sistem Informasi Surat & Kearsipan Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga"
        >
            <x-slot:actions>
                <a href="/surat-masuk/create" 
                   id="btn-quick-incoming"
                   class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>Surat Masuk</span>
                </a>
                <a href="/surat-keluar/create" 
                   id="btn-quick-outgoing"
                   class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4 text-slate-500" />
                    <span>Surat Keluar</span>
                </a>
                <a href="/piagam/create" 
                   id="btn-quick-piagam"
                   class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4 text-slate-500" />
                    <span>Catat Piagam</span>
                </a>
            </x-slot:actions>
        </x-page-header>

        <!-- ============================================================ -->
        <!-- OPERATOR FAST TRACK: PENCARIAN PINTAR QUICK BAR              -->
        <!-- ============================================================ -->
        @if ($isOperator)
            <div class="rounded-xl border border-[#E3E8EE] bg-white p-3 sm:p-3.5 shadow-2xs">
                <form action="/laporan" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <x-icon name="search" class="h-4 w-4 text-teal-600" />
                        </div>
                        <input type="search" 
                               name="q" 
                               id="operator-quick-search-input"
                               placeholder="Cari nomor surat, instansi asal, perihal arsip... (Tekan '/' untuk fokus)" 
                               class="w-full rounded-lg border border-[#E3E8EE] bg-[#F7F8FA] py-2.5 pl-10 pr-12 text-sm text-[#17243A] placeholder:text-slate-400 focus:border-[#147D73] focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-[#147D73] transition-colors" />
                        <div class="pointer-events-none absolute inset-y-0 right-0 hidden sm:flex items-center pr-3">
                            <kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 shadow-2xs">/</kbd>
                        </div>
                    </div>
                    <button type="submit" 
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-[#147D73] px-5 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] transition-colors">
                        <x-icon name="search" class="h-4 w-4" />
                        <span>Pencarian Pintar</span>
                    </button>
                </form>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- SUPER ADMIN: SYSTEM HEALTH & BACKUP MONITOR                  -->
        <!-- ============================================================ -->
        @if ($isSuperAdmin && isset($systemHealth))
            <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-[#147D73] border border-teal-200">
                            <x-icon name="database" class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-sm tracking-tight text-slate-900">Status Cadangan Data Sistem</h3>
                                @if ($systemHealth['is_healthy'])
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800 border border-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span> Operasional Aman
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-semibold text-rose-800 border border-rose-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span> Perhatian Backup
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-600">
                                @if ($systemHealth['last_backup'])
                                    Cadangan terakhir: <strong class="text-slate-800">{{ $systemHealth['last_backup']->created_at->diffForHumans() }}</strong> 
                                    ({{ $systemHealth['last_backup']->created_at->format('d/m/Y H:i') }} WIB) &bull; 
                                    Ukuran: {{ round(($systemHealth['last_backup']->archive_size_bytes ?? 0) / 1024 / 1024, 2) }} MB
                                @else
                                    Belum ada catatan snapshot cadangan. Sistem siap dilakukan backup perdana.
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="/admin/backups" 
                           class="inline-flex min-h-[38px] items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 text-xs font-semibold text-slate-700 hover:border-teal-500 hover:text-[#147D73] hover:bg-slate-50 transition-colors shadow-2xs">
                            <span>Kelola Backup & Pemulihan</span>
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- ADMIN DIVISI: DATA ATTENTION WARNING (IF ANY)                -->
        <!-- ============================================================ -->
        @if ($isDivisionAdmin && isset($attentionItems) && ($attentionItems['missing_attachments_count'] ?? 0) > 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-2xs">
                <div class="flex items-start gap-3">
                    <x-icon name="alert-circle" class="h-5 w-5 shrink-0 text-amber-700 mt-0.5" />
                    <div class="flex-1 text-xs">
                        <h4 class="font-bold text-sm text-amber-900">Perhatian Integritas Lampiran Arsip</h4>
                        <p class="mt-0.5 text-amber-800">
                            Terdapat <strong>{{ $attentionItems['missing_attachments_count'] }}</strong> arsip lampiran yang terdata belum lengkap atau perlu diunggah ulang.
                        </p>
                    </div>
                    <a href="/persuratan?tab=masuk" class="rounded-lg bg-amber-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-800 transition-colors">
                        Tinjau Arsip
                    </a>
                </div>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- 2. INTERACTIVE METRIC ENTRY CARDS (ZERO REDUNDANCY)          -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5">
            <!-- 1. Total Rekap / Pusat Persuratan -->
            <a href="/persuratan" 
               id="metric-card-total"
               class="group block rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-teal-500 hover:shadow-md focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#657184] group-hover:text-[#147D73] transition-colors">Pusat Persuratan</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-[#147D73] group-hover:bg-[#147D73] group-hover:text-white transition-colors">
                        <x-icon name="layers" class="h-4.5 w-4.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalRekap) }}</p>
                    <p class="mt-1 text-xs text-[#657184] line-clamp-1">Total seluruh arsip terpadu</p>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2 text-xs font-semibold text-[#147D73]">
                    <span>Buka Arsip</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </a>

            <!-- 2. Surat Masuk -->
            <a href="/persuratan?tab=masuk" 
               id="metric-card-incoming"
               class="group block rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-500 hover:shadow-md focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Surat Masuk</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 group-hover:bg-emerald-700 group-hover:text-white transition-colors">
                        <x-icon name="mail" class="h-4.5 w-4.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalIncoming) }}</p>
                    <p class="mt-1 text-xs text-[#657184] line-clamp-1">Register arsip surat masuk</p>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2 text-xs font-semibold text-emerald-700">
                    <span>Lihat Register</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </a>

            <!-- 3. Surat Keluar -->
            <a href="/persuratan?tab=keluar" 
               id="metric-card-outgoing"
               class="group block rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-500 hover:shadow-md focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-800">Surat Keluar</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700 group-hover:bg-blue-700 group-hover:text-white transition-colors">
                        <x-icon name="send" class="h-4.5 w-4.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalOutgoing) }}</p>
                    <p class="mt-1 text-xs text-[#657184] line-clamp-1">Buku nomor & arsip surat keluar</p>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2 text-xs font-semibold text-blue-700">
                    <span>Lihat Register</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </a>

            <!-- 4. Piagam & Sertifikat -->
            <a href="/persuratan?tab=piagam" 
               id="metric-card-piagam"
               class="group block rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-500 hover:shadow-md focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800">Piagam & Sertifikat</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700 group-hover:bg-amber-700 group-hover:text-white transition-colors">
                        <x-icon name="award" class="h-4.5 w-4.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalPiagam) }}</p>
                    <p class="mt-1 text-xs text-[#657184] line-clamp-1">Buku serah terima & piagam dinas</p>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2 text-xs font-semibold text-amber-700">
                    <span>Buka Register</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </a>
        </div>

        <!-- ============================================================ -->
        <!-- 3. SUPER ADMIN: INTERACTIVE CROSS-DIVISION MATRIX            -->
        <!-- ============================================================ -->
        @if ($isSuperAdmin && $divisionMatrix->isNotEmpty())
            <div class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between border-b border-[#E3E8EE] pb-3">
                    <div>
                        <h2 class="font-bold text-base text-[#17243A]">Matriks Agregasi Lintas Divisi</h2>
                        <p class="text-xs text-[#657184]">Klik nama bidang untuk langsung beralih konteks kerja kearsipan.</p>
                    </div>
                    <span class="self-start sm:self-auto rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                        {{ $divisionMatrix->count() }} Bidang Kerja Terdata
                    </span>
                </div>

                <!-- Desktop Table View (>= md) -->
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="border-b border-[#E3E8EE] bg-[#F7F8FA] text-left text-xs font-semibold uppercase tracking-wider text-[#657184]">
                                <th class="p-3">Kode & Nama Divisi</th>
                                <th class="p-3 text-right">Surat Masuk</th>
                                <th class="p-3 text-right">Surat Keluar</th>
                                <th class="p-3 text-right">Piagam</th>
                                <th class="p-3 text-right font-bold text-[#17243A]">Total Arsip</th>
                                <th class="p-3 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E3E8EE]">
                            @foreach ($divisionMatrix as $div)
                                @php
                                    $totalDiv = $div->incoming_letters_count + $div->outgoing_letters_count + $div->piagams_count;
                                    $isCurrent = $activeDivision && $activeDivision->id === $div->id;
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition-colors {{ $isCurrent ? 'bg-teal-50/40' : '' }}">
                                    <td class="p-3 font-medium text-[#17243A]">
                                        <form action="/switch-division" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="division_id" value="{{ $div->id }}" />
                                            <button type="submit" 
                                                    class="text-left group flex items-center gap-1.5 hover:underline focus-visible:outline-hidden">
                                                <span class="font-bold text-[#147D73] font-mono group-hover:text-[#0F635B]">{{ $div->code }}</span>
                                                <span class="text-slate-300">&bull;</span>
                                                <span class="font-semibold text-slate-800 group-hover:text-[#147D73]">{{ $div->name }}</span>
                                                @if ($isCurrent)
                                                    <span class="rounded bg-teal-100 px-1.5 py-0.2 text-[10px] font-bold text-teal-800">Aktif</span>
                                                @endif
                                            </button>
                                        </form>
                                    </td>
                                    <td class="p-3 text-right text-slate-700 tabular-nums">{{ number_format($div->incoming_letters_count) }}</td>
                                    <td class="p-3 text-right text-slate-700 tabular-nums">{{ number_format($div->outgoing_letters_count) }}</td>
                                    <td class="p-3 text-right text-slate-700 tabular-nums">{{ number_format($div->piagams_count) }}</td>
                                    <td class="p-3 text-right font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalDiv) }}</td>
                                    <td class="p-3 text-center">
                                        <form action="/switch-division" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="division_id" value="{{ $div->id }}" />
                                            <button type="submit" 
                                                    class="inline-flex items-center gap-1 rounded border border-[#E3E8EE] bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:border-teal-500 hover:text-[#147D73] transition-colors">
                                                <span>Buka</span>
                                                <x-icon name="arrow-right" class="h-3 w-3" />
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- 4. ANALYTICS: 6-MONTH COMPARATIVE TREND                      -->
        <!-- (Visible to Super Admin & Admin Divisi)                      -->
        <!-- ============================================================ -->
        @if ($isSuperAdmin || $isDivisionAdmin)
            <div class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between border-b border-[#E3E8EE] pb-3">
                    <div>
                        <h2 class="font-bold text-base text-[#17243A]">Tren Volume Persuratan (6 Bulan Terakhir)</h2>
                        <p class="text-xs text-[#657184]">Skala bar terstandarisasi absolut berdasarkan volume puncak kalender.</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5 text-emerald-800 font-semibold">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-600"></span> Surat Masuk
                        </span>
                        <span class="flex items-center gap-1.5 text-blue-700 font-semibold">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span> Surat Keluar
                        </span>
                    </div>
                </div>

                <div class="space-y-4 pt-1">
                    @foreach ($trendData as $trend)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800">{{ $trend['label'] }}</span>
                                <span class="text-slate-600 text-xs tabular-nums">
                                    <span class="text-emerald-700 font-bold">SM: {{ number_format($trend['incoming']) }}</span>
                                    <span class="mx-1 text-slate-300">&bull;</span>
                                    <span class="text-blue-700 font-bold">SK: {{ number_format($trend['outgoing']) }}</span>
                                    <span class="mx-1 text-slate-300">&bull;</span>
                                    <span class="font-semibold text-slate-500">Total: {{ number_format($trend['total']) }}</span>
                                </span>
                            </div>
                            <!-- Bar scale normalized to max global -->
                            <div class="flex h-3 w-full gap-1 overflow-hidden rounded-full bg-slate-100 border border-slate-200">
                                @if ($trend['incoming'] > 0)
                                    <div style="width: {{ $trend['incoming_width'] }}%" 
                                         class="rounded-full bg-emerald-600 transition-all duration-300" 
                                         title="Surat Masuk: {{ $trend['incoming'] }}"></div>
                                @endif
                                @if ($trend['outgoing'] > 0)
                                    <div style="width: {{ $trend['outgoing_width'] }}%" 
                                         class="rounded-full bg-blue-600 transition-all duration-300" 
                                         title="Surat Keluar: {{ $trend['outgoing'] }}"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- 5. OPERATIONAL WORKSPACE: RECENT CORRESPONDENCE & AUDIT      -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 gap-4 sm:gap-5 {{ $isOperator ? 'lg:grid-cols-2' : 'lg:grid-cols-12' }}">
            <!-- Left Workspace: Tabbed Recent Letters -->
            <div class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs {{ $isOperator ? '' : 'lg:col-span-7' }}">
                <div class="mb-3 flex items-center justify-between border-b border-[#E3E8EE] pb-2.5">
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                onclick="switchRecentTab('incoming')" 
                                id="tab-btn-incoming"
                                class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-bold text-[#147D73] border border-teal-200 transition-colors">
                            Surat Masuk Terkini
                        </button>
                        <button type="button" 
                                onclick="switchRecentTab('outgoing')" 
                                id="tab-btn-outgoing"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 border border-transparent transition-colors">
                            Surat Keluar Terkini
                        </button>
                    </div>
                    <a href="/persuratan" class="text-xs font-semibold text-[#147D73] hover:underline">
                        Lihat semua &rarr;
                    </a>
                </div>

                <!-- Tab Content: Surat Masuk -->
                <div id="tab-content-incoming" class="divide-y divide-[#E3E8EE]">
                    @forelse ($recentIncoming as $letter)
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold text-[#0F635B] border border-teal-200">MASUK</span>
                                        <a href="/surat-masuk/{{ $letter->id }}" class="truncate font-semibold text-xs sm:text-sm text-[#17243A] hover:text-[#147D73] hover:underline">
                                            {{ $letter->letter_number }}
                                        </a>
                                    </div>
                                    <p class="truncate text-xs text-[#657184] mt-0.5">{{ $letter->origin_agency }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-medium text-slate-500 tabular-nums">
                                    {{ $letter->received_date ? $letter->received_date->format('d/m/Y') : '-' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-[#657184]">
                            <p>Belum ada catatan arsip surat masuk.</p>
                            <a href="/surat-masuk/create" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#147D73] hover:underline">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                <span>Catat Surat Masuk Pertama</span>
                            </a>
                        </div>
                    @endforelse
                </div>

                <!-- Tab Content: Surat Keluar -->
                <div id="tab-content-outgoing" class="hidden divide-y divide-[#E3E8EE]">
                    @forelse ($recentOutgoing as $letter)
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-800 border border-blue-200">KELUAR</span>
                                        <a href="/surat-keluar/{{ $letter->id }}" class="truncate font-semibold text-xs sm:text-sm text-[#17243A] hover:text-blue-700 hover:underline">
                                            {{ $letter->letter_number }}
                                        </a>
                                    </div>
                                    <p class="truncate text-xs text-[#657184] mt-0.5">{{ $letter->subject }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-medium text-slate-500 tabular-nums">
                                    {{ $letter->letter_date ? $letter->letter_date->format('d/m/Y') : '-' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-[#657184]">
                            <p>Belum ada catatan arsip surat keluar.</p>
                            <a href="/surat-keluar/create" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:underline">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                <span>Buat Nomor Surat Keluar</span>
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right Workspace: Activity Feed / Audit Log -->
            <div class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs {{ $isOperator ? '' : 'lg:col-span-5' }}">
                <div class="mb-3 flex items-center justify-between border-b border-[#E3E8EE] pb-2.5">
                    <div class="flex items-center gap-2">
                        <x-icon name="clock" class="h-4 w-4 text-slate-500" />
                        <h2 class="font-bold text-sm text-[#17243A]">
                            {{ $isOperator ? 'Aktivitas Kearsipan Terkini' : 'Aktivitas Sistem (Audit Log)' }}
                        </h2>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Terkini</span>
                </div>

                <div class="divide-y divide-[#E3E8EE]">
                    @forelse ($recentActivities as $act)
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="rounded bg-teal-50 px-1.5 py-0.2 text-[10px] font-bold text-[#0F635B] border border-teal-200">
                                            {{ $act->human_event ?? $act->event_code }}
                                        </span>
                                        <span class="rounded bg-slate-100 px-1.5 py-0.2 font-mono text-[9px] text-slate-500">
                                            {{ $act->event_code }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs font-medium text-[#17243A] line-clamp-2">{{ $act->description }}</p>
                                    <p class="mt-0.5 text-[11px] text-[#657184]">
                                        Oleh: <strong class="text-slate-700">{{ $act->username ?? 'Sistem' }}</strong>
                                        @if ($act->role)
                                            <span class="text-slate-400">({{ ucwords(str_replace('_', ' ', $act->role)) }})</span>
                                        @endif
                                    </p>
                                </div>
                                <span class="shrink-0 text-[10px] font-medium text-slate-500 whitespace-nowrap cursor-help hover:text-slate-800 transition-colors"
                                      title="{{ $act->created_at->format('d/m/Y H:i:s') }} WIB">
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="clock" class="h-3 w-3 inline text-slate-400" />
                                        {{ $act->created_at->diffForHumans() }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-[#657184]">Belum ada aktivitas tercatat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- Client-side Interactive Behaviors -->
    <script>
        function switchRecentTab(tab) {
            const inContent = document.getElementById('tab-content-incoming');
            const outContent = document.getElementById('tab-content-outgoing');
            const inBtn = document.getElementById('tab-btn-incoming');
            const outBtn = document.getElementById('tab-btn-outgoing');

            if (!inContent || !outContent || !inBtn || !outBtn) return;

            if (tab === 'incoming') {
                inContent.classList.remove('hidden');
                outContent.classList.add('hidden');
                inBtn.className = 'rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-bold text-[#147D73] border border-teal-200 transition-colors';
                outBtn.className = 'rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 border border-transparent transition-colors';
            } else {
                inContent.classList.add('hidden');
                outContent.classList.remove('hidden');
                outBtn.className = 'rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 border border-blue-200 transition-colors';
                inBtn.className = 'rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 border border-transparent transition-colors';
            }
        }

        // Keyboard Shortcut: '/' to focus search
        document.addEventListener('keydown', function(e) {
            if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                const searchInput = document.getElementById('operator-quick-search-input');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });
    </script>
</x-layouts.app>
