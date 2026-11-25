<x-layouts.app title="Tambah Surat Masuk">
    <form data-testid="incoming-letter-form" method="post" action="/api/v1/surat-masuk" enctype="multipart/form-data" class="grid min-h-screen grid-cols-1 gap-4 px-4 pb-28 pt-4 md:grid-cols-2 md:px-8" data-idempotent-form>
        @csrf
        <header class="md:col-span-2">
            <p class="text-sm font-semibold text-emerald-700">Surat Masuk</p>
            <h1 class="text-2xl font-semibold text-slate-900">Tambah Surat Masuk</h1>
            <p class="mt-1 text-xs text-slate-500">Catat surat masuk dari pihak eksternal, instansi, atau kedinasan.</p>
            <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 border border-slate-200 px-2.5 py-1 font-medium text-slate-700">
                    <x-icon name="user" class="h-3.5 w-3.5 text-slate-500" />
                    <span>Petugas: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->username }})</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 border border-emerald-200 px-2.5 py-1 font-semibold text-emerald-800">
                    <x-icon name="building" class="h-3.5 w-3.5 text-emerald-600" />
                    <span>Divisi: {{ auth()->user()->division?->name ?? 'Semua Divisi (Global)' }}</span>
                </span>
            </div>
        </header>

        <div id="form-error-banner" class="hidden rounded-md border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 md:col-span-2"></div>
        <div id="duplicate-warning-banner" class="hidden rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 md:col-span-2">
            <p id="duplicate-warning-text" class="font-medium"></p>
            <label class="mt-2 flex items-center gap-2 text-xs font-semibold text-amber-950">
                <input type="checkbox" name="confirm_duplicate" id="confirm_duplicate" value="1" class="h-4 w-4 rounded border-amber-400">
                Saya mengonfirmasi bahwa surat ini sah dan bukan kesalahan duplikasi ganda.
            </label>
        </div>

        @if (auth()->user()->role === 'super_admin')
            <label class="block md:col-span-2">
                <span class="text-sm font-medium text-slate-800">Divisi Pengarsip *</span>
                <select name="division_id" id="division_id" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
                    <option value="" disabled @selected(!session('active_division_id'))>-- Pilih Divisi Pengarsip (Wajib) --</option>
                    @foreach (\App\Models\Division::where('status', 'active')->orderBy('name')->get() as $div)
                        <option value="{{ $div->id }}" @selected(session('active_division_id') == $div->id)>
                            {{ $div->code }} - {{ $div->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Nomor Surat *</span>
            <input name="nomor_surat" id="nomor_surat" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: 005/789/Disdik/2026" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Asal Surat (Instansi Pengirim) *</span>
            <input name="asal_surat" id="asal_surat" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: Kementerian Pendidikan Dasar" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Tanggal Surat *</span>
            <input name="tanggal_surat" id="tanggal_surat" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
        </label>

        <label class="block md:col-span-1">
            <span class="text-sm font-medium text-slate-800">Tanggal Diterima *</span>
            <input name="tanggal_diterima" id="tanggal_diterima" type="date" value="{{ date('Y-m-d') }}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" required>
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm font-medium text-slate-800">Perihal *</span>
            <textarea name="perihal" id="perihal" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Perihal atau ringkasan isi surat masuk..." required></textarea>
        </label>

        <!-- Attachment with dual Camera and File chooser -->
        <div class="block md:col-span-2">
            <span class="text-sm font-medium text-slate-800">Lampiran Berkas / Foto Fisik (Opsional)</span>
            <p class="text-xs text-slate-500 mb-2">Unggah file scan dokumen (PDF/DOC) atau ambil foto fisik surat langsung menggunakan kamera. Maksimal 2 MB untuk berkas utama dan total maksimal 5 MB untuk foto.</p>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex min-h-[48px] cursor-pointer items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 shadow-xs">
                    <x-icon name="upload" class="h-4 w-4 text-emerald-700" />
                    <span>Pilih Berkas Dokumen</span>
                    <input name="attachment" id="attachment" type="file" accept="application/pdf,image/*,.doc,.docx" class="sr-only" onchange="handleFileSelected(this)">
                </label>
                <label class="flex min-h-[48px] cursor-pointer items-center justify-center gap-2 rounded-md border border-emerald-600 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800 hover:bg-emerald-100 shadow-xs">
                    <x-icon name="camera" class="h-4 w-4 text-emerald-700" />
                    <span>Ambil Foto (Kamera HP)</span>
                    <input name="attachment_camera" id="attachment_camera" type="file" accept="image/*" capture="environment" class="sr-only" onchange="handleCameraSelected(this)">
                </label>
            </div>
            
            <!-- File Info & Smart AI OCR Action Card -->
            <div id="file-info" class="mt-3 hidden rounded-lg border border-emerald-200 bg-white p-3.5 shadow-2xs space-y-2.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                            <x-icon name="file-text" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p id="file-info-name" class="font-semibold text-xs text-slate-900 truncate max-w-[240px] sm:max-w-xs"></p>
                            <p id="file-info-size" class="text-[11px] text-slate-500 font-mono"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" id="btn-ocr-scan" onclick="triggerCreateOcrScan()" class="inline-flex min-h-[34px] items-center gap-1.5 rounded-md bg-emerald-700 px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-800 focus:outline-hidden transition-all">
                            <x-icon name="search" class="h-3.5 w-3.5 text-emerald-100" />
                            <span id="btn-ocr-scan-text">Pindai Data Otomatis (AI)</span>
                        </button>
                        <button type="button" id="btn-optional-compress" onclick="executeIncomingOptionalCompress()" class="hidden inline-flex min-h-[34px] items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition-all">
                            <x-icon name="refresh-cw" class="h-3.5 w-3.5 text-teal-600" />
                            <span>Perkecil Berkas</span>
                        </button>
                    </div>
                </div>

                <div id="ocr-status-badge" class="hidden rounded-md bg-emerald-50 p-2 text-xs font-medium text-emerald-900 border border-emerald-200 flex items-center gap-2">
                    <svg class="animate-spin h-3.5 w-3.5 text-emerald-700 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="ocr-status-text">Memindai data surat dengan AI untuk mengisi formulir otomatis...</span>
                </div>
            </div>

            <!-- Multi-Photo Gallery Container (Lembar 1, 2, 3...) -->
            <div id="form-photos-gallery-container" class="mt-3 hidden rounded-lg border border-teal-200 bg-teal-50/60 p-3 space-y-2.5">
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

                <!-- Scan Button for Captured Photos -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-teal-100/70 p-2.5 rounded-lg border border-teal-200">
                    <span class="text-[11px] font-medium text-teal-900">Ekstrak otomatis teks dokumen:</span>
                    <button type="button" id="btn-gallery-ocr-scan" onclick="triggerCreateOcrScan()" class="inline-flex min-h-[32px] items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-800 transition-colors">
                        <x-icon name="search" class="h-3.5 w-3.5 text-emerald-100" />
                        <span id="btn-gallery-ocr-scan-text">Pindai Data Otomatis (AI)</span>
                    </button>
                </div>

                <!-- Button to take next page -->
                <button type="button" onclick="document.getElementById('attachment_camera').click()" class="w-full flex items-center justify-center gap-1.5 rounded-md border border-dashed border-teal-600 bg-white py-2 text-xs font-semibold text-teal-800 hover:bg-teal-50 shadow-2xs">
                    <x-icon name="camera" class="h-4 w-4 text-teal-700" />
                    <span>+ Ambil Lembar Berikutnya (Halaman Baru)</span>
                </button>
            </div>
        </div>

        <div class="sticky bottom-0 -mx-4 mt-auto flex gap-3 border-t border-slate-200 bg-white/95 p-4 md:col-span-2 md:mx-0 md:rounded-t-md">
            <a href="/surat-masuk" onclick="return handleCancelCreate(event, '/surat-masuk')" class="flex min-h-[44px] flex-1 items-center justify-center rounded-md border border-slate-300 px-4 font-medium text-slate-700 hover:bg-slate-100">Batal</a>
            <button type="submit" id="btn-submit-incoming" data-loading-label="Menyimpan..." class="min-h-[44px] flex-1 rounded-md bg-emerald-700 px-4 font-semibold text-white hover:bg-emerald-800">Simpan Surat</button>
        </div>
    </form>

    <script>
        let capturedPhotos = []; // array of { file, previewUrl, name }
        let pdfCompressionLibsLoaded = false;

        function isFormDirty() {
            if (capturedPhotos.length > 0) return true;
            const att = document.getElementById('attachment');
            if (att && att.files && att.files.length > 0) return true;
            const nomor = document.getElementById('nomor_surat')?.value?.trim() || '';
            const perihal = document.getElementById('perihal')?.value?.trim() || '';
            const asal = document.getElementById('asal_surat')?.value?.trim() || '';
            return nomor !== '' || perihal !== '' || asal !== '';
        }

        function handleCancelCreate(e, targetUrl) {
            if (isFormDirty()) {
                if (!confirm('Ada draf isian surat atau foto kamera yang belum disimpan. Batalkan dan kembali ke daftar surat?')) {
                    if (e) e.preventDefault();
                    return false;
                }
            }
            return true;
        }

        function clearFieldErrors() {
            const form = document.querySelector('[data-idempotent-form]');
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
            const form = document.querySelector('[data-idempotent-form]');
            if (!form || !errors) return;

            let firstInvalidInput = null;
            const fieldMapping = {
                'nomor_surat': 'nomor_surat',
                'asal_surat': 'asal_surat',
                'tanggal_surat': 'tanggal_surat',
                'tanggal_diterima': 'tanggal_diterima',
                'perihal': 'perihal',
                'division_id': 'division_id',
                'attachment': 'attachment',
                'photos': 'attachment_camera'
            };

            Object.entries(errors).forEach(([fieldKey, messages]) => {
                const elementId = fieldMapping[fieldKey] || fieldKey;
                const targetEl = document.getElementById(elementId) || form.querySelector(`[name="${fieldKey}"]`);

                if (targetEl) {
                    targetEl.classList.add('border-rose-500', 'bg-rose-50/40');
                    targetEl.classList.remove('border-slate-300');

                    const errorMsg = document.createElement('p');
                    errorMsg.className = 'field-error-message mt-1 text-xs font-semibold text-rose-600 flex items-center gap-1';
                    errorMsg.innerHTML = `<span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-600"></span> ${messages[0]}`;

                    targetEl.parentNode.appendChild(errorMsg);

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

        function renderPhotoGallery() {
            const container = document.getElementById('form-photos-gallery-container');
            const grid = document.getElementById('gallery-thumbnails-grid');
            const countEl = document.getElementById('gallery-photo-count');

            if (!container || !grid) return;

            if (capturedPhotos.length === 0) {
                container.classList.add('hidden');
                return;
            }

            const totalBytes = capturedPhotos.reduce((sum, item) => sum + (item.file.size || 0), 0);
            const totalMb = (totalBytes / (1024 * 1024)).toFixed(2);

            container.classList.remove('hidden');
            if (countEl) countEl.textContent = `${capturedPhotos.length} Lembar Foto Terambil (Total: ${totalMb} MB / Maks. 5 MB)`;

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
            if (capturedPhotos.length === 0) {
                const info = document.getElementById('file-info');
                if (info) info.classList.add('hidden');
            }
        }

        function clearAllCapturedPhotos() {
            capturedPhotos = [];
            renderPhotoGallery();
        }

        async function loadPdfCompressionLibraries() {
            if (pdfCompressionLibsLoaded && window.pdfjsLib && window.jspdf) {
                return true;
            }

            return new Promise((resolve, reject) => {
                let loadedCount = 0;
                function checkDone() {
                    loadedCount++;
                    if (loadedCount === 2) {
                        if (window.pdfjsLib) {
                            window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                        }
                        pdfCompressionLibsLoaded = true;
                        resolve(true);
                    }
                }

                if (!window.pdfjsLib) {
                    const s1 = document.createElement('script');
                    s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                    s1.onload = checkDone;
                    s1.onerror = () => reject(new Error('Gagal memuat pustaka pembaca PDF.'));
                    document.head.appendChild(s1);
                } else {
                    checkDone();
                }

                if (!window.jspdf) {
                    const s2 = document.createElement('script');
                    s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                    s2.onload = checkDone;
                    s2.onerror = () => reject(new Error('Gagal memuat pustaka generator PDF.'));
                    document.head.appendChild(s2);
                } else {
                    checkDone();
                }
            });
        }

        async function compressPdfClient(file, onProgress) {
            await loadPdfCompressionLibraries();

            const arrayBuffer = await file.arrayBuffer();
            const pdfDoc = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            const numPages = pdfDoc.numPages;

            const { jsPDF } = window.jspdf;
            let newPdf = null;

            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                if (typeof onProgress === 'function') {
                    onProgress(pageNum, numPages);
                }

                const page = await pdfDoc.getPage(pageNum);
                const unscaledViewport = page.getViewport({ scale: 1.0 });
                let targetScale = 1.5;
                const maxDim = Math.max(unscaledViewport.width, unscaledViewport.height);
                if (maxDim * targetScale > 1600) {
                    targetScale = 1600 / maxDim;
                }

                const viewport = page.getViewport({ scale: targetScale });
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(viewport.width);
                canvas.height = Math.round(viewport.height);
                const ctx = canvas.getContext('2d');

                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                await page.render({
                    canvasContext: ctx,
                    viewport: viewport
                }).promise;

                const imgData = canvas.toDataURL('image/jpeg', 0.75);
                const orientation = viewport.width > viewport.height ? 'landscape' : 'portrait';
                const pageWidthPt = unscaledViewport.width;
                const pageHeightPt = unscaledViewport.height;

                if (pageNum === 1) {
                    newPdf = new jsPDF({
                        orientation: orientation,
                        unit: 'pt',
                        format: [pageWidthPt, pageHeightPt],
                        compress: true
                    });
                    newPdf.addImage(imgData, 'JPEG', 0, 0, pageWidthPt, pageHeightPt, undefined, 'FAST');
                } else {
                    newPdf.addPage([pageWidthPt, pageHeightPt], orientation);
                    newPdf.addImage(imgData, 'JPEG', 0, 0, pageWidthPt, pageHeightPt, undefined, 'FAST');
                }
            }

            const outputBlob = newPdf.output('blob');
            return new File([outputBlob], file.name.replace(/\.[^.]+$/, '.pdf'), { type: 'application/pdf' });
        }

        function compressImage(file, maxDimension = 1600, quality = 0.8) {
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
                        ctx.fillStyle = '#FFFFFF';
                        ctx.fillRect(0, 0, width, height);
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

        function updateFileInfoDisplay(file, isCompressed = false) {
            const info = document.getElementById('file-info');
            const nameEl = document.getElementById('file-info-name');
            const sizeEl = document.getElementById('file-info-size');
            const compressBtn = document.getElementById('btn-optional-compress');

            if (!file) {
                if (info) info.classList.add('hidden');
                return;
            }

            if (nameEl) nameEl.textContent = file.name;
            const sizeKb = (file.size / 1024).toFixed(1);
            if (sizeEl) sizeEl.textContent = `${sizeKb} KB ${isCompressed ? '• Terkompresi' : ''}`;
            if (info) info.classList.remove('hidden');

            const isImage = file.type.startsWith('image/');
            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

            if (compressBtn) {
                if (file.size >= 300 * 1024 && (isImage || isPdf) && !isCompressed) {
                    compressBtn.classList.remove('hidden');
                    compressBtn.classList.add('inline-flex');
                } else {
                    compressBtn.classList.add('hidden');
                    compressBtn.classList.remove('inline-flex');
                }
            }
        }

        async function triggerCreateOcrScan() {
            let fileToScan = null;
            const input = document.getElementById('attachment');
            if (input && input.files && input.files[0]) {
                fileToScan = input.files[0];
            } else if (capturedPhotos.length > 0) {
                fileToScan = capturedPhotos[0].file;
            }

            if (!fileToScan) {
                if (window.showToast) {
                    window.showToast('Pilih berkas dokumen atau foto terlebih dahulu sebelum memindai.', 'warning');
                } else {
                    alert('Pilih berkas dokumen atau foto terlebih dahulu sebelum memindai.');
                }
                return;
            }

            const isDoc = fileToScan.name.toLowerCase().endsWith('.doc') || fileToScan.name.toLowerCase().endsWith('.docx');
            if (isDoc) {
                if (window.showToast) {
                    window.showToast('Format dokumen Word (.doc/.docx) tidak dapat dipindai otomatis oleh AI. Silakan simpan sebagai PDF terlebih dahulu.', 'warning');
                } else {
                    alert('Format dokumen Word (.doc/.docx) tidak dapat dipindai otomatis oleh AI. Silakan simpan sebagai PDF terlebih dahulu.');
                }
                return;
            }

            const btn = document.getElementById('btn-ocr-scan');
            const btnText = document.getElementById('btn-ocr-scan-text');
            const galleryBtn = document.getElementById('btn-gallery-ocr-scan');
            const galleryBtnText = document.getElementById('btn-gallery-ocr-scan-text');
            const badge = document.getElementById('ocr-status-badge');
            const badgeText = document.getElementById('ocr-status-text');

            if (btn) btn.disabled = true;
            if (btnText) btnText.textContent = 'Memindai Data...';
            if (galleryBtn) galleryBtn.disabled = true;
            if (galleryBtnText) galleryBtnText.textContent = 'Memindai...';

            if (badge) {
                badge.className = 'rounded-md bg-emerald-50 p-2 text-xs font-medium text-emerald-900 border border-emerald-200 flex items-center gap-2';
                badge.classList.remove('hidden');
            }
            if (badgeText) badgeText.textContent = 'Memindai data surat dengan AI untuk mengisi formulir otomatis...';

            try {
                const formData = new FormData();
                formData.append('file', fileToScan);
                formData.append('type', 'surat');

                const res = await fetch('/api/v1/ocr/scan', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const json = await res.json();

                if (res.ok && json.success && json.data) {
                    const d = json.data;
                    const highlight = (id, val) => {
                        const el = document.getElementById(id);
                        if (el && val) {
                            el.value = val;
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-50');
                            setTimeout(() => el.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-50'), 2500);
                        }
                    };

                    if (d.letter_number) highlight('nomor_surat', d.letter_number);
                    if (d.letter_date) highlight('tanggal_surat', d.letter_date);
                    if (d.origin_agency) highlight('asal_surat', d.origin_agency);
                    if (d.subject) highlight('perihal', d.subject);

                    if (badge) {
                        badge.className = 'rounded-md bg-emerald-100 p-2 text-xs font-semibold text-emerald-900 border border-emerald-300 flex items-center gap-2';
                        badge.innerHTML = '<span>✓</span> <span>Data surat berhasil dipindai dan dimasukkan ke formulir otomatis.</span>';
                    }
                    if (window.showToast) {
                        window.showToast('Data surat berhasil dipindai dan dimasukkan ke formulir.', 'success');
                    }
                } else {
                    if (badge) {
                        badge.className = 'rounded-md bg-amber-50 p-2 text-xs font-medium text-amber-900 border border-amber-200 flex items-center gap-2';
                        badge.innerHTML = `<span>!</span> <span>${json.message || 'Layanan pemindai AI sedang sibuk atau teks dokumen kurang jelas. Silakan lengkapi manual.'}</span>`;
                    }
                }
            } catch (err) {
                console.error('OCR Error:', err);
                if (badge) {
                    badge.className = 'rounded-md bg-amber-50 p-2 text-xs font-medium text-amber-900 border border-amber-200 flex items-center gap-2';
                    badge.innerHTML = '<span>!</span> <span>Tidak dapat terhubung ke pemindai AI. Silakan isi formulir manual.</span>';
                }
            } finally {
                if (btn) btn.disabled = false;
                if (btnText) btnText.textContent = 'Pindai Ulang (AI)';
                if (galleryBtn) galleryBtn.disabled = false;
                if (galleryBtnText) galleryBtnText.textContent = 'Pindai Ulang (AI)';
            }
        }

        async function handleFileSelected(input) {
            const info = document.getElementById('file-info');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const isImage = file.type.startsWith('image/');
                const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

                // Clear any camera photos so single file is primary
                clearAllCapturedPhotos();

                if (file.size > 2 * 1024 * 1024) {
                    const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                    if (isImage || isPdf) {
                        const typeLabel = isPdf ? 'Dokumen PDF' : 'Berkas gambar';
                        if (confirm(`${typeLabel} terpilih berukuran ${sizeMb} MB (melebihi batas aman 2 MB).\n\nApakah Anda ingin mengecilkan/mengompres berkas ini secara otomatis agar pas dan cepat diunggah?`)) {
                            const badge = document.getElementById('ocr-status-badge');
                            if (badge) {
                                badge.className = 'rounded-md bg-emerald-50 p-2 text-xs font-medium text-emerald-900 border border-emerald-200 flex items-center gap-2';
                                badge.classList.remove('hidden');
                            }
                            const badgeText = document.getElementById('ocr-status-text');
                            if (badgeText) badgeText.textContent = isPdf ? 'Memuat mesin kompresi PDF...' : 'Mengompres gambar terpilih...';
                            try {
                                let compressed = null;
                                if (isPdf) {
                                    compressed = await compressPdfClient(file, (cur, total) => {
                                        if (badgeText) badgeText.textContent = `Mengompres halaman PDF ${cur} dari ${total}...`;
                                    });
                                } else {
                                    compressed = await compressImage(file);
                                }
                                const dt = new DataTransfer();
                                dt.items.add(compressed);
                                input.files = dt.files;
                                const cam = document.getElementById('attachment_camera');
                                if (cam) cam.value = '';
                                updateFileInfoDisplay(compressed, true);
                                return;
                            } catch (e) {
                                console.error('Gagal mengompres berkas:', e);
                                alert('Gagal mengompres berkas.');
                            }
                        }
                    } else {
                        alert(`Dokumen Word (${sizeMb} MB) melebihi batas 2 MB. Format dokumen Word tidak dapat diperkecil langsung di peramban. Silakan gunakan berkas di bawah 2 MB atau simpan sebagai PDF.`);
                    }
                    input.value = '';
                    if (info) info.classList.add('hidden');
                    return;
                }

                updateFileInfoDisplay(file);
                const cam = document.getElementById('attachment_camera');
                if (cam) cam.value = '';
            }
        }

        async function executeIncomingOptionalCompress() {
            const input = document.getElementById('attachment');
            if (!input || !input.files || !input.files[0]) return;

            const file = input.files[0];
            const isImage = file.type.startsWith('image/');
            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
            if (!isImage && !isPdf) return;

            const badge = document.getElementById('ocr-status-badge');
            const badgeText = document.getElementById('ocr-status-text');
            if (badge) badge.classList.remove('hidden');
            if (badgeText) badgeText.textContent = isPdf ? 'Memperkecil halaman PDF...' : 'Memperkecil gambar...';

            try {
                let compressed = null;
                if (isPdf) {
                    compressed = await compressPdfClient(file, (cur, total) => {
                        if (badgeText) badgeText.textContent = `Memperkecil halaman PDF ${cur}/${total}...`;
                    });
                } else {
                    compressed = await compressImage(file);
                }

                if (compressed && compressed.size < file.size) {
                    const dt = new DataTransfer();
                    dt.items.add(compressed);
                    input.files = dt.files;
                    updateFileInfoDisplay(compressed, true);
                    if (window.showToast) {
                        window.showToast('Ukuran berkas berhasil diperkecil.', 'success');
                    }
                } else {
                    updateFileInfoDisplay(file, false);
                    if (window.showToast) {
                        window.showToast('Ukuran berkas sudah optimal.', 'info');
                    }
                }
            } catch (e) {
                console.error(e);
                alert('Gagal memperkecil berkas.');
            }
        }

        async function handleCameraSelected(input) {
            const info = document.getElementById('file-info');
            if (input.files && input.files[0]) {
                const rawFile = input.files[0];
                const file = await compressImage(rawFile);
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

                // Clear single attachment file when camera photo is captured
                const attInput = document.getElementById('attachment');
                if (attInput) attInput.value = '';
                if (info) info.classList.add('hidden');

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

        document.querySelectorAll('[data-idempotent-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const errorBanner = document.getElementById('form-error-banner');
                const warningBanner = document.getElementById('duplicate-warning-banner');
                const warningText = document.getElementById('duplicate-warning-text');
                errorBanner.classList.add('hidden');
                errorBanner.textContent = '';
                clearFieldErrors();

                const button = form.querySelector('[type="submit"]');
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = capturedPhotos.length > 0 ? 'Menggabungkan foto & menyimpan...' : button.dataset.loadingLabel;

                try {
                    const formData = new FormData(form);

                    // If multi-photo captured, append as photos[] and remove attachment
                    if (capturedPhotos.length > 0) {
                        formData.delete('attachment');
                        formData.delete('attachment_camera');
                        capturedPhotos.forEach((item, idx) => {
                            formData.append('photos[]', item.file, `lembar_${idx + 1}.jpg`);
                        });
                    }

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Idempotency-Key': (window.generateUUID ? window.generateUUID() : crypto.randomUUID()),
                            'Accept': 'application/json'
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (response.ok) {
                        if (data.duplicate_advisory && !document.getElementById('confirm_duplicate').checked) {
                            warningBanner.classList.remove('hidden');
                            warningText.textContent = data.message;
                            button.disabled = false;
                            button.textContent = 'Simpan Ulang (Konfirmasi Duplikat)';
                            return;
                        }

                        sessionStorage.setItem('sisurat_toast', JSON.stringify({
                            message: data.message || 'Surat masuk berhasil ditambahkan.',
                            type: 'success'
                        }));
                        window.location.href = '/surat-masuk';
                    } else {
                        errorBanner.classList.remove('hidden');
                        if (data.errors) {
                            applyFieldErrors(data.errors);
                            const errMsgs = Object.values(data.errors).flat().join('<br>');
                            errorBanner.innerHTML = errMsgs;
                        } else {
                            clearFieldErrors();
                            errorBanner.textContent = data.message || 'Gagal menyimpan surat masuk. Periksa isian formulir.';
                        }
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                } catch (e) {
                    clearFieldErrors();
                    errorBanner.classList.remove('hidden');
                    errorBanner.textContent = 'Terjadi gangguan koneksi jaringan. Silakan coba kembali.';
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        });
    </script>
</x-layouts.app>
