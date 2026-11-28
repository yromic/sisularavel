@props([
    'current' => 15,
    'options' => [15, 30, 50, 100, 250],
    'tab' => null,
])

@php
    $resolvedTab = $tab ?? ($attributes->get('id') && in_array($attributes->get('id'), ['masuk', 'keluar', 'piagam'], true) ? $attributes->get('id') : null);
@endphp

<div class="inline-flex items-center gap-2 text-xs text-slate-600">
    <label for="per-page-select-{{ $attributes->get('id', 'default') }}" class="whitespace-nowrap font-medium">Tampilkan:</label>
    <select id="per-page-select-{{ $attributes->get('id', 'default') }}" 
            data-per-page-tab="{{ $resolvedTab ?? '' }}"
            onchange="if (typeof handlePerPageChange === 'function') { handlePerPageChange(this); } else { window.location.href = this.value; }" 
            class="min-h-[32px] rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 focus:border-emerald-600 focus:outline-hidden cursor-pointer shadow-2xs">
        @foreach ($options as $opt)
            @php
                $query = array_merge(request()->query(), [
                    'per_page' => $opt,
                    'page' => null,
                    'masuk_page' => null,
                    'keluar_page' => null,
                    'piagam_page' => null,
                ]);
                if ($resolvedTab !== null) {
                    $query['tab'] = $resolvedTab;
                }
            @endphp
            <option value="{{ request()->fullUrlWithQuery($query) }}" {{ (int) $current === (int) $opt ? 'selected' : '' }}>
                {{ $opt === 250 ? '250 (Semua)' : $opt }} baris
            </option>
        @endforeach
    </select>
</div>
