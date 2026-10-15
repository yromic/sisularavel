<x-layouts.app title="Sesi Berakhir (419)">
    <div class="flex min-h-[70vh] flex-col items-center justify-center px-4 py-12 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-blue-600 mb-4">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">419 — Sesi Halaman Berakhir</h1>
        <p class="mt-3 max-w-md text-sm text-slate-600">
            Masa berlaku form atau token keamanan telah kedaluwarsa karena tidak ada aktivitas. Silakan muat ulang halaman atau login kembali.
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="javascript:location.reload()" class="inline-flex min-h-[42px] items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50">
                Muat Ulang Halaman
            </a>
            <a href="/login" class="inline-flex min-h-[42px] items-center rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800">
                Login Kembali
            </a>
        </div>
    </div>
</x-layouts.app>
