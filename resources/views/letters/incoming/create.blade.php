<x-layouts.app title="Tambah Surat Masuk">
    <form data-testid="incoming-letter-form" method="post" action="/api/v1/surat-masuk" enctype="multipart/form-data" class="grid min-h-screen grid-cols-1 gap-4 px-4 pb-28 pt-4 md:grid-cols-2 md:px-8" data-idempotent-form>
        @csrf
        <header class="md:col-span-2">
            <p class="text-sm font-semibold text-emerald-700">Surat Masuk</p>
            <h1 class="text-2xl font-semibold text-slate-900">Tambah Surat Masuk</h1>
            <p class="mt-1 text-xs text-slate-500">Catat surat masuk dari pihak eksternal, instansi, atau kedinasan.</p>
        </header>

        <div id="form-error-banner" class="hidden rounded-md border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 md:col-span-2"></div>
        <div id="duplicate-warning-banner" class="hidden rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 md:col-span-2">
            <p id="duplicate-warning-text" class="font-medium"></p>
            <label class="mt-2 flex items-center gap-2 text-xs font-semibold text-amber-950">
                <input type="checkbox" name="confirm_duplicate" id="confirm_duplicate" value="1" class="h-4 w-4 rounded border-amber-400">
                Saya mengonfirmasi bahwa surat ini sah dan bukan kesalahan duplikasi ganda.
            </label>
        </div>

        @if (auth()->user()->role === 'super_admin')
            <label class="block md:col-span-2">
                <span class="text-sm font-medium text-slate-800">Divisi Pengarsip *</span>
                <select name="division_id" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
                    @foreach (\App\Models\Division::where('status', 'active')->orderBy('name')->get() as $div)
                        <option value="{{ $div->id }}" @selected(session('active_division_id') == $div->id)>
                            {{ $div->code }} - {{ $div->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Nomor Surat *</span>
            <input name="nomor_surat" id="nomor_surat" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: 005/789/Disdik/2026" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Asal Surat (Instansi Pengirim) *</span>
            <input name="asal_surat" id="asal_surat" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: Kementerian Pendidikan Dasar" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Tanggal Surat *</span>
            <input name="tanggal_surat" id="tanggal_surat" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Tanggal Diterima *</span>
            <input name="tanggal_diterima" id="tanggal_diterima" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm font-medium text-slate-800">Perihal *</span>
            <textarea name="perihal" id="perihal" rows="4" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Perihal atau ringkasan isi surat masuk..." required></textarea>
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm font-medium text-slate-800">Lampiran Berkas (Opsional)</span>
            <span class="mt-1 flex min-h-[48px] items-center gap-3 rounded-md border border-slate-300 px-3">
                <x-icon name="upload" class="h-5 w-5 text-slate-600" />
                <input name="attachment" id="attachment" type="file" accept="application/pdf,image/*,.doc,.docx" class="w-full text-sm">
            </span>
        </label>

        <div class="sticky bottom-0 -mx-4 mt-auto flex gap-3 border-t border-slate-200 bg-white/95 p-4 md:col-span-2 md:mx-0 md:rounded-t-md">
            <a href="/surat-masuk" class="flex min-h-[44px] flex-1 items-center justify-center rounded-md border border-slate-300 px-4 font-medium text-slate-700 hover:bg-slate-100">Batal</a>
            <button type="submit" data-loading-label="Menyimpan..." class="min-h-[44px] flex-1 rounded-md bg-emerald-700 px-4 font-semibold text-white hover:bg-emerald-800">Simpan Surat</button>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-idempotent-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const errorBanner = document.getElementById('form-error-banner');
                const warningBanner = document.getElementById('duplicate-warning-banner');
                const warningText = document.getElementById('duplicate-warning-text');
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
                        if (data.duplicate_advisory && !document.getElementById('confirm_duplicate').checked) {
                            warningBanner.classList.remove('hidden');
                            warningText.textContent = data.message;
                            button.disabled = false;
                            button.textContent = 'Simpan Ulang (Konfirmasi Duplikat)';
                            return;
                        }

                        window.location.href = '/surat-masuk';
                    } else {
                        errorBanner.classList.remove('hidden');
                        if (data.errors) {
                            const errMsgs = Object.values(data.errors).flat().join(' ');
                            errorBanner.textContent = errMsgs;
                        } else {
                            errorBanner.textContent = data.message || 'Gagal menyimpan surat masuk. Periksa isian formulir.';
                        }
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                } catch (e) {
                    errorBanner.classList.remove('hidden');
                    errorBanner.textContent = 'Terjadi gangguan koneksi jaringan. Silakan coba kembali.';
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        });
    </script>
</x-layouts.app>
