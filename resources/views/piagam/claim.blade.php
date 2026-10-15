<x-layouts.app title="Tanda Terima Pengambilan Piagam">
    <div class="min-h-screen bg-slate-50 py-6 px-4 sm:px-6">
        <div class="mx-auto max-w-3xl">
            <!-- Header Section -->
            <header class="mb-6 text-center">
                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 tracking-wide uppercase">
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
