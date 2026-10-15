<x-layouts.app title="Halaman Tidak Ditemukan (404)">
    <div class="flex min-h-[70vh] flex-col items-center justify-center px-4 py-12 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">404 — Data Tidak Ditemukan</h1>
        <p class="mt-3 max-w-md text-sm text-slate-600">
            {{ $exception->getMessage() ?: 'Halaman, berkas lampiran, atau arsip yang Anda cari tidak tersedia atau telah dipindahkan.' }}
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/dashboard' }}" class="inline-flex min-h-[42px] items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50">
                &larr; Kembali
            </a>
            <a href="/dashboard" class="inline-flex min-h-[42px] items-center rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800">
                Menuju Dashboard
            </a>
        </div>
    </div>
</x-layouts.app>
