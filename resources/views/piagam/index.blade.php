<x-layouts.app title="Data Piagam">
    <section class="px-4 py-4 md:px-8">
        <header class="sticky top-0 z-10 -mx-4 mb-4 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:static md:mx-0 md:rounded-md md:border">
            <div>
                <p class="text-sm font-semibold text-emerald-700">{{ auth()->user()->division?->code ?? 'GLOBAL' }}</p>
                <h1 class="text-xl font-semibold text-slate-900">Rekap Pengambilan Piagam</h1>
                <p class="text-xs text-slate-500">Log serah terima piagam penghargaan yang diambil oleh tamu / perwakilan sekolah.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/" target="_blank" class="flex min-h-[44px] items-center gap-2 rounded-md border border-emerald-600 bg-emerald-50 px-3.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                    <x-icon name="external-link" class="h-4 w-4" />
                    Buka Portal Tamu
                </a>
            </div>
        </header>

        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50/70 p-3 text-xs text-emerald-900">
            <strong>Informasi Alur:</strong> Piagam penghargaan tidak diinput manual oleh staf. Tamu atau perwakilan sekolah mengambil dan menandatangani tanda terima secara langsung melalui <a href="/" target="_blank" class="underline font-bold text-emerald-950">Portal Pengambilan Piagam di Halaman Utama (Landing Page)</a>.
        </div>

        <!-- Search / Filter -->
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <form method="get" action="/piagam" class="flex min-h-[44px] flex-1 items-center gap-2 rounded-md border border-slate-300 bg-white px-3">
                <x-icon name="search" class="h-5 w-5 text-slate-600" />
                <input name="q" value="{{ request('q') }}" class="min-h-[42px] flex-1 border-0 bg-transparent text-sm focus:outline-hidden" placeholder="Cari nama siswa atau sekolah...">
            </form>
        </div>

        <!-- Mobile Viewport: Card Summary -->
        <div class="space-y-3 md:hidden">
            @forelse ($piagams as $piagam)
                <article data-testid="mobile-piagam-card" class="rounded-md border border-slate-200 bg-white p-4 shadow-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">Tahun {{ $piagam->competition_year }}</span>
                            <h2 class="mt-1 font-semibold text-slate-900">{{ $piagam->student_name }}</h2>
                            <p class="text-xs font-medium text-slate-600">{{ $piagam->school?->name ?? 'NPSN: ' . $piagam->school?->npsn }}</p>
                        </div>
                        <div>
                            @if ($piagam->recipient_name)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Sudah Diambil</span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Tersedia</span>
                            @endif
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Kategori: {{ $piagam->competitionCategory?->name ?? '-' }}</p>
                    @if ($piagam->recipient_name)
                        <div class="mt-2 rounded-sm bg-slate-50 p-2 text-xs text-slate-600">
                            <p class="font-medium">Diambil oleh: {{ $piagam->recipient_name }} ({{ $piagam->recipient_position ?? 'Perwakilan' }})</p>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-md border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                    Belum ada data piagam terdaftar.
                </div>
            @endforelse
        </div>

        <!-- Desktop Viewport: Dense Grid -->
        <div class="hidden overflow-x-auto md:block">
            <table data-testid="desktop-piagam-grid" class="w-full min-w-[1024px] border-collapse bg-white text-sm shadow-xs rounded-md border border-slate-200">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="p-3">ID</th>
                        <th class="p-3">Nama Siswa</th>
                        <th class="p-3">Asal Sekolah</th>
                        <th class="p-3">Lomba / Kategori</th>
                        <th class="p-3">Tahun</th>
                        <th class="p-3">Status Pengambilan</th>
                        <th class="p-3">Penerima</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($piagams as $piagam)
                        <tr class="hover:bg-slate-50/75">
                            <td class="p-3 font-mono text-xs">{{ $piagam->id }}</td>
                            <td class="p-3 font-medium text-slate-900">{{ $piagam->student_name }}</td>
                            <td class="p-3">{{ $piagam->school?->name ?? '-' }}</td>
                            <td class="p-3">{{ $piagam->competitionCategory?->name ?? '-' }}</td>
                            <td class="p-3 font-semibold text-slate-600">{{ $piagam->competition_year }}</td>
                            <td class="p-3">
                                @if ($piagam->recipient_name)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Sudah Diambil</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Tersedia</span>
                                @endif
                            </td>
                            <td class="p-3 text-xs text-slate-600">
                                @if ($piagam->recipient_name)
                                    {{ $piagam->recipient_name }} <span class="text-slate-400">({{ $piagam->recipient_position ?? '-' }})</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-sm text-slate-500">Belum ada data piagam terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $piagams->links() }}
        </div>
    </section>
</x-layouts.app>
