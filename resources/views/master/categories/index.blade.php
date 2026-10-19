<x-layouts.app title="Master Kategori">
    <section class="px-4 py-4 md:px-8">
        <x-page-header
            badge="Master Data"
            meta="Kategori Referensi Lomba & Piagam"
            title="Kategori Lomba & Pengambilan"
            subtitle="Kelola kategori referensi untuk divisi aktif."
        />

        @if (session('success'))
            <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900 shadow-xs">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md border border-rose-300 bg-rose-50 p-4 text-sm font-medium text-rose-900 shadow-xs">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md border border-rose-300 bg-rose-50 p-4 text-sm text-rose-800 shadow-xs">
                <div class="font-semibold mb-1">Gagal Menyimpan Data Kategori:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Competition Categories -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">Kategori Lomba (Piagam)</h2>
                        <p class="text-xs text-slate-500">Kategori lomba siswa yang diselenggarakan divisi.</p>
                    </div>
                </div>

                <form method="post" action="/api/v1/reference/competition-categories" class="mb-4 flex gap-2">
                    @csrf
                    <input name="name" id="input-cat-competition-name" aria-label="Nama kategori lomba baru" class="min-h-[44px] flex-1 rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Nama kategori lomba baru..." required>
                    <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white hover:bg-emerald-800">
                        + Tambah
                    </button>
                </form>

                <div class="divide-y divide-slate-100">
                    @forelse ($competitionCategories as $cat)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <span class="text-sm font-medium text-slate-800">{{ $cat->name }}</span>
                                <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                            </div>
                            <form method="post" action="/api/v1/reference/competition-categories/{{ $cat->id }}" onsubmit="return confirmAction(event, 'Hapus kategori lomba {{ $cat->name }}?', 'Konfirmasi Hapus Kategori', 'danger', 'Ya, Hapus')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus kategori lomba">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">Belum ada kategori lomba.</p>
                    @endforelse
                </div>
            </div>

            <!-- Pickup Categories -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">Kategori Pengambilan</h2>
                        <p class="text-xs text-slate-500">Metode atau kategori serah terima surat/piagam.</p>
                    </div>
                </div>

                <form method="post" action="/api/v1/reference/pickup-categories" class="mb-4 flex gap-2">
                    @csrf
                    <input name="name" id="input-cat-pickup-name" aria-label="Nama kategori pengambilan baru" class="min-h-[44px] flex-1 rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Nama kategori pengambilan baru..." required>
                    <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white hover:bg-emerald-800">
                        + Tambah
                    </button>
                </form>

                <div class="divide-y divide-slate-100">
                    @forelse ($pickupCategories as $cat)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <span class="text-sm font-medium text-slate-800">{{ $cat->name }}</span>
                                <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                            </div>
                            <form method="post" action="/api/v1/reference/pickup-categories/{{ $cat->id }}" onsubmit="return confirmAction(event, 'Hapus kategori pengambilan {{ $cat->name }}?', 'Konfirmasi Hapus Kategori', 'danger', 'Ya, Hapus')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus kategori pengambilan">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">Belum ada kategori pengambilan.</p>
                    @endforelse
                </div>
            </div>

            <!-- Work Units (Unit Kerja / Instansi Non-Sekolah) -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">Unit Kerja / Instansi</h2>
                        <p class="text-xs text-slate-500">Unit kerja atau instansi non-sekolah dinas.</p>
                    </div>
                </div>

                <form method="post" action="/api/v1/reference/work-units" class="mb-4 flex gap-2">
                    @csrf
                    <input name="name" id="input-work-unit-name" aria-label="Nama unit kerja baru" class="min-h-[44px] flex-1 rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Nama unit kerja baru..." required>
                    <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white hover:bg-emerald-800">
                        + Tambah
                    </button>
                </form>

                <div class="divide-y divide-slate-100">
                    @forelse ($workUnits as $unit)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <span class="text-sm font-medium text-slate-800">{{ $unit->name }}</span>
                                <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                            </div>
                            <form method="post" action="/master/kategori/unit-kerja/{{ $unit->id }}" onsubmit="return confirmAction(event, 'Hapus unit kerja {{ $unit->name }}?', 'Konfirmasi Hapus Unit Kerja', 'danger', 'Ya, Hapus')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus unit kerja">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">Belum ada unit kerja.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
