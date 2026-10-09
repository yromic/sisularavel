<x-layouts.app title="Manajemen Pengguna">
    <section class="px-4 py-4 md:px-8">
        <header class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Master Data</p>
                <h1 class="text-xl font-semibold text-slate-900">Manajemen Pengguna & Staf</h1>
                <p class="text-xs text-slate-500">Kelola akun operator dan administrator sesuai lingkup divisi kerja aktif.</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-tambah-user').classList.toggle('hidden')" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-3 font-semibold text-white hover:bg-emerald-800">
                <x-icon name="plus" class="h-5 w-5" />
                + Tambah Pengguna
            </button>
        </header>

        <!-- Secret Issue Result Display -->
        <div id="secret-result-banner" class="hidden mb-4 rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 shadow-xs"></div>

        <!-- Modal Tambah Pengguna -->
        <div id="modal-tambah-user" class="hidden mb-6 rounded-lg border border-emerald-300 bg-white p-5 shadow-sm">
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
                    <input name="username" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: op_dikdas02" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Nama Lengkap *</label>
                    <input name="name" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Nama lengkap pegawai/operator" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Email Resmi *</label>
                    <input name="email" type="email" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="email@disdikbudpora.test" required>
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

        <!-- Users Table -->
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
                                    @if ($user->must_reset_password)
                                        <button type="button" onclick="issueSecret({{ $user->id }}, '{{ $user->username }}')" class="min-h-[44px] rounded-md border border-amber-400 bg-amber-50 px-2.5 text-xs font-semibold text-amber-900 hover:bg-amber-100" title="Terbitkan Kunci Rahasia Sekali Pakai (Path B)">
                                            Kunci Rahasia
                                        </button>
                                    @endif

                                    @if ($user->id !== auth()->id())
                                        <form method="post" action="/api/v1/users/{{ $user->id }}" onsubmit="return confirm('Hapus akun pengguna {{ $user->username }}?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus pengguna">
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

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </section>

    <script>
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
