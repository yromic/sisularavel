<x-layouts.app title="Pusat Persuratan & Buku Ekspedisi">
    <section class="px-4 py-4 md:px-8">
        
        <!-- Header Halaman Terpadu -->
        <header class="sticky top-0 z-10 -mx-4 mb-4 flex flex-col gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between md:static md:mx-0 md:rounded-md md:border">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">
                    {{ auth()->user()->division?->code ?? 'LOKET GLOBAL' }} — {{ auth()->user()->division?->name ?? 'Seluruh Bidang' }}
                </p>
                <h1 class="text-xl font-bold text-slate-900">Pusat Persuratan & Buku Ekspedisi</h1>
                <p class="text-xs text-slate-500">Kelola seluruh arsip Surat Masuk, Surat Keluar, dan Buku Ekspedisi Piagam dalam satu konsol terpadu.</p>
            </div>

            <!-- Dynamic Action Toolbar: Menyesuaikan Tab Aktif -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Aksi Tambah Surat Masuk -->
                <button type="button" id="btn-action-masuk" onclick="openLetterFormModal('surat-masuk', 'create')" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-3.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800">
                    <x-icon name="plus" class="h-4 w-4" />
                    + Tambah Surat Masuk
                </button>

                <!-- Aksi Tambah Surat Keluar -->
                <button type="button" id="btn-action-keluar" onclick="openLetterFormModal('surat-keluar', 'create')" class="hidden flex min-h-[44px] items-center gap-2 rounded-md bg-blue-700 px-3.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-800">
                    <x-icon name="plus" class="h-4 w-4" />
                    + Tambah Surat Keluar
                </button>

                <!-- Aksi Tambah Piagam -->
                <a href="/piagam/ambil" id="btn-action-piagam" class="hidden flex min-h-[44px] items-center gap-2 rounded-md bg-teal-700 px-3.5 text-xs font-semibold text-white shadow-xs hover:bg-teal-800">
                    <x-icon name="plus" class="h-4 w-4" />
                    + Catat Pengambilan Piagam
                </a>

                <!-- Ekspor CSV -->
                <a id="btn-export-csv" href="/api/v1/reports/export.csv?type=incoming" class="flex min-h-[44px] items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs">
                    <x-icon name="download" class="h-4 w-4 text-slate-500" />
                    Ekspor CSV
                </a>

                <!-- Cetak PDF -->
                <a id="btn-export-pdf" href="/api/v1/reports/export.pdf?type=incoming" target="_blank" class="flex min-h-[44px] items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs">
                    <x-icon name="file-text" class="h-4 w-4 text-slate-500" />
                    Cetak PDF
                </a>
            </div>
        </header>

        <!-- Segmented Tab Switcher (Touch-First & Zero Reload) -->
        <div class="mb-4 overflow-x-auto pb-1">
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

        <!-- Universal Filter & Search Toolbar -->
        <div class="mb-5 rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs">
            <form method="get" action="/persuratan" id="workspace-search-form" class="grid grid-cols-1 gap-2.5 sm:grid-cols-4">
                <input type="hidden" name="tab" id="form-active-tab-input" value="{{ $tab }}">
                
                <div class="sm:col-span-2">
                    <div class="flex min-h-[42px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3">
                        <x-icon name="search" class="h-4 w-4 text-slate-400 shrink-0" />
                        <input name="q" id="input-search-q" value="{{ request('q') }}" class="min-h-[40px] w-full border-0 bg-transparent text-xs focus:outline-hidden" placeholder="Cari nomor surat, perihal, instansi, atau pengambil...">
                    </div>
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="min-h-[42px] w-full rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-700" title="Dari Tanggal">
                </div>
                <div class="flex items-center gap-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="min-h-[42px] w-full rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-700" title="Sampai Tanggal">
                    <button type="submit" class="min-h-[42px] rounded-md bg-slate-800 px-4 text-xs font-semibold text-white hover:bg-slate-900 shadow-xs">
                        Filter
                    </button>
                    @if (request()->hasAny(['q', 'date_from', 'date_to']))
                        <a href="/persuratan?tab={{ $tab }}" class="min-h-[42px] flex items-center justify-center rounded-md border border-slate-300 px-2.5 text-xs text-slate-600 hover:bg-slate-100" title="Reset filter">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
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
                                <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700">Agenda #{{ $letter->id }}</span>
                                <span class="rounded bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 ml-1">{{ $letter->division?->code }}</span>
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
                            <th class="p-3">Agenda #</th>
                            <th class="p-3">Nomor Surat</th>
                            <th class="p-3">Asal Instansi</th>
                            <th class="p-3">Tgl Surat</th>
                            <th class="p-3">Diterima</th>
                            <th class="p-3">Perihal / Ringkasan</th>
                            <th class="p-3 text-center">Berkas</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($incomingLetters as $letter)
                            <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="openLetterDetailModal('surat-masuk', {{ $letter->id }})">
                                <td class="p-3 font-mono text-xs font-semibold text-slate-600">#{{ $letter->id }}</td>
                                <td class="p-3 font-bold text-slate-900">{{ $letter->letter_number }}</td>
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
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-sm text-slate-500">
                                    <x-icon name="mail" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                                    Tidak ada arsip surat masuk ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $incomingLetters->appends(['tab' => 'masuk'])->links() }}
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
                                <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700">Agenda #{{ $letter->id }}</span>
                                <span class="rounded bg-blue-50 border border-blue-200 px-1.5 py-0.5 text-[10px] font-bold text-blue-800 ml-1">{{ $letter->division?->code }}</span>
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
                            <th class="p-3">Agenda #</th>
                            <th class="p-3">Nomor Surat</th>
                            <th class="p-3">Tahun</th>
                            <th class="p-3">Tgl Surat</th>
                            <th class="p-3">Diseminasi / Share</th>
                            <th class="p-3">Perihal / Ringkasan</th>
                            <th class="p-3 text-center">Berkas</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($outgoingLetters as $letter)
                            <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="openLetterDetailModal('surat-keluar', {{ $letter->id }})">
                                <td class="p-3 font-mono text-xs font-semibold text-slate-600">#{{ $letter->id }}</td>
                                <td class="p-3 font-bold text-slate-900">{{ $letter->letter_number }}</td>
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
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-sm text-slate-500">
                                    <x-icon name="mail-outgoing" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                                    Tidak ada arsip surat keluar ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $outgoingLetters->appends(['tab' => 'keluar'])->links() }}
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
                                <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700">Resi #{{ $piagam->id }}</span>
                                <span class="rounded bg-teal-50 border border-teal-200 px-1.5 py-0.5 text-[10px] font-bold text-teal-800 ml-1">{{ $piagam->division?->code }}</span>
                            </div>
                            <span class="text-xs text-slate-400 font-mono">{{ $piagam->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-slate-900 leading-snug">{{ $piagam->piagam_description ?: $piagam->student_name }}</h2>
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
                            <button type="button" onclick="event.stopPropagation(); openPiagamDetailModal({{ $piagam->id }})" class="inline-flex min-h-[32px] items-center gap-1 rounded-md border border-teal-300 bg-teal-50 px-3 text-xs font-semibold text-teal-800 hover:bg-teal-100 shadow-2xs">
                                <x-icon name="signature" class="h-3.5 w-3.5 text-teal-700" />
                                Detail & TTD &rarr;
                            </button>
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
                            <th class="p-3">Waktu Pencatatan</th>
                            <th class="p-3">Keterangan Piagam / Lomba</th>
                            <th class="p-3">Asal Sekolah / Instansi</th>
                            <th class="p-3">Nama Pengambil Fisik</th>
                            <th class="p-3">Jabatan / Unit Kerja</th>
                            <th class="p-3">Divisi</th>
                            <th class="p-3 text-center">Tanda Tangan</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($piagams as $piagam)
                            <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="openPiagamDetailModal({{ $piagam->id }})">
                                <td class="p-3 font-mono text-xs text-slate-600 whitespace-nowrap">{{ $piagam->created_at->format('d/m/Y H:i') }}</td>
                                <td class="p-3 font-medium text-slate-900 max-w-xs">{{ $piagam->piagam_description ?: $piagam->student_name }}</td>
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
                                    <button type="button" onclick="event.stopPropagation(); openPiagamDetailModal({{ $piagam->id }})" class="inline-flex min-h-[30px] items-center gap-1 rounded border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-sm text-slate-500">
                                    <x-icon name="signature" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                                    Tidak ada catatan bukti serah terima piagam ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $piagams->appends(['tab' => 'piagam'])->links() }}
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

            // Update URL without page reload
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('tab', tab);
            window.history.replaceState({}, '', currentUrl);
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
