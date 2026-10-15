<x-layouts.app title="Manajemen Pengguna">
    <section class="px-4 py-4 md:px-8">
        <header class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Master Data</p>
                <h1 class="text-xl font-semibold text-slate-900">Manajemen Pengguna & Staf</h1>
                <p class="text-xs text-slate-500">
                    @if (($status ?? 'active') === 'trashed')
                        Daftar akun pengguna yang diarsipkan (soft-deleted). Administrator berwenang dapat memulihkan akun ke status nonaktif.
                    @else
                        Kelola akun operator dan administrator sesuai lingkup divisi kerja aktif.
                    @endif
                </p>
            </div>
            @if (($status ?? 'active') !== 'trashed')
                <button type="button" onclick="document.getElementById('modal-tambah-user').classList.toggle('hidden')" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-3 font-semibold text-white hover:bg-emerald-800">
                    <x-icon name="plus" class="h-5 w-5" />
                    + Tambah Pengguna
                </button>
            @endif
        </header>

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
                <div class="font-semibold mb-1">Gagal Menyimpan Data Pengguna:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Secret Issue Result Display -->
        <div id="secret-result-banner" class="hidden mb-4 rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 shadow-xs"></div>

        <!-- Tab Navigation: Pengguna Aktif vs Pengguna Terhapus -->
        <div class="mb-5 flex items-center gap-2 border-b border-slate-200">
            <a href="/master/pengguna?status=active"
               class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ ($status ?? 'active') === 'active' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <span>Pengguna Aktif</span>
                <span class="rounded-full px-2 py-0.5 text-xs {{ ($status ?? 'active') === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $activeCount ?? 0 }}
                </span>
            </a>
            <a href="/master/pengguna?status=trashed"
               class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ ($status ?? 'active') === 'trashed' ? 'border-rose-600 text-rose-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <span>Pengguna Terhapus (Arsip)</span>
                <span class="rounded-full px-2 py-0.5 text-xs {{ ($status ?? 'active') === 'trashed' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $trashedCount ?? 0 }}
                </span>
            </a>
        </div>

        <!-- Modal Tambah Pengguna -->
        <div id="modal-tambah-user" class="{{ $errors->any() && ($status ?? 'active') !== 'trashed' ? '' : 'hidden' }} mb-6 rounded-lg border border-emerald-300 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between border-b border-slate-200 pb-2">
                <h2 class="font-semibold text-slate-900">Tambah Akun Pengguna Baru</h2>
                <button type="button" onclick="document.getElementById('modal-tambah-user').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>
            <form id="form-create-user" method="post" action="/api/v1/users" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Username *</label>
                    <input name="username" value="{{ old('username') }}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: op_dikdas02" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Nama Lengkap *</label>
                    <input name="name" value="{{ old('name') }}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Nama lengkap pegawai/operator" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Email Resmi *</label>
                    <input name="email" type="email" value="{{ old('email') }}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="email@disdikbudpora.test" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Peran (Role) *</label>
                    <select name="role" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden" required>
                        <option value="operator">Operator Divisi</option>
                        @if (auth()->user()->role === 'super_admin')
                            <option value="division_admin">Admin Divisi</option>
                            <option value="super_admin">Super Admin</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Divisi Kerja *</label>
                    <select name="division_id" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden">
                        @if (auth()->user()->role === 'super_admin')
                            <option value="">-- Tanpa Divisi (Global Super Admin) --</option>
                        @endif
                        @foreach ($divisions as $division)
                            <option value="{{ $division->id }}" @selected(auth()->user()->division_id === $division->id)>
                                {{ $division->code }} - {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Password Sementara *</label>
                    <input name="password" type="password" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Minimal 8 karakter" required>
                </div>
                <div class="sm:col-span-2 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-tambah-user').classList.add('hidden')" class="min-h-[44px] rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                    <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Akun</button>
                </div>
            </form>
        </div>

        @if (($status ?? 'active') === 'trashed')
            <!-- Trashed Users Table -->
            <div class="overflow-x-auto rounded-md border border-slate-200 bg-white shadow-xs">
                <table class="w-full min-w-[768px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-rose-50/50 text-xs font-semibold uppercase tracking-wider text-slate-700">
                            <th class="p-3">Nama Lengkap</th>
                            <th class="p-3">Username</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Divisi Terakhir</th>
                            <th class="p-3">Role Terakhir</th>
                            <th class="p-3">Tanggal Dihapus</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50/75">
                                <td class="p-3 font-medium text-slate-900">{{ $user->name }}</td>
                                <td class="p-3 font-mono text-slate-700">{{ $user->username }}</td>
                                <td class="p-3 text-slate-600">{{ $user->email ?? '-' }}</td>
                                <td class="p-3 text-xs text-slate-600">{{ $user->division?->name ?? 'Global' }}</td>
                                <td class="p-3">
                                    <span class="inline-flex rounded-xs bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 uppercase">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                </td>
                                <td class="p-3 text-xs text-rose-700 font-mono">
                                    {{ $user->deleted_at ? $user->deleted_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="p-3 text-center">
                                    <button type="button"
                                            onclick="openRestoreModal({{ $user->id }}, '{{ addslashes($user->username) }}', '{{ addslashes($user->name) }}', '{{ addslashes($user->email ?? '-') }}')"
                                            class="inline-flex min-h-[38px] items-center gap-1.5 rounded-md border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-xs">
                                        <x-icon name="refresh-cw" class="h-3.5 w-3.5" />
                                        Pulihkan Akun
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-sm text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <x-icon name="trash" class="h-8 w-8 text-slate-300" />
                                        <p class="font-medium text-slate-600">Tidak ada akun pengguna yang berada dalam arsip terhapus.</p>
                                        <p class="text-xs text-slate-400">Seluruh akun yang dihapus secara logis akan tercantum di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <!-- Active Users Table -->
            <div class="overflow-x-auto rounded-md border border-slate-200 bg-white shadow-xs">
                <table class="w-full min-w-[768px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="p-3">Username</th>
                            <th class="p-3">Nama</th>
                            <th class="p-3">Role</th>
                            <th class="p-3">Divisi</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-center">Aksi / Aktivasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50/75">
                                <td class="p-3 font-mono font-medium text-slate-900">{{ $user->username }}</td>
                                <td class="p-3">{{ $user->name }}</td>
                                <td class="p-3">
                                    <span class="inline-flex rounded-xs bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 uppercase">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                </td>
                                <td class="p-3 text-xs text-slate-600">{{ $user->division?->name ?? 'Global' }}</td>
                                <td class="p-3">
                                    @if ($user->must_reset_password)
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Wajib Reset</span>
                                    @elseif ($user->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" onclick="openEditUserModal({{ json_encode($user) }})" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50" title="Edit Pengguna">
                                            <x-icon name="settings" class="h-4 w-4" />
                                        </button>

                                        @if ($user->must_reset_password)
                                            <button type="button" onclick="issueSecret({{ $user->id }}, '{{ $user->username }}')" class="min-h-[44px] rounded-md border border-amber-400 bg-amber-50 px-2.5 text-xs font-semibold text-amber-900 hover:bg-amber-100" title="Terbitkan Kunci Rahasia Sekali Pakai (Path B)">
                                                Kunci Rahasia
                                            </button>
                                        @endif

                                        @if ($user->id !== auth()->id())
                                            <form method="post" action="/api/v1/users/{{ $user->id }}" onsubmit="return confirm('Hapus akun pengguna {{ $user->username }}? Akun akan diarsipkan dan dapat dipulihkan kapan saja.')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus pengguna (Soft Delete)">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400" title="Akun yang sedang aktif">(Anda)</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-sm text-slate-500">Tidak ada pengguna ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Modal Edit Pengguna -->
        <div id="modal-edit-user" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-2xl rounded-lg border border-slate-200 bg-white p-5 shadow-lg">
                <div class="mb-3 flex items-center justify-between border-b border-slate-200 pb-2">
                    <h2 class="font-semibold text-slate-900" id="edit-user-title">Edit Pengguna</h2>
                    <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>
                <form id="form-edit-user" method="post" action="" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Username</label>
                        <input id="edit-username" type="text" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600" disabled>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nama Lengkap *</label>
                        <input name="name" id="edit-name" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Email Resmi</label>
                        <input name="email" id="edit-email" type="email" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Password Baru (Opsional)</label>
                        <input name="password" id="edit-password" type="password" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Kosongkan jika tidak diubah">
                    </div>
                    @if (auth()->user()->role === 'super_admin')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Peran (Role) *</label>
                            <select name="role" id="edit-role" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden">
                                <option value="operator">Operator Divisi</option>
                                <option value="division_admin">Admin Divisi</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Divisi Kerja</label>
                            <select name="division_id" id="edit-division_id" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden">
                                <option value="">-- Tanpa Divisi (Global) --</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->code }} - {{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="edit-is_active" value="1" class="rounded border-slate-300 text-emerald-700">
                            <span class="text-xs font-semibold text-slate-700">Akun Pengguna Aktif</span>
                        </label>
                    </div>
                    <div class="sm:col-span-2 flex justify-end gap-2 border-t border-slate-200 pt-3">
                        <button type="button" onclick="closeEditUserModal()" class="min-h-[44px] rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="min-h-[44px] rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Restore Pengguna -->
        <div id="modal-confirm-restore" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-5 shadow-lg">
                <div class="mb-3 flex items-center justify-between border-b border-slate-200 pb-2">
                    <h2 class="font-semibold text-slate-900">Konfirmasi Pemulihan Akun</h2>
                    <button type="button" onclick="closeRestoreModal()" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>
                <form id="form-restore-user" method="post" action="" onsubmit="submitRestoreForm(event)">
                    @csrf
                    <div class="space-y-3 mb-4">
                        <p class="text-sm text-slate-600">
                            Apakah Anda yakin ingin memulihkan akun pengguna berikut dari arsip?
                        </p>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs space-y-1">
                            <div><strong class="text-slate-700">Username:</strong> <span id="restore-username" class="font-mono text-slate-900"></span></div>
                            <div><strong class="text-slate-700">Nama:</strong> <span id="restore-name" class="text-slate-900"></span></div>
                            <div><strong class="text-slate-700">Email:</strong> <span id="restore-email" class="text-slate-900"></span></div>
                        </div>
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                            <strong>Catatan Keamanan:</strong> Akun yang dipulihkan akan berstatus <em>Nonaktif</em> secara default. Administrator dapat mengaktifkannya kembali setelah proses pemulihan selesai.
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-200 pt-3">
                        <button type="button" onclick="closeRestoreModal()" class="min-h-[44px] rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                        <button type="submit" id="btn-submit-restore" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                            <span id="btn-restore-spinner" class="hidden">Memproses...</span>
                            <span id="btn-restore-text">Pulihkan Akun Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </section>

    <script>
        function openEditUserModal(user) {
            document.getElementById('edit-user-title').textContent = `Edit Pengguna: ${user.username}`;
            document.getElementById('edit-username').value = user.username;
            document.getElementById('edit-name').value = user.name;
            document.getElementById('edit-email').value = user.email || '';
            document.getElementById('edit-password').value = '';
            document.getElementById('edit-is_active').checked = !!user.is_active;

            const roleSelect = document.getElementById('edit-role');
            if (roleSelect) roleSelect.value = user.role;

            const divSelect = document.getElementById('edit-division_id');
            if (divSelect) divSelect.value = user.division_id || '';

            document.getElementById('form-edit-user').action = `/api/v1/users/${user.id}`;
            document.getElementById('modal-edit-user').classList.remove('hidden');
        }

        function closeEditUserModal() {
            document.getElementById('modal-edit-user').classList.add('hidden');
        }

        function openRestoreModal(userId, username, name, email) {
            document.getElementById('restore-username').textContent = username;
            document.getElementById('restore-name').textContent = name;
            document.getElementById('restore-email').textContent = email;
            document.getElementById('form-restore-user').action = `/master/pengguna/${userId}/restore`;
            document.getElementById('btn-submit-restore').disabled = false;
            document.getElementById('btn-submit-restore').classList.remove('opacity-75');
            document.getElementById('btn-restore-spinner').classList.add('hidden');
            document.getElementById('btn-restore-text').classList.remove('hidden');
            document.getElementById('modal-confirm-restore').classList.remove('hidden');
        }

        function closeRestoreModal() {
            document.getElementById('modal-confirm-restore').classList.add('hidden');
        }

        function submitRestoreForm(event) {
            const btn = document.getElementById('btn-submit-restore');
            btn.disabled = true;
            btn.classList.add('opacity-75');
            document.getElementById('btn-restore-spinner').classList.remove('hidden');
            document.getElementById('btn-restore-text').classList.add('hidden');
        }

        async function issueSecret(userId, username) {
            if (!confirm(`Terbitkan kunci rahasia aktivasi sekali pakai untuk akun ${username}?`)) return;
            const banner = document.getElementById('secret-result-banner');
            banner.classList.add('hidden');
            try {
                const response = await fetch(`/admin/users/${userId}/activation-secret`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (response.ok) {
                    banner.classList.remove('hidden');
                    banner.innerHTML = `<strong>Kunci Rahasia untuk ${username}:</strong> <code class="bg-white px-2 py-1 rounded font-mono font-bold text-amber-950">${data.temporary_secret}</code><br><span class="text-xs">Berikan kode ini kepada operator. Kunci ini kedaluwarsa dalam 24 jam.</span>`;
                } else {
                    alert(data.message || 'Gagal menerbitkan kunci rahasia.');
                }
            } catch (e) {
                alert('Terjadi gangguan koneksi.');
            }
        }
    </script>
</x-layouts.app>
