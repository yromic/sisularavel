@props([
    'schools' => collect(),
    'divisions' => collect(),
    'workUnits' => collect(),
    'pickupCategories' => collect(),
    'competitionCategories' => collect(),
])

@php
    if ($divisions->isEmpty()) {
        $divisions = \App\Models\Division::query()->where('status', 'active')->orderBy('id')->get(['id', 'code', 'name']);
    }
    if ($workUnits->isEmpty()) {
        $workUnits = \App\Models\WorkUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
    }
    if ($pickupCategories->isEmpty()) {
        $pickupCategories = \App\Models\PickupCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'division_id', 'name']);
    }
    if ($competitionCategories->isEmpty()) {
        $competitionCategories = \App\Models\CompetitionCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'division_id', 'name']);
    }
@endphp

<div class="w-full">
    <!-- Success Banner -->
    <div id="claim-success-banner" class="hidden mb-6 rounded-2xl border border-emerald-300 bg-emerald-50 p-6 sm:p-8 text-emerald-950 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-xs">
                <x-icon name="check" class="h-7 w-7" />
            </div>
            <div class="flex-1">
                <span class="inline-flex rounded-full bg-emerald-200/80 px-2.5 py-0.5 text-xs font-bold text-emerald-900 mb-1">
                    Tercatat Sah di Buku Ekspedisi
                </span>
                <h3 class="font-extrabold text-xl text-emerald-950">Tanda Terima Berhasil Disimpan!</h3>
                <p class="mt-1 text-xs sm:text-sm text-emerald-800 leading-relaxed" id="claim-success-message">
                    Bukti serah terima fisik piagam beserta tanda tangan digital telah tersimpan aman dalam Buku Ekspedisi Digital SISURAT.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" onclick="resetFormForNewReceipt()" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-emerald-700 px-5 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition-colors">
                        <x-icon name="plus" class="h-4 w-4" />
                        Catat Tanda Terima Lainnya
                    </button>
                    <a href="/" class="inline-flex min-h-[44px] items-center rounded-lg border border-emerald-300 bg-white px-4 text-xs font-semibold text-emerald-800 hover:bg-emerald-100/50 transition-colors">
                        Kembali ke Halaman Muka
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Handover Receipt Card -->
    <div id="claim-form-card" class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Buku Tanda Terima Pengambilan Piagam</h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Silakan lengkapi formulir di bawah ini sebagai bukti penyerahan fisik piagam penghargaan di loket dinas.
            </p>
        </div>

        <div id="form-error-banner" class="hidden mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 sm:p-5 text-sm text-rose-800 shadow-xs">
            <div class="font-bold flex items-center gap-2 text-rose-900" id="form-error-title">
                <x-icon name="x" class="h-4 w-4 text-rose-600" />
                Gagal Menyimpan Tanda Terima:
            </div>
            <ul id="form-error-list" class="mt-2 list-disc pl-5 space-y-1 text-xs text-rose-700 font-medium"></ul>
        </div>

        <form id="public-handover-form" data-testid="piagam-claim-form" method="post" action="/api/v1/piagam/ambil" class="space-y-8">
            @csrf
            <input type="hidden" name="signature_canvas" id="signature_canvas" required>
            <input type="hidden" name="piagam_id" id="piagam_id" value="">
            <input type="hidden" name="recipient_unit" id="recipient_unit" value="">

            <!-- ============================================================ -->
            <!-- SECTION A: INFORMASI PENGAMBIL                               -->
            <!-- ============================================================ -->
            <fieldset class="space-y-4 rounded-xl border border-slate-200/90 bg-slate-50/50 p-5 sm:p-6">
                <legend class="px-2 text-xs font-black uppercase tracking-wider text-emerald-800 bg-emerald-100/80 rounded-md py-1 border border-emerald-200">
                    BAGIAN A — INFORMASI PENGAMBIL
                </legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <!-- 1. DIVISI TUJUAN -->
                    <div>
                        <label for="division_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Divisi Tujuan / Bidang Pengampu *
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih bidang atau ketik manual jika tidak ada di daftar.</p>
                        <select name="division_id"
                                id="division_id"
                                onchange="handleDivisionChange(this.value)"
                                class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                                required>
                            <option value="">-- Pilih Bidang Penyerah --</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}" @selected(request('bidang') === $division->code || old('division_id') == $division->id)>
                                    {{ $division->code }} — {{ $division->name }}
                                </option>
                            @endforeach
                            <option value="manual" @selected(old('division_id') === 'manual')>-- Lainnya / Ketik Manual --</option>
                        </select>

                        <!-- Manual Division Input (Initially Hidden) -->
                        <div id="division_manual_wrapper" class="{{ old('division_id') === 'manual' ? '' : 'hidden' }} mt-2">
                            <label for="division_manual" class="block text-[11px] font-semibold text-slate-600">
                                Ketik Nama / Kode Bidang:
                            </label>
                            <input type="text"
                                   name="division_manual"
                                   id="division_manual"
                                   value="{{ old('division_manual') }}"
                                   class="mt-1 min-h-[42px] w-full rounded-lg border border-amber-300 bg-amber-50/40 px-3 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                                   placeholder="Contoh: DIKDAS atau Bidang Pembinaan SD">
                            <p class="text-[11px] text-slate-400 mt-0.5">Akan dicocokkan dengan divisi dinas resmi yang aktif.</p>
                        </div>
                    </div>

                    <!-- 2. NAMA PENGAMBIL -->
                    <div>
                        <label for="recipient_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Nama Lengkap Pengambil *
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">Nama orang yang menerima fisik piagam di loket.</p>
                        <input type="text"
                               name="recipient_name"
                               id="recipient_name"
                               value="{{ old('recipient_name') }}"
                               class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                               placeholder="Contoh: Budi Santoso, S.Pd."
                               required>
                    </div>

                    <!-- 3. POSISI / JABATAN -->
                    <div>
                        <label for="recipient_position" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Posisi / Jabatan Pengambil *
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih atau ketik jabatan yang mewakili.</p>
                        <input list="positions_datalist"
                               name="recipient_position"
                               id="recipient_position"
                               value="{{ old('recipient_position') }}"
                               class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                               placeholder="Ketik atau pilih jabatan"
                               required>
                        <datalist id="positions_datalist">
                            <option value="Guru Pendamping"></option>
                            <option value="Kepala Sekolah"></option>
                            <option value="Staf Tata Usaha"></option>
                            <option value="Wali Murid / Orang Tua"></option>
                            <option value="Perwakilan Sekolah"></option>
                            <option value="Pelatih / Pembina Ekstrakurikuler"></option>
                        </datalist>
                    </div>

                    <!-- 4. JENIS PENGAMBILAN -->
                    <div>
                        <label for="pickup_category_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Jenis Pengambilan *
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih metode pengambilan atau isi manual.</p>
                        <select name="pickup_category_id"
                                id="pickup_category_id"
                                onchange="handlePickupCategoryChange(this.value)"
                                class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden">
                            <option value="">-- Pilih Jenis Pengambilan --</option>
                            @foreach ($pickupCategories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('pickup_category_id') == $cat->id || $loop->first)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                            <option value="manual" @selected(old('pickup_category_id') === 'manual')>-- Lainnya / Ketik Manual --</option>
                        </select>

                        <!-- Manual Pickup Category Input (Initially Hidden) -->
                        <div id="pickup_category_manual_wrapper" class="{{ old('pickup_category_id') === 'manual' ? '' : 'hidden' }} mt-2">
                            <input type="text"
                                   name="pickup_category_manual"
                                   id="pickup_category_manual"
                                   value="{{ old('pickup_category_manual') }}"
                                   class="min-h-[42px] w-full rounded-lg border border-amber-300 bg-amber-50/40 px-3 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                                   placeholder="Ketik jenis pengambilan lainnya...">
                        </div>
                    </div>

                    <!-- 5. UNIT KERJA -->
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label for="work_unit_selection" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Unit Kerja / Asal Instansi *
                            </label>
                            <p class="text-xs text-slate-500 mt-0.5">Pilih dari master sekolah / unit dinas, atau ketik manual.</p>
                            <select name="work_unit_selection"
                                    id="work_unit_selection"
                                    onchange="handleWorkUnitSelection(this.value)"
                                    class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                                    required>
                                <option value="">-- Pilih Sekolah / Unit Kerja --</option>
                                <optgroup label="Sekolah (Satuan Pendidikan)">
                                    @foreach ($schools as $school)
                                        <option value="school:{{ $school->id }}" data-npsn="{{ $school->npsn }}" data-name="{{ $school->name }}" @selected(old('work_unit_selection') === 'school:'.$school->id)>
                                            {{ $school->name }} (NPSN: {{ $school->npsn }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                @if($workUnits->isNotEmpty())
                                    <optgroup label="Unit Kerja / Instansi Non-Sekolah">
                                        @foreach ($workUnits as $unit)
                                            <option value="work_unit:{{ $unit->id }}" data-npsn="" data-name="{{ $unit->name }}" @selected(old('work_unit_selection') === 'work_unit:'.$unit->id)>
                                                {{ $unit->name }} {{ $unit->code ? '('.$unit->code.')' : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                <option value="manual" @selected(old('work_unit_selection') === 'manual')>-- Lainnya / Ketik Manual --</option>
                            </select>

                            <!-- Manual Unit Kerja Input (Initially Hidden) -->
                            <div id="recipient_unit_manual_wrapper" class="{{ old('work_unit_selection') === 'manual' ? '' : 'hidden' }} mt-2">
                                <label for="recipient_unit_manual" class="block text-[11px] font-semibold text-slate-600">
                                    Ketik Nama Unit Kerja / Sekolah:
                                </label>
                                <input type="text"
                                       name="recipient_unit_manual"
                                       id="recipient_unit_manual"
                                       value="{{ old('recipient_unit_manual') }}"
                                       class="mt-1 min-h-[42px] w-full rounded-lg border border-amber-300 bg-amber-50/40 px-3 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                                       placeholder="Nama sekolah atau instansi pengambil">
                            </div>
                        </div>

                        <!-- 6. NOMOR PUBLIK (NPSN) -->
                        <div>
                            <label for="npsn" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Nomor Publik (NPSN)
                            </label>
                            <p class="text-xs text-slate-500 mt-0.5">8 digit angka (otomatis terisi jika memilih sekolah).</p>
                            <input type="text"
                                   name="npsn"
                                   id="npsn"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   value="{{ old('npsn') }}"
                                   class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                                   placeholder="Contoh: 20320340">
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- ============================================================ -->
            <!-- SECTION B: ATRIBUT KEJUARAAN                                 -->
            <!-- ============================================================ -->
            <fieldset class="space-y-4 rounded-xl border border-slate-200/90 bg-slate-50/50 p-5 sm:p-6">
                <legend class="px-2 text-xs font-black uppercase tracking-wider text-emerald-800 bg-emerald-100/80 rounded-md py-1 border border-emerald-200">
                    BAGIAN B — ATRIBUT KEJUARAAN & SISWA
                </legend>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <!-- 1. KELAS KOMPETISI -->
                    <div class="md:col-span-2">
                        <label for="competition_category_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Kelas Kompetisi / Kategori Perlombaan *
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih kategori lomba dari dinas atau isi nama lomba manual.</p>
                        <select name="competition_category_id"
                                id="competition_category_id"
                                onchange="handleCompetitionCategoryChange(this.value)"
                                class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden">
                            <option value="">-- Pilih Kategori Perlombaan --</option>
                            @foreach ($competitionCategories as $comp)
                                <option value="{{ $comp->id }}" data-division-id="{{ $comp->division_id }}" @selected(old('competition_category_id') == $comp->id)>
                                    {{ $comp->name }}
                                </option>
                            @endforeach
                            <option value="manual" @selected(old('competition_category_id') === 'manual')>-- Lainnya / Ketik Manual --</option>
                        </select>

                        <!-- Manual Competition Category Input (Initially Hidden) -->
                        <div id="competition_category_manual_wrapper" class="{{ old('competition_category_id') === 'manual' ? '' : 'hidden' }} mt-2">
                            <input type="text"
                                   name="competition_category_manual"
                                   id="competition_category_manual"
                                   value="{{ old('competition_category_manual') }}"
                                   class="min-h-[42px] w-full rounded-lg border border-amber-300 bg-amber-50/40 px-3 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                                   placeholder="Ketik nama perlombaan/kategori lomba...">
                        </div>
                    </div>

                    <!-- 2. TAHUN ACARA -->
                    <div>
                        <label for="competition_year" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Tahun Acara *
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">Tahun penyelenggaraan lomba.</p>
                        <input type="number"
                               name="competition_year"
                               id="competition_year"
                               min="2000"
                               max="2099"
                               value="{{ old('competition_year', date('Y')) }}"
                               class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                               required>
                    </div>

                    <!-- 3. KANDIDAT PRESTASI (NAMA SISWA) -->
                    <div class="md:col-span-3">
                        <label for="student_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Kandidat Prestasi (Nama Siswa) *
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">Nama siswa peraih penghargaan/piagam (atau tim peraih juara).</p>
                        <input type="text"
                               name="student_name"
                               id="student_name"
                               value="{{ old('student_name') }}"
                               class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                               placeholder="Contoh: Ahmad Zaki / Regu Putri SMPN 1"
                               required>
                    </div>

                    <!-- 4. KETERANGAN PIAGAM / PRESTASI (OPSIONAL) -->
                    <div class="md:col-span-3">
                        <label for="piagam_description" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Keterangan Piagam Tambahan (Opsional)
                        </label>
                        <p class="text-xs text-slate-500 mt-0.5">Dapat diisi peringkat juara atau rincian spesifik penghargaan.</p>
                        <input type="text"
                               name="piagam_description"
                               id="piagam_description"
                               value="{{ old('piagam_description') }}"
                               class="mt-1.5 min-h-[46px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 focus:outline-hidden"
                               placeholder="Contoh: Juara 1 Solo Vokal FLS2N Tingkat Kabupaten">
                    </div>
                </div>
            </fieldset>

            <!-- ============================================================ -->
            <!-- SECTION C: BUKTI PENGAMBILAN (TANDA TANGAN DIGITAL)          -->
            <!-- ============================================================ -->
            <fieldset class="space-y-4 rounded-xl border border-slate-200/90 bg-slate-50/50 p-5 sm:p-6">
                <legend class="px-2 text-xs font-black uppercase tracking-wider text-emerald-800 bg-emerald-100/80 rounded-md py-1 border border-emerald-200">
                    BAGIAN C — BUKTI PENGAMBILAN & TANDA TANGAN DIGITAL
                </legend>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-900">
                            Tanda Tangan Digital Pengambil *
                        </label>
                        <p class="text-xs text-slate-500">Bubuhkan tanda tangan Anda di area putih menggunakan layar sentuh atau mouse.</p>
                    </div>
                    <button type="button"
                            id="btn-clear-signature"
                            onclick="clearSignaturePad()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 self-start sm:self-auto py-1 px-3 rounded-md hover:bg-rose-50 border border-rose-200 transition-colors">
                        <x-icon name="x" class="h-3.5 w-3.5" />
                        Bersihkan Tanda Tangan
                    </button>
                </div>

                <div class="relative w-full overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-white shadow-inner">
                    <canvas id="signature-pad"
                            data-testid="signature-pad"
                            class="block w-full min-h-[180px] h-48 cursor-crosshair touch-none"
                            style="touch-action: none; -ms-touch-action: none;"></canvas>
                    <div id="signature-watermark" class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs font-medium text-slate-300 select-none">
                        Tanda Tangan di Sini
                    </div>
                </div>
                <p id="signature-error-text" class="hidden mt-1.5 text-xs font-semibold text-rose-600">
                    Tanda tangan digital wajib dibubuhkan sebelum menyimpan tanda terima.
                </p>

                <div class="rounded-lg bg-emerald-50/70 border border-emerald-100 p-3 text-[11px] text-emerald-800 flex items-center gap-2">
                    <x-icon name="clock" class="h-4 w-4 shrink-0 text-emerald-700" />
                    <span>Waktu penyerahan fisik akan dicatat secara otomatis oleh server saat formulir disimpan.</span>
                </div>
            </fieldset>

            <!-- SUBMIT BUTTON -->
            <div class="pt-2">
                <button type="submit"
                        id="btn-submit-handover"
                        class="flex min-h-[50px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-6 text-sm font-bold text-white shadow-md hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-200 focus:outline-hidden transition-all disabled:opacity-50">
                    <span id="btn-submit-text" class="flex items-center gap-2">
                        <x-icon name="signature" class="h-5 w-5" />
                        Simpan Tanda Terima (Konfirmasi & Ambil Piagam)
                    </span>
                    <span id="btn-submit-spinner" class="hidden flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyimpan ke Buku Ekspedisi...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let canvas, ctx;
    let isDrawing = false;
    let hasStrokes = false;
    let strokeCount = 0;
    const idempotencyKey = '{{ (string) \Illuminate\Support\Str::uuid() }}';

    function initSignatureCanvas() {
        canvas = document.getElementById('signature-pad');
        if (!canvas) return;
        ctx = canvas.getContext('2d');

        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) return;

            // Preserve canvas content if user has already drawn
            let tempImage = null;
            if (hasStrokes) {
                tempImage = canvas.toDataURL('image/png');
            }

            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            ctx.scale(ratio, ratio);

            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#0f172a'; // slate-900

            if (tempImage) {
                const img = new Image();
                img.onload = () => {
                    ctx.drawImage(img, 0, 0, rect.width, rect.height);
                };
                img.src = tempImage;
            }
        }

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            let clientX, clientY;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else {
                clientX = e.clientX;
                clientY = e.clientY;
            }
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function start(e) {
            if (e.cancelable) e.preventDefault();
            isDrawing = true;
            hasStrokes = true;
            strokeCount++;
            document.getElementById('signature-watermark').classList.add('hidden');
            document.getElementById('signature-error-text').classList.add('hidden');
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function move(e) {
            if (!isDrawing) return;
            if (e.cancelable) e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }

        function stop(e) {
            if (!isDrawing) return;
            if (e.cancelable) e.preventDefault();
            isDrawing = false;
        }

        // Pointer / Touch / Mouse Events
        canvas.addEventListener('pointerdown', start);
        canvas.addEventListener('pointermove', move);
        canvas.addEventListener('pointerup', stop);
        canvas.addEventListener('pointercancel', stop);

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', stop);
        canvas.addEventListener('mouseleave', stop);

        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', stop, { passive: false });
    }

    function clearSignaturePad() {
        if (!canvas || !ctx) return;
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
        hasStrokes = false;
        strokeCount = 0;
        document.getElementById('signature_canvas').value = '';
        document.getElementById('signature-watermark').classList.remove('hidden');
    }

    function handleDivisionChange(val) {
        const wrapper = document.getElementById('division_manual_wrapper');
        if (val === 'manual') {
            wrapper.classList.remove('hidden');
            document.getElementById('division_manual').focus();
        } else {
            wrapper.classList.add('hidden');
        }
    }

    function handlePickupCategoryChange(val) {
        const wrapper = document.getElementById('pickup_category_manual_wrapper');
        if (val === 'manual') {
            wrapper.classList.remove('hidden');
            document.getElementById('pickup_category_manual').focus();
        } else {
            wrapper.classList.add('hidden');
        }
    }

    function handleCompetitionCategoryChange(val) {
        const wrapper = document.getElementById('competition_category_manual_wrapper');
        if (val === 'manual') {
            wrapper.classList.remove('hidden');
            document.getElementById('competition_category_manual').focus();
        } else {
            wrapper.classList.add('hidden');
        }
    }

    function handleWorkUnitSelection(val) {
        const manualWrapper = document.getElementById('recipient_unit_manual_wrapper');
        const npsnInput = document.getElementById('npsn');
        const select = document.getElementById('work_unit_selection');
        const selectedOption = select.options[select.selectedIndex];

        if (val === 'manual') {
            manualWrapper.classList.remove('hidden');
            document.getElementById('recipient_unit_manual').focus();
        } else {
            manualWrapper.classList.add('hidden');
            if (val.startsWith('school:')) {
                const npsn = selectedOption.getAttribute('data-npsn');
                if (npsn) {
                    npsnInput.value = npsn;
                }
            } else if (val.startsWith('work_unit:')) {
                npsnInput.value = '';
            }
        }
    }

    function setupFormSubmit() {
        const form = document.getElementById('public-handover-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validate signature stroke presence
            if (!hasStrokes || strokeCount === 0) {
                document.getElementById('signature-error-text').classList.remove('hidden');
                canvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const dataUrl = canvas.toDataURL('image/png');
            document.getElementById('signature_canvas').value = dataUrl;

            const submitBtn = document.getElementById('btn-submit-handover');
            const submitText = document.getElementById('btn-submit-text');
            const submitSpinner = document.getElementById('btn-submit-spinner');
            const errorBanner = document.getElementById('form-error-banner');
            const errorList = document.getElementById('form-error-list');

            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75');
            submitText.classList.add('hidden');
            submitSpinner.classList.remove('hidden');
            errorBanner.classList.add('hidden');
            errorList.innerHTML = '';

            const payload = {
                division_id: document.getElementById('division_id').value,
                division_manual: document.getElementById('division_manual')?.value || '',
                recipient_name: document.getElementById('recipient_name').value,
                recipient_position: document.getElementById('recipient_position').value,
                work_unit_selection: document.getElementById('work_unit_selection').value,
                recipient_unit_manual: document.getElementById('recipient_unit_manual')?.value || '',
                npsn: document.getElementById('npsn')?.value || null,
                pickup_category_id: document.getElementById('pickup_category_id').value,
                pickup_category_manual: document.getElementById('pickup_category_manual')?.value || '',
                competition_category_id: document.getElementById('competition_category_id').value,
                competition_category_manual: document.getElementById('competition_category_manual')?.value || '',
                competition_year: document.getElementById('competition_year').value,
                student_name: document.getElementById('student_name').value,
                piagam_description: document.getElementById('piagam_description')?.value || '',
                signature_canvas: dataUrl
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                              document.querySelector('input[name="_token"]')?.value;

            try {
                const response = await fetch('/api/v1/piagam/ambil', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Idempotency-Key': idempotencyKey
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    errorBanner.classList.remove('hidden');
                    if (data.errors) {
                        for (const [field, messages] of Object.entries(data.errors)) {
                            messages.forEach(msg => {
                                const li = document.createElement('li');
                                li.textContent = msg;
                                errorList.appendChild(li);
                            });
                        }
                    } else {
                        const li = document.createElement('li');
                        li.textContent = data.message || 'Terjadi kesalahan pada sistem.';
                        errorList.appendChild(li);
                    }
                    errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-75');
                    submitText.classList.remove('hidden');
                    submitSpinner.classList.add('hidden');
                    return;
                }

                // SUCCESS STATE
                document.getElementById('claim-form-card').classList.add('hidden');
                document.getElementById('claim-success-banner').classList.remove('hidden');
                const successMsg = document.getElementById('claim-success-message');
                if (data.data) {
                    successMsg.textContent = `Tanda terima untuk ${data.data.recipient_name} (${data.data.school_origin}) telah berhasil dicatat pada Buku Ekspedisi Digital #${data.data.id}.`;
                }
                document.getElementById('claim-success-banner').scrollIntoView({ behavior: 'smooth', block: 'start' });

            } catch (err) {
                errorBanner.classList.remove('hidden');
                const li = document.createElement('li');
                li.textContent = 'Gagal terhubung dengan server. Silakan periksa koneksi internet Anda dan coba lagi.';
                errorList.appendChild(li);
                errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-75');
                submitText.classList.remove('hidden');
                submitSpinner.classList.add('hidden');
            }
        });
    }

    function resetFormForNewReceipt() {
        document.getElementById('public-handover-form').reset();
        clearSignaturePad();
        document.getElementById('claim-success-banner').classList.add('hidden');
        document.getElementById('claim-form-card').classList.remove('hidden');
        const submitBtn = document.getElementById('btn-submit-handover');
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-75');
        document.getElementById('btn-submit-text').classList.remove('hidden');
        document.getElementById('btn-submit-spinner').classList.add('hidden');
        document.getElementById('division_manual_wrapper').classList.add('hidden');
        document.getElementById('pickup_category_manual_wrapper').classList.add('hidden');
        document.getElementById('competition_category_manual_wrapper').classList.add('hidden');
        document.getElementById('recipient_unit_manual_wrapper').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSignatureCanvas();
        setupFormSubmit();
    });
</script>
