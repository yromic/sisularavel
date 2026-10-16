<!-- Modal Form Tambah & Edit Surat (Surat Masuk & Surat Keluar) -->
<div id="letter-form-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 p-3 sm:p-4 backdrop-blur-xs md:p-6" role="dialog" aria-modal="true" aria-labelledby="form-modal-title">
    <div class="flex min-h-full items-center justify-center">
        <div class="relative w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl transition-all duration-300">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4">
                <div>
                    <span id="form-modal-type-badge" class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Surat Masuk</span>
                    <h2 id="form-modal-title" class="mt-1 text-lg font-bold text-slate-900">Tambah Surat Masuk</h2>
                </div>
                <button type="button" onclick="requestCloseLetterFormModal()" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-200 hover:text-slate-600" aria-label="Tutup form modal">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <!-- Form Body -->
            <form id="letter-modal-form" method="post" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="form-modal-method" value="POST">
                <input type="hidden" id="form-modal-letter-id" value="">

                <!-- Error Banner -->
                <div id="form-modal-error-banner" class="hidden rounded-md border border-rose-300 bg-rose-50 p-3 text-xs text-rose-800"></div>

                <!-- Duplicate Warning Banner (Advisory) -->
                <div id="form-modal-duplicate-banner" class="hidden rounded-md border border-amber-300 bg-amber-50 p-3.5 text-xs text-amber-900">
                    <div class="flex items-start gap-2">
                        <x-icon name="alert-triangle" class="h-4 w-4 text-amber-700 shrink-0 mt-0.5" />
                        <div>
                            <p id="form-modal-duplicate-text" class="font-medium"></p>
                            <label class="mt-2 flex items-center gap-2 font-semibold text-amber-950 cursor-pointer">
                                <input type="checkbox" name="confirm_duplicate" id="form-modal-confirm-duplicate" value="1" class="h-4 w-4 rounded border-amber-400 text-amber-700 focus:ring-amber-500">
                                <span>Saya mengonfirmasi bahwa surat ini sah dan bukan kesalahan duplikasi ganda.</span>
                            </label>
                        </div>
                    </div>
                </div>

                @if (auth()->user()->role === 'super_admin')
                    <!-- Super Admin Division Selector -->
                    <div id="form-row-division">
                        <label class="block text-xs font-semibold text-slate-700">Divisi Pengarsip *</label>
                        <select name="division_id" id="form-input-division" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden" required>
                            @foreach (\App\Models\Division::where('status', 'active')->orderBy('name')->get() as $div)
                                <option value="{{ $div->id }}" @selected(session('active_division_id') == $div->id)>
                                    {{ $div->code }} - {{ $div->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nomor Surat -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Nomor Surat *</label>
                        <input name="nomor_surat" id="form-input-nomor" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: 005/789/Disdik/2026" required>
                    </div>

                    <!-- Asal Surat (Hanya Surat Masuk) -->
                    <div id="form-row-asal" class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Asal Surat (Instansi Pengirim) *</label>
                        <input name="asal_surat" id="form-input-asal" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: Kementerian Pendidikan Dasar" required>
                    </div>

                    <!-- Tanggal Surat -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Tanggal Surat *</label>
                        <input name="tanggal_surat" id="form-input-tanggal-surat" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" required>
                    </div>

                    <!-- Tanggal Diterima (Surat Masuk) -->
                    <div id="form-row-tanggal-diterima">
                        <label class="block text-xs font-semibold text-slate-700">Tanggal Diterima *</label>
                        <input name="tanggal_diterima" id="form-input-tanggal-diterima" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden" required>
                    </div>

                    <!-- Tanggal Share (Surat Keluar) -->
                    <div id="form-row-tanggal-share" class="hidden">
                        <label class="block text-xs font-semibold text-slate-700">Tanggal Diseminasi / Share *</label>
                        <input name="tanggal_share" id="form-input-tanggal-share" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[44px] w-full rounded-md border border-slate-300 px-3 text-sm focus:border-emerald-700 focus:outline-hidden">
                    </div>
                </div>

                <!-- Perihal -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Perihal / Ringkasan *</label>
                    <textarea name="perihal" id="form-input-perihal" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Perihal atau ringkasan isi surat..." required></textarea>
                </div>

                <!-- Lampiran Berkas & Kamera Multi-Lembar -->
                <div id="form-row-attachment" class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 space-y-3">
                    <div>
                        <span class="block text-xs font-semibold text-slate-700">Dokumen Lampiran (PDF / Word / Foto Kamera HP)</span>
                        <p class="text-[11px] text-slate-500">Unggah berkas dokumen (maks. 2 MB) atau foto lembaran surat fisik menggunakan kamera HP (maks. 2 MB/lembar, total maks. 5 MB).</p>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <label class="flex min-h-[42px] cursor-pointer items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 shadow-xs">
                            <x-icon name="upload" class="h-4 w-4 text-emerald-700 shrink-0" />
                            <span id="label-attachment-file">Pilih Berkas Dokumen</span>
                            <input name="attachment" id="form-input-attachment" type="file" accept="application/pdf,image/*,.doc,.docx" class="sr-only" onchange="handleModalFilePicked(this)">
                        </label>
                        
                        <label class="flex min-h-[42px] cursor-pointer items-center justify-center gap-2 rounded-md border border-emerald-600 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800 hover:bg-emerald-100 shadow-xs">
                            <x-icon name="camera" class="h-4 w-4 text-emerald-700 shrink-0" />
                            <span id="label-camera-trigger">Ambil Foto (Kamera HP)</span>
                            <input id="form-camera-input" type="file" accept="image/*" capture="environment" class="sr-only" onchange="handleModalCameraCaptured(this)">
                        </label>
                    </div>

                    <!-- Single File Document Pill (e.g. PDF/DOCX) -->
                    <div id="form-file-preview-pill" class="hidden flex items-center justify-between rounded-md bg-emerald-100/70 px-3 py-1.5 text-xs text-emerald-900 border border-emerald-300">
                        <div class="flex items-center gap-2 min-w-0">
                            <x-icon name="file-text" class="h-4 w-4 text-emerald-700 shrink-0" />
                            <span id="form-file-preview-name" class="truncate font-medium"></span>
                        </div>
                        <button type="button" onclick="clearModalAttachment()" class="text-emerald-700 hover:text-emerald-900 font-bold ml-2">×</button>
                    </div>

                    <!-- Multi-Photo Gallery Container (Lembar 1, 2, 3...) -->
                    <div id="form-photos-gallery-container" class="hidden rounded-lg border border-teal-200 bg-teal-50/60 p-3 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-teal-900">
                                <x-icon name="check" class="h-4 w-4 text-teal-700 shrink-0" />
                                <span id="gallery-photo-count">1 Lembar Foto Terambil</span>
                            </div>
                            <button type="button" onclick="clearAllCapturedPhotos()" class="text-[11px] font-semibold text-rose-600 hover:underline">Hapus Semua Foto</button>
                        </div>
                        <p class="text-[11px] text-teal-800 leading-relaxed">
                            Foto lembaran ini akan <strong>otomatis digabungkan menjadi 1 berkas PDF resmi</strong> saat surat disimpan.
                        </p>

                        <!-- Thumbnail Grid -->
                        <div id="gallery-thumbnails-grid" class="grid grid-cols-3 sm:grid-cols-4 gap-2"></div>

                        <!-- Button to take next page -->
                        <button type="button" onclick="document.getElementById('form-camera-input').click()" class="w-full flex items-center justify-center gap-1.5 rounded-md border border-dashed border-teal-600 bg-white py-2 text-xs font-semibold text-teal-800 hover:bg-teal-50 shadow-2xs">
                            <x-icon name="camera" class="h-4 w-4 text-teal-700" />
                            <span>+ Ambil Lembar Berikutnya (Halaman Baru)</span>
                        </button>
                    </div>
                </div>

                <!-- Footer Aksi -->
                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button" onclick="requestCloseLetterFormModal()" class="min-h-[40px] rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        Batal
                    </button>
                    <button type="submit" id="form-modal-btn-submit" class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-md bg-emerald-700 px-5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800 focus:outline-hidden disabled:opacity-60 disabled:cursor-not-allowed">
                        <span id="form-modal-submit-text">Simpan Surat</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentFormType = 'surat-masuk'; // 'surat-masuk' | 'surat-keluar'
    let currentFormMode = 'create'; // 'create' | 'edit'
    let capturedPhotos = []; // array of { file, previewUrl, name }

    function openLetterFormModal(type, mode, letterData = null) {
        currentFormType = type;
        currentFormMode = mode;
        const modal = document.getElementById('letter-form-modal');
        const form = document.getElementById('letter-modal-form');
        const typeBadge = document.getElementById('form-modal-type-badge');
        const titleEl = document.getElementById('form-modal-title');
        const methodInput = document.getElementById('form-modal-method');
        const letterIdInput = document.getElementById('form-modal-letter-id');
        const errorBanner = document.getElementById('form-modal-error-banner');
        const dupBanner = document.getElementById('form-modal-duplicate-banner');
        const submitText = document.getElementById('form-modal-submit-text');

        // Reset banners, field errors & states
        errorBanner.classList.add('hidden');
        errorBanner.textContent = '';
        dupBanner.classList.add('hidden');
        clearFieldErrors();
        document.getElementById('form-modal-confirm-duplicate').checked = false;
        clearModalAttachment();
        clearAllCapturedPhotos();

        // Configure Form based on Type
        if (type === 'surat-masuk') {
            typeBadge.textContent = 'Surat Masuk';
            typeBadge.className = 'rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800';
            document.getElementById('form-row-asal').classList.remove('hidden');
            document.getElementById('form-input-asal').required = true;
            document.getElementById('form-row-tanggal-diterima').classList.remove('hidden');
            document.getElementById('form-input-tanggal-diterima').required = true;
            document.getElementById('form-row-tanggal-share').classList.add('hidden');
            document.getElementById('form-input-tanggal-share').required = false;
        } else {
            typeBadge.textContent = 'Surat Keluar';
            typeBadge.className = 'rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800';
            document.getElementById('form-row-asal').classList.add('hidden');
            document.getElementById('form-input-asal').required = false;
            document.getElementById('form-row-tanggal-diterima').classList.add('hidden');
            document.getElementById('form-input-tanggal-diterima').required = false;
            document.getElementById('form-row-tanggal-share').classList.remove('hidden');
            document.getElementById('form-input-tanggal-share').required = true;
        }

        // Configure Form based on Mode (Create vs Edit)
        if (mode === 'create') {
            titleEl.textContent = type === 'surat-masuk' ? 'Tambah Surat Masuk' : 'Tambah Surat Keluar';
            submitText.textContent = 'Simpan Surat';
            methodInput.value = 'POST';
            letterIdInput.value = '';
            form.action = `/api/v1/${type}`;
            form.reset();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('form-input-tanggal-surat').value = today;
            document.getElementById('form-input-tanggal-diterima').value = today;
            document.getElementById('form-input-tanggal-share').value = today;
        } else if (mode === 'edit' && letterData) {
            titleEl.textContent = type === 'surat-masuk' ? 'Edit Surat Masuk' : 'Edit Surat Keluar';
            submitText.textContent = 'Simpan Perubahan';
            methodInput.value = 'PUT';
            letterIdInput.value = letterData.id;
            form.action = `/api/v1/${type}/${letterData.id}`;

            document.getElementById('form-input-nomor').value = letterData.nomor_surat || letterData.letter_number || '';
            document.getElementById('form-input-tanggal-surat').value = letterData.tanggal_surat || letterData.letter_date || '';
            document.getElementById('form-input-perihal').value = letterData.perihal || letterData.subject || '';

            if (type === 'surat-masuk') {
                document.getElementById('form-input-asal').value = letterData.asal_surat || letterData.origin_agency || '';
                document.getElementById('form-input-tanggal-diterima').value = letterData.tanggal_diterima || letterData.received_date || '';
            } else {
                document.getElementById('form-input-tanggal-share').value = letterData.tanggal_share || letterData.disseminated_date || '';
            }

            if (letterData.division_id && document.getElementById('form-input-division')) {
                document.getElementById('form-input-division').value = letterData.division_id;
            }
        }

        previousFormFocusedElement = document.activeElement;
        document.addEventListener('keydown', trapFormModalFocus);
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    let previousFormFocusedElement = null;

    function trapFormModalFocus(e) {
        const modal = document.getElementById('letter-form-modal');
        if (!modal || modal.classList.contains('hidden')) return;
        if (e.key === 'Tab') {
            const focusables = modal.querySelectorAll('button:not([disabled]), [href]:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
            const visibleFocusables = Array.from(focusables).filter(el => el.offsetParent !== null);
            if (visibleFocusables.length === 0) return;
            const first = visibleFocusables[0];
            const last = visibleFocusables[visibleFocusables.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) {
                    last.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === last) {
                    first.focus();
                    e.preventDefault();
                }
            }
        }
    }

    function isLetterFormDirty() {
        if (capturedPhotos.length > 0) return true;
        const fileInput = document.getElementById('form-input-attachment');
        if (fileInput && fileInput.files && fileInput.files.length > 0) return true;

        const nomor = document.getElementById('form-input-nomor')?.value?.trim() || '';
        const perihal = document.getElementById('form-input-perihal')?.value?.trim() || '';
        const asal = document.getElementById('form-input-asal')?.value?.trim() || '';

        if (currentFormMode === 'create') {
            if (nomor !== '' || perihal !== '' || (currentFormType === 'surat-masuk' && asal !== '')) {
                return true;
            }
        }
        return false;
    }

    function requestCloseLetterFormModal() {
        if (isLetterFormDirty()) {
            const confirmed = window.confirm('Ada draf isian surat atau foto kamera yang belum disimpan. Batalkan dan tutup formulir?');
            if (!confirmed) {
                return;
            }
        }
        closeLetterFormModal();
    }

    function closeLetterFormModal() {
        clearFieldErrors();
        document.removeEventListener('keydown', trapFormModalFocus);
        const modal = document.getElementById('letter-form-modal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        clearModalAttachment();
        clearAllCapturedPhotos();
        if (previousFormFocusedElement && typeof previousFormFocusedElement.focus === 'function') {
            previousFormFocusedElement.focus();
        }
    }

    function clearFieldErrors() {
        const form = document.getElementById('letter-modal-form');
        if (!form) return;

        form.querySelectorAll('.field-error-message').forEach(el => el.remove());
        form.querySelectorAll('input, select, textarea').forEach(input => {
            input.classList.remove('border-rose-500', 'bg-rose-50/40', 'focus:border-rose-600', 'focus:ring-rose-500');
            if (!input.classList.contains('border-slate-300') && !input.classList.contains('sr-only')) {
                input.classList.add('border-slate-300');
            }
        });
    }

    function applyFieldErrors(errors) {
        clearFieldErrors();
        const form = document.getElementById('letter-modal-form');
        if (!form || !errors) return;

        let firstInvalidInput = null;
        const fieldMapping = {
            'nomor_surat': 'form-input-nomor',
            'letter_number': 'form-input-nomor',
            'asal_surat': 'form-input-asal',
            'origin_agency': 'form-input-asal',
            'tanggal_surat': 'form-input-tanggal-surat',
            'letter_date': 'form-input-tanggal-surat',
            'tanggal_diterima': 'form-input-tanggal-diterima',
            'received_date': 'form-input-tanggal-diterima',
            'tanggal_share': 'form-input-tanggal-share',
            'disseminated_date': 'form-input-tanggal-share',
            'perihal': 'form-input-perihal',
            'subject': 'form-input-perihal',
            'division_id': 'form-input-division',
            'attachment': 'form-row-attachment',
            'photos': 'form-row-attachment'
        };

        Object.entries(errors).forEach(([fieldKey, messages]) => {
            const elementId = fieldMapping[fieldKey] || `form-input-${fieldKey}`;
            const targetEl = document.getElementById(elementId) || form.querySelector(`[name="${fieldKey}"]`);

            if (targetEl) {
                targetEl.classList.add('border-rose-500', 'bg-rose-50/40');
                targetEl.classList.remove('border-slate-300');

                const errorMsg = document.createElement('p');
                errorMsg.className = 'field-error-message mt-1 text-xs font-semibold text-rose-600 flex items-center gap-1';
                errorMsg.innerHTML = `<span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-600"></span> ${messages[0]}`;

                if (targetEl.id === 'form-row-attachment') {
                    targetEl.appendChild(errorMsg);
                } else {
                    targetEl.parentNode.appendChild(errorMsg);
                }

                const cleanUpOnInput = () => {
                    targetEl.classList.remove('border-rose-500', 'bg-rose-50/40');
                    targetEl.classList.add('border-slate-300');
                    errorMsg.remove();
                };
                targetEl.addEventListener('input', cleanUpOnInput, { once: true });
                targetEl.addEventListener('change', cleanUpOnInput, { once: true });

                if (!firstInvalidInput) {
                    firstInvalidInput = targetEl;
                }
            }
        });

        if (firstInvalidInput && typeof firstInvalidInput.focus === 'function') {
            firstInvalidInput.focus();
        }
    }

    function compressImageModal(file, maxDimension = 1600, quality = 0.8) {
        return new Promise((resolve) => {
            if (!file.type.startsWith('image/')) {
                resolve(file);
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let width = img.width;
                    let height = img.height;
                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.round((width * maxDimension) / height);
                            height = maxDimension;
                        }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    canvas.toBlob(function(blob) {
                        if (blob && blob.size < file.size) {
                            const compressed = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
                            resolve(compressed);
                        } else {
                            resolve(file);
                        }
                    }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function handleModalFilePicked(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) {
                const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                alert(`Ukuran berkas (${sizeMb} MB) melebihi batas maksimal 2 MB. Silakan pilih berkas yang lebih kecil.`);
                input.value = '';
                clearModalAttachment();
                return;
            }
            clearAllCapturedPhotos();
            showModalFilePill(`${file.name} (${(file.size / 1024).toFixed(1)} KB)`);
        }
    }

    async function handleModalCameraCaptured(input) {
        if (input.files && input.files[0]) {
            const rawFile = input.files[0];
            clearModalAttachment();

            const triggerLabel = document.getElementById('label-camera-trigger');
            const prevLabel = triggerLabel.textContent;
            triggerLabel.textContent = 'Memproses Foto...';

            const file = await compressImageModal(rawFile);
            triggerLabel.textContent = prevLabel;

            if (file.size > 2 * 1024 * 1024) {
                const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                alert(`Ukuran foto (${sizeMb} MB) melebihi batas 2 MB per lembar.`);
                input.value = '';
                return;
            }

            const currentTotalBytes = capturedPhotos.reduce((sum, item) => sum + (item.file.size || 0), 0);
            if (currentTotalBytes + file.size > 5 * 1024 * 1024) {
                const totalMb = ((currentTotalBytes + file.size) / (1024 * 1024)).toFixed(2);
                alert(`Total ukuran seluruh foto (${totalMb} MB) akan melebihi batas maksimal 5 MB. Hapus sebagian foto atau gunakan dokumen scan PDF.`);
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                capturedPhotos.push({
                    file: file,
                    previewUrl: e.target.result,
                    name: `Lembar ${capturedPhotos.length + 1}`
                });
                renderPhotoGallery();
            };
            reader.readAsDataURL(file);

            input.value = '';
        }
    }

    function renderPhotoGallery() {
        const container = document.getElementById('form-photos-gallery-container');
        const grid = document.getElementById('gallery-thumbnails-grid');
        const countEl = document.getElementById('gallery-photo-count');
        const triggerLabel = document.getElementById('label-camera-trigger');

        if (capturedPhotos.length === 0) {
            container.classList.add('hidden');
            triggerLabel.textContent = 'Ambil Foto (Kamera HP)';
            return;
        }

        const totalBytes = capturedPhotos.reduce((sum, item) => sum + (item.file.size || 0), 0);
        const totalMb = (totalBytes / (1024 * 1024)).toFixed(2);

        container.classList.remove('hidden');
        countEl.textContent = `${capturedPhotos.length} Lembar Foto Terambil (Total: ${totalMb} MB / Maks. 5 MB)`;
        triggerLabel.textContent = '+ Tambah Lembar Foto';

        grid.innerHTML = '';
        capturedPhotos.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'relative group rounded-md border border-slate-300 bg-white p-1 text-center shadow-2xs';
            card.innerHTML = `
                <div class="h-20 w-full overflow-hidden rounded bg-slate-100 flex items-center justify-center">
                    <img src="${item.previewUrl}" alt="Lembar ${index + 1}" class="h-full w-full object-cover">
                </div>
                <p class="mt-1 text-[10px] font-semibold text-slate-700 truncate">Halaman ${index + 1}</p>
                <button type="button" onclick="removeCapturedPhoto(${index})" class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold text-white shadow hover:bg-rose-700" title="Hapus lembar ini">×</button>
            `;
            grid.appendChild(card);
        });
    }

    function removeCapturedPhoto(index) {
        capturedPhotos.splice(index, 1);
        renderPhotoGallery();
    }

    function clearAllCapturedPhotos() {
        capturedPhotos = [];
        renderPhotoGallery();
    }

    function showModalFilePill(filename) {
        const pill = document.getElementById('form-file-preview-pill');
        const nameEl = document.getElementById('form-file-preview-name');
        nameEl.textContent = filename;
        pill.classList.remove('hidden');
    }

    function clearModalAttachment() {
        const attInput = document.getElementById('form-input-attachment');
        if (attInput) attInput.value = '';
        const pill = document.getElementById('form-file-preview-pill');
        if (pill) pill.classList.add('hidden');
    }

    // Form Submit Handler with Idempotency & Error Handling
    document.getElementById('letter-modal-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const errorBanner = document.getElementById('form-modal-error-banner');
        const dupBanner = document.getElementById('form-modal-duplicate-banner');
        const dupText = document.getElementById('form-modal-duplicate-text');
        const submitBtn = document.getElementById('form-modal-btn-submit');
        const submitText = document.getElementById('form-modal-submit-text');
        const originalText = submitText.textContent;

        errorBanner.classList.add('hidden');
        errorBanner.textContent = '';

        submitBtn.disabled = true;
        submitText.textContent = capturedPhotos.length > 0 ? 'Menggabungkan foto & menyimpan...' : 'Menyimpan...';

        try {
            const formData = new FormData(form);
            const method = document.getElementById('form-modal-method').value;

            // If captured photos exist, append each as photos[]
            if (capturedPhotos.length > 0) {
                formData.delete('attachment');
                capturedPhotos.forEach((item, idx) => {
                    formData.append('photos[]', item.file, `lembar_${idx + 1}.jpg`);
                });
            }

            // Headers with Idempotency-Key
            const headers = {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'Idempotency-Key': crypto.randomUUID()
            };

            const response = await fetch(form.action, {
                method: method === 'PUT' ? 'POST' : method,
                headers: headers,
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                // Advisory Duplicate warning for incoming letters
                if (data.duplicate_advisory && !document.getElementById('form-modal-confirm-duplicate').checked) {
                    dupBanner.classList.remove('hidden');
                    dupText.textContent = data.message;
                    submitBtn.disabled = false;
                    submitText.textContent = 'Simpan Ulang (Konfirmasi Duplikat)';
                    return;
                }

                // Success! Close modal, set toast and refresh page to show updated table
                const successMsg = method === 'PUT' ? 'Perubahan arsip surat berhasil disimpan.' : 'Arsip surat berhasil ditambahkan.';
                sessionStorage.setItem('sisurat_toast', JSON.stringify({ message: data.message || successMsg, type: 'success' }));
                closeLetterFormModal();
                window.location.reload();
            } else {
                errorBanner.classList.remove('hidden');
                if (data.errors) {
                    applyFieldErrors(data.errors);
                    const messages = Object.values(data.errors).flat().join('<br>');
                    errorBanner.innerHTML = messages;
                } else {
                    clearFieldErrors();
                    errorBanner.textContent = data.message || 'Gagal menyimpan surat. Periksa isian formulir.';
                }
                submitBtn.disabled = false;
                submitText.textContent = originalText;
            }
        } catch (err) {
            clearFieldErrors();
            errorBanner.classList.remove('hidden');
            errorBanner.textContent = 'Terjadi gangguan jaringan saat menghubungi server. Silakan coba kembali.';
            submitBtn.disabled = false;
            submitText.textContent = originalText;
        }
    });

    // Close on Escape with dirty form guard
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const formModal = document.getElementById('letter-form-modal');
            if (formModal && !formModal.classList.contains('hidden')) {
                requestCloseLetterFormModal();
            }
        }
    });
</script>
