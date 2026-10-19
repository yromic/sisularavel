<x-layouts.app title="Tanda Terima Pengambilan Piagam">
    <!-- Top Bar Navigasi Publik -->
    <header class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-xs">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/icon/icon.svg') }}" alt="Logo SISURAT" class="h-8 w-8 shrink-0 object-contain drop-shadow-xs" />
                <div>
                    <span class="text-sm font-bold text-slate-900 leading-tight">SISURAT</span>
                    <span class="text-xs text-slate-400 hidden sm:inline-block">· Disdikbudpora Kab. Semarang</span>
                </div>
            </div>

            <a href="/" class="inline-flex min-h-[40px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 transition-colors">
                <x-icon name="arrow-left" class="h-3.5 w-3.5 text-emerald-700" />
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </header>

    <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6">
        <div class="mx-auto max-w-3xl">
            <!-- Header Section -->
            <header class="mb-6 text-center">
                <span class="inline-flex rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-bold text-emerald-800 tracking-wide uppercase">
                    Loket Pelayanan Tamu & Pengambilan Piagam Fisik
                </span>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    Tanda Terima Pengambilan Piagam
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 max-w-xl mx-auto">
                    Silakan isi data berikut sebagai bukti penerimaan piagam dari dinas.
                </p>
            </header>

            <!-- Single-Action Handover Receipt Component -->
            <x-piagam.claim-form
                :schools="$schools ?? collect()"
                :divisions="$divisions ?? collect()"
                :work-units="$workUnits ?? collect()"
                :pickup-categories="$pickupCategories ?? collect()"
                :competition-categories="$competitionCategories ?? collect()" />
        </div>
    </div>
</x-layouts.app>
