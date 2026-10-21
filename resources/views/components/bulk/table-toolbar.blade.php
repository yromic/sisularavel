@props([
    'entityLabel' => 'item',
    'canRestore' => false,
    'isTrashed' => false,
])

<!-- Floating Bulk Action Toolbar -->
<div id="bulk-action-toolbar" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex flex-col md:flex-row items-center gap-3 bg-slate-900/95 text-white px-5 py-3 rounded-xl shadow-2xl border border-slate-700/80 backdrop-blur-md transition-all duration-300">
    <div class="flex items-center gap-3 text-xs md:text-sm font-medium">
        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400 font-bold text-xs" id="bulk-selected-count">0</span>
        <span id="bulk-selected-label">{{ $entityLabel }} dipilih</span>
        <div id="bulk-all-filter-prompt" class="hidden text-xs text-slate-300 border-l border-slate-700 pl-3">
            <span>Semua di halaman ini dipilih.</span>
            <button type="button" id="btn-select-all-filtered" class="font-semibold text-emerald-400 hover:text-emerald-300 underline ml-1 cursor-pointer">
                Pilih semua <span id="bulk-total-filter-count">0</span> data hasil filter
            </button>
        </div>
        <div id="bulk-all-selected-banner" class="hidden text-xs text-amber-300 border-l border-slate-700 pl-3 font-medium">
            <span>Seluruh data hasil pencarian terpilih.</span>
            <button type="button" id="btn-clear-all-filter-select" class="text-slate-400 hover:text-white underline ml-1 cursor-pointer">
                Batalkan seleksi penuh
            </button>
        </div>
    </div>

    <div class="flex items-center gap-2 border-t md:border-t-0 md:border-l border-slate-700 pt-2 md:pt-0 md:pl-3">
        <button type="button" id="btn-bulk-cancel" class="min-h-[34px] px-3 rounded-lg border border-slate-700 text-xs text-slate-300 hover:bg-slate-800 transition-colors">
            Batal
        </button>

        @if ($canRestore && $isTrashed)
            <button type="button" id="btn-bulk-restore" class="inline-flex min-h-[34px] items-center gap-1.5 px-3.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-xs font-semibold text-white shadow-xs transition-colors">
                <x-icon name="check" class="h-4 w-4" />
                <span>Pulihkan Terpilih</span>
            </button>
        @else
            <button type="button" id="btn-bulk-delete" class="inline-flex min-h-[34px] items-center gap-1.5 px-3.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-xs font-semibold text-white shadow-xs transition-colors">
                <x-icon name="trash" class="h-4 w-4" />
                <span>Hapus Terpilih</span>
            </button>
        @endif
    </div>
</div>
