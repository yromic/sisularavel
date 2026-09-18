<x-layouts.app title="Tambah Surat Masuk">
    <form data-testid="incoming-letter-form" method="post" action="/api/v1/surat-masuk" enctype="multipart/form-data" class="grid min-h-screen grid-cols-1 gap-4 px-4 pb-28 pt-4 md:grid-cols-2 md:px-8" data-idempotent-form>
        @csrf
        <header class="md:col-span-2">
            <p class="text-sm font-semibold text-emerald-700">Surat Masuk</p>
            <h1 class="text-2xl font-semibold">Tambah Surat Masuk</h1>
        </header>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium">Nomor Surat *</span>
            <input name="letter_number" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium">Tanggal Surat *</span>
            <input name="letter_date" type="date" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium">Tanggal Diterima *</span>
            <input name="received_date" type="date" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium">Asal Surat *</span>
            <input name="origin_agency" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3" required>
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm font-medium">Perihal *</span>
            <textarea name="subject" rows="4" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-3" required></textarea>
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm font-medium">Lampiran</span>
            <span class="mt-1 flex min-h-[48px] items-center gap-3 rounded-md border border-slate-300 px-3">
                <x-icon name="upload" class="h-5 w-5 text-slate-600" />
                <input name="attachment" type="file" accept="application/pdf,image/*,.doc,.docx" class="w-full text-sm">
            </span>
        </label>

        <div class="sticky bottom-0 -mx-4 mt-auto flex gap-3 border-t border-slate-200 bg-white/95 p-4 md:col-span-2 md:mx-0 md:rounded-t-md">
            <a href="/surat-masuk" class="flex min-h-[44px] flex-1 items-center justify-center rounded-md border border-slate-300 px-4 font-medium">Batal</a>
            <button type="submit" data-loading-label="Menyimpan..." class="min-h-[44px] flex-1 rounded-md bg-emerald-700 px-4 font-semibold text-white">Simpan Surat</button>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-idempotent-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = form.querySelector('[type="submit"]');
                button.disabled = true;
                button.textContent = button.dataset.loadingLabel;
                await fetch(form.action, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Idempotency-Key': crypto.randomUUID()},
                    body: new FormData(form),
                });
            });
        });
    </script>
</x-layouts.app>
