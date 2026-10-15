<x-layouts.app title="Surat Keluar">
    <section class="px-4 py-4 md:px-8">
        <header class="sticky top-0 z-10 -mx-4 mb-4 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:static md:mx-0 md:rounded-md md:border">
            <div>
                <p class="text-sm font-semibold text-emerald-700">{{ auth()->user()->division?->code ?? 'GLOBAL' }}</p>
                <h1 class="text-xl font-semibold text-slate-900">Surat Keluar</h1>
            </div>
            <a href="/surat-keluar/create" onclick="event.preventDefault(); openLetterFormModal('surat-keluar', 'create')" class="flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-3 font-semibold text-white hover:bg-emerald-800 shadow-xs">
                <x-icon name="plus" class="h-5 w-5" />
                Tambah
            </a>
        </header>

        @if(session('success'))
            <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <form method="get" action="/surat-keluar" class="mb-4 flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3 shadow-xs">
            <x-icon name="search" class="h-5 w-5 text-slate-600" />
            <input name="q" value="{{ request('q') }}" class="min-h-[42px] flex-1 border-0 bg-transparent text-sm focus:outline-hidden" placeholder="Cari nomor surat atau perihal...">
            @if (request('q'))
                <a href="/surat-keluar" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Mobile Viewport: Card Summary (Vertical Mobile-First) -->
        <div class="space-y-3 md:hidden">
            @forelse ($letters as $letter)
                @php
                    $letterPayload = [
                        'id' => $letter->id,
                        'nomor_surat' => $letter->letter_number,
                        'tanggal_surat' => $letter->letter_date->format('Y-m-d'),
                        'tanggal_share' => $letter->disseminated_date ? $letter->disseminated_date->format('Y-m-d') : '',
                        'perihal' => $letter->subject,
                        'division_id' => $letter->division_id,
                        'letter_year' => $letter->letter_year,
                    ];
                @endphp
                <article data-testid="mobile-outgoing-card" class="rounded-md border border-slate-200 bg-white p-4 shadow-xs">
                    <div class="flex items-start justify-between gap-3 cursor-pointer" onclick="openLetterDetailModal('surat-keluar', '{{ $letter->id }}')">
                        <div>
                            <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">Tahun {{ $letter->letter_year }}</span>
                            <h2 class="mt-1 font-semibold text-slate-900">{{ $letter->letter_number }}</h2>
                            <p class="mt-1 text-xs text-slate-500">Tgl: {{ $letter->letter_date->format('d/m/Y') }} &bull; Share: {{ $letter->disseminated_date ? $letter->disseminated_date->format('d/m/Y') : '-' }}</p>
                        </div>
                    </div>
                    <p class="mt-2.5 text-sm text-slate-700 line-clamp-2 cursor-pointer" onclick="openLetterDetailModal('surat-keluar', '{{ $letter->id }}')">{{ $letter->subject }}</p>

                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2.5">
                        <div class="flex items-center gap-1.5">
                            <a href="/surat-keluar/{{ $letter->id }}" onclick="event.preventDefault(); openLetterDetailModal('surat-keluar', '{{ $letter->id }}')" class="inline-flex min-h-[36px] items-center rounded-md border border-slate-300 px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Detail
                            </a>
                            <a href="/surat-keluar/{{ $letter->id }}/edit" onclick="event.preventDefault(); openLetterFormModal('surat-keluar', 'edit', {{ json_encode($letterPayload) }})" class="inline-flex min-h-[36px] items-center rounded-md border border-emerald-600 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                                Edit
                            </a>
                        </div>
                        @if ($letter->attachments->isNotEmpty())
                            <a href="/api/v1/attachments/{{ $letter->attachments->first()->uuid }}/preview" onclick="event.preventDefault(); openLetterDetailModal('surat-keluar', '{{ $letter->id }}')" class="inline-flex min-h-[36px] items-center gap-1 rounded-md bg-slate-100 px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-200">
                                <x-icon name="file-text" class="h-3.5 w-3.5 text-teal-700" />
                                Berkas
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-md border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                    Tidak ada surat keluar ditemukan.
                </div>
            @endforelse
        </div>

        <!-- Desktop Viewport: Dense Grid (Click Row to View Split Modal) -->
        <div class="hidden overflow-x-auto md:block">
            <table data-testid="desktop-outgoing-grid" class="w-full min-w-[1024px] border-collapse bg-white text-sm shadow-xs rounded-md border border-slate-200">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="p-3">No. Agenda</th>
                        <th class="p-3">Tahun</th>
                        <th class="p-3">Nomor Surat</th>
                        <th class="p-3">Tanggal Surat</th>
                        <th class="p-3">Tanggal Share</th>
                        <th class="p-3">Perihal</th>
                        <th class="p-3">Lampiran</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($letters as $letter)
                        @php
                            $letterPayload = [
                                'id' => $letter->id,
                                'nomor_surat' => $letter->letter_number,
                                'tanggal_surat' => $letter->letter_date->format('Y-m-d'),
                                'tanggal_share' => $letter->disseminated_date ? $letter->disseminated_date->format('Y-m-d') : '',
                                'perihal' => $letter->subject,
                                'division_id' => $letter->division_id,
                                'letter_year' => $letter->letter_year,
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="if (!event.target.closest('a, button, form, input')) openLetterDetailModal('surat-keluar', '{{ $letter->id }}')">
                            <td class="p-3 font-mono text-xs">{{ $letter->id }}</td>
                            <td class="p-3 font-semibold text-slate-600">{{ $letter->letter_year }}</td>
                            <td class="p-3 font-medium text-slate-900">{{ $letter->letter_number }}</td>
                            <td class="p-3">{{ $letter->letter_date->format('d/m/Y') }}</td>
                            <td class="p-3">{{ $letter->disseminated_date ? $letter->disseminated_date->format('d/m/Y') : '-' }}</td>
                            <td class="p-3 max-w-xs truncate text-slate-700" title="{{ $letter->subject }}">{{ $letter->subject }}</td>
                            <td class="p-3">
                                @if ($letter->attachments->isNotEmpty())
                                    <a href="/api/v1/attachments/{{ $letter->attachments->first()->uuid }}/preview" onclick="event.preventDefault(); openLetterDetailModal('surat-keluar', '{{ $letter->id }}')" class="inline-flex min-h-[34px] items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        <x-icon name="file-text" class="h-3.5 w-3.5 text-teal-700" />
                                        Berkas
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Tanpa Berkas</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1.5" onclick="event.stopPropagation()">
                                    <a href="/surat-keluar/{{ $letter->id }}" onclick="event.preventDefault(); openLetterDetailModal('surat-keluar', '{{ $letter->id }}')" class="inline-flex min-h-[34px] items-center rounded-md border border-slate-300 px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50" title="Detail surat">
                                        Detail
                                    </a>
                                    <a href="/surat-keluar/{{ $letter->id }}/edit" onclick="event.preventDefault(); openLetterFormModal('surat-keluar', 'edit', {{ json_encode($letterPayload) }})" class="inline-flex min-h-[34px] items-center rounded-md border border-emerald-600 bg-emerald-50 px-2.5 text-xs font-medium text-emerald-800 hover:bg-emerald-100" title="Edit surat">
                                        Edit
                                    </a>
                                    @if (in_array(auth()->user()->role, ['super_admin', 'division_admin'], true))
                                        <button type="button" onclick="openLetterDeleteModal('surat-keluar', '{{ $letter->id }}', '{{ addslashes($letter->letter_number) }}')" class="flex min-h-[34px] min-w-[34px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus surat keluar">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-sm text-slate-500">Tidak ada surat keluar ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $letters->links() }}
        </div>
    </section>

    <!-- Modal Components (Split Detail, Form Create/Edit, Confirm Delete) -->
    <x-letters.split-modal-detail />
    <x-letters.form-modal />
    <x-letters.delete-modal />
</x-layouts.app>
