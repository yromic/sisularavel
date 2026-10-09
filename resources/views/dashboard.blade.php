<x-layouts.app title="Dashboard">
    <section class="px-4 py-6 md:px-8">
        <!-- Hero Header -->
        <header class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <span class="inline-flex items-center rounded-sm bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                        {{ $activeDivision ? $activeDivision->code . ' — ' . $activeDivision->name : 'Semua Divisi (Konteks Global)' }}
                    </span>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Dashboard Pengarsipan Surat</h1>
                    <p class="mt-1 text-sm text-slate-600">Sistem Informasi Surat & Kearsipan Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="/surat-masuk/create" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-hidden">
                        <x-icon name="plus" class="h-4 w-4" />
                        + Surat Masuk
                    </a>
                    <a href="/surat-keluar/create" class="flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:bg-slate-50 focus:outline-hidden">
                        <x-icon name="plus" class="h-4 w-4" />
                        + Surat Keluar
                    </a>
                </div>
            </div>
        </header>

        <!-- Stat Cards -->
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-600">Surat Masuk</span>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                        <x-icon name="mail" class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-3xl font-bold text-slate-900">{{ number_format($totalIncoming) }}</p>
                    <a href="/surat-masuk" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
                        Lihat semua surat masuk &rarr;
                    </a>
                </div>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-600">Surat Keluar</span>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                        <x-icon name="send" class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-3xl font-bold text-slate-900">{{ number_format($totalOutgoing) }}</p>
                    <a href="/surat-keluar" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:underline">
                        Lihat semua surat keluar &rarr;
                    </a>
                </div>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-600">Piagam & Sertifikat</span>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-700">
                        <x-icon name="award" class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-3xl font-bold text-slate-900">{{ number_format($totalPiagam) }}</p>
                    <a href="/piagam" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:underline">
                        Lihat data piagam &rarr;
                    </a>
                </div>
            </article>
        </div>

        <!-- Recent Activity Grids -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Incoming -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Surat Masuk Terakhir</h2>
                    <a href="/surat-masuk" class="text-xs font-semibold text-emerald-700 hover:underline">Kelola &rarr;</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentIncoming as $letter)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $letter->letter_number }}</p>
                                    <p class="text-xs text-slate-600">{{ $letter->origin_agency }}</p>
                                    <p class="mt-1 text-xs text-slate-500 line-clamp-1">{{ $letter->subject }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-medium text-slate-400">{{ $letter->received_date->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">Belum ada catatan surat masuk.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Outgoing -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Surat Keluar Terakhir</h2>
                    <a href="/surat-keluar" class="text-xs font-semibold text-blue-700 hover:underline">Kelola &rarr;</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentOutgoing as $letter)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $letter->letter_number }}</p>
                                    <p class="text-xs text-slate-500">Tahun: {{ $letter->letter_year }}</p>
                                    <p class="mt-1 text-xs text-slate-600 line-clamp-1">{{ $letter->subject }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-medium text-slate-400">{{ $letter->letter_date->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">Belum ada catatan surat keluar.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
