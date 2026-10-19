@props([
    'badge' => null,
    'badgeIcon' => null,
    'meta' => null,
    'title',
    'subtitle' => null,
])

<header {{ $attributes->merge(['class' => 'mb-6 rounded-xl border border-[#E3E8EE] bg-white p-4 sm:p-5 shadow-2xs']) }}>
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1">
            @if (isset($breadcrumbs))
                <div class="mb-1 text-xs text-slate-500">
                    {{ $breadcrumbs }}
                </div>
            @endif

            @if ($badge || $meta || isset($badgeSlot))
                <div class="flex flex-wrap items-center gap-2">
                    @if (isset($badgeSlot))
                        {{ $badgeSlot }}
                    @elseif ($badge)
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-[#EBF7F6] border border-[#9DD8D2] px-2.5 py-0.5 text-xs font-bold text-[#0F635B]">
                            @if ($badgeIcon)
                                <x-icon :name="$badgeIcon" class="h-3.5 w-3.5" />
                            @endif
                            <span>{{ $badge }}</span>
                        </span>
                    @endif

                    @if ($meta)
                        <span class="hidden sm:inline-block text-xs text-[#657184] font-medium">
                            &bull; {{ $meta }}
                        </span>
                    @endif
                </div>
            @endif

            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#17243A]">
                {!! strip_tags($title) !!}
            </h1>

            @if ($subtitle)
                <p class="mt-0.5 text-xs sm:text-sm text-[#657184]">
                    {!! strip_tags($subtitle) !!}
                </p>
            @endif
        </div>

        @if (isset($actions) && $actions->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                {{ $actions }}
            </div>
        @endif
    </div>
</header>
