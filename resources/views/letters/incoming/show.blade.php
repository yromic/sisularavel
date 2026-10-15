<x-layouts.app :title="'Detail Surat Masuk - ' . $letter->letter_number">
    <section class="px-4 py-4 md:px-8">
        <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <nav class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                    <a href="/surat-masuk" class="hover:text-emerald-700">Surat Masuk</a>
                    <span>/</span>
                    <span class="font-medium text-slate-800">Detail #{{ $letter->id }}</span>
                </nav>
                <h1 class="text-xl font-bold text-slate-900">{{ $letter->letter_number }}</h1>
                <p class="text-xs text-slate-500">Divisi: <span class="font-semibold text-emerald-700">{{ $letter->division?->name }} ({{ $letter->division?->code }})</span></p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/surat-masuk" class="flex min-h-[40px] items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Kembali
                </a>
                @if (in_array(auth()->user()->role, ['super_admin', 'division_admin'], true))
                    <a href="/surat-masuk/{{ $letter->id }}/edit" class="flex min-h-[40px] items-center gap-1.5 rounded-md border border-emerald-600 bg-emerald-50 px-3.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                        <x-icon name="edit" class="h-4 w-4" />
                        Edit Surat
                    </a>
                    <form method="post" action="/api/v1/surat-masuk/{{ $letter->id }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus arsip surat masuk ini?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex min-h-[40px] items-center gap-1.5 rounded-md border border-rose-200 bg-rose-50 px-3.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                            <x-icon name="trash" class="h-4 w-4" />
                            Hapus
                        </button>
                    </form>
                @endif
            </div>
        </header>

        @if(session('success'))
            <div class="mb-6 rounded-md border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Metadata Card -->
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-xs lg:col-span-2">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Informasi Surat Masuk</h2>
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
                        <dt class="text-xs font-medium text-slate-500">Asal Surat (Instansi)</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->origin_agency }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Divisi Pengarsip</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->division?->name }} ({{ $letter->division?->code }})</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Tanggal Surat</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->letter_date->format('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Tanggal Diterima</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $letter->received_date->format('d F Y') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500">Perihal / Ringkasan</dt>
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
                                <x-icon name="file-text" class="h-8 w-8 text-emerald-700 shrink-0" />
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
                                    <a href="/api/v1/attachments/{{ $attachment->uuid }}/preview" target="_blank" class="flex min-h-[38px] items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 text-xs font-semibold text-white hover:bg-emerald-800">
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

                                    @if (in_array(auth()->user()->role, ['super_admin', 'division_admin'], true))
                                        <div class="mt-3 rounded-md border border-amber-300 bg-amber-50 p-3">
                                            <div class="flex items-center gap-1.5 text-xs font-semibold text-amber-900 mb-1">
                                                <x-icon name="upload" class="h-4 w-4 text-amber-700" />
                                                Remediasi Berkas Lampiran
                                            </div>
                                            <p class="text-[11px] text-amber-800 mb-2">Unggah dokumen fisik/scan pengganti untuk memulihkan arsip ini ke penyimpanan privat SISURAT.</p>
                                            <form method="post" action="/api/v1/surat-masuk/{{ $letter->id }}/remediate-attachment" enctype="multipart/form-data" class="space-y-2">
                                                @csrf
                                                <input type="file" name="attachment" required accept="application/pdf,image/jpeg,image/png,.doc,.docx" class="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-amber-700 file:text-white hover:file:bg-amber-800">
                                                <button type="submit" class="flex min-h-[36px] w-full items-center justify-center gap-1.5 rounded-md bg-amber-700 px-3 text-xs font-semibold text-white hover:bg-amber-800 shadow-xs">
                                                    <x-icon name="check" class="h-4 w-4" />
                                                    Unggah Dokumen Pengganti
                                                </button>
                                            </form>
                                        </div>
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
