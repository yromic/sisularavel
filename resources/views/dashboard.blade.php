<x-layouts.app title="Dashboard">
    @php
        $user = auth()->user();
        $isSuperAdmin = $user->role === 'super_admin';
        $isDivisionAdmin = $user->role === 'division_admin';
        $isOperator = $user->role === 'operator';
    @endphp

    <section class="px-4 py-4 sm:px-6 sm:py-6 md:px-8">
        <!-- ============================================================ -->
        <!-- REFINED WORKSPACE HEADER (STANDARDIZED)                      -->
        <!-- ============================================================ -->
        <x-page-header
            :badge="$activeDivision ? $activeDivision->code . ' — ' . $activeDivision->name : 'Semua Divisi (Konteks Global)'"
            :meta="'Peran: ' . ucwords(str_replace('_', ' ', $user->role))"
            title="Dashboard Pengarsipan Surat"
            subtitle="Sistem Informasi Surat & Kearsipan Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga"
        >
            <x-slot:actions>
                <a href="/surat-masuk/create" 
                   class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>Surat Masuk</span>
                </a>
                <a href="/surat-keluar/create" 
                   class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4 text-slate-500" />
                    <span>Surat Keluar</span>
                </a>
                <a href="/piagam/create" 
                   class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4 text-slate-500" />
                    <span>Catat Piagam</span>
                </a>
            </x-slot:actions>
        </x-page-header>


        <!-- ============================================================ -->
        <!-- QUICK NAVIGATION CARDS (UX-018)                              -->
        <!-- ============================================================ -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <!-- 1. Pusat Persuratan -->
            <a href="/persuratan" class="group flex flex-col justify-between rounded-xl border border-[#E3E8EE] bg-white p-4 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-teal-500 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-[#147D73] transition-colors group-hover:bg-[#147D73] group-hover:text-white">
                        <x-icon name="layers" class="h-5 w-5" />
                    </div>
                    <span class="rounded bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-[#0F635B] border border-teal-200">Terpadu</span>
                </div>
                <div class="mt-3">
                    <h3 class="font-bold text-sm text-[#17243A] group-hover:text-[#147D73] transition-colors">Pusat Persuratan</h3>
                    <p class="mt-0.5 text-xs text-[#657184] line-clamp-1">Workspace terpadu kearsipan dinas</p>
                </div>
                <div class="mt-3 flex items-center gap-1 text-xs font-semibold text-[#147D73]">
                    <span>Buka Workspace</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </a>

            <!-- 2. Surat Masuk -->
            <a href="/persuratan?tab=masuk" class="group flex flex-col justify-between rounded-xl border border-[#E3E8EE] bg-white p-4 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-500 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 transition-colors group-hover:bg-emerald-700 group-hover:text-white">
                        <x-icon name="mail" class="h-5 w-5" />
                    </div>
                    <span class="text-xs font-bold text-[#657184] tabular-nums">{{ number_format($totalIncoming) }} Arsip</span>
                </div>
                <div class="mt-3">
                    <h3 class="font-bold text-sm text-[#17243A] group-hover:text-emerald-700 transition-colors">Surat Masuk</h3>
                    <p class="mt-0.5 text-xs text-[#657184] line-clamp-1">Register arsip surat masuk</p>
                </div>
                <div class="mt-3 flex items-center gap-1 text-xs font-semibold text-emerald-700">
                    <span>Lihat Register</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </a>

            <!-- 3. Surat Keluar -->
            <a href="/persuratan?tab=keluar" class="group flex flex-col justify-between rounded-xl border border-[#E3E8EE] bg-white p-4 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-500 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700 transition-colors group-hover:bg-blue-700 group-hover:text-white">
                        <x-icon name="send" class="h-5 w-5" />
                    </div>
                    <span class="text-xs font-bold text-[#657184] tabular-nums">{{ number_format($totalOutgoing) }} Arsip</span>
                </div>
                <div class="mt-3">
                    <h3 class="font-bold text-sm text-[#17243A] group-hover:text-blue-700 transition-colors">Surat Keluar</h3>
                    <p class="mt-0.5 text-xs text-[#657184] line-clamp-1">Buku nomor & arsip surat keluar</p>
                </div>
                <div class="mt-3 flex items-center gap-1 text-xs font-semibold text-blue-700">
                    <span>Lihat Register</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </a>

            <!-- 4. Pencarian Pintar -->
            <a href="/laporan" class="group flex flex-col justify-between rounded-xl border border-[#E3E8EE] bg-white p-4 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-500 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700 transition-colors group-hover:bg-amber-700 group-hover:text-white">
                        <x-icon name="search" class="h-5 w-5" />
                    </div>
                    <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-800 border border-amber-200">Pencarian</span>
                </div>
                <div class="mt-3">
                    <h3 class="font-bold text-sm text-[#17243A] group-hover:text-amber-800 transition-colors">Pencarian Pintar</h3>
                    <p class="mt-0.5 text-xs text-[#657184] line-clamp-1">Cari arsip & ekspor rekap resmi</p>
                </div>
                <div class="mt-3 flex items-center gap-1 text-xs font-semibold text-amber-700">
                    <span>Mulai Cari</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </a>
        </div>

        <!-- ============================================================ -->
        <!-- METRIC SUMMARY TILES (INTEGRATED RATIOS, NO DUPLICATION)     -->
        <!-- ============================================================ -->
        <div class="mb-6 grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- 1. Total Rekap Arsip -->
            <article class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs transition-shadow hover:shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#657184]">Total Rekap Arsip</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <x-icon name="file-text" class="h-4.5 w-4.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalRekap) }}</p>
                    <p class="mt-1 text-xs text-[#657184]">Seluruh surat & piagam terdata</p>
                </div>
            </article>

            <!-- 2. Surat Masuk -->
            <article class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs transition-shadow hover:shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#0F635B]">Surat Masuk</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-[#147D73]">
                        <x-icon name="mail" class="h-4.5 w-4.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalIncoming) }}</p>
                    <div class="mt-1 flex items-center justify-between text-xs">
                        <span class="font-medium text-[#0F635B]">{{ $percentIncoming }}% total</span>
                        <a href="/persuratan?tab=masuk" class="font-semibold text-[#147D73] hover:underline">
                            Buka arsip &rarr;
                        </a>
                    </div>
                </div>
            </article>

            <!-- 3. Surat Keluar -->
            <article class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs transition-shadow hover:shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-blue-700">Surat Keluar</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                        <x-icon name="send" class="h-4.5 w-4.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalOutgoing) }}</p>
                    <div class="mt-1 flex items-center justify-between text-xs">
                        <span class="font-medium text-blue-700">{{ $percentOutgoing }}% total</span>
                        <a href="/persuratan?tab=keluar" class="font-semibold text-blue-700 hover:underline">
                            Buka arsip &rarr;
                        </a>
                    </div>
                </div>
            </article>

            <!-- 4. Piagam & Sertifikat (Required exact label) -->
            <article class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs transition-shadow hover:shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-700">Piagam & Sertifikat</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                        <x-icon name="award" class="h-4.5 w-4.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalPiagam) }}</p>
                    <div class="mt-1 flex items-center justify-between text-xs">
                        <span class="font-medium text-amber-700">{{ $percentPiagam }}% total</span>
                        <a href="/persuratan?tab=piagam" class="font-semibold text-amber-700 hover:underline">
                            Buka log &rarr;
                        </a>
                    </div>
                </div>
            </article>
        </div>

        <!-- ============================================================ -->
        <!-- SUPER ADMIN: CROSS-DIVISION AGGREGATION MATRIX               -->
        <!-- ============================================================ -->
        @if ($isSuperAdmin && $divisionMatrix->isNotEmpty())
            <div class="mb-6 rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between border-b border-[#E3E8EE] pb-3">
                    <div>
                        <h2 class="font-bold text-base text-[#17243A]">Matriks Agregasi Lintas Divisi</h2>
                        <p class="text-xs text-[#657184]">Ringkasan volume kearsipan di seluruh bidang kerja dinas.</p>
                    </div>
                    <span class="self-start sm:self-auto rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                        {{ $divisionMatrix->count() }} Divisi Terdata
                    </span>
                </div>

                <!-- Mobile Card Summary (< md) -->
                <div class="space-y-2.5 md:hidden">
                    @foreach ($divisionMatrix as $div)
                        @php
                            $totalDiv = $div->incoming_letters_count + $div->outgoing_letters_count + $div->piagams_count;
                        @endphp
                        <div class="rounded-lg border border-[#E3E8EE] bg-[#F7F8FA] p-3 text-xs">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                                <span class="font-bold text-[#147D73]">{{ $div->code }}</span>
                                <span class="font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalDiv) }} Arsip</span>
                            </div>
                            <p class="mt-1 font-medium text-slate-800">{{ $div->name }}</p>
                            <div class="mt-2 grid grid-cols-3 gap-1 text-center text-[11px] text-[#657184]">
                                <div class="rounded bg-white p-1 border border-slate-200">
                                    <span class="block font-bold text-teal-800 tabular-nums">{{ number_format($div->incoming_letters_count) }}</span>
                                    <span>Masuk</span>
                                </div>
                                <div class="rounded bg-white p-1 border border-slate-200">
                                    <span class="block font-bold text-blue-800 tabular-nums">{{ number_format($div->outgoing_letters_count) }}</span>
                                    <span>Keluar</span>
                                </div>
                                <div class="rounded bg-white p-1 border border-slate-200">
                                    <span class="block font-bold text-amber-800 tabular-nums">{{ number_format($div->piagams_count) }}</span>
                                    <span>Piagam</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Desktop Table View (>= md) -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[640px] border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="border-b border-[#E3E8EE] bg-[#F7F8FA] text-left text-xs font-semibold uppercase tracking-wider text-[#657184]">
                                <th class="p-3">Kode & Nama Divisi</th>
                                <th class="p-3 text-right">Surat Masuk</th>
                                <th class="p-3 text-right">Surat Keluar</th>
                                <th class="p-3 text-right">Piagam</th>
                                <th class="p-3 text-right font-bold text-[#17243A]">Total Arsip</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E3E8EE]">
                            @foreach ($divisionMatrix as $div)
                                @php
                                    $totalDiv = $div->incoming_letters_count + $div->outgoing_letters_count + $div->piagams_count;
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="p-3 font-medium text-[#17243A]">
                                        <span class="font-bold text-[#147D73] font-mono">{{ $div->code }}</span>
                                        <span class="mx-1.5 text-slate-300">&bull;</span>
                                        <span>{{ $div->name }}</span>
                                    </td>
                                    <td class="p-3 text-right text-slate-700 tabular-nums">{{ number_format($div->incoming_letters_count) }}</td>
                                    <td class="p-3 text-right text-slate-700 tabular-nums">{{ number_format($div->outgoing_letters_count) }}</td>
                                    <td class="p-3 text-right text-slate-700 tabular-nums">{{ number_format($div->piagams_count) }}</td>
                                    <td class="p-3 text-right font-extrabold text-[#17243A] tabular-nums">{{ number_format($totalDiv) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- ANALYTICS: 6-MONTH CORRESPONDENCE TREND                      -->
        <!-- (Visible to Super Admin & Admin Divisi)                      -->
        <!-- ============================================================ -->
        @if ($isSuperAdmin || $isDivisionAdmin)
            <div class="mb-6 rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs">
                <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between border-b border-[#E3E8EE] pb-3">
                    <div>
                        <h2 class="font-bold text-base text-[#17243A]">Tren Persuratan (6 Bulan Terakhir)</h2>
                        <p class="text-xs text-[#657184]">Volume surat masuk dan surat keluar tercatat per bulan kalender.</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="flex items-center gap-1.5 text-[#0F635B] font-semibold">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#147D73]"></span> Surat Masuk
                        </span>
                        <span class="flex items-center gap-1.5 text-blue-700 font-semibold">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span> Surat Keluar
                        </span>
                    </div>
                </div>

                <div class="space-y-3 pt-1">
                    @foreach ($trendData as $trend)
                        @php
                            $max = max(1, $trend['incoming'] + $trend['outgoing']);
                            $wIn = ($trend['incoming'] / $max) * 100;
                            $wOut = ($trend['outgoing'] / $max) * 100;
                        @endphp
                        <div>
                            <div class="mb-1 flex justify-between text-xs font-medium text-[#17243A]">
                                <span class="font-semibold">{{ $trend['label'] }}</span>
                                <span class="text-[#657184] text-[11px] tabular-nums">
                                    <span class="text-[#0F635B] font-bold">SM: {{ $trend['incoming'] }}</span>
                                    <span class="mx-1 text-slate-300">&bull;</span>
                                    <span class="text-blue-700 font-bold">SK: {{ $trend['outgoing'] }}</span>
                                </span>
                            </div>
                            <div class="flex h-2.5 w-full gap-1 overflow-hidden rounded-full bg-[#F7F8FA] border border-[#E3E8EE]">
                                @if ($trend['incoming'] > 0)
                                    <div style="width: {{ $wIn }}%" class="rounded-full bg-[#147D73]" title="Surat Masuk: {{ $trend['incoming'] }}"></div>
                                @endif
                                @if ($trend['outgoing'] > 0)
                                    <div style="width: {{ $wOut }}%" class="rounded-full bg-blue-600" title="Surat Keluar: {{ $trend['outgoing'] }}"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- OPERATIONAL WORKSPACE: RECENT CORRESPONDENCE & ACTIVITY      -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 gap-6 {{ $isOperator ? 'lg:grid-cols-2' : 'lg:grid-cols-12' }}">
            <!-- Recent Letters Workspace (Incoming & Outgoing) -->
            <div class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs {{ $isOperator ? '' : 'lg:col-span-7' }}">
                <!-- Tab/Section: Recent Incoming -->
                <div class="mb-3 flex items-center justify-between border-b border-[#E3E8EE] pb-2.5">
                    <div class="flex items-center gap-2">
                        <x-icon name="mail" class="h-4 w-4 text-[#147D73]" />
                        <h2 class="font-bold text-sm text-[#17243A]">Arsip Masuk Terkini</h2>
                    </div>
                    <a href="/persuratan?tab=masuk" class="text-xs font-semibold text-[#147D73] hover:underline">
                        Lihat semua &rarr;
                    </a>
                </div>
                <div class="divide-y divide-[#E3E8EE]">
                    @forelse ($recentIncoming as $letter)
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-teal-50 px-1.5 py-0.2 text-[10px] font-bold text-[#0F635B] border border-teal-200">MASUK</span>
                                        <a href="/surat-masuk/{{ $letter->id }}" class="truncate font-semibold text-xs sm:text-sm text-[#17243A] hover:text-[#147D73] hover:underline">
                                            {{ $letter->letter_number }}
                                        </a>
                                    </div>
                                    <p class="truncate text-xs text-[#657184] mt-0.5">{{ $letter->origin_agency }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-medium text-slate-500 tabular-nums">
                                    {{ $letter->received_date->format('d/m/Y') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="py-3 text-center text-xs text-[#657184]">Belum ada catatan arsip masuk.</p>
                    @endforelse
                </div>

                <!-- Tab/Section: Recent Outgoing -->
                <div class="mb-3 mt-6 flex items-center justify-between border-b border-[#E3E8EE] pb-2.5">
                    <div class="flex items-center gap-2">
                        <x-icon name="send" class="h-4 w-4 text-blue-700" />
                        <h2 class="font-bold text-sm text-[#17243A]">Arsip Keluar Terkini</h2>
                    </div>
                    <a href="/persuratan?tab=keluar" class="text-xs font-semibold text-blue-700 hover:underline">
                        Lihat semua &rarr;
                    </a>
                </div>
                <div class="divide-y divide-[#E3E8EE]">
                    @forelse ($recentOutgoing as $letter)
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-blue-50 px-1.5 py-0.2 text-[10px] font-bold text-blue-800 border border-blue-200">KELUAR</span>
                                        <a href="/surat-keluar/{{ $letter->id }}" class="truncate font-semibold text-xs sm:text-sm text-[#17243A] hover:text-blue-700 hover:underline">
                                            {{ $letter->letter_number }}
                                        </a>
                                    </div>
                                    <p class="truncate text-xs text-[#657184] mt-0.5">{{ $letter->subject }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-medium text-slate-500 tabular-nums">
                                    {{ $letter->letter_date->format('d/m/Y') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="py-3 text-center text-xs text-[#657184]">Belum ada catatan arsip keluar.</p>
                    @endforelse
                </div>
            </div>

            <!-- Activity & Audit Log Feed -->
            <div class="rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs {{ $isOperator ? '' : 'lg:col-span-5' }}">
                <div class="mb-3 flex items-center justify-between border-b border-[#E3E8EE] pb-2.5">
                    <div class="flex items-center gap-2">
                        <x-icon name="settings" class="h-4 w-4 text-slate-500" />
                        <h2 class="font-bold text-sm text-[#17243A]">Aktivitas Sistem Terkini</h2>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Audit Log</span>
                </div>

                <div class="divide-y divide-[#E3E8EE]">
                    @forelse ($recentActivities as $act)
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="rounded bg-[#F7F8FA] px-1.5 py-0.2 font-mono text-[10px] font-bold text-slate-700 border border-slate-200">
                                            {{ $act->event_code }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs font-medium text-[#17243A] line-clamp-2">{{ $act->description }}</p>
                                    <p class="mt-0.5 text-[11px] text-[#657184]">
                                        Oleh: <strong class="text-slate-700">{{ $act->username ?? 'Publik' }}</strong>
                                        @if ($act->role)
                                            <span class="text-slate-400">({{ $act->role }})</span>
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
</x-layouts.app>
