<x-layouts.app title="Surat Masuk">
    <section class="px-4 py-4 md:px-8">
        <header class="sticky top-0 z-10 -mx-4 mb-4 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:static md:mx-0 md:rounded-md md:border">
            <div>
                <p class="text-sm font-semibold text-emerald-700">{{ auth()->user()->division?->code ?? 'GLOBAL' }}</p>
                <h1 class="text-xl font-semibold">Surat Masuk</h1>
            </div>
            <a href="/surat-masuk/create" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-3 font-semibold text-white">
                <x-icon name="plus" class="h-5 w-5" />
                Tambah
            </a>
        </header>

        <div class="mb-4 flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3">
            <x-icon name="search" class="h-5 w-5 text-slate-600" />
            <input class="min-h-[42px] flex-1 border-0 bg-transparent focus:outline-none" placeholder="Cari nomor, asal, atau perihal">
        </div>

        <div class="space-y-3 md:hidden">
            @forelse ($letters as $letter)
                <article data-testid="mobile-letter-card" class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">{{ $letter->letter_number }}</h2>
                            <p class="mt-1 text-sm text-slate-700">{{ $letter->origin_agency }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $letter->letter_date->toDateString() }} / {{ $letter->received_date->toDateString() }}</p>
                        </div>
                        <button class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-slate-300" aria-label="Buka aksi surat {{ $letter->letter_number }}">
                            <x-icon name="more-horizontal" />
                        </button>
                    </div>
                    <p class="mt-3 text-sm">{{ $letter->subject }}</p>
                    <button class="mt-3 min-h-[44px] rounded-md border border-slate-300 px-3 text-sm font-medium">Lihat PDF</button>
                </article>
            @empty
                <div class="rounded-md border border-dashed border-slate-300 bg-white p-6 text-center">Tidak ada surat ditemukan</div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table data-testid="desktop-letter-grid" class="w-full min-w-[1024px] border-collapse bg-white text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left">
                        <th class="p-3">No. Agenda</th>
                        <th class="p-3">Nomor Surat</th>
                        <th class="p-3">Tanggal Surat</th>
                        <th class="p-3">Tanggal Diterima</th>
                        <th class="p-3">Asal Surat</th>
                        <th class="p-3">Perihal</th>
                        <th class="p-3">Lampiran</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($letters as $letter)
                        <tr class="border-b border-slate-100">
                            <td class="p-3">{{ $letter->id }}</td>
                            <td class="p-3 font-medium">{{ $letter->letter_number }}</td>
                            <td class="p-3">{{ $letter->letter_date->toDateString() }}</td>
                            <td class="p-3">{{ $letter->received_date->toDateString() }}</td>
                            <td class="p-3">{{ $letter->origin_agency }}</td>
                            <td class="p-3">{{ $letter->subject }}</td>
                            <td class="p-3"><button class="min-h-[44px] rounded-md border border-slate-300 px-3">Lihat PDF</button></td>
                            <td class="p-3"><button class="min-h-[44px] rounded-md border border-slate-300 px-3" aria-label="Buka aksi surat {{ $letter->letter_number }}">Aksi</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
