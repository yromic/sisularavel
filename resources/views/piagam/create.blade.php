<x-layouts.app title="Tambah Piagam">
    <form data-testid="piagam-create-form" method="post" action="/api/v1/piagam" class="grid min-h-screen grid-cols-1 gap-4 px-4 pb-28 pt-4 md:grid-cols-2 md:px-8" data-idempotent-form>
        @csrf
        <header class="md:col-span-2">
            <p class="text-sm font-semibold text-emerald-700">Piagam & Sertifikat</p>
            <h1 class="text-2xl font-semibold text-slate-900">Tambah Piagam Baru</h1>
            <p class="mt-1 text-xs text-slate-500">Daftarkan piagam siswa untuk dapat diklaim/diambil oleh pihak sekolah atau perwakilan.</p>
        </header>

        <div id="form-error-banner" class="hidden rounded-md border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 md:col-span-2"></div>

        <label class="block md:col-span-2">
            <span class="text-sm font-medium text-slate-800">Nama Lengkap Siswa *</span>
            <input name="student_name" id="student_name" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Nama lengkap siswa penerima penghargaan..." required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Asal Sekolah (NPSN) *</span>
            <select name="npsn" id="npsn" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
                <option value="">-- Pilih Sekolah (NPSN) --</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->npsn }}">{{ $school->npsn }} - {{ $school->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Kategori Lomba *</span>
            <select name="competition_category_id" id="competition_category_id" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
                <option value="">-- Pilih Kategori Lomba --</option>
                @foreach ($competitionCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Tahun Lomba *</span>
            <input name="competition_year" id="competition_year" type="number" min="1900" max="2100" value="{{ date('Y') }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
        </label>

        <div class="sticky bottom-0 -mx-4 mt-auto flex gap-3 border-t border-slate-200 bg-white/95 p-4 md:col-span-2 md:mx-0 md:rounded-t-md">
            <a href="/piagam" class="flex min-h-[44px] flex-1 items-center justify-center rounded-md border border-slate-300 px-4 font-medium text-slate-700 hover:bg-slate-100">Batal</a>
            <button type="submit" data-loading-label="Menyimpan..." class="min-h-[44px] flex-1 rounded-md bg-emerald-700 px-4 font-semibold text-white hover:bg-emerald-800">Simpan Piagam</button>
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
                        window.location.href = '/piagam';
                    } else {
                        errorBanner.classList.remove('hidden');
                        errorBanner.textContent = data.message || 'Gagal menyimpan data piagam. Periksa isian formulir.';
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                } catch (e) {
                    errorBanner.classList.remove('hidden');
                    errorBanner.textContent = 'Terjadi gangguan jaringan. Silakan coba kembali.';
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        });
    </script>
</x-layouts.app>
