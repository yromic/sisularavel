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
                <h3 class="font-extrabold text-xl text-emerald-950">Tanda terima tersimpan</h3>
                <p class="mt-1 text-base text-emerald-800 leading-relaxed" id="claim-success-message">
                    Bukti serah terima dan tanda tangan sudah tercatat di Buku Ekspedisi Digital.
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

    @php
        $labelCls = 'block text-sm font-semibold text-slate-800';
        $hintCls = 'mt-1 text-sm text-slate-500';
        $inputCls = 'mt-2 min-h-[48px] w-full rounded-lg border border-slate-300 bg-white px-4 text-base text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20 focus:outline-hidden';
        $manualCls = 'min-h-[48px] w-full rounded-lg border border-amber-300 bg-amber-50/40 px-4 text-base text-slate-900 placeholder-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20 focus:outline-hidden';
        $stepCls = 'flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-sm font-bold text-white';
        $legendCls = 'flex items-center gap-3 text-lg font-bold text-slate-900';
    @endphp

    <!-- Main Handover Receipt Card -->
    <div id="claim-form-card" class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-8 shadow-sm">
        <div id="form-error-banner" class="hidden mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 sm:p-5 text-sm text-rose-800 shadow-xs">
            <div class="font-bold flex items-center gap-2 text-rose-900" id="form-error-title">
                <x-icon name="x" class="h-4 w-4 text-rose-600" />
                Tanda terima belum tersimpan
            </div>
            <ul id="form-error-list" class="mt-2 list-disc pl-5 space-y-1 text-sm text-rose-700"></ul>
        </div>

        <form id="public-handover-form" data-testid="piagam-claim-form" method="post" action="/api/v1/piagam/ambil" class="space-y-10">
            @csrf
            <input type="hidden" name="signature_canvas" id="signature_canvas" required>
            <input type="hidden" name="piagam_id" id="piagam_id" value="">
            <input type="hidden" name="recipient_unit" id="recipient_unit" value="">

            <!-- LANGKAH 1: PENGAMBIL -->
            <fieldset class="space-y-5">
                <legend class="{{ $legendCls }}">
                    <span class="{{ $stepCls }}">1</span>
                    Data pengambil
                </legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-6">
                    <div>
                        <label for="recipient_name" class="{{ $labelCls }}">Nama lengkap</label>
                        <input type="text"
                               name="recipient_name"
                               id="recipient_name"
                               value="{{ old('recipient_name') }}"
                               autocomplete="name"
                               class="{{ $inputCls }}"
                               placeholder="Contoh: Budi Santoso, S.Pd."
                               required>
                    </div>

                    <div>
                        <label for="recipient_position" class="{{ $labelCls }}">Jabatan</label>
                        <input list="positions_datalist"
                               name="recipient_position"
                               id="recipient_position"
                               value="{{ old('recipient_position') }}"
                               class="{{ $inputCls }}"
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

                    <!-- Sekolah: satu pintu pencarian, pilihan daftar disimpan di bagian "tidak ditemukan" -->
                    <div class="md:col-span-2">
                        <label for="school_search_autocomplete" class="{{ $labelCls }}">Asal sekolah atau instansi</label>
                        <div class="relative">
                            <div class="relative">
                                <input type="text"
                                       id="school_search_autocomplete"
                                       autocomplete="off"
                                       class="{{ $inputCls }} pl-11 pr-10"
                                       placeholder="Ketik nama sekolah atau NPSN">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pt-2 pointer-events-none text-slate-400">
                                    <x-icon name="search" class="h-4 w-4" />
                                </div>
                                <button type="button" id="btn-clear-school-autocomplete" onclick="clearSchoolAutocomplete()" aria-label="Hapus pencarian" class="hidden absolute inset-y-0 right-0 flex items-center pr-4 pt-2 text-slate-400 hover:text-slate-600">
                                    <x-icon name="x" class="h-4 w-4" />
                                </button>
                            </div>
                            <div id="school-autocomplete-results" class="hidden absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg text-sm"></div>
                        </div>
                        <p class="{{ $hintCls }}">Pilih dari hasil pencarian. NPSN terisi otomatis.</p>

                        <details class="mt-3 group" @if(old('work_unit_selection') === 'manual' || old('work_unit_selection')) open @endif>
                            <summary class="cursor-pointer text-sm font-semibold text-emerald-800 hover:text-emerald-900">
                                Tidak ketemu? Pilih dari daftar atau isi sendiri
                            </summary>
                            <div class="mt-4 space-y-5 rounded-xl bg-slate-50 p-4">
                                <div>
                                    <label for="work_unit_selection" class="{{ $labelCls }}">Daftar sekolah dan instansi</label>
                                    <select name="work_unit_selection"
                                            id="work_unit_selection"
                                            onchange="handleWorkUnitSelection(this.value)"
                                            class="{{ $inputCls }}">
                                        <option value="">Pilih sekolah atau instansi</option>
                                        <optgroup label="Sekolah">
                                            @foreach ($schools as $school)
                                                <option value="school:{{ $school->id }}" data-npsn="{{ $school->npsn }}" data-name="{{ $school->name }}" @selected(old('work_unit_selection') === 'school:'.$school->id)>
                                                    {{ $school->name }} (NPSN {{ $school->npsn }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        @if($workUnits->isNotEmpty())
                                            <optgroup label="Instansi non-sekolah">
                                                @foreach ($workUnits as $unit)
                                                    <option value="work_unit:{{ $unit->id }}" data-npsn="" data-name="{{ $unit->name }}" @selected(old('work_unit_selection') === 'work_unit:'.$unit->id)>
                                                        {{ $unit->name }} {{ $unit->code ? '('.$unit->code.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                        <option value="manual" @selected(old('work_unit_selection') === 'manual')>Belum terdaftar, isi sendiri</option>
                                    </select>
                                </div>

                                <div id="recipient_unit_manual_wrapper" class="{{ old('work_unit_selection') === 'manual' ? '' : 'hidden' }}">
                                    <label for="recipient_unit_manual" class="{{ $labelCls }}">Nama sekolah atau instansi</label>
                                    <input type="text"
                                           name="recipient_unit_manual"
                                           id="recipient_unit_manual"
                                           value="{{ old('recipient_unit_manual') }}"
                                           class="{{ $manualCls }} mt-2"
                                           placeholder="Nama sekolah atau instansi pengambil">
                                </div>

                                <div>
                                    <label for="npsn" class="{{ $labelCls }}">NPSN <span class="font-normal text-slate-500">(opsional)</span></label>
                                    <input type="text"
                                           name="npsn"
                                           id="npsn"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           value="{{ old('npsn') }}"
                                           class="{{ $inputCls }}"
                                           placeholder="8 digit, contoh: 20320340">
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </fieldset>

            <!-- LANGKAH 2: PIAGAM -->
            <fieldset class="space-y-5">
                <legend class="{{ $legendCls }}">
                    <span class="{{ $stepCls }}">2</span>
                    Data piagam
                </legend>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-5 gap-y-6">
                    <div class="md:col-span-3">
                        <label for="student_name" class="{{ $labelCls }}">Nama siswa atau regu</label>
                        <input type="text"
                               name="student_name"
                               id="student_name"
                               value="{{ old('student_name') }}"
                               class="{{ $inputCls }}"
                               placeholder="Contoh: Ahmad Zaki atau Regu Putri SMPN 1"
                               required>
                    </div>

                    <div class="md:col-span-2">
                        <label for="competition_category_id" class="{{ $labelCls }}">Lomba atau kategori</label>
                        <select name="competition_category_id"
                                id="competition_category_id"
                                onchange="handleCompetitionCategoryChange(this.value)"
                                class="{{ $inputCls }}">
                            <option value="">Pilih lomba</option>
                            @foreach ($competitionCategories as $comp)
                                <option value="{{ $comp->id }}" data-division-id="{{ $comp->division_id }}" @selected(old('competition_category_id') == $comp->id)>
                                    {{ $comp->name }}
                                </option>
                            @endforeach
                            <option value="manual" @selected(old('competition_category_id') === 'manual')>Lainnya, isi sendiri</option>
                        </select>

                        <div id="competition_category_manual_wrapper" class="{{ old('competition_category_id') === 'manual' ? '' : 'hidden' }} mt-3">
                            <input type="text"
                                   name="competition_category_manual"
                                   id="competition_category_manual"
                                   value="{{ old('competition_category_manual') }}"
                                   aria-label="Nama lomba"
                                   class="{{ $manualCls }}"
                                   placeholder="Nama lomba atau kategori">
                        </div>
                    </div>

                    <div>
                        <label for="competition_year" class="{{ $labelCls }}">Tahun</label>
                        <input type="number"
                               name="competition_year"
                               id="competition_year"
                               min="2000"
                               max="2099"
                               value="{{ old('competition_year', date('Y')) }}"
                               class="{{ $inputCls }}"
                               required>
                    </div>

                    <div class="md:col-span-3">
                        <label for="division_id" class="{{ $labelCls }}">Bidang yang menyerahkan</label>
                        <select name="division_id"
                                id="division_id"
                                onchange="handleDivisionChange(this.value)"
                                class="{{ $inputCls }}"
                                required>
                            <option value="">Pilih bidang</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}" @selected(request('bidang') === $division->code || old('division_id') == $division->id)>
                                    {{ $division->code }} · {{ $division->name }}
                                </option>
                            @endforeach
                            <option value="manual" @selected(old('division_id') === 'manual')>Lainnya, isi sendiri</option>
                        </select>

                        <div id="division_manual_wrapper" class="{{ old('division_id') === 'manual' ? '' : 'hidden' }} mt-3">
                            <input type="text"
                                   name="division_manual"
                                   id="division_manual"
                                   value="{{ old('division_manual') }}"
                                   aria-label="Nama atau kode bidang"
                                   class="{{ $manualCls }}"
                                   placeholder="Contoh: DIKDAS atau Bidang Pembinaan SD">
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label for="pickup_category_id" class="{{ $labelCls }}">Jenis pengambilan</label>
                        <select name="pickup_category_id"
                                id="pickup_category_id"
                                onchange="handlePickupCategoryChange(this.value)"
                                class="{{ $inputCls }}">
                            <option value="">Pilih jenis pengambilan</option>
                            @foreach ($pickupCategories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('pickup_category_id') == $cat->id || $loop->first)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                            <option value="manual" @selected(old('pickup_category_id') === 'manual')>Lainnya, isi sendiri</option>
                        </select>

                        <div id="pickup_category_manual_wrapper" class="{{ old('pickup_category_id') === 'manual' ? '' : 'hidden' }} mt-3">
                            <input type="text"
                                   name="pickup_category_manual"
                                   id="pickup_category_manual"
                                   value="{{ old('pickup_category_manual') }}"
                                   aria-label="Jenis pengambilan lainnya"
                                   class="{{ $manualCls }}"
                                   placeholder="Jenis pengambilan lainnya">
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label for="piagam_description" class="{{ $labelCls }}">Keterangan <span class="font-normal text-slate-500">(opsional)</span></label>
                        <input type="text"
                               name="piagam_description"
                               id="piagam_description"
                               value="{{ old('piagam_description') }}"
                               class="{{ $inputCls }}"
                               placeholder="Contoh: Juara 1 Solo Vokal FLS2N tingkat kabupaten">
                    </div>
                </div>
            </fieldset>

            <!-- LANGKAH 3: TANDA TANGAN -->
            <fieldset class="space-y-5">
                <legend class="{{ $legendCls }}">
                    <span class="{{ $stepCls }}">3</span>
                    Tanda tangan
                </legend>

                <div id="handover-summary" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-base leading-relaxed text-emerald-950">
                    <span id="handover-summary-text"></span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-slate-600">Tanda tangan di kotak di bawah dengan jari atau mouse.</p>
                    <button type="button"
                            id="btn-clear-signature"
                            onclick="clearSignaturePad()"
                            class="inline-flex min-h-[40px] items-center gap-1.5 self-start rounded-lg border border-rose-200 px-3 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors sm:self-auto">
                        <x-icon name="x" class="h-4 w-4" />
                        Bersihkan Tanda Tangan
                    </button>
                </div>

                <div class="relative w-full overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-white shadow-inner">
                    <canvas id="signature-pad"
                            data-testid="signature-pad"
                            aria-label="Kanvas Tanda Tangan Digital"
                            class="block w-full min-h-[180px] h-52 cursor-crosshair touch-none"
                            style="touch-action: none; -ms-touch-action: none;"></canvas>
                    <div id="signature-watermark" class="pointer-events-none absolute inset-0 flex items-center justify-center text-base text-slate-300 select-none">
                        Tanda tangan di sini
                    </div>
                </div>
                <p id="signature-error-text" class="hidden text-sm font-semibold text-rose-600">
                    Mohon bubuhkan tanda tangan sebelum menyimpan.
                </p>

                <p class="flex items-center gap-2 text-sm text-slate-500">
                    <x-icon name="clock" class="h-4 w-4 shrink-0 text-emerald-700" />
                    Waktu penyerahan dicatat otomatis saat disimpan.
                </p>
            </fieldset>

            <div>
                <button type="submit"
                        id="btn-submit-handover"
                        class="flex min-h-[56px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-6 text-base font-bold text-white shadow-md hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-200 focus:outline-hidden transition-all disabled:opacity-50">
                    <span id="btn-submit-text" class="flex items-center gap-2">
                        <x-icon name="signature" class="h-5 w-5" />
                        Simpan tanda terima
                    </span>
                    <span id="btn-submit-spinner" class="hidden flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyimpan...
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
    let lastCanvasWidth = 0;
    let lastCanvasHeight = 0;

    function generateIdempotencyKey() {
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    let idempotencyKey = '{{ (string) \Illuminate\Support\Str::uuid() }}' || generateIdempotencyKey();

    function initSignatureCanvas() {
        canvas = document.getElementById('signature-pad');
        if (!canvas) return;
        ctx = canvas.getContext('2d');

        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) return;

            const currentWidth = Math.round(rect.width);
            const currentHeight = Math.round(rect.height);
            // Guard against redundant canvas redraws when mobile virtual keyboard triggers resize without changing width
            if (currentWidth === lastCanvasWidth && currentHeight === lastCanvasHeight) {
                return;
            }
            lastCanvasWidth = currentWidth;
            lastCanvasHeight = currentHeight;

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
                    if (response.status === 409) {
                        idempotencyKey = generateIdempotencyKey();
                        const li = document.createElement('li');
                        li.textContent = 'Permintaan tanda terima terdeteksi ganda atau sudah pernah diproses. Kunci transaksi telah diperbarui, silakan periksa atau kirim ulang formulir.';
                        errorList.appendChild(li);
                    } else if (data.errors) {
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

                // SUCCESS STATE - regenerate key for subsequent submissions
                idempotencyKey = generateIdempotencyKey();
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
        idempotencyKey = generateIdempotencyKey();
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
        clearSchoolAutocomplete();
    }

    let schoolSearchDebounceTimer = null;
    let schoolSearchAbortController = null;

    function clearSchoolAutocomplete() {
        const input = document.getElementById('school_search_autocomplete');
        const clearBtn = document.getElementById('btn-clear-school-autocomplete');
        const dropdown = document.getElementById('school-autocomplete-results');
        if (input) input.value = '';
        if (clearBtn) clearBtn.classList.add('hidden');
        if (dropdown) {
            dropdown.innerHTML = '';
            dropdown.classList.add('hidden');
        }
    }

    function initSchoolAutocomplete() {
        const input = document.getElementById('school_search_autocomplete');
        const clearBtn = document.getElementById('btn-clear-school-autocomplete');
        const dropdown = document.getElementById('school-autocomplete-results');
        const select = document.getElementById('work_unit_selection');
        const npsnInput = document.getElementById('npsn');

        if (!input || !dropdown || !select) return;

        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', query === '');
            }

            clearTimeout(schoolSearchDebounceTimer);
            if (query.length < 2) {
                dropdown.innerHTML = '';
                dropdown.classList.add('hidden');
                return;
            }

            schoolSearchDebounceTimer = setTimeout(async () => {
                if (schoolSearchAbortController) {
                    schoolSearchAbortController.abort();
                }
                schoolSearchAbortController = new AbortController();

                try {
                    const res = await fetch(`/api/v1/schools?search=${encodeURIComponent(query)}`, {
                        signal: schoolSearchAbortController.signal,
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return;
                    const json = await res.json();
                    const schools = json.data || [];

                    dropdown.innerHTML = '';
                    if (schools.length === 0) {
                        dropdown.innerHTML = '<div class="px-3 py-2.5 text-center text-xs text-slate-400">Tidak ada sekolah yang cocok. Buka "Tidak ketemu?" di bawah untuk mengisi sendiri.</div>';
                    } else {
                        schools.forEach((s) => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'w-full text-left px-3 py-2 hover:bg-emerald-50 focus:bg-emerald-50 focus:outline-hidden flex items-center justify-between border-b border-slate-100 last:border-0 transition-colors';
                            btn.innerHTML = `
                                <div>
                                    <div class="font-semibold text-slate-800">${s.name}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">NPSN: ${s.npsn}</div>
                                </div>
                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Pilih</span>
                            `;
                            btn.addEventListener('click', () => {
                                selectSchoolFromTypeahead(s);
                            });
                            dropdown.appendChild(btn);
                        });
                    }
                    dropdown.classList.remove('hidden');
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('School autocomplete fetch error', err);
                    }
                }
            }, 250);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Support direct NPSN typing auto-sync
        if (npsnInput) {
            npsnInput.addEventListener('change', async () => {
                const npsnVal = npsnInput.value.trim();
                if (npsnVal.length >= 8) {
                    try {
                        const res = await fetch(`/api/v1/schools?search=${encodeURIComponent(npsnVal)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const json = await res.json();
                            const matched = (json.data || []).find(s => s.npsn === npsnVal);
                            if (matched) {
                                selectSchoolFromTypeahead(matched, false);
                            }
                        }
                    } catch (e) {
                        // Ignore
                    }
                }
            });
        }

        function selectSchoolFromTypeahead(school, updateNpsn = true) {
            input.value = `${school.name} (${school.npsn})`;
            if (clearBtn) clearBtn.classList.remove('hidden');
            dropdown.classList.add('hidden');

            if (updateNpsn && npsnInput) {
                npsnInput.value = school.npsn;
            }

            // Sync with select element
            const optionValue = `school:${school.id}`;
            let option = select.querySelector(`option[value="${optionValue}"]`);
            if (!option) {
                option = new Option(`${school.name} (NPSN: ${school.npsn})`, optionValue, true, true);
                option.setAttribute('data-npsn', school.npsn);
                option.setAttribute('data-name', school.name);
                select.appendChild(option);
            }
            select.value = optionValue;
            handleWorkUnitSelection(optionValue);
        }
    }

    function updateHandoverSummary() {
        const box = document.getElementById('handover-summary');
        const text = document.getElementById('handover-summary-text');
        if (!box || !text) return;
        const name = document.getElementById('recipient_name').value.trim();
        const student = document.getElementById('student_name').value.trim();
        const comp = document.getElementById('competition_category_id');
        const compLabel = comp.value && comp.value !== 'manual'
            ? comp.options[comp.selectedIndex].text.trim()
            : (document.getElementById('competition_category_manual')?.value.trim() || '');
        if (!name || !student) {
            box.classList.add('hidden');
            return;
        }
        text.textContent = `Saya, ${name}, menerima piagam atas nama ${student}${compLabel ? ' (' + compLabel + ')' : ''}.`;
        box.classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSignatureCanvas();
        setupFormSubmit();
        initSchoolAutocomplete();
        ['recipient_name', 'student_name', 'competition_category_id', 'competition_category_manual'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', updateHandoverSummary);
                el.addEventListener('change', updateHandoverSummary);
            }
        });
        updateHandoverSummary();
    });
</script>
