<!-- Modal Dialog Laporan Hasil Tindakan Massal -->
<div id="bulk-result-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="bulk-result-title">
    <div class="flex min-h-full items-center justify-center">
        <div class="relative w-full max-w-md overflow-hidden rounded-xl bg-white p-6 shadow-2xl transition-all">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600" id="bulk-result-icon">
                    <x-icon name="check" class="h-6 w-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="bulk-result-title" class="text-base font-bold text-slate-900">Operasi Massal Selesai</h3>
                    <p class="mt-1 text-xs text-slate-600 leading-relaxed" id="bulk-result-summary">
                        Laporan ringkasan pemrosesan data massal.
                    </p>
                </div>
            </div>

            <div class="mt-4 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2.5">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 text-center">
                        <span class="block text-slate-500 text-[11px]">Berhasil Diproses</span>
                        <span class="text-lg font-bold text-emerald-700" id="bulk-result-success-count">0</span>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-center">
                        <span class="block text-slate-500 text-[11px]">Dilewati</span>
                        <span class="text-lg font-bold text-amber-700" id="bulk-result-skipped-count">0</span>
                    </div>
                </div>

                <div id="bulk-result-skipped-box" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold text-slate-800">Daftar Item Yang Dilewati:</span>
                        <button type="button" id="btn-bulk-download-csv" class="font-semibold text-emerald-700 hover:text-emerald-800 underline text-[11px] cursor-pointer">
                            Unduh Laporan CSV
                        </button>
                    </div>
                    <ul id="bulk-result-skipped-list" class="space-y-1 text-slate-600 pl-4 list-disc max-h-32 overflow-y-auto"></ul>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button type="button" id="btn-bulk-result-close" class="min-h-[38px] rounded-lg bg-slate-900 px-5 text-xs font-semibold text-white shadow-xs hover:bg-slate-800 transition-colors">
                    Selesai & Muat Ulang
                </button>
            </div>
        </div>
    </div>
</div>
