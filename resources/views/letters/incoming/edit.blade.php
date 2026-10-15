<x-layouts.app :title="'Edit Surat Masuk - ' . $letter->letter_number">
    <div class="px-4 py-4 md:px-8">
        <header class="mb-6">
            <nav class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                <a href="/surat-masuk" class="hover:text-emerald-700">Surat Masuk</a>
                <span>/</span>
                <a href="/surat-masuk/{{ $letter->id }}" class="hover:text-emerald-700">Detail #{{ $letter->id }}</a>
                <span>/</span>
                <span class="font-medium text-slate-800">Edit</span>
            </nav>
            <h1 class="text-2xl font-semibold text-slate-900">Perbarui Surat Masuk</h1>
            <p class="mt-1 text-xs text-slate-500">Perbaiki informasi metadata surat masuk resmi.</p>
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

        <form method="post" action="/api/v1/surat-masuk/{{ $letter->id }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-xs md:grid-cols-2">
            @csrf
            @method('PUT')

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Nomor Surat *</span>
                <input name="nomor_surat" id="nomor_surat" value="{{ old('nomor_surat', $letter->letter_number) }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
            </label>

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Asal Surat (Instansi) *</span>
                <input name="asal_surat" id="asal_surat" value="{{ old('asal_surat', $letter->origin_agency) }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
            </label>

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Tanggal Surat *</span>
                <input name="tanggal_surat" id="tanggal_surat" type="date" value="{{ old('tanggal_surat', $letter->letter_date->format('Y-m-d')) }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
            </label>

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Tanggal Diterima *</span>
                <input name="tanggal_diterima" id="tanggal_diterima" type="date" value="{{ old('tanggal_diterima', $letter->received_date->format('Y-m-d')) }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm font-medium text-slate-800">Perihal *</span>
                <textarea name="perihal" id="perihal" rows="4" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-3 text-base focus:border-emerald-700 focus:outline-hidden" required>{{ old('perihal', $letter->subject) }}</textarea>
            </label>

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Petugas Terakhir Mengubah</span>
                <input type="text" value="{{ auth()->user()->name }} ({{ auth()->user()->username }})" readonly class="mt-1 min-h-[48px] w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-base text-slate-600 cursor-not-allowed">
            </label>

            <label class="block md:col-span-1">
                <span class="text-sm font-medium text-slate-800">Divisi Pengarsip</span>
                <input type="text" value="{{ $letter->division?->name }} ({{ $letter->division?->code }})" readonly class="mt-1 min-h-[48px] w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-base text-slate-600 cursor-not-allowed">
            </label>

            <div class="mt-4 flex gap-3 border-t border-slate-200 pt-4 md:col-span-2">
                <a href="/surat-masuk/{{ $letter->id }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-md border border-slate-300 px-4 font-medium text-slate-700 hover:bg-slate-100">Batal</a>
                <button type="submit" class="min-h-[44px] flex-1 rounded-md bg-emerald-700 px-4 font-semibold text-white hover:bg-emerald-800">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-layouts.app>
