<x-layouts.app title="Master Kategori">
    <section class="px-4 py-4 md:px-8">
        <x-page-header
            badge="Master Data"
            meta="Kategori Referensi Lomba & Piagam"
            title="Kategori Lomba & Pengambilan"
            :subtitle="(($status ?? 'active') === 'trashed') ? 'Daftar kategori dan unit kerja yang diarsipkan (soft-deleted). Anda dapat memulihkannya kembali.' : 'Kelola kategori referensi untuk divisi aktif.'"
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

        <!-- Tab Navigation: Kategori Aktif vs Tempat Sampah -->
        <div class="mb-5 flex items-center gap-2 border-b border-slate-200">
            <a href="/master/kategori?status=active"
               class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ ($status ?? 'active') === 'active' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <span>Kategori Aktif</span>
                <span class="rounded-full px-2 py-0.5 text-xs {{ ($status ?? 'active') === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $activeCount ?? 0 }}
                </span>
            </a>
            <a href="/master/kategori?status=trashed"
               class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ ($status ?? 'active') === 'trashed' ? 'border-rose-600 text-rose-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <span>Tempat Sampah</span>
                <span class="rounded-full px-2 py-0.5 text-xs {{ ($status ?? 'active') === 'trashed' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $trashedCount ?? 0 }}
                </span>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- 1. Competition Categories -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs" id="section-competition">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">Kategori Lomba (Piagam)</h2>
                        <p class="text-xs text-slate-500">Kategori lomba siswa divisi aktif.</p>
                    </div>
                </div>

                @if (($status ?? 'active') !== 'trashed')
                    <form method="post" action="/api/v1/reference/competition-categories" class="mb-4 flex gap-2">
                        @csrf
                        <input name="name" id="input-cat-competition-name" aria-label="Nama kategori lomba baru" class="min-h-[44px] flex-1 rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Nama kategori lomba baru..." required>
                        <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white hover:bg-emerald-800">
                            + Tambah
                        </button>
                    </form>
                @endif

                <!-- Bulk Selection Bar Competition -->
                <div id="bar-bulk-competition" class="hidden mb-3 flex items-center justify-between rounded-md bg-slate-100 p-2 text-xs">
                    <span class="font-semibold text-slate-700"><span id="count-bulk-competition">0</span> dipilih</span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="executeCategoryBulk('competition-categories', '{{ ($status ?? 'active') === 'trashed' ? 'restore' : 'delete' }}')" class="rounded-md {{ ($status ?? 'active') === 'trashed' ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-rose-600 hover:bg-rose-700' }} px-3 py-1.5 font-semibold text-white">
                            {{ ($status ?? 'active') === 'trashed' ? 'Pulihkan Terpilih' : 'Hapus Terpilih' }}
                        </button>
                        <button type="button" onclick="clearCategorySelection('competition')" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                    </div>
                </div>

                @if (count($competitionCategories) > 0)
                    <div class="mb-2 flex items-center gap-2 border-b border-slate-100 pb-2 text-xs text-slate-500">
                        <input type="checkbox" id="check-all-competition" onchange="toggleSelectAllCategory('competition', this.checked)" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                        <label for="check-all-competition" class="cursor-pointer font-medium">Pilih Semua</label>
                    </div>
                @endif

                <div class="divide-y divide-slate-100 max-h-[400px] overflow-y-auto">
                    @forelse ($competitionCategories as $cat)
                        <div class="flex items-center justify-between py-2.5">
                            <div class="flex items-center gap-2.5">
                                <input type="checkbox" class="cat-check-competition rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" value="{{ $cat->id }}" onchange="updateCategoryCount('competition')">
                                <span class="text-sm font-medium text-slate-800">{{ $cat->name }}</span>
                                @if ($cat->trashed())
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">Terhapus</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                                @endif
                            </div>
                            <div>
                                @if (($status ?? 'active') === 'trashed')
                                    <form method="post" action="/master/kategori/lomba/{{ $cat->id }}/restore" class="inline">
                                        @csrf
                                        <button type="submit" class="flex min-h-[38px] items-center gap-1 rounded-md border border-emerald-300 bg-emerald-50 px-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100" title="Pulihkan kategori">
                                            <x-icon name="rotate-ccw" class="h-3.5 w-3.5" />
                                            <span>Pulihkan</span>
                                        </button>
                                    </form>
                                @else
                                    <form method="post" action="/api/v1/reference/competition-categories/{{ $cat->id }}" onsubmit="return confirmAction(event, 'Hapus kategori lomba {{ $cat->name }}?', 'Konfirmasi Hapus Kategori', 'danger', 'Ya, Hapus')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex min-h-[38px] min-w-[38px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus kategori lomba">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">
                            {{ ($status ?? 'active') === 'trashed' ? 'Tidak ada kategori lomba di Tempat Sampah.' : 'Belum ada kategori lomba.' }}
                        </p>
                    @endforelse
                </div>
            </div>

            <!-- 2. Pickup Categories -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs" id="section-pickup">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">Kategori Pengambilan</h2>
                        <p class="text-xs text-slate-500">Metode atau kategori serah terima surat/piagam.</p>
                    </div>
                </div>

                @if (($status ?? 'active') !== 'trashed')
                    <form method="post" action="/api/v1/reference/pickup-categories" class="mb-4 flex gap-2">
                        @csrf
                        <input name="name" id="input-cat-pickup-name" aria-label="Nama kategori pengambilan baru" class="min-h-[44px] flex-1 rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Nama kategori pengambilan baru..." required>
                        <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white hover:bg-emerald-800">
                            + Tambah
                        </button>
                    </form>
                @endif

                <!-- Bulk Selection Bar Pickup -->
                <div id="bar-bulk-pickup" class="hidden mb-3 flex items-center justify-between rounded-md bg-slate-100 p-2 text-xs">
                    <span class="font-semibold text-slate-700"><span id="count-bulk-pickup">0</span> dipilih</span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="executeCategoryBulk('pickup-categories', '{{ ($status ?? 'active') === 'trashed' ? 'restore' : 'delete' }}')" class="rounded-md {{ ($status ?? 'active') === 'trashed' ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-rose-600 hover:bg-rose-700' }} px-3 py-1.5 font-semibold text-white">
                            {{ ($status ?? 'active') === 'trashed' ? 'Pulihkan Terpilih' : 'Hapus Terpilih' }}
                        </button>
                        <button type="button" onclick="clearCategorySelection('pickup')" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                    </div>
                </div>

                @if (count($pickupCategories) > 0)
                    <div class="mb-2 flex items-center gap-2 border-b border-slate-100 pb-2 text-xs text-slate-500">
                        <input type="checkbox" id="check-all-pickup" onchange="toggleSelectAllCategory('pickup', this.checked)" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                        <label for="check-all-pickup" class="cursor-pointer font-medium">Pilih Semua</label>
                    </div>
                @endif

                <div class="divide-y divide-slate-100 max-h-[400px] overflow-y-auto">
                    @forelse ($pickupCategories as $cat)
                        <div class="flex items-center justify-between py-2.5">
                            <div class="flex items-center gap-2.5">
                                <input type="checkbox" class="cat-check-pickup rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" value="{{ $cat->id }}" onchange="updateCategoryCount('pickup')">
                                <span class="text-sm font-medium text-slate-800">{{ $cat->name }}</span>
                                @if ($cat->trashed())
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">Terhapus</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                                @endif
                            </div>
                            <div>
                                @if (($status ?? 'active') === 'trashed')
                                    <form method="post" action="/master/kategori/pengambilan/{{ $cat->id }}/restore" class="inline">
                                        @csrf
                                        <button type="submit" class="flex min-h-[38px] items-center gap-1 rounded-md border border-emerald-300 bg-emerald-50 px-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100" title="Pulihkan kategori">
                                            <x-icon name="rotate-ccw" class="h-3.5 w-3.5" />
                                            <span>Pulihkan</span>
                                        </button>
                                    </form>
                                @else
                                    <form method="post" action="/api/v1/reference/pickup-categories/{{ $cat->id }}" onsubmit="return confirmAction(event, 'Hapus kategori pengambilan {{ $cat->name }}?', 'Konfirmasi Hapus Kategori', 'danger', 'Ya, Hapus')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex min-h-[38px] min-w-[38px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus kategori pengambilan">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">
                            {{ ($status ?? 'active') === 'trashed' ? 'Tidak ada kategori pengambilan di Tempat Sampah.' : 'Belum ada kategori pengambilan.' }}
                        </p>
                    @endforelse
                </div>
            </div>

            <!-- 3. Work Units (Unit Kerja / Instansi Non-Sekolah) -->
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-xs" id="section-work-unit">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">Unit Kerja / Instansi</h2>
                        <p class="text-xs text-slate-500">Unit kerja atau instansi non-sekolah dinas.</p>
                    </div>
                </div>

                @if (auth()->user()->role === 'super_admin' && ($status ?? 'active') !== 'trashed')
                    <form method="post" action="/api/v1/reference/work-units" class="mb-4 flex gap-2">
                        @csrf
                        <input name="name" id="input-work-unit-name" aria-label="Nama unit kerja baru" class="min-h-[44px] flex-1 rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Nama unit kerja baru..." required>
                        <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white hover:bg-emerald-800">
                            + Tambah
                        </button>
                    </form>
                @elseif (auth()->user()->role !== 'super_admin')
                    <p class="mb-4 text-xs text-slate-500 italic">Daftar referensi unit kerja dinas (dikelola oleh Super Admin).</p>
                @endif

                @if (auth()->user()->role === 'super_admin')
                    <!-- Bulk Selection Bar Work Unit -->
                    <div id="bar-bulk-work-unit" class="hidden mb-3 flex items-center justify-between rounded-md bg-slate-100 p-2 text-xs">
                        <span class="font-semibold text-slate-700"><span id="count-bulk-work-unit">0</span> dipilih</span>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="executeCategoryBulk('work-units', '{{ ($status ?? 'active') === 'trashed' ? 'restore' : 'delete' }}')" class="rounded-md {{ ($status ?? 'active') === 'trashed' ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-rose-600 hover:bg-rose-700' }} px-3 py-1.5 font-semibold text-white">
                                {{ ($status ?? 'active') === 'trashed' ? 'Pulihkan Terpilih' : 'Hapus Terpilih' }}
                            </button>
                            <button type="button" onclick="clearCategorySelection('work-unit')" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-slate-600 hover:bg-slate-50">
                                Batal
                            </button>
                        </div>
                    </div>

                    @if (count($workUnits) > 0)
                        <div class="mb-2 flex items-center gap-2 border-b border-slate-100 pb-2 text-xs text-slate-500">
                            <input type="checkbox" id="check-all-work-unit" onchange="toggleSelectAllCategory('work-unit', this.checked)" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                            <label for="check-all-work-unit" class="cursor-pointer font-medium">Pilih Semua</label>
                        </div>
                    @endif
                @endif

                <div class="divide-y divide-slate-100 max-h-[400px] overflow-y-auto">
                    @forelse ($workUnits as $unit)
                        <div class="flex items-center justify-between py-2.5">
                            <div class="flex items-center gap-2.5">
                                @if (auth()->user()->role === 'super_admin')
                                    <input type="checkbox" class="cat-check-work-unit rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" value="{{ $unit->id }}" onchange="updateCategoryCount('work-unit')">
                                @endif
                                <span class="text-sm font-medium text-slate-800">{{ $unit->name }}</span>
                                @if ($unit->trashed())
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">Terhapus</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                                @endif
                            </div>
                            <div>
                                @if (auth()->user()->role === 'super_admin')
                                    @if (($status ?? 'active') === 'trashed')
                                        <form method="post" action="/master/kategori/unit-kerja/{{ $unit->id }}/restore" class="inline">
                                            @csrf
                                            <button type="submit" class="flex min-h-[38px] items-center gap-1 rounded-md border border-emerald-300 bg-emerald-50 px-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100" title="Pulihkan unit kerja">
                                                <x-icon name="rotate-ccw" class="h-3.5 w-3.5" />
                                                <span>Pulihkan</span>
                                            </button>
                                        </form>
                                    @else
                                        <form method="post" action="/master/kategori/unit-kerja/{{ $unit->id }}" onsubmit="return confirmAction(event, 'Hapus unit kerja {{ $unit->name }}?', 'Konfirmasi Hapus Unit Kerja', 'danger', 'Ya, Hapus')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="flex min-h-[38px] min-w-[38px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus unit kerja">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">
                            {{ ($status ?? 'active') === 'trashed' ? 'Tidak ada unit kerja di Tempat Sampah.' : 'Belum ada unit kerja.' }}
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <script>
        function updateCategoryCount(section) {
            const checked = document.querySelectorAll(`.cat-check-${section}:checked`);
            const bar = document.getElementById(`bar-bulk-${section}`);
            const countEl = document.getElementById(`count-bulk-${section}`);
            if (bar && countEl) {
                countEl.textContent = checked.length;
                if (checked.length > 0) {
                    bar.classList.remove('hidden');
                } else {
                    bar.classList.add('hidden');
                }
            }
        }

        function toggleSelectAllCategory(section, checked) {
            document.querySelectorAll(`.cat-check-${section}`).forEach(cb => {
                cb.checked = checked;
            });
            updateCategoryCount(section);
        }

        function clearCategorySelection(section) {
            const master = document.getElementById(`check-all-${section}`);
            if (master) master.checked = false;
            toggleSelectAllCategory(section, false);
        }

        function generateUuid() {
            if (typeof crypto !== 'undefined' && crypto.randomUUID) {
                return crypto.randomUUID();
            }
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        async function executeCategoryBulk(endpointPrefix, action) {
            let section = 'competition';
            if (endpointPrefix === 'pickup-categories') section = 'pickup';
            if (endpointPrefix === 'work-units') section = 'work-unit';

            const checkboxes = Array.from(document.querySelectorAll(`.cat-check-${section}:checked`));
            const ids = checkboxes.map(cb => parseInt(cb.value, 10));
            if (ids.length === 0) return;

            const actionWord = action === 'restore' ? 'memulihkan' : 'menghapus';
            const actionNoun = action === 'restore' ? 'Pemulihan' : 'Penghapusan';

            if (!confirm(`Apakah Anda yakin ingin ${actionWord} ${ids.length} data kategori terpilih?`)) {
                return;
            }

            const url = action === 'restore'
                ? `/api/v1/reference/${endpointPrefix}/bulk-restore`
                : `/api/v1/reference/${endpointPrefix}/bulk-delete`;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Idempotency-Key': generateUuid()
                    },
                    body: JSON.stringify({ ids: ids })
                });

                const data = await response.json();
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || `Gagal ${actionWord} kategori.`);
                }
            } catch (err) {
                alert('Gagal menghubungi server.');
            }
        }
    </script>
</x-layouts.app>
