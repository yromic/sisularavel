<x-layouts.app title="Tambah Surat Keluar">
    <form data-testid="outgoing-letter-form" method="post" action="/api/v1/surat-keluar" enctype="multipart/form-data" class="grid min-h-screen grid-cols-1 gap-4 px-4 pb-28 pt-4 md:grid-cols-2 md:px-8" data-idempotent-form>
        @csrf
        <header class="md:col-span-2">
            <p class="text-sm font-semibold text-emerald-700">Surat Keluar</p>
            <h1 class="text-2xl font-semibold text-slate-900">Tambah Surat Keluar</h1>
            <p class="mt-1 text-xs text-slate-500">Nomor surat keluar unik per divisi dan tahun kalender.</p>
        </header>

        <div id="form-error-banner" class="hidden rounded-md border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 md:col-span-2"></div>

        @if(auth()->user()->role === 'super_admin')
            @php
                $divisions = \App\Models\Division::where('status', 'active')->get();
            @endphp
            <label class="block md:col-span-2">
                <span class="text-sm font-medium text-slate-800">Divisi Pemilik Surat *</span>
                <select name="division_id" id="division_id" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}" {{ session('active_division_id') == $div->id ? 'selected' : '' }}>{{ $div->name }} ({{ $div->code }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Pilih divisi yang menerbitkan surat keluar ini.</p>
            </label>
        @endif

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Nomor Surat *</span>
            <input name="nomor_surat" id="nomor_surat" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: 420/123/2026" required>
            <p class="mt-1 text-xs text-slate-500">Nomor akan divalidasi keunikannya dalam tahun surat yang sama.</p>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Tanggal Surat *</span>
            <input name="tanggal_surat" id="tanggal_surat" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Tanggal Share / Disseminasi *</span>
            <input name="tanggal_share" id="tanggal_share" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm font-medium text-slate-800">Perihal *</span>
            <textarea name="perihal" id="perihal" rows="4" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Perihal atau isi ringkas surat keluar..." required></textarea>
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm font-medium text-slate-800">Lampiran Dokumen (Opsional)</span>
            <span class="mt-1 flex min-h-[48px] items-center gap-3 rounded-md border border-slate-300 px-3">
                <x-icon name="upload" class="h-5 w-5 text-slate-600" />
                <input name="attachment" type="file" accept="application/pdf,image/*,.doc,.docx" class="w-full text-sm">
            </span>
        </label>

        <div class="sticky bottom-0 -mx-4 mt-auto flex gap-3 border-t border-slate-200 bg-white/95 p-4 md:col-span-2 md:mx-0 md:rounded-t-md">
            <a href="/surat-keluar" class="flex min-h-[44px] flex-1 items-center justify-center rounded-md border border-slate-300 px-4 font-medium text-slate-700 hover:bg-slate-100">Batal</a>
            <button type="submit" data-loading-label="Menyimpan..." class="min-h-[44px] flex-1 rounded-md bg-emerald-700 px-4 font-semibold text-white hover:bg-emerald-800">Simpan Surat Keluar</button>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-idempotent-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const errorBanner = document.getElementById('form-error-banner');
                errorBanner.classList.add('hidden');
                errorBanner.textContent = '';

                const button = form.querySelector('[type="submit"]');
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = button.dataset.loadingLabel;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Idempotency-Key': crypto.randomUUID(),
                            'Accept': 'application/json'
                        },
                        body: new FormData(form),
                    });

                    const data = await response.json();
                    if (response.ok) {
                        window.location.href = '/surat-keluar';
                    } else {
                        errorBanner.classList.remove('hidden');
                        errorBanner.textContent = data.message || 'Gagal menyimpan surat keluar. Periksa isian form.';
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                } catch (e) {
                    errorBanner.classList.remove('hidden');
                    errorBanner.textContent = 'Terjadi gangguan koneksi. Silakan coba kembali.';
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        });
    </script>
</x-layouts.app>
