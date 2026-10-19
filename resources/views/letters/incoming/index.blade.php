<x-layouts.app title="Surat Masuk">
    <section class="px-4 py-4 md:px-8">
        <x-page-header
            :badge="auth()->user()->division?->code ? auth()->user()->division->code . ' — ' . auth()->user()->division->name : 'Semua Divisi (Konteks Global)'"
            meta="Register Arsip Masuk"
            title="Surat Masuk"
            subtitle="Daftar seluruh arsip persuratan yang diterima dari instansi internal maupun eksternal."
        >
            <x-slot:actions>
                <a href="/surat-masuk/create" onclick="event.preventDefault(); openLetterFormModal('surat-masuk', 'create')" class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>+ Tambah Surat Masuk</span>
                </a>
            </x-slot:actions>
        </x-page-header>

        @if(session('success'))
            <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        @if ($canViewTrashed ?? false)
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="inline-flex items-center gap-1 rounded-md bg-slate-100 p-1 border border-slate-200 text-xs">
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => null]) }}"
                       class="inline-flex items-center gap-1.5 rounded-sm px-3 py-1.5 font-semibold transition-colors {{ ($status ?? 'active') !== 'trashed' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <span>Surat Aktif</span>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'trashed', 'page' => null]) }}"
                       class="inline-flex items-center gap-1.5 rounded-sm px-3 py-1.5 font-semibold transition-colors {{ ($status ?? 'active') === 'trashed' ? 'bg-rose-700 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <x-icon name="trash" class="h-3.5 w-3.5 {{ ($status ?? 'active') === 'trashed' ? 'text-white' : 'text-slate-500' }}" />
                        <span>Kotak Sampah (Terhapus)</span>
                    </a>
                </div>
                @if (($status ?? 'active') === 'trashed')
                    <span class="text-xs font-semibold text-rose-700 flex items-center gap-1">
                        <x-icon name="alert-triangle" class="h-3.5 w-3.5 text-rose-600" />
                        <span>Mode Arsip Terhapus (Dapat Dipulihkan)</span>
                    </span>
                @endif
            </div>
        @endif

        <form method="get" action="/surat-masuk" class="mb-4 flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-3 shadow-xs">
            <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
            <x-icon name="search" class="h-5 w-5 text-slate-600" />
            <input name="q" value="{{ request('q') }}" class="min-h-[42px] flex-1 border-0 bg-transparent text-sm focus:outline-hidden" placeholder="Cari nomor surat, instansi asal, atau perihal...">
            @if(request('q'))
                <a href="/surat-masuk?status={{ $status ?? 'active' }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Mobile Viewport: Card Summary (Vertical Mobile-First) -->
        <div class="space-y-3 md:hidden">
            @forelse ($letters as $letter)
                @php
                    $letterPayload = [
                        'id' => $letter->id,
                        'nomor_surat' => $letter->letter_number,
                        'asal_surat' => $letter->origin_agency,
                        'tanggal_surat' => $letter->letter_date->format('Y-m-d'),
                        'tanggal_diterima' => $letter->received_date->format('Y-m-d'),
                        'perihal' => $letter->subject,
                        'division_id' => $letter->division_id,
                    ];
                @endphp
                <article data-testid="mobile-letter-card" class="rounded-md border border-slate-200 bg-white p-4 shadow-xs">
                    <div class="flex items-start justify-between gap-3 cursor-pointer" onclick="openLetterDetailModal('surat-masuk', '{{ $letter->id }}')">
                        <div>
                            <span class="inline-block rounded-xs bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">Agenda #{{ $letter->id }}</span>
                            @if ($letter->trashed())
                                <span class="rounded bg-rose-100 text-rose-800 px-2 py-0.5 text-[10px] font-bold ml-1">Terhapus</span>
                            @endif
                            <h2 class="mt-1 font-semibold text-slate-900">{{ $letter->letter_number }}</h2>
                            <p class="mt-0.5 text-xs text-slate-600">{{ $letter->origin_agency }}</p>
                            <p class="mt-1 text-xs text-slate-500">Tgl: {{ $letter->letter_date->format('d/m/Y') }} &bull; Diterima: {{ $letter->received_date->format('d/m/Y') }}</p>
                        </div>
                        <a href="/surat-masuk/{{ $letter->id }}" onclick="event.preventDefault(); openLetterDetailModal('surat-masuk', '{{ $letter->id }}')" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50" aria-label="Buka aksi surat {{ $letter->letter_number }}">
                            <x-icon name="more-horizontal" class="h-5 w-5" />
                        </a>
                    </div>
                    <p class="mt-2 text-sm text-slate-700 line-clamp-2 cursor-pointer" onclick="openLetterDetailModal('surat-masuk', '{{ $letter->id }}')">{{ $letter->subject }}</p>
                    
                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2.5">
                        <div class="flex items-center gap-1.5">
                            @if ($letter->trashed())
                                <form method="post" action="/surat-masuk/{{ $letter->id }}/restore" class="inline" onsubmit="return confirmAction(event, 'Pulihkan kembali surat masuk {{ $letter->letter_number }}?', 'Konfirmasi Pemulihan Surat', 'emerald', 'Ya, Pulihkan')">
                                    @csrf
                                    <button type="submit" class="inline-flex min-h-[36px] items-center gap-1 rounded-md border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs">
                                        <x-icon name="rotate-ccw" class="h-3.5 w-3.5 text-emerald-700" />
                                        <span>Pulihkan</span>
                                    </button>
                                </form>
                            @else
                                <a href="/surat-masuk/{{ $letter->id }}" onclick="event.preventDefault(); openLetterDetailModal('surat-masuk', '{{ $letter->id }}')" class="inline-flex min-h-[36px] items-center rounded-md border border-slate-300 px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Detail
                                </a>
                                @if (in_array(auth()->user()->role, ['super_admin', 'division_admin'], true))
                                    <a href="/surat-masuk/{{ $letter->id }}/edit" onclick="event.preventDefault(); openLetterFormModal('surat-masuk', 'edit', {{ json_encode($letterPayload) }})" class="inline-flex min-h-[36px] items-center rounded-md border border-emerald-600 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                                        Edit
                                    </a>
                                @endif
                            @endif
                        </div>
                        @if ($letter->attachments->isNotEmpty())
                            <a href="/api/v1/attachments/{{ $letter->attachments->first()->uuid }}/preview" onclick="event.preventDefault(); openLetterDetailModal('surat-masuk', '{{ $letter->id }}')" class="inline-flex min-h-[36px] items-center gap-1 rounded-md bg-slate-100 px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-200">
                                <x-icon name="file-text" class="h-3.5 w-3.5 text-emerald-700" />
                                Berkas
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-md border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                    Tidak ada surat masuk ditemukan.
                </div>
            @endforelse
        </div>

        <!-- Desktop Viewport: Dense Grid (Click Row to View Split Modal) -->
        <div class="hidden overflow-x-auto md:block">
            <table data-testid="desktop-letter-grid" class="w-full min-w-[1024px] border-collapse bg-white text-sm shadow-xs rounded-md border border-slate-200">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="p-3">No. Agenda</th>
                        <th class="p-3">Nomor Surat</th>
                        <th class="p-3">Tanggal Surat</th>
                        <th class="p-3">Tanggal Diterima</th>
                        <th class="p-3">Asal Surat</th>
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
                                'asal_surat' => $letter->origin_agency,
                                'tanggal_surat' => $letter->letter_date->format('Y-m-d'),
                                'tanggal_diterima' => $letter->received_date->format('Y-m-d'),
                                'perihal' => $letter->subject,
                                'division_id' => $letter->division_id,
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/75 cursor-pointer transition-colors" onclick="if (!event.target.closest('a, button, form, input')) openLetterDetailModal('surat-masuk', '{{ $letter->id }}')">
                            <td class="p-3 font-mono text-xs">{{ $letter->id }}</td>
                            <td class="p-3 font-medium text-slate-900">
                                {{ $letter->letter_number }}
                                @if ($letter->trashed())
                                    <span class="rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 text-[10px] font-bold ml-1">Terhapus</span>
                                @endif
                            </td>
                            <td class="p-3">{{ $letter->letter_date->format('d/m/Y') }}</td>
                            <td class="p-3">{{ $letter->received_date->format('d/m/Y') }}</td>
                            <td class="p-3">{{ $letter->origin_agency }}</td>
                            <td class="p-3 max-w-xs truncate text-slate-700" title="{{ $letter->subject }}">{{ $letter->subject }}</td>
                            <td class="p-3">
                                @if ($letter->attachments->isNotEmpty())
                                    <a href="/api/v1/attachments/{{ $letter->attachments->first()->uuid }}/preview" onclick="event.preventDefault(); openLetterDetailModal('surat-masuk', '{{ $letter->id }}')" class="inline-flex min-h-[34px] items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        <x-icon name="file-text" class="h-3.5 w-3.5 text-emerald-700" />
                                        Berkas
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Tanpa Berkas</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1.5" onclick="event.stopPropagation()">
                                    @if ($letter->trashed())
                                        <form method="post" action="/surat-masuk/{{ $letter->id }}/restore" class="inline" onsubmit="return confirmAction(event, 'Pulihkan kembali surat masuk {{ $letter->letter_number }}?', 'Konfirmasi Pemulihan Surat', 'emerald', 'Ya, Pulihkan')">
                                            @csrf
                                            <button type="submit" class="inline-flex min-h-[34px] items-center gap-1 rounded-md border border-emerald-300 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs" title="Pulihkan surat">
                                                <x-icon name="rotate-ccw" class="h-3.5 w-3.5 text-emerald-700" />
                                                <span>Pulihkan</span>
                                            </button>
                                        </form>
                                    @else
                                        <a href="/surat-masuk/{{ $letter->id }}" onclick="event.preventDefault(); openLetterDetailModal('surat-masuk', '{{ $letter->id }}')" class="inline-flex min-h-[34px] items-center rounded-md border border-slate-300 px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50" title="Detail surat">
                                            Detail
                                        </a>
                                        @if (in_array(auth()->user()->role, ['super_admin', 'division_admin'], true))
                                            <a href="/surat-masuk/{{ $letter->id }}/edit" onclick="event.preventDefault(); openLetterFormModal('surat-masuk', 'edit', {{ json_encode($letterPayload) }})" class="inline-flex min-h-[34px] items-center rounded-md border border-emerald-600 bg-emerald-50 px-2.5 text-xs font-medium text-emerald-800 hover:bg-emerald-100" title="Edit surat">
                                                Edit
                                            </a>
                                        @endif
                                        @if (in_array(auth()->user()->role, ['super_admin', 'division_admin'], true))
                                            <button type="button" onclick="openLetterDeleteModal('surat-masuk', '{{ $letter->id }}', '{{ addslashes($letter->letter_number) }}')" class="flex min-h-[34px] min-w-[34px] items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" title="Hapus surat masuk">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-sm text-slate-500">Tidak ada surat masuk ditemukan.</td>
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
