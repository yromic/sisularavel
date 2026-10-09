<x-layouts.app title="Surat Keluar">
    <section class="px-4 py-4 md:px-8">
        <header class="sticky top-0 z-10 -mx-4 mb-4 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:static md:mx-0 md:rounded-md md:border">
            <div>
                <p class="text-sm font-semibold text-emerald-700">{{ auth()->user()->division?->code ?? 'GLOBAL' }}</p>
                <h1 class="text-xl font-semibold text-slate-900">Surat Keluar</h1>
            </div>
            <a href="/surat-keluar/create" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-3 font-semibold text-white hover:bg-emerald-800">
                <x-icon name="plus" class="h-5 w-5" />
                Tambah
            </a>
        </header>

        <form method="get" action="/surat-keluar" class="mb-4 flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3">
            <x-icon name="search" class="h-5 w-5 text-slate-600" />
            <input name="q" value="{{ request('q') }}" class="min-h-[42px] flex-1 border-0 bg-transparent text-sm focus:outline-hidden" placeholder="Cari nomor surat atau perihal...">
            @if (request('q'))
                <a href="/surat-keluar" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Mobile Viewport: Card Summary -->
        <div class="space-y-3 md:hidden">
            @forelse ($letters as $letter)
                <article data-testid="mobile-outgoing-card" class="rounded-md border border-slate-200 bg-white p-4 shadow-xs">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">Tahun {{ $letter->letter_year }}</span>
                            <h2 class="mt-1 font-semibold text-slate-900">{{ $letter->letter_number }}</h2>
                            <p class="mt-1 text-xs text-slate-500">Tgl Surat: {{ $letter->letter_date->format('d/m/Y') }} &bull; Share: {{ $letter->disseminated_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-slate-300 text-slate-700" aria-label="Aksi surat {{ $letter->letter_number }}">
                                <x-icon name="more-horizontal" />
                            </button>
                        </div>
                    </div>
                    <p class="mt-2.5 text-sm text-slate-700">{{ $letter->subject }}</p>
                </article>
            @empty
                <div class="rounded-md border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                    Tidak ada surat keluar ditemukan.
                </div>
            @endforelse
        </div>

        <!-- Desktop Viewport: Dense Grid -->
        <div class="hidden overflow-x-auto md:block">
            <table data-testid="desktop-outgoing-grid" class="w-full min-w-[1024px] border-collapse bg-white text-sm shadow-xs rounded-md border border-slate-200">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="p-3">No. Agenda</th>
                        <th class="p-3">Tahun</th>
                        <th class="p-3">Nomor Surat</th>
                        <th class="p-3">Tanggal Surat</th>
                        <th class="p-3">Tanggal Share</th>
                        <th class="p-3">Perihal</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($letters as $letter)
                        <tr class="hover:bg-slate-50/75">
                            <td class="p-3 font-mono text-xs">{{ $letter->id }}</td>
                            <td class="p-3 font-semibold text-slate-600">{{ $letter->letter_year }}</td>
                            <td class="p-3 font-medium text-slate-900">{{ $letter->letter_number }}</td>
                            <td class="p-3">{{ $letter->letter_date->format('d/m/Y') }}</td>
                            <td class="p-3">{{ $letter->disseminated_date->format('d/m/Y') }}</td>
                            <td class="p-3 max-w-xs truncate text-slate-700">{{ $letter->subject }}</td>
                            <td class="p-3 text-center">
                                <form method="post" action="/api/v1/surat-keluar/{{ $letter->id }}" onsubmit="return confirm('Hapus surat keluar {{ $letter->letter_number }}?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus surat keluar">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-sm text-slate-500">Tidak ada surat keluar ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $letters->links() }}
        </div>
    </section>
</x-layouts.app>
