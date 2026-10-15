<x-layouts.app title="Pencarian Pintar & Laporan Arsip">
    <section class="px-4 py-4 md:px-8">
        <!-- Header -->
        <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Pencarian & Rekapitulasi</p>
                <h1 class="text-xl font-bold text-slate-900">Pencarian Terpadu & Ekspor Laporan Resmi</h1>
                <p class="text-xs text-slate-500">Cari seluruh arsip surat masuk, surat keluar, dan piagam secara terpadu dalam satu aliran berkas.</p>
            </div>
            @php
                $queryString = http_build_query(request()->query());
            @endphp
            <div class="flex flex-wrap items-center gap-2">
                <a href="/api/v1/reports/export.csv?{{ $queryString }}" onclick="handleExportClick(this, 'Menyiapkan CSV...')" class="flex min-h-[40px] items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 shadow-xs" title="Unduh data dalam format CSV Spreadsheet">
                    <x-icon name="download" class="h-4 w-4 text-emerald-700" />
                    Unduh Rekap CSV
                </a>
                <a href="/api/v1/reports/export.pdf?{{ $queryString }}" target="_blank" onclick="handleExportClick(this, 'Menyiapkan PDF...')" class="flex min-h-[40px] items-center gap-1.5 rounded-md bg-emerald-700 px-3.5 text-xs font-semibold text-white hover:bg-emerald-800 shadow-xs" title="Cetak atau unduh laporan resmi format PDF">
                    <x-icon name="file-text" class="h-4 w-4" />
                    Cetak Laporan PDF
                </a>
            </div>
        </header>

        <!-- Advanced Search & Filter Form -->
        <form method="get" action="/laporan" class="mb-6 rounded-lg border border-slate-200 bg-white p-4 shadow-xs">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <!-- Keyword input -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-slate-700">Kata Kunci Pencarian</label>
                    <div class="mt-1 flex items-center rounded-md border border-slate-300 px-3 focus-within:border-emerald-700 bg-white">
                        <x-icon name="search" class="h-4 w-4 text-slate-400 shrink-0" />
                        <input name="q" value="{{ request('q') }}" class="min-h-[38px] w-full border-0 bg-transparent text-xs focus:outline-hidden" placeholder="Nomor surat, perihal, asal sekolah, nama siswa, atau pengambil...">
                    </div>
                </div>

                <!-- Type Selector -->
                <div>
                    <label class="block text-xs font-medium text-slate-700">Jenis Administrasi</label>
                    <select name="type" class="mt-1 min-h-[38px] w-full rounded-md border border-slate-300 bg-white px-2.5 text-xs text-slate-800 focus:border-emerald-700 focus:outline-hidden">
                        <option value="all" @selected(request('type', 'all') === 'all')>Semua Dokumen</option>
                        <option value="incoming" @selected(request('type') === 'incoming')>Surat Masuk Saja</option>
                        <option value="outgoing" @selected(request('type') === 'outgoing')>Surat Keluar Saja</option>
                        <option value="piagam" @selected(request('type') === 'piagam')>Piagam Penghargaan Saja</option>
                    </select>
                </div>

                <!-- Date Range From -->
                <div>
                    <label class="block text-xs font-medium text-slate-700">Rentang Dari</label>
                    <input name="date_from" type="date" value="{{ request('date_from') }}" class="mt-1 min-h-[38px] w-full rounded-md border border-slate-300 px-2.5 text-xs text-slate-800 focus:border-emerald-700 focus:outline-hidden">
                </div>

                <!-- Date Range To -->
                <div>
                    <label class="block text-xs font-medium text-slate-700">Sampai Dengan</label>
                    <input name="date_to" type="date" value="{{ request('date_to') }}" class="mt-1 min-h-[38px] w-full rounded-md border border-slate-300 px-2.5 text-xs text-slate-800 focus:border-emerald-700 focus:outline-hidden">
                </div>
            </div>

            <!-- Action buttons -->
            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-[11px] text-slate-500">Pencarian terpadu menyaring seluruh surat masuk, surat keluar, dan piagam.</span>
                <div class="flex items-center gap-2">
                    @if (request()->hasAny(['q', 'type', 'date_from', 'date_to', 'year']) && (request('type') !== 'all' || request()->hasAny(['q', 'date_from', 'date_to', 'year'])))
                        <a href="/laporan" class="inline-flex min-h-[34px] items-center rounded-md border border-slate-300 px-3 text-xs font-medium text-slate-600 hover:bg-slate-50">
                            Reset Filter
                        </a>
                    @endif
                    <button type="submit" class="inline-flex min-h-[34px] items-center gap-1.5 rounded-md bg-slate-900 px-4 text-xs font-semibold text-white hover:bg-slate-800 shadow-xs">
                        <x-icon name="filter" class="h-3.5 w-3.5" />
                        Terapkan Filter
                    </button>
                </div>
            </div>

            @php
                $hasActiveFilters = request()->filled('q') || (request()->filled('type') && request('type') !== 'all') || request()->filled('date_from') || request()->filled('date_to');
            @endphp
            @if ($hasActiveFilters)
                <!-- Active Filter Chips (UX-011) -->
                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-xs">
                    <span class="text-xs font-semibold text-slate-500">Filter Aktif:</span>
                    @if (request()->filled('q'))
                        <a href="/laporan?{{ http_build_query(request()->except('q')) }}" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                            <span>Kata Kunci: "{{ request('q') }}"</span>
                            <span class="font-bold text-slate-400 hover:text-slate-700">&times;</span>
                        </a>
                    @endif
                    @if (request()->filled('type') && request('type') !== 'all')
                        @php
                            $typeLabels = [
                                'incoming' => 'Surat Masuk Saja',
                                'outgoing' => 'Surat Keluar Saja',
                                'piagam' => 'Piagam Penghargaan Saja',
                            ];
                        @endphp
                        <a href="/laporan?{{ http_build_query(request()->except('type')) }}" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800 hover:bg-emerald-200">
                            <span>Jenis: {{ $typeLabels[request('type')] ?? request('type') }}</span>
                            <span class="font-bold text-emerald-500 hover:text-emerald-800">&times;</span>
                        </a>
                    @endif
                    @if (request()->filled('date_from'))
                        <a href="/laporan?{{ http_build_query(request()->except('date_from')) }}" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                            <span>Dari: {{ date('d/m/Y', strtotime(request('date_from'))) }}</span>
                            <span class="font-bold text-slate-400 hover:text-slate-700">&times;</span>
                        </a>
                    @endif
                    @if (request()->filled('date_to'))
                        <a href="/laporan?{{ http_build_query(request()->except('date_to')) }}" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                            <span>Sampai: {{ date('d/m/Y', strtotime(request('date_to'))) }}</span>
                            <span class="font-bold text-slate-400 hover:text-slate-700">&times;</span>
                        </a>
                    @endif
                    <a href="/laporan" class="ml-auto text-xs font-semibold text-emerald-700 hover:underline">
                        Hapus Semua Filter
                    </a>
                </div>
            @endif
        </form>

        <!-- Results Stream Toolbar & Dual-View Mode Switcher (Pure SVG, Zero OS Symbols) -->
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between rounded-lg border border-slate-200 bg-white px-4 py-2.5 shadow-xs">
            <div class="flex items-center gap-2 text-xs text-slate-600">
                <x-icon name="layers" class="h-4 w-4 text-emerald-700 shrink-0" />
                <span>Ditemukan <strong class="font-bold text-slate-900">{{ $totalResults }}</strong> arsip dokumen terpadu</span>
            </div>

            <div class="flex items-center gap-1 self-end sm:self-auto">
                <span class="text-[11px] text-slate-400 mr-1.5 font-medium">Tampilan:</span>
                
                <!-- Button Grid View -->
                <button type="button" id="btn-view-grid" onclick="setSearchViewMode('grid')" class="flex h-8 items-center gap-1.5 rounded px-2.5 text-xs font-semibold transition-all bg-emerald-700 text-white shadow-2xs" title="Tampilan Kisi Kartu (Grid View)">
                    <x-icon name="layout-grid" class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">Grid</span>
                </button>

                <!-- Button List View -->
                <button type="button" id="btn-view-list" onclick="setSearchViewMode('list')" class="flex h-8 items-center gap-1.5 rounded px-2.5 text-xs font-semibold transition-all text-slate-600 hover:bg-slate-100" title="Tampilan Daftar Rapat (List View)">
                    <x-icon name="list" class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">List</span>
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SINGLE UNIFIED FEED (SEMUANYA TERCAMPUR 1 ALIRAN TANPA SEKAT) -->
        <!-- ============================================================== -->
        @if ($totalResults === 0)
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500 shadow-xs">
                <x-icon name="search" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                <p class="text-sm font-semibold text-slate-700">Tidak ada arsip dokumen yang sesuai.</p>
                <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci lain atau sesuaikan rentang tanggal.</p>
            </div>
        @else
            <!-- 1. GRID VIEW MODE (KARTU MODERN RESPONSIF) -->
            <div id="search-grid-view" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($unifiedResults as $item)
                    <article class="group rounded-xl border border-slate-200 bg-white p-4 shadow-xs transition-all hover:border-emerald-400 hover:shadow-md cursor-pointer flex flex-col justify-between" onclick="handleSearchDetailClick('{{ $item['modal_type'] }}', {{ $item['raw_id'] }})">
                        <div>
                            <!-- Card Header: Badge Jenis Dokumen & Tanggal -->
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5 mb-2.5">
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[11px] font-bold {{ $item['badge_class'] }}">
                                    <x-icon name="{{ $item['icon_name'] }}" class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{ $item['type_label'] }}</span>
                                </span>
                                <span class="text-[11px] font-mono text-slate-400">{{ $item['date_formatted'] }}</span>
                            </div>

                            <!-- Card Body: Judul & Asal/Pihak -->
                            <h2 class="font-bold text-sm text-slate-900 group-hover:text-emerald-700 transition-colors leading-snug">
                                {{ $item['title'] }}
                            </h2>
                            <p class="mt-1 text-xs text-slate-600 font-medium line-clamp-1">
                                {{ $item['origin_or_party'] }}
                            </p>

                            <!-- Perihal / Keterangan Snippet -->
                            <p class="mt-2 text-xs text-slate-500 line-clamp-2 leading-relaxed bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                {{ $item['subject'] }}
                            </p>
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-3.5 flex items-center justify-between border-t border-slate-100 pt-2.5">
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-mono font-semibold text-slate-600">{{ $item['extra_info'] }}</span>
                                @if ($item['has_attachment'])
                                    <span class="inline-flex items-center gap-1 rounded bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800">
                                        <x-icon name="file-text" class="h-3 w-3" /> Berkas
                                    </span>
                                @endif
                            </div>
                            <button type="button" onclick="event.stopPropagation(); handleSearchDetailClick('{{ $item['modal_type'] }}', {{ $item['raw_id'] }})" class="inline-flex min-h-[30px] items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-700 group-hover:border-emerald-600 group-hover:text-emerald-800 shadow-2xs">
                                <x-icon name="eye" class="h-3.5 w-3.5" />
                                <span>Detail</span>
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- 2. LIST VIEW MODE (BARIS DAFTAR RAPAT & RINGKAS) -->
            <div id="search-list-view" class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/80 font-bold uppercase tracking-wider text-slate-600">
                                <th class="p-3">Jenis Dokumen</th>
                                <th class="p-3">Nomor / Identitas Berkas</th>
                                <th class="p-3">Asal / Instansi / Pihak</th>
                                <th class="p-3">Tanggal</th>
                                <th class="p-3">Perihal / Keterangan</th>
                                <th class="p-3 text-center">Berkas</th>
                                <th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($unifiedResults as $item)
                                <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="handleSearchDetailClick('{{ $item['modal_type'] }}', {{ $item['raw_id'] }})">
                                    <td class="p-3 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[11px] font-bold {{ $item['badge_class'] }}">
                                            <x-icon name="{{ $item['icon_name'] }}" class="h-3 w-3 shrink-0" />
                                            <span>{{ $item['type_label'] }}</span>
                                        </span>
                                    </td>
                                    <td class="p-3 font-bold text-slate-900 whitespace-nowrap">
                                        {{ $item['title'] }}
                                        <span class="block text-[10px] font-normal font-mono text-slate-400">{{ $item['extra_info'] }}</span>
                                    </td>
                                    <td class="p-3 font-medium text-slate-700 max-w-[200px] truncate">
                                        {{ $item['origin_or_party'] }}
                                    </td>
                                    <td class="p-3 font-mono text-slate-600 whitespace-nowrap">
                                        {{ $item['date_formatted'] }}
                                    </td>
                                    <td class="p-3 text-slate-500 max-w-xs truncate">
                                        {{ $item['subject'] }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if ($item['has_attachment'])
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Ada</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center whitespace-nowrap">
                                        <button type="button" onclick="event.stopPropagation(); handleSearchDetailClick('{{ $item['modal_type'] }}', {{ $item['raw_id'] }})" class="inline-flex min-h-[28px] items-center gap-1 rounded border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 shadow-2xs">
                                            <x-icon name="eye" class="h-3 w-3" />
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Unified Pagination -->
            <div class="mt-4">
                {{ $unifiedResults->links() }}
            </div>
        @endif
    </section>

    <!-- Modal Shared: In-Place Split Live Viewers (Zero Page Reload) -->
    <x-letters.split-modal-detail />
    <x-piagam.split-modal-detail />

    <!-- JavaScript Dual-View Mode Switcher & Modal Trigger -->
    <script>
        function setSearchViewMode(mode) {
            const gridView = document.getElementById('search-grid-view');
            const listView = document.getElementById('search-list-view');
            const btnGrid = document.getElementById('btn-view-grid');
            const btnList = document.getElementById('btn-view-list');

            if (!gridView || !listView) return;

            if (mode === 'grid') {
                gridView.classList.remove('hidden');
                listView.classList.add('hidden');
                btnGrid.className = 'flex h-8 items-center gap-1.5 rounded px-2.5 text-xs font-semibold transition-all bg-emerald-700 text-white shadow-2xs';
                btnList.className = 'flex h-8 items-center gap-1.5 rounded px-2.5 text-xs font-semibold transition-all text-slate-600 hover:bg-slate-100';
            } else {
                gridView.classList.add('hidden');
                listView.classList.remove('hidden');
                btnList.className = 'flex h-8 items-center gap-1.5 rounded px-2.5 text-xs font-semibold transition-all bg-emerald-700 text-white shadow-2xs';
                btnGrid.className = 'flex h-8 items-center gap-1.5 rounded px-2.5 text-xs font-semibold transition-all text-slate-600 hover:bg-slate-100';
            }

            try {
                localStorage.setItem('sisurat_search_view_mode', mode);
            } catch (e) {}
        }

        function handleSearchDetailClick(modalType, rawId) {
            if (modalType === 'surat-masuk' || modalType === 'surat-keluar') {
                if (typeof openLetterDetailModal === 'function') {
                    openLetterDetailModal(modalType, rawId);
                }
            } else if (modalType === 'piagam') {
                if (typeof openPiagamDetailModal === 'function') {
                    openPiagamDetailModal(rawId);
                }
            }
        }

        // Initialize saved view mode on page load
        document.addEventListener('DOMContentLoaded', function () {
            let savedMode = 'grid';
            try {
                savedMode = localStorage.getItem('sisurat_search_view_mode') || 'grid';
            } catch (e) {}
            setSearchViewMode(savedMode);
        });
    </script>
</x-layouts.app>
