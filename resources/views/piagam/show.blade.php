<x-layouts.app :title="'Detail Tanda Terima - ' . ($piagam->piagam_description ?: $piagam->student_name)">
    <section class="px-4 py-4 md:px-8">
        <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <nav class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                    <a href="/piagam" class="hover:text-emerald-700">Buku Ekspedisi Piagam</a>
                    <span>/</span>
                    <span class="font-medium text-slate-800">Tanda Terima #{{ $piagam->id }}</span>
                </nav>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900">{{ $piagam->piagam_description ?: $piagam->student_name }}</h1>
                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                        Tercatat Sah
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Bidang: <span class="font-semibold text-emerald-700">{{ $piagam->division?->name }} ({{ $piagam->division?->code }})</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/piagam" class="flex min-h-[40px] items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Kembali ke Buku Ekspedisi
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Informasi Serah Terima -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs lg:col-span-2">
                <h2 class="mb-4 text-base font-bold text-slate-900">Informasi Tanda Terima Serah Terima Fisik</h2>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">ID Tanda Terima</dt>
                        <dd class="mt-1 font-mono text-sm font-bold text-slate-900">#{{ $piagam->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Waktu Penyerahan</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $piagam->created_at->format('d F Y, H:i') }} WIB</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500">Keterangan Piagam / Lomba</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $piagam->piagam_description ?: $piagam->student_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Asal Sekolah / Instansi</dt>
                        <dd class="mt-1 text-sm text-slate-800 font-medium">{{ $piagam->unit_display_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Nomor Publik (NPSN)</dt>
                        <dd class="mt-1 text-sm font-mono text-slate-800">{{ $piagam->npsn ?? $piagam->school?->npsn ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Bidang Pengampu</dt>
                        <dd class="mt-1 text-sm text-emerald-700 font-semibold">{{ $piagam->division?->code }} — {{ $piagam->division?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Kelas Kompetisi / Kategori</dt>
                        <dd class="mt-1 text-sm text-slate-800 font-medium">{{ $piagam->competition_display_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Tahun Acara</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $piagam->competition_year ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Kandidat Prestasi (Siswa)</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-900">{{ $piagam->student_name }}</dd>
                    </div>
                </dl>

                <hr class="my-6 border-slate-100">

                <h3 class="mb-4 text-base font-bold text-slate-900">Identitas Penerima / Pengambil</h3>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Nama Pengambil</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-900">{{ $piagam->recipient_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Jabatan Pengambil</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $piagam->recipient_position ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Unit Kerja / Instansi</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $piagam->unit_display_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Jenis Pengambilan</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $piagam->pickup_display_name }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Bukti Tanda Tangan -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                <h2 class="mb-4 text-base font-bold text-slate-900">Bukti Tanda Tangan</h2>
                @php
                    $signature = $piagam->attachments->first();
                @endphp

                @if ($signature && ! $signature->is_missing)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center">
                        <p class="mb-2 text-xs font-medium text-slate-500">Tanda Tangan Digital Penerima</p>
                        <div class="mx-auto flex h-48 max-w-full items-center justify-center rounded-lg border border-slate-300 bg-white p-3 shadow-inner">
                            <img src="/api/v1/attachments/{{ $signature->uuid }}/preview" alt="Tanda Tangan {{ $piagam->recipient_name }}" class="max-h-full max-w-full object-contain">
                        </div>
                        <div class="mt-4">
                            <a href="/api/v1/attachments/{{ $signature->uuid }}/preview" target="_blank" class="inline-flex min-h-[38px] items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs">
                                <x-icon name="external-link" class="h-3.5 w-3.5" />
                                Buka Tanda Tangan Ukuran Penuh
                            </a>
                        </div>
                    </div>
                @elseif ($signature && $signature->is_missing && $signature->legacy_drive_url)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-center">
                        <p class="text-xs font-semibold text-amber-900">Tanda Tangan Legacy Drive</p>
                        <p class="mt-1 text-xs text-amber-700">Tanda tangan tersimpan di Google Drive arsip lama.</p>
                        <a href="{{ $signature->legacy_drive_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-[38px] items-center gap-1.5 rounded-md border border-amber-400 bg-white px-3 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                            <x-icon name="external-link" class="h-3.5 w-3.5" />
                            Buka Tautan Drive Lama
                        </a>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                        <x-icon name="signature" class="mx-auto h-8 w-8 text-slate-300" />
                        <p class="mt-2 text-xs font-medium text-slate-500">Belum ada tanda tangan tersimpan.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
