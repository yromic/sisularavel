<x-layouts.app title="Buku Ekspedisi Pengambilan Piagam">
    <section class="px-4 py-4 md:px-8">
        <x-page-header
            :badge="auth()->user()->division?->code ? auth()->user()->division->code . ' — ' . auth()->user()->division->name : 'Semua Divisi (Konteks Global)'"
            meta="Buku Ekspedisi"
            title="Buku Ekspedisi Pengambilan Piagam"
            subtitle="Catatan resmi bukti serah terima fisik piagam penghargaan beserta tanda tangan pengambil."
        >
            <x-slot:actions>
                <a href="/piagam/ambil" class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>+ Catat Pengambilan Baru</span>
                </a>
                <a href="/api/v1/reports/export.csv?type=piagam" class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="download" class="h-4 w-4 text-slate-500" />
                    <span>Ekspor CSV</span>
                </a>
                <a href="/api/v1/reports/export.pdf?type=piagam" target="_blank" class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="file-text" class="h-4 w-4 text-slate-500" />
                    <span>Cetak PDF</span>
                </a>
            </x-slot:actions>
        </x-page-header>

        @if(session('success'))
            <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900 shadow-xs">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filter & Search Toolbar -->
        <div class="mb-5 rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs">
            <form method="get" action="/piagam" class="grid grid-cols-1 gap-2.5 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <div class="flex min-h-[42px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3">
                        <x-icon name="search" class="h-4 w-4 text-slate-400 shrink-0" />
                        <input name="q" value="{{ request('q') }}" class="min-h-[40px] w-full border-0 bg-transparent text-xs focus:outline-hidden" placeholder="Cari nama piagam, nama siswa, sekolah, atau pengambil...">
                    </div>
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="min-h-[42px] w-full rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-700" title="Dari Tanggal">
                </div>
                <div class="flex items-center gap-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="min-h-[42px] w-full rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-700" title="Sampai Tanggal">
                    <button type="submit" class="min-h-[42px] rounded-md bg-slate-800 px-4 text-xs font-semibold text-white hover:bg-slate-900">
                        Filter
                    </button>
                    @if (request()->hasAny(['q', 'date_from', 'date_to']))
                        <a href="/piagam" class="min-h-[42px] flex items-center justify-center rounded-md border border-slate-300 px-2.5 text-xs text-slate-600 hover:bg-slate-100" title="Reset filter">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Mobile Viewport: Card Summary -->
        <div class="space-y-3 md:hidden">
            @forelse ($piagams as $piagam)
                <article data-testid="mobile-piagam-card" class="rounded-lg border border-slate-200 bg-white p-4 shadow-xs space-y-2.5">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2">
                        <div>
                            <span class="inline-block rounded-xs bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[11px] font-bold text-emerald-800">
                                {{ $piagam->division?->code ?? 'DIKDAS' }}
                            </span>
                            <span class="text-xs text-slate-400 ml-1.5 font-mono">
                                {{ $piagam->created_at->format('d/m/Y H:i') }} WIB
                            </span>
                        </div>
                        @if ($piagam->recipient_name)
                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">
                                Tercatat Sah
                            </span>
                        @else
                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">
                                Belum Diambil (Tersedia)
                            </span>
                        @endif
                    </div>

                    <div>
                        <h2 class="font-bold text-sm text-slate-900 leading-snug">
                            {{ $piagam->piagam_description ?: $piagam->student_name }}
                        </h2>
                        <p class="text-xs font-medium text-slate-600 mt-0.5">
                            Asal: <strong class="text-slate-800">{{ $piagam->unit_display_name }}</strong>
                        </p>
                    </div>

                    <div class="rounded-md bg-slate-50 p-2.5 text-xs text-slate-700 space-y-0.5 border border-slate-100">
                        <p><strong>Pengambil:</strong> {{ $piagam->recipient_name ?? '-' }}</p>
                        <p class="text-slate-500"><strong>Jabatan:</strong> {{ $piagam->recipient_position ?? '-' }} ({{ $piagam->recipient_unit ?? $piagam->school_origin ?? '-' }})</p>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 pt-2.5">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                            @if ($piagam->attachments->isNotEmpty())
                                <x-icon name="check" class="h-4 w-4 text-emerald-600" />
                                <span class="font-medium text-emerald-700">Ada TTD Digital</span>
                            @else
                                <span class="text-slate-400">Tanpa TTD</span>
                            @endif
                        </div>
                        <button type="button" onclick="openPiagamDetailModal({{ $piagam->id }})" class="inline-flex min-h-[36px] items-center gap-1 rounded-md border border-emerald-300 bg-emerald-50 px-3 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                            <x-icon name="signature" class="h-3.5 w-3.5 text-emerald-700" />
                            Detail & Bukti TTD &rarr;
                        </button>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                    <x-icon name="signature" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                    Belum ada catatan serah terima pengambilan piagam.
                </div>
            @endforelse
        </div>

        <!-- Desktop Viewport: Comprehensive Dense Grid -->
        <div class="hidden overflow-x-auto md:block">
            <table data-testid="desktop-piagam-grid" class="w-full min-w-[1024px] border-collapse bg-white text-sm shadow-xs rounded-lg border border-slate-200">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="p-3">Tanggal & Jam</th>
                        <th class="p-3">Keterangan Piagam / Lomba</th>
                        <th class="p-3">Asal Sekolah / Instansi</th>
                        <th class="p-3">Nama Pengambil</th>
                        <th class="p-3">Jabatan / Unit Kerja</th>
                        <th class="p-3">Divisi</th>
                        <th class="p-3 text-center">Tanda Tangan</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($piagams as $piagam)
                        <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="openPiagamDetailModal({{ $piagam->id }})">
                            <td class="p-3 font-mono text-xs text-slate-600 whitespace-nowrap">
                                {{ $piagam->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-3 font-medium text-slate-900 max-w-xs">
                                {{ $piagam->piagam_description ?: $piagam->student_name }}
                            </td>
                            <td class="p-3 text-slate-700">
                                {{ $piagam->unit_display_name }}
                            </td>
                            <td class="p-3 font-semibold text-slate-800">
                                {{ $piagam->recipient_name ?? '-' }}
                            </td>
                            <td class="p-3 text-xs text-slate-600">
                                {{ $piagam->recipient_position ?? '-' }}
                                @if($piagam->recipient_unit && $piagam->recipient_unit !== $piagam->school_origin)
                                    <div class="text-[11px] text-slate-400">({{ $piagam->recipient_unit }})</div>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="inline-flex rounded-xs bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">
                                    {{ $piagam->division?->code ?? 'DIKDAS' }}
                                </span>
                            </td>
                            <td class="p-3 text-center">
                                @php
                                    $sig = $piagam->attachments->first();
                                @endphp
                                @if ($sig && ! $sig->is_missing)
                                    <button type="button" onclick="event.stopPropagation(); openPiagamDetailModal({{ $piagam->id }})" class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-100" title="Buka modal pratinjau tanda tangan">
                                        <x-icon name="signature" class="h-3.5 w-3.5 text-emerald-700" />
                                        Lihat TTD
                                    </button>
                                @elseif ($sig && $sig->is_missing && $sig->legacy_drive_url)
                                    <a href="{{ $sig->legacy_drive_url }}" target="_blank" onclick="event.stopPropagation();" class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800 hover:bg-amber-100" title="Tautan arsip Drive lama">
                                        <x-icon name="external-link" class="h-3.5 w-3.5" />
                                        Drive
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <button type="button" onclick="event.stopPropagation(); openPiagamDetailModal({{ $piagam->id }})" class="inline-flex min-h-[34px] items-center gap-1 rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs" title="Lihat detail lengkap">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-sm text-slate-500">
                                <x-icon name="signature" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                                Belum ada catatan bukti serah terima pengambilan piagam ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $piagams->links() }}
        </div>
    </section>

    <!-- Modal Split Detail Tanda Terima Piagam -->
    <x-piagam.split-modal-detail />
</x-layouts.app>
