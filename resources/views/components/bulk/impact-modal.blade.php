<!-- Modal Dialog Pratinjau Dampak & Konfirmasi Tindakan Massal -->
<div id="bulk-impact-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="bulk-impact-title">
    <div class="flex min-h-full items-center justify-center">
        <div class="relative w-full max-w-lg overflow-hidden rounded-xl bg-white p-6 shadow-2xl transition-all">
            <div class="flex items-start gap-4">
                <div id="bulk-modal-icon-container" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <x-icon name="trash" class="h-6 w-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="bulk-impact-title" class="text-base font-bold text-slate-900">Konfirmasi Hapus Massal</h3>
                    <p class="mt-1 text-xs text-slate-600 leading-relaxed" id="bulk-impact-subtitle">
                        Sistem sedang memverifikasi dampak penghapusan data terpilih...
                    </p>
                </div>
            </div>

            <!-- Loading State -->
            <div id="bulk-impact-loading" class="mt-6 flex flex-col items-center justify-center py-6 text-slate-500">
                <div class="h-8 w-8 animate-spin rounded-full border-3 border-slate-200 border-t-rose-600"></div>
                <p class="mt-3 text-xs font-medium">Menganalisis dependensi & hak akses data...</p>
            </div>

            <!-- Content State -->
            <div id="bulk-impact-content" class="hidden mt-4 space-y-3.5">
                <!-- Summary Stats -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-3">
                        <span class="block text-slate-500" id="bulk-preview-deletable-label">Dapat Dihapus</span>
                        <span class="text-lg font-bold text-emerald-800" id="bulk-preview-deletable-count">0</span>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-3">
                        <span class="block text-slate-500">Dilewati (Dilindungi)</span>
                        <span class="text-lg font-bold text-amber-800" id="bulk-preview-skipped-count">0</span>
                    </div>
                </div>

                <!-- Skipped Details List (if any) -->
                <div id="bulk-preview-skipped-section" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs max-h-36 overflow-y-auto">
                    <div class="font-semibold text-slate-800 mb-1.5 flex items-center justify-between">
                        <span>Detail Item Yang Dilewati:</span>
                        <span class="text-[11px] text-slate-500 font-normal" id="bulk-preview-skipped-total-label"></span>
                    </div>
                    <ul id="bulk-preview-skipped-list" class="space-y-1 text-slate-600 pl-4 list-disc"></ul>
                </div>

                <!-- Confirmation Phrase Prompt (for >= threshold) -->
                <div id="bulk-confirm-phrase-section" class="hidden mt-3 rounded-lg border border-rose-200 bg-rose-50/80 p-3.5">
                    <label for="bulk-confirm-phrase-input" class="block text-xs font-bold text-rose-900 mb-1">
                        Konfirmasi Keamanan (Penghapusan Berskala Besar)
                    </label>
                    <p class="text-xs text-rose-700 mb-2">
                        Untuk melanjutkan, ketik persis kalimat konfirmasi: <code class="font-mono font-bold bg-white px-1.5 py-0.5 rounded border border-rose-300" id="bulk-confirm-phrase-expected">-</code>
                    </p>
                    <input type="text" id="bulk-confirm-phrase-input" autocomplete="off" class="w-full min-h-[38px] rounded-md border border-rose-300 bg-white px-3 text-xs font-mono focus:border-rose-600 focus:outline-hidden" placeholder="Ketik kalimat konfirmasi di sini...">
                </div>

                <div id="bulk-modal-error" class="hidden rounded-md border border-rose-300 bg-rose-50 p-2.5 text-xs text-rose-800"></div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4">
                <button type="button" id="bulk-btn-modal-cancel" class="min-h-[38px] rounded-lg border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Batal
                </button>
                <button type="button" id="bulk-btn-modal-confirm" class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-lg bg-rose-600 px-4 text-xs font-semibold text-white shadow-xs hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <x-icon name="trash" class="h-4 w-4" />
                    <span id="bulk-btn-modal-confirm-text">Eksekusi Hapus</span>
                </button>
            </div>
        </div>
    </div>
</div>
