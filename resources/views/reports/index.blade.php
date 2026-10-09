<x-layouts.app title="Pencarian & Laporan Arsip">
    <section class="px-4 py-4 md:px-8">
        <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Pencarian & Rekap</p>
                <h1 class="text-xl font-semibold text-slate-900">Pencarian Terpadu & Ekspor Laporan</h1>
                <p class="text-xs text-slate-500">Cari arsip surat masuk, surat keluar, dan piagam dalam satu pencarian, atau unduh rekap berkas CSV.</p>
            </div>
            <a href="/api/v1/reports/export.csv" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                <x-icon name="download" class="h-4 w-4" />
                Unduh Rekap CSV
            </a>
        </header>

        <!-- Search Form -->
        <form method="get" action="/laporan" class="mb-6 flex min-h-[48px] items-center gap-3 rounded-lg border border-slate-300 bg-white px-4 shadow-xs">
            <x-icon name="search" class="h-5 w-5 text-slate-500" />
            <input name="q" value="{{ request('q') }}" class="min-h-[44px] flex-1 border-0 bg-transparent text-base focus:outline-hidden" placeholder="Ketik kata kunci nomor surat, asal instansi, atau perihal...">
            @if (request('q'))
                <a href="/laporan" class="text-xs text-slate-500 hover:underline">Bersihkan</a>
            @endif
            <button type="submit" class="min-h-[40px] rounded-md bg-slate-900 px-4 text-xs font-semibold text-white hover:bg-slate-800">Cari</button>
        </form>

        <!-- Results Display -->
        <div class="space-y-6">
            <!-- Incoming Letters Section -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="flex items-center gap-2 font-semibold text-slate-900">
                        <x-icon name="mail" class="h-4 w-4 text-emerald-700" />
                        Hasil Surat Masuk ({{ $incoming->count() }})
                    </h2>
                    <a href="/surat-masuk" class="text-xs text-emerald-700 hover:underline">Buka modul surat masuk &rarr;</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($incoming as $letter)
                        <div class="py-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $letter->letter_number }}</p>
                                    <p class="text-xs text-slate-600">Asal: {{ $letter->origin_agency }} &bull; Tanggal: {{ $letter->letter_date->format('d/m/Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $letter->subject }}</p>
                                </div>
                                <span class="rounded-xs bg-slate-100 px-2 py-0.5 text-xs text-slate-600">No. {{ $letter->id }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-3 text-center text-xs text-slate-500">Tidak ada surat masuk yang cocok.</p>
                    @endforelse
                </div>
            </div>

            <!-- Outgoing Letters Section -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="flex items-center gap-2 font-semibold text-slate-900">
                        <x-icon name="send" class="h-4 w-4 text-blue-700" />
                        Hasil Surat Keluar ({{ $outgoing->count() }})
                    </h2>
                    <a href="/surat-keluar" class="text-xs text-blue-700 hover:underline">Buka modul surat keluar &rarr;</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($outgoing as $letter)
                        <div class="py-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $letter->letter_number }}</p>
                                    <p class="text-xs text-slate-600">Tahun: {{ $letter->letter_year }} &bull; Tanggal: {{ $letter->letter_date->format('d/m/Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $letter->subject }}</p>
                                </div>
                                <span class="rounded-xs bg-slate-100 px-2 py-0.5 text-xs text-slate-600">No. {{ $letter->id }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-3 text-center text-xs text-slate-500">Tidak ada surat keluar yang cocok.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
