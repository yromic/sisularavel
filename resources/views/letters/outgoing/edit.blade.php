<x-layouts.app :title="'Edit Surat Keluar - ' . $letter->letter_number">
    <div class="px-4 py-4 md:px-8">
        <header class="mb-6">
            <nav class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                <a href="/surat-keluar" class="hover:text-emerald-700">Surat Keluar</a>
                <span>/</span>
                <a href="/surat-keluar/{{ $letter->id }}" class="hover:text-emerald-700">Detail #{{ $letter->id }}</a>
                <span>/</span>
                <span class="font-medium text-slate-800">Edit</span>
            </nav>
            <h1 class="text-2xl font-semibold text-slate-900">Perbarui Surat Keluar</h1>
            <p class="mt-1 text-xs text-slate-500">Perbarui metadata surat keluar resmi.</p>
        </header>

        @if (session('success'))
            <div class="mb-6 rounded-md border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900 shadow-xs">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-md border border-rose-300 bg-rose-50 p-4 text-sm font-medium text-rose-900 shadow-xs">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-md border border-rose-300 bg-rose-50 p-4 text-sm text-rose-800 shadow-xs">
                <div class="font-semibold mb-1">Gagal Menyimpan Perubahan:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="/api/v1/surat-keluar/{{ $letter->id }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-xs md:grid-cols-2">
            @csrf
            @method('PUT')

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Nomor Surat *</span>
                <input name="nomor_surat" id="nomor_surat" value="{{ old('nomor_surat', $letter->letter_number) }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
            </label>

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Tanggal Surat *</span>
                <input name="tanggal_surat" id="tanggal_surat" type="date" value="{{ old('tanggal_surat', $letter->letter_date->format('Y-m-d')) }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
            </label>

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Tanggal Share / Diseminasi *</span>
                <input name="tanggal_share" id="tanggal_share" type="date" value="{{ old('tanggal_share', $letter->disseminated_date ? $letter->disseminated_date->format('Y-m-d') : $letter->letter_date->format('Y-m-d')) }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
            </label>

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Divisi Penerbit</span>
                <input type="text" value="{{ $letter->division?->name }} ({{ $letter->division?->code }})" readonly class="mt-1 min-h-[48px] w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-base text-slate-600 cursor-not-allowed">
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm font-medium text-slate-800">Perihal *</span>
                <textarea name="perihal" id="perihal" rows="4" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-3 text-base focus:border-emerald-700 focus:outline-hidden" required>{{ old('perihal', $letter->subject) }}</textarea>
            </label>

            <div class="block md:col-span-2">
                <span class="text-sm font-medium text-slate-800">Perbarui Lampiran (Opsional)</span>
                <p class="text-xs text-slate-500 mb-2">Pilih berkas baru jika ingin mengganti lampiran saat ini (Maksimal 2 MB).</p>
                <input name="attachment" type="file" accept="application/pdf,image/*,.doc,.docx" onchange="if(this.files[0] && this.files[0].size > 2097152){ alert('Ukuran berkas melebihi batas 2 MB.'); this.value = ''; }" class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            </div>

            <div class="mt-4 flex gap-3 border-t border-slate-200 pt-4 md:col-span-2">
                <a href="/surat-keluar/{{ $letter->id }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-md border border-slate-300 px-4 font-medium text-slate-700 hover:bg-slate-100">Batal</a>
                <button type="submit" class="min-h-[44px] flex-1 rounded-md bg-emerald-700 px-4 font-semibold text-white hover:bg-emerald-800">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-layouts.app>
