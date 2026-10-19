<x-layouts.app title="Master Data Divisi">
    <section class="px-4 py-4 md:px-8">
        <x-page-header
            badge="Master Data"
            meta="Kewenangan: Super Admin"
            title="Manajemen Divisi Kerja"
            subtitle="Kelola unit kerja dan partisi tenant data di lingkungan dinas."
        >
            @if (auth()->user()->role === 'super_admin')
                <x-slot:actions>
                    <button type="button" onclick="openCreateDivisionModal()" class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span>+ Tambah Divisi</span>
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
                <div class="font-semibold mb-1">Gagal Menyimpan Data Divisi:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Search Bar -->
        <form method="get" action="/master/divisi" class="mb-4 flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3">
            <x-icon name="search" class="h-5 w-5 text-slate-600" />
            <input name="q" value="{{ request('q') }}" class="min-h-[42px] flex-1 border-0 bg-transparent text-sm focus:outline-hidden" placeholder="Cari berdasarkan nama atau kode divisi...">
            @if (request('q'))
                <a href="/master/divisi" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Modal Tambah Divisi (UX2-009 Standardized Popup Modal) -->
        @if (auth()->user()->role === 'super_admin')
            <div id="modal-tambah-divisi" class="{{ $errors->any() ? '' : 'hidden' }} fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="create-division-title">
                <div class="relative w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-2xl transition-all">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                        <h2 class="text-base font-bold text-slate-900" id="create-division-title">Tambah Divisi Baru</h2>
                        <button type="button" onclick="closeCreateDivisionModal()" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup modal tambah divisi">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>
                    <form method="post" action="/api/v1/divisions" class="space-y-4">
                        @csrf
                        <div>
                            <label for="create-division-code" class="block text-xs font-semibold text-slate-700">Kode Divisi (Unik) *</label>
                            <input name="code" id="create-division-code" value="{{ old('code') }}" maxlength="20" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm uppercase focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: DIK-03" required>
                        </div>
                        <div>
                            <label for="create-division-name" class="block text-xs font-semibold text-slate-700">Nama Divisi Lengkap *</label>
                            <input name="name" id="create-division-name" value="{{ old('name') }}" maxlength="150" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: Bidang Pembinaan SMP" required>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 pt-3">
                            <button type="button" onclick="closeCreateDivisionModal()" class="min-h-[44px] rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                            <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Divisi</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Table View -->
        <div class="overflow-x-auto rounded-md border border-slate-200 bg-white shadow-xs">
            <table class="w-full min-w-[768px] border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <th class="p-3">Kode</th>
                        <th class="p-3">Nama Divisi</th>
                        <th class="p-3">Statistik Data</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($divisions as $division)
                        <tr class="hover:bg-slate-50/75">
                            <td class="p-3 font-mono font-bold text-emerald-800">{{ $division->code }}</td>
                            <td class="p-3 font-medium text-slate-900">{{ $division->name }}</td>
                            <td class="p-3 text-xs text-slate-600">
                                <span class="inline-flex gap-2">
                                    <span title="Pengguna">{{ $division->users_count ?? 0 }} Staf</span> &bull;
                                    <span title="Surat Masuk">{{ $division->incoming_letters_count ?? 0 }} Masuk</span> &bull;
                                    <span title="Surat Keluar">{{ $division->outgoing_letters_count ?? 0 }} Keluar</span> &bull;
                                    <span title="Piagam">{{ $division->piagams_count ?? 0 }} Piagam</span>
                                </span>
                            </td>
                            <td class="p-3">
                                @if ($division->status === 'active')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">Nonaktif</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if (auth()->user()->role === 'super_admin')
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" onclick="openEditDivisionModal({{ json_encode($division) }})" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50" title="Edit Divisi">
                                            <x-icon name="edit" class="h-4 w-4" />
                                        </button>

                                        <!-- Toggle Status Form -->
                                        <form method="post" action="/api/v1/divisions/{{ $division->id }}/status" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $division->status === 'active' ? 'inactive' : 'active' }}">
                                            <button type="submit" class="min-h-[44px] rounded-md border border-slate-300 px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-100" title="Ubah status aktif/nonaktif">
                                                {{ $division->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <!-- Delete Division Button -->
                                        <button type="button" onclick="openDeleteDivisionModal('{{ $division->id }}', '{{ addslashes($division->code) }}', '{{ addslashes($division->name) }}')" class="min-h-[44px] rounded-md border border-rose-300 bg-rose-50 px-2.5 text-xs font-semibold text-rose-700 hover:bg-rose-100" title="Hapus divisi">
                                            Hapus
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-sm text-slate-500">Tidak ada data divisi yang ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (auth()->user()->role === 'super_admin')
            <!-- Modal Edit Divisi -->
            <div id="modal-edit-divisi" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                <div class="w-full max-w-lg rounded-lg border border-slate-200 bg-white p-5 shadow-lg">
                    <div class="mb-3 flex items-center justify-between border-b border-slate-200 pb-2">
                        <h2 class="font-semibold text-slate-900" id="edit-division-title">Edit Divisi</h2>
                        <button type="button" onclick="closeEditDivisionModal()" class="text-slate-400 hover:text-slate-600">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>
                    <form id="form-edit-divisi" method="post" action="" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="edit-division-code" class="block text-xs font-semibold text-slate-700">Kode Divisi (Unik) *</label>
                            <input name="code" id="edit-division-code" maxlength="20" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm uppercase focus:border-emerald-700 focus:outline-hidden" required>
                        </div>
                        <div>
                            <label for="edit-division-name" class="block text-xs font-semibold text-slate-700">Nama Divisi Lengkap *</label>
                            <input name="name" id="edit-division-name" maxlength="150" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" required>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 pt-3">
                            <button type="button" onclick="closeEditDivisionModal()" class="min-h-[44px] rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                            <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Konfirmasi Hapus Divisi (UX-007) -->
            <div id="modal-delete-divisi" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
                <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-2xl">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <x-icon name="trash" class="h-6 w-6" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-bold text-slate-900">Konfirmasi Hapus Divisi</h3>
                            <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                Apakah Anda yakin ingin menghapus divisi <strong id="delete-division-code" class="text-slate-900">-</strong> (<span id="delete-division-name">-</span>)?
                            </p>
                            <p class="mt-1.5 text-[11px] text-slate-500">
                                Divisi yang telah memiliki keterkaitan arsip surat atau pengguna akan dialihkan statusnya ke nonaktif untuk menjaga integritas data.
                            </p>
                        </div>
                    </div>
                    <form id="form-delete-divisi" method="post" action="" class="mt-6 flex items-center justify-end gap-2.5">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="closeDeleteDivisionModal()" class="min-h-[38px] rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-md bg-rose-600 px-4 text-xs font-semibold text-white shadow-xs hover:bg-rose-700">
                            <x-icon name="trash" class="h-4 w-4" />
                            <span>Ya, Hapus Divisi</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="mt-4">
            {{ $divisions->links() }}
        </div>
    </section>

    <script>
        function openCreateDivisionModal() {
            const modal = document.getElementById('modal-tambah-divisi');
            if (modal) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    const first = document.getElementById('create-division-code');
                    if (first) first.focus();
                }, 50);
            }
        }

        function closeCreateDivisionModal() {
            const modal = document.getElementById('modal-tambah-divisi');
            if (modal) modal.classList.add('hidden');
        }

        function openEditDivisionModal(division) {
            document.getElementById('edit-division-title').textContent = `Edit Divisi: ${division.code}`;
            document.getElementById('edit-division-code').value = division.code;
            document.getElementById('edit-division-name').value = division.name;
            document.getElementById('form-edit-divisi').action = `/api/v1/divisions/${division.id}`;
            document.getElementById('modal-edit-divisi').classList.remove('hidden');
            setTimeout(() => {
                const first = document.getElementById('edit-division-name');
                if (first) first.focus();
            }, 50);
        }

        function closeEditDivisionModal() {
            document.getElementById('modal-edit-divisi').classList.add('hidden');
        }

        function openDeleteDivisionModal(id, code, name) {
            document.getElementById('delete-division-code').textContent = code;
            document.getElementById('delete-division-name').textContent = name;
            document.getElementById('form-delete-divisi').action = `/api/v1/divisions/${id}`;
            document.getElementById('modal-delete-divisi').classList.remove('hidden');
        }

        function closeDeleteDivisionModal() {
            document.getElementById('modal-delete-divisi').classList.add('hidden');
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCreateDivisionModal();
                closeEditDivisionModal();
                closeDeleteDivisionModal();
            }
        });
    </script>
</x-layouts.app>
