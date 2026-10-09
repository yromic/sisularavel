<x-layouts.app title="Master Data Sekolah">
    <section class="px-4 py-4 md:px-8">
        <header class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Master Data</p>
                <h1 class="text-xl font-semibold text-slate-900">Data Referensi Sekolah</h1>
                <p class="text-xs text-slate-500">Daftar sekolah aktif dan validasi 8 digit NPSN di lingkungan Kabupaten Semarang.</p>
            </div>
            @if (auth()->user()->role === 'super_admin')
                <button type="button" onclick="document.getElementById('modal-tambah-sekolah').classList.toggle('hidden')" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-3 font-semibold text-white hover:bg-emerald-800">
                    <x-icon name="plus" class="h-5 w-5" />
                    + Tambah Sekolah
                </button>
            @endif
        </header>

        <!-- Search Bar -->
        <form method="get" action="/master/sekolah" class="mb-4 flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3">
            <x-icon name="search" class="h-5 w-5 text-slate-600" />
            <input name="q" value="{{ request('q') }}" class="min-h-[42px] flex-1 border-0 bg-transparent text-sm focus:outline-hidden" placeholder="Cari berdasarkan nama sekolah atau 8 digit NPSN...">
            @if (request('q'))
                <a href="/master/sekolah" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Modal Tambah Sekolah (Super Admin Only) -->
        @if (auth()->user()->role === 'super_admin')
            <div id="modal-tambah-sekolah" class="hidden mb-6 rounded-lg border border-emerald-300 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between border-b border-slate-200 pb-2">
                    <h2 class="font-semibold text-slate-900">Tambah Sekolah Baru</h2>
                    <button type="button" onclick="document.getElementById('modal-tambah-sekolah').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>
                <form method="post" action="/api/v1/schools" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">NPSN (8 Digit Angka) *</label>
                        <input name="npsn" maxlength="8" pattern="\d{8}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: 20320340" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nama Resmi Sekolah *</label>
                        <input name="name" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: SMP Negeri 1 Ungaran" required>
                    </div>
                    <div class="sm:col-span-2 flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-tambah-sekolah').classList.add('hidden')" class="min-h-[44px] rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Sekolah</button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Table View -->
        <div class="overflow-x-auto rounded-md border border-slate-200 bg-white shadow-xs">
            <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <th class="p-3">NPSN</th>
                        <th class="p-3">Nama Sekolah</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Terdaftar Sejak</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schools as $school)
                        <tr class="hover:bg-slate-50/75">
                            <td class="p-3 font-mono font-medium text-emerald-800">{{ $school->npsn }}</td>
                            <td class="p-3 font-medium text-slate-900">{{ $school->name }}</td>
                            <td class="p-3">
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                            </td>
                            <td class="p-3 text-xs text-slate-500">{{ $school->created_at?->format('d/m/Y') ?? '-' }}</td>
                            <td class="p-3 text-center">
                                @if (auth()->user()->role === 'super_admin')
                                    <form method="post" action="/api/v1/schools/{{ $school->id }}" onsubmit="return confirm('Hapus sekolah {{ $school->name }}?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus sekolah">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-sm text-slate-500">Tidak ada data sekolah yang sesuai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $schools->links() }}
        </div>
    </section>
</x-layouts.app>
