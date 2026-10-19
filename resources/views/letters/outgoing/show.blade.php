<x-layouts.app :title="'Detail Surat Keluar - ' . $letter->letter_number">
    <section class="px-4 py-4 md:px-8">
        <x-page-header
            :badge="$letter->division?->code ? $letter->division->name . ' (' . $letter->division->code . ')' : 'Divisi Tidak Diketahui'"
            :meta="'Tahun ' . $letter->letter_year . ' • ID #' . $letter->id"
            :title="$letter->letter_number"
            subtitle="Detail rekaman dokumen arsip surat keluar dinas dan status lampiran berkas."
        >
            <x-slot:breadcrumbs>
                <nav class="flex items-center gap-2 text-xs text-slate-500">
                    <a href="/surat-keluar" class="hover:text-teal-700">Surat Keluar</a>
                    <span>/</span>
                    <span class="font-medium text-slate-800">Detail #{{ $letter->id }}</span>
                </nav>
            </x-slot:breadcrumbs>

            <x-slot:actions>
                <a href="/surat-keluar" class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-3.5 text-xs font-semibold text-[#17243A] shadow-2xs hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    <span>Kembali</span>
                </a>
                @if (in_array(auth()->user()->role, ['super_admin', 'division_admin'], true))
                    <a href="/surat-keluar/{{ $letter->id }}/edit" class="flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#147D73] px-4 text-xs font-semibold text-white shadow-xs hover:bg-[#0F635B] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors">
                        <x-icon name="edit" class="h-4 w-4" />
                        <span>Edit Surat</span>
                    </a>
                    <form method="post" action="/api/v1/surat-keluar/{{ $letter->id }}" onsubmit="return confirmAction(event, 'Apakah Anda yakin ingin menghapus arsip surat keluar ini?', 'Konfirmasi Hapus Surat Keluar', 'danger', 'Ya, Hapus Surat')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex min-h-[44px] items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors">
                            <x-icon name="trash" class="h-4 w-4" />
                            <span>Hapus</span>
                        </button>
                    </form>
                @endif
            </x-slot:actions>
        </x-page-header>

        @if(session('success'))
            <div class="mb-6 rounded-md border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Metadata Card -->
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-xs lg:col-span-2">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Informasi Surat Keluar</h2>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">No. Agenda / ID</dt>
                        <dd class="mt-1 font-mono text-sm font-semibold text-slate-900">#{{ $letter->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Nomor Resmi Surat</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $letter->letter_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Tahun Surat</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->letter_year }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Divisi Penerbit</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->division?->name }} ({{ $letter->division?->code }})</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Tanggal Surat</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->letter_date->format('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Tanggal Shared / Diseminasi</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->disseminated_date ? $letter->disseminated_date->format('d F Y') : '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500">Perihal / Isi Surat</dt>
                        <dd class="mt-1 rounded-md bg-slate-50 p-3 text-sm text-slate-800 whitespace-pre-line">{{ $letter->subject }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Diinput Oleh</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->createdBy?->name ?? 'Sistem' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Waktu Pencatatan</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->created_at->format('d/m/Y H:i') }} WIB</dd>
                    </div>
                </dl>
            </div>

            <!-- Attachment Card -->
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-xs">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Lampiran Dokumen</h2>
                @if ($letter->attachments->isNotEmpty())
                    @foreach ($letter->attachments as $attachment)
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start gap-3">
                                <x-icon name="file-text" class="h-8 w-8 text-blue-700 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-900" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">{{ number_format($attachment->file_size_bytes / 1024, 1) }} KB &bull; {{ $attachment->mime_type }}</p>
                                    @if ($attachment->is_missing)
                                        <span class="mt-1.5 inline-block rounded-xs bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">Legacy Drive (Tautan Lama)</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-4 flex flex-col gap-2">
                                @if (! $attachment->is_missing)
                                    <a href="/api/v1/attachments/{{ $attachment->uuid }}/preview" target="_blank" class="flex min-h-[38px] items-center justify-center gap-1.5 rounded-md bg-blue-700 px-3 text-xs font-semibold text-white hover:bg-blue-800">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Buka & Unduh Berkas
                                    </a>
                                @else
                                    @if ($attachment->legacy_drive_url)
                                        <a href="{{ $attachment->legacy_drive_url }}" target="_blank" rel="noopener noreferrer" class="flex min-h-[38px] items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                            <x-icon name="external-link" class="h-4 w-4" />
                                            Buka Google Drive Lama
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="rounded-md border border-dashed border-slate-300 p-6 text-center">
                        <x-icon name="file" class="mx-auto h-8 w-8 text-slate-400" />
                        <p class="mt-2 text-xs font-medium text-slate-500">Tidak ada berkas lampiran yang diunggah.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
