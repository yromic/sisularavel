@props([
    'column',
    'title',
    'currentSort' => 'id',
    'currentDir' => 'desc',
    'defaultDir' => 'desc',
    'tab' => null,
])

@php
    $isActive = $currentSort === $column;
    $nextDir = $isActive ? ($currentDir === 'asc' ? 'desc' : 'asc') : $defaultDir;
    
    $query = array_merge(request()->query(), [
        'sort' => $column,
        'dir' => $nextDir,
        'page' => null,
        'masuk_page' => null,
        'keluar_page' => null,
        'piagam_page' => null,
    ]);

    if ($tab !== null) {
        $query['tab'] = $tab;
    }

    $url = request()->fullUrlWithQuery($query);
@endphp

<a href="{{ $url }}" data-sort-link="{{ $tab ?? '' }}" class="inline-flex items-center gap-1 font-semibold text-slate-700 hover:text-emerald-700 transition-colors group">
    <span>{{ $title }}</span>
    <span class="inline-flex items-center">
        @if ($isActive)
            @if ($currentDir === 'asc')
                <x-icon name="arrow-up" class="h-3.5 w-3.5 text-emerald-700" />
            @else
                <x-icon name="arrow-down" class="h-3.5 w-3.5 text-emerald-700" />
            @endif
        @else
            <x-icon name="arrow-up-down" class="h-3 w-3 text-slate-400 group-hover:text-slate-600 opacity-60" />
        @endif
    </span>
</a>
