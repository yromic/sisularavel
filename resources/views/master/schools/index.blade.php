<x-layouts.app title="Master Data Sekolah">
    <section class="px-4 py-4 md:px-8">
        <x-page-header
            badge="Master Data"
            meta="Kewenangan: Super Admin"
            title="Data Referensi Sekolah"
            :subtitle="(($status ?? 'active') === 'trashed') ? 'Daftar sekolah yang diarsipkan (soft-deleted). Super Admin dapat memulihkan sekolah kembali ke data aktif.' : 'Daftar sekolah aktif dan validasi 8 digit NPSN di lingkungan Kabupaten Semarang.'"
        >
            @if (auth()->user()->role === 'super_admin' && ($status ?? 'active') !== 'trashed')
                <x-slot:actions>
                    <button type="button" onclick="openCreateSchoolModal()" class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span>+ Tambah Sekolah</span>
                    </button>
                </x-slot:actions>
            @endif
        </x-page-header>

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
                <div class="font-semibold mb-1">Gagal Menyimpan Data Sekolah:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Tab Navigation: Sekolah Aktif vs Tempat Sampah -->
        <div class="mb-5 flex items-center gap-2 border-b border-slate-200">
            <a href="/master/sekolah?status=active"
               class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ ($status ?? 'active') === 'active' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <span>Sekolah Aktif</span>
                <span class="rounded-full px-2 py-0.5 text-xs {{ ($status ?? 'active') === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $activeCount ?? 0 }}
                </span>
            </a>
            <a href="/master/sekolah?status=trashed"
               class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ ($status ?? 'active') === 'trashed' ? 'border-rose-600 text-rose-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <span>Tempat Sampah</span>
                <span class="rounded-full px-2 py-0.5 text-xs {{ ($status ?? 'active') === 'trashed' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $trashedCount ?? 0 }}
                </span>
            </a>
        </div>

        <!-- Search Bar -->
        <form method="get" action="/master/sekolah" class="mb-4 flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3">
            <x-icon name="search" class="h-5 w-5 text-slate-600" />
            <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
            <input name="q" value="{{ request('q') }}" class="min-h-[42px] flex-1 border-0 bg-transparent text-sm focus:outline-hidden" placeholder="Cari berdasarkan nama sekolah atau 8 digit NPSN...">
            @if (request('q'))
                <a href="/master/sekolah?status={{ $status ?? 'active' }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Modal Tambah Sekolah (UX2-009 Standardized Popup Modal) -->
        @if (auth()->user()->role === 'super_admin')
            <div id="modal-tambah-sekolah" class="{{ $errors->any() && ($status ?? 'active') !== 'trashed' ? '' : 'hidden' }} fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="create-school-title">
                <div class="relative w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-2xl transition-all">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                        <h2 class="text-base font-bold text-slate-900" id="create-school-title">Tambah Sekolah Baru</h2>
                        <button type="button" onclick="closeCreateSchoolModal()" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup modal tambah sekolah">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>
                    <form method="post" action="/api/v1/schools" class="space-y-4">
                        @csrf
                        <div>
                            <label for="create-school-npsn" class="block text-xs font-semibold text-slate-700">NPSN (8 Digit Angka) *</label>
                            <input name="npsn" id="create-school-npsn" value="{{ old('npsn') }}" maxlength="8" pattern="\d{8}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: 20320340" required>
                        </div>
                        <div>
                            <label for="create-school-name" class="block text-xs font-semibold text-slate-700">Nama Resmi Sekolah *</label>
                            <input name="name" id="create-school-name" value="{{ old('name') }}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: SMP Negeri 1 Ungaran" required>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 pt-3">
                            <button type="button" onclick="closeCreateSchoolModal()" class="min-h-[44px] rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                            <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Sekolah</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Table View -->
        <div class="overflow-x-auto rounded-md border border-slate-200 bg-white shadow-xs">
            <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        @if (auth()->user()->role === 'super_admin')
                            <th class="w-10 p-3 text-center">
                                <input type="checkbox" id="bulk-select-all-page" class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600 cursor-pointer" aria-label="Pilih semua sekolah di halaman ini">
                            </th>
                        @endif
                        <th class="p-3">NPSN</th>
                        <th class="p-3">Nama Sekolah</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">{{ ($status ?? 'active') === 'trashed' ? 'Dihapus Pada' : 'Terdaftar Sejak' }}</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schools as $school)
                        <tr class="hover:bg-slate-50/75 {{ ($status ?? 'active') === 'trashed' ? 'bg-rose-50/20' : '' }}">
                            @if (auth()->user()->role === 'super_admin')
                                <td class="w-10 p-3 text-center">
                                    <input type="checkbox" class="bulk-row-checkbox h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600"
                                           value="{{ $school->id }}"
                                           data-label="{{ $school->name }}"
                                           aria-label="Pilih sekolah {{ $school->name }}">
                                </td>
                            @endif
                            <td class="p-3 font-mono font-medium text-emerald-800">{{ $school->npsn }}</td>
                            <td class="p-3 font-medium text-slate-900">{{ $school->name }}</td>
                            <td class="p-3">
                                @if ($school->trashed())
                                    <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">Terhapus</span>
                                @elseif ($school->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">Nonaktif</span>
                                @endif
                            </td>
                            <td class="p-3 text-xs text-slate-500">
                                {{ ($status ?? 'active') === 'trashed' ? ($school->deleted_at?->format('d/m/Y H:i') ?? '-') : ($school->created_at?->format('d/m/Y') ?? '-') }}
                            </td>
                            <td class="p-3 text-center">
                                @if (auth()->user()->role === 'super_admin')
                                    @if (($status ?? 'active') === 'trashed')
                                        <button type="button" onclick="openRestoreSchoolModal('{{ $school->id }}', '{{ addslashes($school->name) }}', '{{ $school->npsn }}')" class="inline-flex min-h-[38px] items-center gap-1.5 rounded-md border border-emerald-300 bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 hover:bg-emerald-100" title="Pulihkan Sekolah">
                                            <x-icon name="rotate-ccw" class="h-4 w-4" />
                                            <span>Pulihkan</span>
                                        </button>
                                    @else
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button" onclick="openEditSchoolModal({{ json_encode($school) }})" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50" title="Edit Sekolah">
                                                <x-icon name="edit" class="h-4 w-4" />
                                            </button>
                                            <button type="button" onclick="openDeleteSchoolModal('{{ $school->id }}', '{{ addslashes($school->name) }}', '{{ $school->npsn }}')" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus sekolah">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'super_admin' ? 6 : 5 }}" class="p-6 text-center text-sm text-slate-500">
                                {{ ($status ?? 'active') === 'trashed' ? 'Tidak ada data sekolah di Tempat Sampah.' : 'Tidak ada data sekolah yang sesuai.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (auth()->user()->role === 'super_admin')
            <!-- Modal Edit Sekolah -->
            <div id="modal-edit-sekolah" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                <div class="w-full max-w-lg rounded-lg border border-slate-200 bg-white p-5 shadow-lg">
                    <div class="mb-3 flex items-center justify-between border-b border-slate-200 pb-2">
                        <h2 class="font-semibold text-slate-900" id="edit-school-title">Edit Data Sekolah</h2>
                        <button type="button" onclick="closeEditSchoolModal()" class="text-slate-400 hover:text-slate-600">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>
                    <form id="form-edit-sekolah" method="post" action="" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="edit-school-npsn" class="block text-xs font-semibold text-slate-700">NPSN (8 Digit Angka) *</label>
                            <input name="npsn" id="edit-school-npsn" maxlength="8" pattern="\d{8}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" required>
                        </div>
                        <div>
                            <label for="edit-school-name" class="block text-xs font-semibold text-slate-700">Nama Resmi Sekolah *</label>
                            <input name="name" id="edit-school-name" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" required>
                        </div>
                        <div>
                            <label for="edit-school-is_active" class="inline-flex items-center gap-2">
                                <input type="checkbox" name="is_active" id="edit-school-is_active" value="1" class="rounded border-slate-300 text-emerald-700">
                                <span class="text-xs font-semibold text-slate-700">Status Sekolah Aktif</span>
                            </label>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 pt-3">
                            <button type="button" onclick="closeEditSchoolModal()" class="min-h-[44px] rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                            <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Konfirmasi Hapus Sekolah (UX-007) -->
            <div id="modal-delete-sekolah" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
                <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-2xl">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <x-icon name="trash" class="h-6 w-6" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-bold text-slate-900">Konfirmasi Hapus Data Sekolah</h3>
                            <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                Apakah Anda yakin ingin memindahkan data sekolah <strong id="delete-school-name" class="text-slate-900">-</strong> (NPSN: <span id="delete-school-npsn" class="font-mono font-semibold">-</span>) ke Tempat Sampah?
                            </p>
                        </div>
                    </div>
                    <form id="form-delete-sekolah" method="post" action="" class="mt-6 flex items-center justify-end gap-2.5">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="closeDeleteSchoolModal()" class="min-h-[38px] rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-md bg-rose-600 px-4 text-xs font-semibold text-white shadow-xs hover:bg-rose-700">
                            <x-icon name="trash" class="h-4 w-4" />
                            <span>Ya, Hapus Sekolah</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modal Pulihkan Sekolah -->
            <div id="modal-restore-sekolah" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
                <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-2xl">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <x-icon name="rotate-ccw" class="h-6 w-6" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-bold text-slate-900">Konfirmasi Pemulihan Sekolah</h3>
                            <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                Apakah Anda yakin ingin memulihkan data sekolah <strong id="restore-school-name" class="text-slate-900">-</strong> (NPSN: <span id="restore-school-npsn" class="font-mono font-semibold">-</span>) dari Tempat Sampah?
                            </p>
                        </div>
                    </div>
                    <form id="form-restore-sekolah" method="post" action="" class="mt-6 flex items-center justify-end gap-2.5">
                        @csrf
                        <button type="button" onclick="closeRestoreSchoolModal()" class="min-h-[38px] rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800">
                            <x-icon name="rotate-ccw" class="h-4 w-4" />
                            <span>Pulihkan Sekolah</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="mt-4">
            {{ $schools->links() }}
        </div>
    </section>

    <script>
        function openCreateSchoolModal() {
            const modal = document.getElementById('modal-tambah-sekolah');
            if (modal) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    const first = document.getElementById('create-school-npsn');
                    if (first) first.focus();
                }, 50);
            }
        }

        function closeCreateSchoolModal() {
            const modal = document.getElementById('modal-tambah-sekolah');
            if (modal) modal.classList.add('hidden');
        }

        function openEditSchoolModal(school) {
            document.getElementById('edit-school-title').textContent = `Edit Sekolah: ${school.name}`;
            document.getElementById('edit-school-npsn').value = school.npsn;
            document.getElementById('edit-school-name').value = school.name;
            document.getElementById('edit-school-is_active').checked = !!school.is_active;
            document.getElementById('form-edit-sekolah').action = `/api/v1/schools/${school.id}`;
            document.getElementById('modal-edit-sekolah').classList.remove('hidden');
            setTimeout(() => {
                const first = document.getElementById('edit-school-name');
                if (first) first.focus();
            }, 50);
        }

        function closeEditSchoolModal() {
            document.getElementById('modal-edit-sekolah').classList.add('hidden');
        }

        function openDeleteSchoolModal(id, name, npsn) {
            document.getElementById('delete-school-name').textContent = name;
            document.getElementById('delete-school-npsn').textContent = npsn;
            document.getElementById('form-delete-sekolah').action = `/api/v1/schools/${id}`;
            document.getElementById('modal-delete-sekolah').classList.remove('hidden');
        }

        function closeDeleteSchoolModal() {
            document.getElementById('modal-delete-sekolah').classList.add('hidden');
        }

        function openRestoreSchoolModal(id, name, npsn) {
            document.getElementById('restore-school-name').textContent = name;
            document.getElementById('restore-school-npsn').textContent = npsn;
            document.getElementById('form-restore-sekolah').action = `/master/sekolah/${id}/restore`;
            document.getElementById('modal-restore-sekolah').classList.remove('hidden');
        }

        function closeRestoreSchoolModal() {
            document.getElementById('modal-restore-sekolah').classList.add('hidden');
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCreateSchoolModal();
                closeEditSchoolModal();
                closeDeleteSchoolModal();
                closeRestoreSchoolModal();
            }
        });
    </script>

    @if (auth()->user()->role === 'super_admin')
        <!-- Bulk Operations UI (UX-BULK-001) -->
        <x-bulk.table-toolbar entity-label="sekolah" :can-restore="true" :is-trashed="($status ?? 'active') === 'trashed'" />
        <x-bulk.impact-modal />
        <x-bulk.result-modal />

        <script src="/js/bulk-select.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const isTrashed = {{ ($status ?? 'active') === 'trashed' ? 'true' : 'false' }};
                window.bulkEngine = new BulkSelectEngine({
                    entityName: 'sekolah',
                    previewUrl: '/api/v1/schools/bulk-delete/preview',
                    previewRestoreUrl: '/api/v1/schools/bulk-restore/preview',
                    deleteUrl: '/api/v1/schools/bulk-delete',
                    restoreUrl: '/api/v1/schools/bulk-restore',
                    totalFilteredCount: {{ $schools->total() ?? count($schools) }},
                    confirmThreshold: 50,
                    filterParams: {
                        status: '{{ $status ?? 'active' }}',
                        q: '{{ request('q', '') }}'
                    }
                });
            });
        </script>
    @endif
</x-layouts.app>
