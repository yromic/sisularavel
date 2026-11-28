<x-layouts.app title="Pusat Persuratan & Buku Ekspedisi">
    <section class="px-4 py-4 md:px-8">
        
        <!-- Header Halaman Terpadu (Standar SISURAT) -->
        <x-page-header
            :badge="auth()->user()->division?->code ? auth()->user()->division->code . ' — ' . auth()->user()->division->name : 'Semua Divisi (Konteks Global)'"
            meta="Status: Konsol Terpadu"
            title="Pusat Persuratan & Buku Ekspedisi"
            subtitle="Kelola seluruh arsip Surat Masuk, Surat Keluar, dan Buku Ekspedisi Piagam dalam satu konsol terpadu."
        >
            <x-slot:actions>
                <!-- Aksi Tambah Surat Masuk -->
                <button type="button" id="btn-action-masuk" onclick="openLetterFormModal('surat-masuk', 'create')" class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>+ Tambah Surat Masuk</span>
                </button>

                <!-- Aksi Tambah Surat Keluar -->
                <button type="button" id="btn-action-keluar" onclick="openLetterFormModal('surat-keluar', 'create')" class="hidden flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>+ Tambah Surat Keluar</span>
                </button>

                <!-- Aksi Tambah Piagam -->
                <a href="/piagam/ambil" id="btn-action-piagam" class="hidden flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>+ Catat Pengambilan Piagam</span>
                </a>

                <!-- Ekspor CSV -->
                <a id="btn-export-csv" href="/api/v1/reports/export.csv?type=incoming" onclick="handleExportClick(this, 'Menyiapkan CSV...')" class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="download" class="h-4 w-4 text-slate-500" />
                    <span>Ekspor CSV</span>
                </a>

                <!-- Cetak PDF -->
                <a id="btn-export-pdf" href="/api/v1/reports/export.pdf?type=incoming" target="_blank" onclick="handleExportClick(this, 'Menyiapkan PDF...')" class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="file-text" class="h-4 w-4 text-slate-500" />
                    <span>Cetak PDF</span>
                </a>
            </x-slot:actions>
        </x-page-header>

        <!-- Segmented Tab Switcher (Touch-First & Zero Reload) & Status Toggle -->
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="overflow-x-auto pb-1">
                <div class="inline-flex min-w-full sm:min-w-0 items-center rounded-lg bg-slate-100 p-1 border border-slate-200 text-xs gap-1">
                    <button type="button" id="tab-btn-masuk" onclick="switchWorkspaceTab('masuk')" class="flex items-center gap-2 rounded-md px-4 py-2.5 font-bold transition-all whitespace-nowrap bg-emerald-700 text-white shadow-xs">
                        <x-icon name="mail" class="h-4 w-4" />
                        <span>Surat Masuk</span>
                        <span id="tab-count-masuk" class="rounded-full bg-emerald-800/80 px-2 py-0.5 text-[10px] font-mono text-emerald-100">{{ $totalIncoming }}</span>
                    </button>

                    <button type="button" id="tab-btn-keluar" onclick="switchWorkspaceTab('keluar')" class="flex items-center gap-2 rounded-md px-4 py-2.5 font-semibold text-slate-600 hover:text-slate-900 transition-all whitespace-nowrap">
                        <x-icon name="mail-outgoing" class="h-4 w-4" />
                        <span>Surat Keluar</span>
                        <span id="tab-count-keluar" class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-mono text-slate-700">{{ $totalOutgoing }}</span>
                    </button>

                    <button type="button" id="tab-btn-piagam" onclick="switchWorkspaceTab('piagam')" class="flex items-center gap-2 rounded-md px-4 py-2.5 font-semibold text-slate-600 hover:text-slate-900 transition-all whitespace-nowrap">
                        <x-icon name="signature" class="h-4 w-4" />
                        <span>Buku Ekspedisi Piagam</span>
                        <span id="tab-count-piagam" class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-mono text-slate-700">{{ $totalPiagam }}</span>
                    </button>
                </div>
            </div>

            @if ($canViewTrashed)
                <!-- Status Toggle for Admin: Aktif vs Kotak Sampah (UX-010) -->
                <div class="inline-flex items-center rounded-lg bg-slate-100 p-1 border border-slate-200 text-xs self-start sm:self-auto shrink-0">
                    <a href="/persuratan?tab={{ $tab }}&status=active{{ request('q') ? '&q='.urlencode(request('q')) : '' }}{{ request('date_from') ? '&date_from='.urlencode(request('date_from')) : '' }}{{ request('date_to') ? '&date_to='.urlencode(request('date_to')) : '' }}" 
                       id="status-link-active"
                       class="flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition-all {{ $status === 'trashed' ? 'text-slate-600 hover:text-slate-900' : 'bg-white text-slate-800 shadow-2xs font-bold' }}">
                        <span>Arsip Aktif</span>
                    </a>
                    <a href="/persuratan?tab={{ $tab }}&status=trashed{{ request('q') ? '&q='.urlencode(request('q')) : '' }}{{ request('date_from') ? '&date_from='.urlencode(request('date_from')) : '' }}{{ request('date_to') ? '&date_to='.urlencode(request('date_to')) : '' }}" 
                       id="status-link-trashed"
                       class="flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition-all {{ $status === 'trashed' ? 'bg-rose-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-rose-700' }}">
                        <x-icon name="trash" class="h-3.5 w-3.5" />
                        <span>Kotak Sampah</span>
                        @php
                            $trashedTotal = $trashedIncomingCount + $trashedOutgoingCount + ($trashedPiagamCount ?? 0);
                        @endphp
                        @if ($trashedTotal > 0)
                            <span class="rounded-full {{ $status === 'trashed' ? 'bg-rose-800 text-rose-100' : 'bg-rose-200 text-rose-800' }} px-1.5 py-0.2 text-[10px] font-mono font-bold">{{ $trashedTotal }}</span>
                        @endif
                    </a>
                </div>
            @endif
        </div>

        @if ($status === 'trashed')
            <!-- Trashed Notice Banner (UX-010) -->
            <div class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-900 shadow-xs">
                <div class="flex items-center gap-2">
                    <x-icon name="alert-triangle" class="h-4 w-4 text-rose-600 shrink-0" />
                    <span><strong>Mode Kotak Sampah:</strong> Menampilkan arsip yang telah dihapus sementara. Anda dapat memulihkan (restore) data kapan saja.</span>
                </div>
                <a href="/persuratan?tab={{ $tab }}&status=active" class="font-bold underline hover:text-rose-950 whitespace-nowrap">Kembali ke Arsip Aktif</a>
            </div>
        @endif

        <!-- Universal Filter & Search Toolbar (UX2-012 Collapsible Mobile Optimization) -->
        <div class="mb-5 rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs">
            <form method="get" action="/persuratan" id="workspace-search-form" class="space-y-3 sm:space-y-0 sm:grid sm:grid-cols-12 sm:gap-2.5">
                <input type="hidden" name="tab" id="form-active-tab-input" value="{{ $tab }}">
                <input type="hidden" name="status" id="form-active-status-input" value="{{ $status ?? 'active' }}">
                @if (request()->filled('sort'))
                    <input type="hidden" name="sort" id="form-active-sort-input" value="{{ request('sort') }}">
                @endif
                @if (request()->filled('dir'))
                    <input type="hidden" name="dir" id="form-active-dir-input" value="{{ request('dir') }}">
                @endif
                @if (request()->filled('per_page'))
                    <input type="hidden" name="per_page" id="form-active-per-page-input" value="{{ request('per_page') }}">
                @endif
                
                <div class="sm:col-span-5">
                    <label for="input-search-q" class="sr-only">Kata Kunci Pencarian</label>
                    <div class="flex min-h-[42px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3 focus-within:border-emerald-700 focus-within:ring-1 focus-within:ring-emerald-700">
                        <x-icon name="search" class="h-4 w-4 text-slate-400 shrink-0" />
                        <input name="q" id="input-search-q" value="{{ request('q') }}" class="min-h-[40px] w-full border-0 bg-transparent text-xs text-slate-800 focus:outline-hidden" placeholder="Cari nomor surat, perihal, instansi, atau pengambil...">
                    </div>
                </div>

                <!-- Mobile Toggle Button for Date Filter Panel -->
                <div class="flex items-center gap-2 sm:hidden">
                    <button type="button"
                            onclick="toggleMobileFilterPanel()"
                            id="btn-toggle-mobile-filter"
                            class="flex min-h-[40px] flex-1 items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-slate-50 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        <x-icon name="calendar" class="h-4 w-4 text-slate-500" />
                        <span>Filter Rentang Tanggal</span>
                        @if (request()->filled('date_from') || request()->filled('date_to'))
                            <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        @endif
                    </button>
                    <button type="submit" class="min-h-[40px] rounded-md bg-slate-800 px-4 text-xs font-semibold text-white hover:bg-slate-900 shadow-xs">
                        Cari
                    </button>
                </div>

                <!-- Date Filter Panel (Collapsible on mobile, inline on desktop) -->
                <div id="workspace-date-filter-panel" class="{{ (request()->filled('date_from') || request()->filled('date_to')) ? '' : 'hidden sm:grid' }} sm:col-span-7 sm:grid sm:grid-cols-7 sm:gap-2 space-y-2.5 sm:space-y-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                    <div class="sm:col-span-3">
                        <label for="filter-date-from" class="block text-[11px] font-semibold text-slate-600 mb-1 sm:sr-only">Tanggal Mulai</label>
                        <input type="date" name="date_from" id="filter-date-from" value="{{ request('date_from') }}" class="min-h-[42px] w-full rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-700" title="Dari Tanggal">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="filter-date-to" class="block text-[11px] font-semibold text-slate-600 mb-1 sm:sr-only">Tanggal Selesai</label>
                        <input type="date" name="date_to" id="filter-date-to" value="{{ request('date_to') }}" class="min-h-[42px] w-full rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-700" title="Sampai Tanggal">
                    </div>
                    <div class="flex items-center gap-1.5 sm:col-span-1">
                        <button type="submit" class="hidden sm:inline-flex min-h-[42px] w-full items-center justify-center rounded-md bg-slate-800 px-3 text-xs font-semibold text-white hover:bg-slate-900 shadow-xs">
                            Filter
                        </button>
                        @if (request()->hasAny(['q', 'date_from', 'date_to']))
                            <a href="/persuratan?tab={{ $tab }}&status={{ $status ?? 'active' }}" id="btn-reset-filter" class="min-h-[38px] sm:min-h-[42px] flex items-center justify-center rounded-md border border-slate-300 px-2.5 text-xs text-slate-600 hover:bg-slate-100 w-full sm:w-auto" title="Reset filter">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @php
                $hasActiveFilters = request()->filled('q') || request()->filled('date_from') || request()->filled('date_to') || ($status === 'trashed');
                $baseParams = ['tab' => $tab];
            @endphp
            @if ($hasActiveFilters)
                <!-- Active Filter Chips Bar (UX-011) -->
                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-xs">
                    <span class="text-xs font-semibold text-slate-500">Filter Aktif:</span>
                    @if (request()->filled('q'))
                        <a href="/persuratan?{{ http_build_query(array_merge($baseParams, request()->except(['q', 'page', 'masuk_page', 'keluar_page', 'piagam_page']))) }}" data-filter-chip class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                            <span>Kata Kunci: "{{ request('q') }}"</span>
                            <span class="font-bold text-slate-400 hover:text-slate-700">&times;</span>
                        </a>
                    @endif
                    @if (request()->filled('date_from'))
                        <a href="/persuratan?{{ http_build_query(array_merge($baseParams, request()->except(['date_from', 'page', 'masuk_page', 'keluar_page', 'piagam_page']))) }}" data-filter-chip class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                            <span>Dari: {{ date('d/m/Y', strtotime(request('date_from'))) }}</span>
                            <span class="font-bold text-slate-400 hover:text-slate-700">&times;</span>
                        </a>
                    @endif
                    @if (request()->filled('date_to'))
                        <a href="/persuratan?{{ http_build_query(array_merge($baseParams, request()->except(['date_to', 'page', 'masuk_page', 'keluar_page', 'piagam_page']))) }}" data-filter-chip class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                            <span>Sampai: {{ date('d/m/Y', strtotime(request('date_to'))) }}</span>
                            <span class="font-bold text-slate-400 hover:text-slate-700">&times;</span>
                        </a>
                    @endif
                    @if ($status === 'trashed')
                        <a href="/persuratan?{{ http_build_query(array_merge($baseParams, request()->except(['status', 'page', 'masuk_page', 'keluar_page', 'piagam_page']), ['status' => 'active'])) }}" data-filter-chip class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-800 hover:bg-rose-200">
                            <span>Status: Kotak Sampah</span>
                            <span class="font-bold text-rose-500 hover:text-rose-800">&times;</span>
                        </a>
                    @endif
                    <a href="/persuratan?tab={{ $tab }}&status=active" id="btn-clear-all-filters" class="ml-auto text-xs font-semibold text-emerald-700 hover:underline">
                        Hapus Semua Filter
                    </a>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- PANEL 1: SURAT MASUK                                           -->
        <!-- ============================================================== -->
        <div id="panel-masuk" class="{{ $tab === 'masuk' ? '' : 'hidden' }}">
            <!-- Mobile Viewport: Card Summary -->
            <div class="space-y-3 md:hidden">
                @forelse ($incomingLetters as $letter)
                    <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-xs space-y-2.5 hover:border-emerald-300 transition-colors cursor-pointer" onclick="openLetterDetailModal('surat-masuk', {{ $letter->id }})">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2">
                            <div>
                                <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700">No. {{ ($incomingLetters->currentPage() - 1) * $incomingLetters->perPage() + $loop->iteration }} &bull; Reg #{{ $letter->id }}</span>
                                <span class="rounded bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 ml-1">{{ $letter->division?->code }}</span>
                                @if ($letter->trashed())
                                    <span class="rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 text-[10px] font-bold ml-1">Terhapus</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400 font-medium">Terima: {{ $letter->received_date?->format('d/m/Y') }}</span>
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-slate-900 leading-snug">{{ $letter->letter_number }}</h2>
                            <p class="text-xs font-medium text-slate-600 mt-0.5">Asal: <strong class="text-slate-800">{{ $letter->origin_agency }}</strong></p>
                        </div>
                        <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed bg-slate-50 p-2 rounded border border-slate-100">{{ $letter->subject }}</p>
                        
                        <div class="flex items-center justify-between border-t border-slate-100 pt-2.5">
                            <span class="text-[11px] text-slate-400 font-mono">Tgl Surat: {{ $letter->letter_date?->format('d/m/Y') }}</span>
                            <div class="flex items-center gap-1.5">
                                @if ($letter->trashed())
                                    <form method="post" action="/surat-masuk/{{ $letter->id }}/restore" class="inline" onsubmit="return confirmAction(event, 'Pulihkan kembali surat masuk {{ $letter->letter_number }}?', 'Konfirmasi Pemulihan Surat', 'emerald', 'Ya, Pulihkan')">
                                        @csrf
                                        <button type="submit" onclick="event.stopPropagation()" class="inline-flex min-h-[32px] items-center gap-1 rounded border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                            <x-icon name="rotate-ccw" class="h-3.5 w-3.5 text-emerald-700" />
                                            <span>Pulihkan</span>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" onclick="event.stopPropagation(); openLetterDetailModal('surat-masuk', {{ $letter->id }})" class="inline-flex min-h-[32px] items-center gap-1 rounded border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs">
                                        Detail
                                    </button>
                                    @if (in_array(auth()->user()->role, ['super_admin', 'division_admin']))
                                        <button type="button" onclick="event.stopPropagation(); openLetterFormModal('surat-masuk', 'edit', {{ json_encode($letter) }})" class="inline-flex min-h-[32px] items-center gap-1 rounded border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                            Edit
                                        </button>
                                        <button type="button" onclick="event.stopPropagation(); openLetterDeleteModal('surat-masuk', {{ $letter->id }}, '{{ addslashes($letter->letter_number) }}')" class="inline-flex min-h-[32px] items-center gap-1 rounded border border-rose-300 bg-rose-50 px-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 shadow-2xs">
                                            Hapus
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                        <x-icon name="mail" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                        Tidak ada arsip surat masuk ditemukan.
                    </div>
                @endforelse
            </div>

            <!-- Desktop Viewport: Dense Grid -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[1024px] border-collapse bg-white text-sm shadow-xs rounded-lg border border-slate-200">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                            <th class="p-3 w-12 text-center">No</th>
                            <th class="p-3"><x-sort-link column="id" title="No. Agenda" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" tab="masuk" /></th>
                            <th class="p-3"><x-sort-link column="letter_number" title="Nomor Surat" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" defaultDir="asc" tab="masuk" /></th>
                            <th class="p-3"><x-sort-link column="origin_agency" title="Asal Instansi" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" defaultDir="asc" tab="masuk" /></th>
                            <th class="p-3"><x-sort-link column="letter_date" title="Tgl Surat" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" tab="masuk" /></th>
                            <th class="p-3"><x-sort-link column="received_date" title="Diterima" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" tab="masuk" /></th>
                            <th class="p-3">Perihal / Ringkasan</th>
                            <th class="p-3 text-center">Berkas</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($incomingLetters as $letter)
                            <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="openLetterDetailModal('surat-masuk', {{ $letter->id }})">
                                <td class="p-3 text-center font-mono text-xs text-slate-500">{{ ($incomingLetters->currentPage() - 1) * $incomingLetters->perPage() + $loop->iteration }}</td>
                                <td class="p-3 font-mono text-xs font-semibold text-slate-600">#{{ $letter->id }}</td>
                                <td class="p-3 font-bold text-slate-900">
                                    {{ $letter->letter_number }}
                                    @if ($letter->trashed())
                                        <span class="rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 text-[10px] font-bold ml-1">Terhapus</span>
                                    @endif
                                </td>
                                <td class="p-3 text-slate-700">{{ $letter->origin_agency }}</td>
                                <td class="p-3 text-xs text-slate-600 whitespace-nowrap">{{ $letter->letter_date?->format('d/m/Y') }}</td>
                                <td class="p-3 text-xs text-slate-600 whitespace-nowrap">{{ $letter->received_date?->format('d/m/Y') }}</td>
                                <td class="p-3 text-xs text-slate-600 max-w-xs truncate">{{ $letter->subject }}</td>
                                <td class="p-3 text-center">
                                    @if ($letter->attachments->isNotEmpty())
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Ada</span>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5" onclick="event.stopPropagation()">
                                        @if ($letter->trashed())
                                            <form method="post" action="/surat-masuk/{{ $letter->id }}/restore" class="inline" onsubmit="return confirmAction(event, 'Pulihkan kembali surat masuk {{ $letter->letter_number }}?', 'Konfirmasi Pemulihan Surat', 'emerald', 'Ya, Pulihkan')">
                                                @csrf
                                                <button type="submit" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                                    <x-icon name="rotate-ccw" class="h-3.5 w-3.5 text-emerald-700" />
                                                    <span>Pulihkan</span>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" onclick="openLetterDetailModal('surat-masuk', {{ $letter->id }})" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-slate-300 px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs">
                                                Detail
                                            </button>
                                            @if (in_array(auth()->user()->role, ['super_admin', 'division_admin']))
                                                <button type="button" onclick="openLetterFormModal('surat-masuk', 'edit', {{ json_encode($letter) }})" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                                    Edit
                                                </button>
                                                <button type="button" onclick="openLetterDeleteModal('surat-masuk', {{ $letter->id }}, '{{ addslashes($letter->letter_number) }}')" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-rose-300 bg-rose-50 px-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 shadow-2xs">
                                                    Hapus
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-sm text-slate-500">
                                    <x-icon name="mail" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                                    Tidak ada arsip surat masuk ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <x-per-page-selector :current="$perPage ?? 15" id="masuk" tab="masuk" />
                <div>
                    {{ $incomingLetters->appends(['tab' => 'masuk', 'status' => $status, 'q' => request('q'), 'date_from' => request('date_from'), 'date_to' => request('date_to'), 'sort' => request('sort'), 'dir' => request('dir'), 'per_page' => $perPage ?? 15])->links() }}
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- PANEL 2: SURAT KELUAR                                          -->
        <!-- ============================================================== -->
        <div id="panel-keluar" class="{{ $tab === 'keluar' ? '' : 'hidden' }}">
            <!-- Mobile Viewport: Card Summary -->
            <div class="space-y-3 md:hidden">
                @forelse ($outgoingLetters as $letter)
                    <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-xs space-y-2.5 hover:border-blue-300 transition-colors cursor-pointer" onclick="openLetterDetailModal('surat-keluar', {{ $letter->id }})">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2">
                            <div>
                                <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700">No. {{ ($outgoingLetters->currentPage() - 1) * $outgoingLetters->perPage() + $loop->iteration }} &bull; Reg #{{ $letter->id }}</span>
                                <span class="rounded bg-blue-50 border border-blue-200 px-1.5 py-0.5 text-[10px] font-bold text-blue-800 ml-1">{{ $letter->division?->code }}</span>
                                @if ($letter->trashed())
                                    <span class="rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 text-[10px] font-bold ml-1">Terhapus</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400 font-medium">Diseminasi: {{ $letter->disseminated_date?->format('d/m/Y') }}</span>
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-slate-900 leading-snug">{{ $letter->letter_number }}</h2>
                            <p class="text-xs font-medium text-slate-600 mt-0.5">Tahun Buku: <strong class="text-slate-800">{{ $letter->letter_year }}</strong></p>
                        </div>
                        <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed bg-slate-50 p-2 rounded border border-slate-100">{{ $letter->subject }}</p>
                        
                        <div class="flex items-center justify-between border-t border-slate-100 pt-2.5">
                            <span class="text-[11px] text-slate-400 font-mono">Tgl Surat: {{ $letter->letter_date?->format('d/m/Y') }}</span>
                            <div class="flex items-center gap-1.5">
                                @if ($letter->trashed())
                                    <form method="post" action="/surat-keluar/{{ $letter->id }}/restore" class="inline" onsubmit="return confirmAction(event, 'Pulihkan kembali surat keluar {{ $letter->letter_number }}?', 'Konfirmasi Pemulihan Surat', 'emerald', 'Ya, Pulihkan')">
                                        @csrf
                                        <button type="submit" onclick="event.stopPropagation()" class="inline-flex min-h-[32px] items-center gap-1 rounded border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                            <x-icon name="rotate-ccw" class="h-3.5 w-3.5 text-emerald-700" />
                                            <span>Pulihkan</span>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" onclick="event.stopPropagation(); openLetterDetailModal('surat-keluar', {{ $letter->id }})" class="inline-flex min-h-[32px] items-center gap-1 rounded border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs">
                                        Detail
                                    </button>
                                    @if (in_array(auth()->user()->role, ['super_admin', 'division_admin']))
                                        <button type="button" onclick="event.stopPropagation(); openLetterFormModal('surat-keluar', 'edit', {{ json_encode($letter) }})" class="inline-flex min-h-[32px] items-center gap-1 rounded border border-blue-300 bg-blue-50 px-2.5 text-xs font-semibold text-blue-800 hover:bg-blue-100 shadow-2xs">
                                            Edit
                                        </button>
                                        <button type="button" onclick="event.stopPropagation(); openLetterDeleteModal('surat-keluar', {{ $letter->id }}, '{{ addslashes($letter->letter_number) }}')" class="inline-flex min-h-[32px] items-center gap-1 rounded border border-rose-300 bg-rose-50 px-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 shadow-2xs">
                                            Hapus
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                        <x-icon name="mail-outgoing" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                        Tidak ada arsip surat keluar ditemukan.
                    </div>
                @endforelse
            </div>

            <!-- Desktop Viewport: Dense Grid -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[1024px] border-collapse bg-white text-sm shadow-xs rounded-lg border border-slate-200">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                            <th class="p-3 w-12 text-center">No</th>
                            <th class="p-3"><x-sort-link column="id" title="No. Agenda" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" tab="keluar" /></th>
                            <th class="p-3"><x-sort-link column="letter_number" title="Nomor Surat" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" defaultDir="asc" tab="keluar" /></th>
                            <th class="p-3"><x-sort-link column="letter_year" title="Tahun" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" tab="keluar" /></th>
                            <th class="p-3"><x-sort-link column="letter_date" title="Tgl Surat" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" tab="keluar" /></th>
                            <th class="p-3"><x-sort-link column="disseminated_date" title="Diseminasi" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" tab="keluar" /></th>
                            <th class="p-3">Perihal / Ringkasan</th>
                            <th class="p-3 text-center">Berkas</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($outgoingLetters as $letter)
                            <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="openLetterDetailModal('surat-keluar', {{ $letter->id }})">
                                <td class="p-3 text-center font-mono text-xs text-slate-500">{{ ($outgoingLetters->currentPage() - 1) * $outgoingLetters->perPage() + $loop->iteration }}</td>
                                <td class="p-3 font-mono text-xs font-semibold text-slate-600">#{{ $letter->id }}</td>
                                <td class="p-3 font-bold text-slate-900">
                                    {{ $letter->letter_number }}
                                    @if ($letter->trashed())
                                        <span class="rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 text-[10px] font-bold ml-1">Terhapus</span>
                                    @endif
                                </td>
                                <td class="p-3 text-xs font-semibold text-slate-700">{{ $letter->letter_year }}</td>
                                <td class="p-3 text-xs text-slate-600 whitespace-nowrap">{{ $letter->letter_date?->format('d/m/Y') }}</td>
                                <td class="p-3 text-xs text-slate-600 whitespace-nowrap">{{ $letter->disseminated_date?->format('d/m/Y') }}</td>
                                <td class="p-3 text-xs text-slate-600 max-w-xs truncate">{{ $letter->subject }}</td>
                                <td class="p-3 text-center">
                                    @if ($letter->attachments->isNotEmpty())
                                        <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">Ada</span>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5" onclick="event.stopPropagation()">
                                        @if ($letter->trashed())
                                            <form method="post" action="/surat-keluar/{{ $letter->id }}/restore" class="inline" onsubmit="return confirmAction(event, 'Pulihkan kembali surat keluar {{ $letter->letter_number }}?', 'Konfirmasi Pemulihan Surat', 'emerald', 'Ya, Pulihkan')">
                                                @csrf
                                                <button type="submit" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                                    <x-icon name="rotate-ccw" class="h-3.5 w-3.5 text-emerald-700" />
                                                    <span>Pulihkan</span>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" onclick="openLetterDetailModal('surat-keluar', {{ $letter->id }})" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-slate-300 px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs">
                                                Detail
                                            </button>
                                            @if (in_array(auth()->user()->role, ['super_admin', 'division_admin']))
                                                <button type="button" onclick="openLetterFormModal('surat-keluar', 'edit', {{ json_encode($letter) }})" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-blue-300 bg-blue-50 px-2.5 text-xs font-semibold text-blue-800 hover:bg-blue-100 shadow-2xs">
                                                    Edit
                                                </button>
                                                <button type="button" onclick="openLetterDeleteModal('surat-keluar', {{ $letter->id }}, '{{ addslashes($letter->letter_number) }}')" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-rose-300 bg-rose-50 px-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 shadow-2xs">
                                                    Hapus
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-sm text-slate-500">
                                    <x-icon name="mail-outgoing" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                                    Tidak ada arsip surat keluar ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <x-per-page-selector :current="$perPage ?? 15" id="keluar" tab="keluar" />
                <div>
                    {{ $outgoingLetters->appends(['tab' => 'keluar', 'status' => $status, 'q' => request('q'), 'date_from' => request('date_from'), 'date_to' => request('date_to'), 'sort' => request('sort'), 'dir' => request('dir'), 'per_page' => $perPage ?? 15])->links() }}
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- PANEL 3: BUKU EKSPEDISI PIAGAM                                 -->
        <!-- ============================================================== -->
        <div id="panel-piagam" class="{{ $tab === 'piagam' ? '' : 'hidden' }}">
            <!-- Mobile Viewport: Card Summary -->
            <div class="space-y-3 md:hidden">
                @forelse ($piagams as $piagam)
                    <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-xs space-y-2.5 hover:border-teal-300 transition-colors cursor-pointer" onclick="openPiagamDetailModal({{ $piagam->id }})">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2">
                            <div>
                                <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700">No. {{ ($piagams->currentPage() - 1) * $piagams->perPage() + $loop->iteration }} &bull; Resi #{{ $piagam->id }}</span>
                                <span class="rounded bg-teal-50 border border-teal-200 px-1.5 py-0.5 text-[10px] font-bold text-teal-800 ml-1">{{ $piagam->division?->code }}</span>
                                <span class="rounded bg-slate-100 border border-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-700 ml-1">{{ $piagam->document_type_display_name }}</span>
                            </div>
                            <span class="text-xs text-slate-400 font-mono">{{ $piagam->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-slate-900 leading-snug">
                                {{ $piagam->piagam_description ?: $piagam->student_name }}
                                @if ($piagam->trashed())
                                    <span class="rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 text-[10px] font-bold ml-1">Terhapus</span>
                                @endif
                            </h2>
                            <p class="text-xs font-medium text-slate-600 mt-0.5">Asal: <strong class="text-slate-800">{{ $piagam->unit_display_name }}</strong></p>
                        </div>
                        <div class="rounded-md bg-slate-50 p-2.5 text-xs text-slate-700 space-y-0.5 border border-slate-100">
                            <p><strong>Pengambil:</strong> {{ $piagam->recipient_name ?? '-' }}</p>
                            <p class="text-slate-500"><strong>Jabatan:</strong> {{ $piagam->recipient_position ?? '-' }} ({{ $piagam->recipient_unit ?? $piagam->school_origin ?? '-' }})</p>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-2.5">
                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                @if ($piagam->attachments->isNotEmpty())
                                    <x-icon name="check" class="h-4 w-4 text-teal-600" />
                                    <span class="font-medium text-teal-700">Ada TTD Digital</span>
                                @else
                                    <span class="text-slate-400">Tanpa TTD</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if ($piagam->trashed())
                                    <form method="post" action="/piagam/{{ $piagam->id }}/restore" class="inline" onsubmit="return confirmAction(event, 'Pulihkan kembali piagam ini?', 'Konfirmasi Pemulihan Piagam', 'emerald', 'Ya, Pulihkan')">
                                        @csrf
                                        <button type="submit" class="inline-flex min-h-[32px] items-center gap-1 rounded-md border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                            <x-icon name="rotate-ccw" class="h-3.5 w-3.5 text-emerald-700" />
                                            <span>Pulihkan</span>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" onclick="event.stopPropagation(); openPiagamDetailModal({{ $piagam->id }})" class="inline-flex min-h-[32px] items-center gap-1 rounded-md border border-teal-300 bg-teal-50 px-3 text-xs font-semibold text-teal-800 hover:bg-teal-100 shadow-2xs">
                                        <x-icon name="signature" class="h-3.5 w-3.5 text-teal-700" />
                                        Detail & TTD &rarr;
                                    </button>
                                    @if (in_array(auth()->user()->role, ['super_admin', 'division_admin']))
                                        <button type="button" onclick="event.stopPropagation(); openLetterDeleteModal('piagam', {{ $piagam->id }}, '{{ addslashes($piagam->piagam_description ?: $piagam->student_name) }}')" class="inline-flex min-h-[32px] items-center gap-1 rounded-md border border-rose-300 bg-rose-50 px-2.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 shadow-2xs">
                                            Hapus
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                        <x-icon name="signature" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                        Tidak ada catatan bukti serah terima piagam ditemukan.
                    </div>
                @endforelse
            </div>

            <!-- Desktop Viewport: Dense Grid -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[1024px] border-collapse bg-white text-sm shadow-xs rounded-lg border border-slate-200">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                            <th class="p-3 w-12 text-center">No</th>
                            <th class="p-3"><x-sort-link column="created_at" title="Waktu Pencatatan" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" tab="piagam" /></th>
                            <th class="p-3"><x-sort-link column="document_type_name" title="Jenis Dokumen" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" defaultDir="asc" tab="piagam" /></th>
                            <th class="p-3"><x-sort-link column="student_name" title="Keterangan Piagam / Lomba" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" defaultDir="asc" tab="piagam" /></th>
                            <th class="p-3">Asal Sekolah / Instansi</th>
                            <th class="p-3"><x-sort-link column="recipient_name" title="Nama Pengambil Fisik" :currentSort="$sort ?? 'id'" :currentDir="$dir ?? 'desc'" defaultDir="asc" tab="piagam" /></th>
                            <th class="p-3">Jabatan / Unit Kerja</th>
                            <th class="p-3">Divisi</th>
                            <th class="p-3 text-center">Tanda Tangan</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($piagams as $piagam)
                            <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="openPiagamDetailModal({{ $piagam->id }})">
                                <td class="p-3 text-center font-mono text-xs text-slate-500">{{ ($piagams->currentPage() - 1) * $piagams->perPage() + $loop->iteration }}</td>
                                <td class="p-3 font-mono text-xs text-slate-600 whitespace-nowrap">{{ $piagam->created_at->format('d/m/Y H:i') }}</td>
                                <td class="p-3 whitespace-nowrap">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 border border-slate-200">
                                        {{ $piagam->document_type_display_name }}
                                    </span>
                                </td>
                                <td class="p-3 font-medium text-slate-900 max-w-xs">
                                    {{ $piagam->piagam_description ?: $piagam->student_name }}
                                    @if ($piagam->trashed())
                                        <span class="rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 text-[10px] font-bold ml-1">Terhapus</span>
                                    @endif
                                </td>
                                <td class="p-3 text-slate-700">{{ $piagam->unit_display_name }}</td>
                                <td class="p-3 font-semibold text-slate-800">{{ $piagam->recipient_name ?? '-' }}</td>
                                <td class="p-3 text-xs text-slate-600">
                                    {{ $piagam->recipient_position ?? '-' }}
                                    @if($piagam->recipient_unit && $piagam->recipient_unit !== $piagam->school_origin)
                                        <div class="text-[11px] text-slate-400">({{ $piagam->recipient_unit }})</div>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex rounded-xs bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $piagam->division?->code }}</span>
                                </td>
                                <td class="p-3 text-center">
                                    @php $sig = $piagam->attachments->first(); @endphp
                                    @if ($sig && ! $sig->is_missing)
                                        <button type="button" onclick="event.stopPropagation(); openPiagamDetailModal({{ $piagam->id }})" class="inline-flex items-center gap-1 rounded-md border border-teal-200 bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-800 hover:bg-teal-100">
                                            <x-icon name="signature" class="h-3.5 w-3.5 text-teal-700" />
                                            Lihat TTD
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5" onclick="event.stopPropagation()">
                                        @if ($piagam->trashed())
                                            <form method="post" action="/piagam/{{ $piagam->id }}/restore" class="inline" onsubmit="return confirmAction(event, 'Pulihkan kembali piagam ini?', 'Konfirmasi Pemulihan Piagam', 'emerald', 'Ya, Pulihkan')">
                                                @csrf
                                                <button type="submit" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                                    <x-icon name="rotate-ccw" class="h-3.5 w-3.5 text-emerald-700" />
                                                    <span>Pulihkan</span>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" onclick="openPiagamDetailModal({{ $piagam->id }})" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs">
                                                Detail
                                            </button>
                                            @if (in_array(auth()->user()->role, ['super_admin', 'division_admin']))
                                                <button type="button" onclick="openLetterDeleteModal('piagam', {{ $piagam->id }}, '{{ addslashes($piagam->piagam_description ?: $piagam->student_name) }}')" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-rose-300 bg-rose-50 px-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 shadow-2xs">
                                                    Hapus
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="p-8 text-center text-sm text-slate-500">
                                    <x-icon name="signature" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                                    Tidak ada catatan bukti serah terima piagam ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <x-per-page-selector :current="$perPage ?? 15" id="piagam" tab="piagam" />
                <div>
                    {{ $piagams->appends(['tab' => 'piagam', 'status' => $status, 'q' => request('q'), 'date_from' => request('date_from'), 'date_to' => request('date_to'), 'sort' => request('sort'), 'dir' => request('dir'), 'per_page' => $perPage ?? 15])->links() }}
                </div>
            </div>
        </div>

    </section>

    <!-- Modal Shared: Live Document Viewer & CRUD Form -->
    <x-letters.split-modal-detail />
    <x-letters.form-modal />
    <x-letters.delete-modal />
    <x-piagam.split-modal-detail />

    <!-- JavaScript Zero-Reload Interactive Tab Switcher -->
    <script>
        let currentWorkspaceTab = '{{ $tab }}';

        function switchWorkspaceTab(tab) {
            currentWorkspaceTab = tab;

            const panelMasuk = document.getElementById('panel-masuk');
            const panelKeluar = document.getElementById('panel-keluar');
            const panelPiagam = document.getElementById('panel-piagam');

            const tabBtnMasuk = document.getElementById('tab-btn-masuk');
            const tabBtnKeluar = document.getElementById('tab-btn-keluar');
            const tabBtnPiagam = document.getElementById('tab-btn-piagam');

            const btnActionMasuk = document.getElementById('btn-action-masuk');
            const btnActionKeluar = document.getElementById('btn-action-keluar');
            const btnActionPiagam = document.getElementById('btn-action-piagam');

            const btnExportCsv = document.getElementById('btn-export-csv');
            const btnExportPdf = document.getElementById('btn-export-pdf');
            const formActiveTab = document.getElementById('form-active-tab-input');

            // Reset all panels
            panelMasuk.classList.add('hidden');
            panelKeluar.classList.add('hidden');
            panelPiagam.classList.add('hidden');

            // Reset tab styles
            [tabBtnMasuk, tabBtnKeluar, tabBtnPiagam].forEach(btn => {
                btn.className = 'flex items-center gap-2 rounded-md px-4 py-2.5 font-semibold text-slate-600 hover:text-slate-900 transition-all whitespace-nowrap';
            });

            // Reset action buttons
            btnActionMasuk.classList.add('hidden');
            btnActionKeluar.classList.add('hidden');
            btnActionPiagam.classList.add('hidden');

            // Activate chosen tab
            if (tab === 'masuk') {
                panelMasuk.classList.remove('hidden');
                tabBtnMasuk.className = 'flex items-center gap-2 rounded-md px-4 py-2.5 font-bold transition-all whitespace-nowrap bg-emerald-700 text-white shadow-xs';
                btnActionMasuk.classList.remove('hidden');
                btnExportCsv.href = '/api/v1/reports/export.csv?type=incoming';
                btnExportPdf.href = '/api/v1/reports/export.pdf?type=incoming';
            } else if (tab === 'keluar') {
                panelKeluar.classList.remove('hidden');
                tabBtnKeluar.className = 'flex items-center gap-2 rounded-md px-4 py-2.5 font-bold transition-all whitespace-nowrap bg-blue-700 text-white shadow-xs';
                btnActionKeluar.classList.remove('hidden');
                btnExportCsv.href = '/api/v1/reports/export.csv?type=outgoing';
                btnExportPdf.href = '/api/v1/reports/export.pdf?type=outgoing';
            } else if (tab === 'piagam') {
                panelPiagam.classList.remove('hidden');
                tabBtnPiagam.className = 'flex items-center gap-2 rounded-md px-4 py-2.5 font-bold transition-all whitespace-nowrap bg-teal-700 text-white shadow-xs';
                btnActionPiagam.classList.remove('hidden');
                btnExportCsv.href = '/api/v1/reports/export.csv?type=piagam';
                btnExportPdf.href = '/api/v1/reports/export.pdf?type=piagam';
            }

            if (formActiveTab) {
                formActiveTab.value = tab;
            }

            // Sync status toggle links (Arsip Aktif / Kotak Sampah)
            const statusActive = document.getElementById('status-link-active');
            if (statusActive) {
                const u = new URL(statusActive.href, window.location.origin);
                u.searchParams.set('tab', tab);
                statusActive.href = u.toString();
            }
            const statusTrashed = document.getElementById('status-link-trashed');
            if (statusTrashed) {
                const u = new URL(statusTrashed.href, window.location.origin);
                u.searchParams.set('tab', tab);
                statusTrashed.href = u.toString();
            }

            // Sync reset filter & clear all filters buttons
            const btnReset = document.getElementById('btn-reset-filter');
            if (btnReset) {
                const u = new URL(btnReset.href, window.location.origin);
                u.searchParams.set('tab', tab);
                btnReset.href = u.toString();
            }
            const btnClear = document.getElementById('btn-clear-all-filters');
            if (btnClear) {
                const u = new URL(btnClear.href, window.location.origin);
                u.searchParams.set('tab', tab);
                btnClear.href = u.toString();
            }

            // Sync filter chips
            document.querySelectorAll('[data-filter-chip]').forEach(chip => {
                const u = new URL(chip.href, window.location.origin);
                u.searchParams.set('tab', tab);
                chip.href = u.toString();
            });

            // Update URL without page reload
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', tab);
            window.history.replaceState({}, '', currentUrl.toString());
        }

        function handlePerPageChange(select) {
            const targetTab = select.getAttribute('data-per-page-tab')
                || (select.closest('#panel-keluar') ? 'keluar' : '')
                || (select.closest('#panel-piagam') ? 'piagam' : '')
                || (select.closest('#panel-masuk') ? 'masuk' : '')
                || currentWorkspaceTab;

            const u = new URL(select.value, window.location.origin);
            if (targetTab) {
                u.searchParams.set('tab', targetTab);
            }
            u.searchParams.delete('page');
            u.searchParams.delete('masuk_page');
            u.searchParams.delete('keluar_page');
            u.searchParams.delete('piagam_page');
            window.location.href = u.toString();
        }

        // Global click interceptor to guarantee sorting headers always carry correct tab and reset pagination
        document.addEventListener('click', function (e) {
            const sortLink = e.target.closest('a[data-sort-link]');
            if (!sortLink) return;

            const targetTab = sortLink.getAttribute('data-sort-link')
                || (sortLink.closest('#panel-keluar') ? 'keluar' : '')
                || (sortLink.closest('#panel-piagam') ? 'piagam' : '')
                || (sortLink.closest('#panel-masuk') ? 'masuk' : '')
                || currentWorkspaceTab;

            if (targetTab) {
                const u = new URL(sortLink.href, window.location.origin);
                u.searchParams.set('tab', targetTab);
                u.searchParams.delete('page');
                u.searchParams.delete('masuk_page');
                u.searchParams.delete('keluar_page');
                u.searchParams.delete('piagam_page');
                sortLink.href = u.toString();
            }
        });

        function toggleMobileFilterPanel() {
            const panel = document.getElementById('workspace-date-filter-panel');
            if (panel) {
                panel.classList.toggle('hidden');
            }
        }

        // Initialize active tab from query string or default
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('tab') || '{{ $tab }}';
            if (['masuk', 'keluar', 'piagam'].includes(initialTab)) {
                switchWorkspaceTab(initialTab);
            }
        });
    </script>
</x-layouts.app>
