<x-layouts.app title="Akses Ditolak (403)">
    <div class="flex min-h-[70vh] flex-col items-center justify-center px-4 py-12 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600 mb-4">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">403 — Akses Ditolak</h1>
        <p class="mt-3 max-w-md text-sm text-slate-600">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses atau wewenang untuk membuka halaman atau data divisi ini.' }}
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
