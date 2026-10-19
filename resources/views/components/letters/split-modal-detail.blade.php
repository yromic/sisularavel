<!-- Modal Split Detail & Live Document Viewer (Desktop: Kiri-Kanan, Mobile: Atas-Bawah) -->
<div id="split-letter-detail-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 p-2 sm:p-4 backdrop-blur-xs md:p-6" role="dialog" aria-modal="true" aria-labelledby="modal-letter-title">
    <div class="flex min-h-full items-center justify-center">
        <div class="relative flex w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl transition-all duration-300 max-h-[92vh]">
            
            <!-- Mobile Header Bar: Judul Singkat & Tombol Tutup -->
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-2.5 lg:hidden">
                <span id="mobile-modal-type-badge" class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Detail Surat</span>
                <button type="button" onclick="closeLetterDetailModal()" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 hover:text-slate-700" aria-label="Tutup modal">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <!-- Responsive Body: Mobile Vertical Stack (flex-col), Desktop Side-by-Side (lg:flex-row) -->
            <div class="flex flex-col lg:flex-row flex-1 overflow-y-auto lg:overflow-hidden">
                
                <!-- ============================================================ -->
                <!-- SISI KIRI (DESKTOP) / SISI ATAS (MOBILE): INFORMASI SURAT    -->
                <!-- ============================================================ -->
                <div class="w-full lg:w-5/12 overflow-y-auto border-b lg:border-b-0 lg:border-r border-slate-200 p-5 lg:p-6 bg-white shrink-0">
                    <!-- Badges -->
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <span id="modal-agenda-badge" class="rounded bg-slate-100 px-2.5 py-0.5 text-xs font-mono font-semibold text-slate-700">Agenda #</span>
                            <span id="modal-division-badge" class="rounded bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800 border border-teal-200">DIVISI</span>
                        </div>
                        <span id="modal-year-badge" class="hidden rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Tahun</span>
                    </div>

                    <!-- Nomor Surat -->
                    <h2 id="modal-letter-title" class="text-lg lg:text-xl font-bold text-slate-900 break-words leading-snug">
                        -
                    </h2>

                    <!-- Loading Skeleton / Placeholder -->
                    <div id="modal-detail-loading" class="my-4 flex items-center gap-2 text-xs text-slate-500">
                        <x-icon name="loader" class="h-4 w-4 animate-spin text-emerald-700" />
                        <span>Mengambil data informasi surat...</span>
                    </div>

                    <!-- Metadata Grid -->
                    <dl id="modal-detail-content" class="mt-4 space-y-3.5 text-xs">
                        <div id="modal-row-agency" class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Asal Surat</dt>
                            <dd id="modal-field-agency" class="col-span-2 font-semibold text-slate-800 break-words">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Tanggal Surat</dt>
                            <dd id="modal-field-letter-date" class="col-span-2 text-slate-800 font-medium">-</dd>
                        </div>
                        <div id="modal-row-received-date" class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Tanggal Diterima</dt>
                            <dd id="modal-field-received-date" class="col-span-2 text-slate-800 font-medium">-</dd>
                        </div>
                        <div id="modal-row-share-date" class="hidden grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Tanggal Share</dt>
                            <dd id="modal-field-share-date" class="col-span-2 text-slate-800 font-medium">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Divisi Pengarsip</dt>
                            <dd id="modal-field-division" class="col-span-2 text-slate-800">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-600">Dicatat Oleh</dt>
                            <dd id="modal-field-created-by" class="col-span-2 text-slate-800">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-600">Waktu Pencatatan</dt>
                            <dd id="modal-field-created-at" class="col-span-2 text-slate-700 font-medium">-</dd>
                        </div>

                        <!-- Perihal / Isi Ringkas -->
                        <div class="border-t border-slate-100 pt-3">
                            <dt class="font-medium text-slate-600 mb-1.5">Perihal / Ringkasan</dt>
                            <dd id="modal-field-subject" class="rounded-lg bg-slate-50 border border-slate-200/80 p-3 text-xs leading-relaxed text-slate-800 whitespace-pre-line max-h-36 overflow-y-auto">
                                -
                            </dd>
                        </div>
                    </dl>

                    <!-- Aksi Metadata Footer (Edit & Hapus) -->
                    <div class="mt-5 pt-4 border-t border-slate-200 flex flex-wrap items-center gap-2">
                        <button type="button" id="modal-btn-edit" onclick="triggerEditFromDetail()" class="hidden inline-flex min-h-[38px] items-center gap-1.5 rounded-md border border-emerald-600 bg-emerald-50 px-3.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-xs">
                            <x-icon name="edit" class="h-4 w-4" />
                            <span>Edit Surat</span>
                        </button>
                        <button type="button" id="modal-btn-delete" onclick="triggerDeleteFromDetail()" class="hidden inline-flex min-h-[38px] items-center gap-1.5 rounded-md border border-rose-300 bg-rose-50 px-3.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 shadow-xs">
                            <x-icon name="trash" class="h-4 w-4" />
                            <span>Hapus</span>
                        </button>
                        <button type="button" onclick="closeLetterDetailModal()" class="inline-flex min-h-[38px] items-center rounded-md border border-slate-300 bg-white px-3.5 text-xs font-medium text-slate-700 hover:bg-slate-100 ml-auto">
                            Tutup
                        </button>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- SISI KANAN (DESKTOP) / SISI BAWAH (MOBILE): PREVIEW BERKAS   -->
                <!-- ============================================================ -->
                <div class="w-full lg:w-7/12 flex flex-col bg-slate-900 text-slate-100 min-h-[380px] lg:min-h-[550px] overflow-hidden">
                    
                    <!-- Preview Toolbar Header -->
                    <div class="flex items-center justify-between border-b border-slate-800 bg-slate-950 px-4 py-3 shrink-0">
                        <div class="flex items-center gap-2 min-w-0 pr-2">
                            <x-icon name="file-text" class="h-5 w-5 text-teal-400 shrink-0" />
                            <div class="min-w-0">
                                <p id="modal-attachment-name" class="truncate text-xs font-semibold text-slate-200" title="Dokumen Lampiran">
                                    Memeriksa berkas...
                                </p>
                                <p id="modal-attachment-meta" class="text-[11px] text-slate-400">-</p>
                            </div>
                        </div>

                        <!-- Action Buttons di Toolbar -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Tombol Download Primer -->
                            <a id="modal-btn-download" href="#" class="hidden min-h-[36px] items-center gap-1.5 rounded-md bg-emerald-600 px-3.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500 focus:outline-hidden">
                                <x-icon name="download" class="h-4 w-4" />
                                <span>Unduh Surat</span>
                            </a>
                            
                            <!-- Buka Tab Baru -->
                            <a id="modal-btn-external" href="#" target="_blank" rel="noopener noreferrer" class="hidden h-9 w-9 items-center justify-center rounded-md text-slate-400 hover:bg-slate-800 hover:text-white" title="Buka di tab baru">
                                <x-icon name="external-link" class="h-4 w-4" />
                            </a>

                            <!-- Tutup Desktop -->
                            <button type="button" onclick="closeLetterDetailModal()" class="hidden lg:flex h-9 w-9 items-center justify-center rounded-md text-slate-400 hover:bg-slate-800 hover:text-white" aria-label="Tutup modal">
                                <x-icon name="x" class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Live Viewer Canvas Container -->
                    <div id="modal-viewer-container" class="relative flex-1 bg-slate-950/80 p-2 sm:p-3 flex items-center justify-center overflow-auto min-h-[320px] lg:min-h-0">
                        
                        <!-- State 1: Loading Preview -->
                        <div id="preview-state-loading" class="flex flex-col items-center justify-center gap-2 p-8 text-center text-slate-400">
                            <x-icon name="loader" class="h-8 w-8 animate-spin text-teal-400" />
                            <p class="text-xs font-medium">Memuat pratinjau dokumen...</p>
                        </div>

                        <!-- State 2: PDF Inline Iframe -->
                        <iframe id="preview-state-pdf" src="" class="hidden h-full w-full min-h-[350px] lg:min-h-[500px] rounded border-0 bg-white shadow-md"></iframe>

                        <!-- State 3: Image / Foto Fisik Kamera HP -->
                        <div id="preview-state-image" class="hidden max-h-full max-w-full overflow-auto p-2">
                            <img id="preview-img-element" src="" alt="Lampiran Berkas" class="max-h-[480px] max-w-full object-contain mx-auto rounded shadow-lg border border-slate-700">
                        </div>

                        <!-- State 4: Word / DOCX / Non-Renderable Binary Fallback Card -->
                        <div id="preview-state-fallback" class="hidden flex-col items-center justify-center p-6 text-center max-w-md">
                            <div class="h-16 w-16 rounded-2xl bg-teal-950 border border-teal-800 flex items-center justify-center text-teal-300 mb-3 shadow-inner">
                                <x-icon name="file-text" class="h-8 w-8" />
                            </div>
                            <h3 id="fallback-filename" class="text-sm font-semibold text-white break-all mb-1">-</h3>
                            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                                Format dokumen ini (.doc/.docx atau arsip) tidak mendukung pratinjau langsung di dalam peramban web.
                            </p>
                            <a id="fallback-btn-download" href="#" class="inline-flex min-h-[42px] items-center gap-2 rounded-lg bg-emerald-600 px-5 text-xs font-semibold text-white hover:bg-emerald-500 shadow-md">
                                <x-icon name="download" class="h-4 w-4" />
                                <span>Unduh Dokumen Sekarang</span>
                            </a>
                        </div>

                        <!-- State 5: Legacy Missing Drive Alert (DEC-FILE-003) -->
                        <div id="preview-state-missing" class="hidden flex-col items-center justify-center p-6 text-center max-w-md">
                            <div class="h-16 w-16 rounded-2xl bg-amber-950/80 border border-amber-800 flex items-center justify-center text-amber-300 mb-3">
                                <x-icon name="alert-triangle" class="h-8 w-8" />
                            </div>
                            <h3 class="text-sm font-semibold text-amber-200 mb-1">Arsip Dokumen Lama (Legacy)</h3>
                            <p class="text-xs text-amber-300/80 mb-4 leading-relaxed">
                                Berkas fisik dokumen ini belum dipindahkan ke penyimpanan privat SISURAT. Anda dapat mengakses Google Drive arsip lama jika masih tersedia.
                            </p>
                            <a id="missing-legacy-link" href="#" target="_blank" rel="noopener noreferrer" class="hidden inline-flex min-h-[40px] items-center gap-2 rounded-lg border border-amber-400 bg-amber-500/20 px-4 text-xs font-semibold text-amber-100 hover:bg-amber-500/30">
                                <x-icon name="external-link" class="h-4 w-4" />
                                <span>Buka di Google Drive Lama</span>
                            </a>
                        </div>

                        <!-- State 6: Tanpa Berkas -->
                        <div id="preview-state-empty" class="hidden flex-col items-center justify-center p-8 text-center text-slate-500">
                            <x-icon name="file" class="h-12 w-12 text-slate-700 mb-2" />
                            <p class="text-xs font-medium text-slate-400">Tidak ada dokumen lampiran pada arsip surat ini.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Sticky Bottom Action Bar (UX-013) -->
            <div id="modal-mobile-sticky-bar" class="lg:hidden sticky bottom-0 z-20 flex items-center justify-between gap-3 border-t border-slate-200 bg-white/95 backdrop-blur-xs px-4 py-2.5 shadow-lg shrink-0">
                <div class="min-w-0 flex-1">
                    <p id="mobile-sticky-attachment-name" class="truncate text-xs font-semibold text-slate-800">Lampiran Berkas</p>
                    <p class="text-[10px] text-slate-500">Pratinjau / Unduh Berkas</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a id="mobile-sticky-btn-download" href="#" class="hidden inline-flex min-h-[36px] items-center gap-1.5 rounded-md bg-emerald-600 px-3 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500">
                        <x-icon name="download" class="h-4 w-4" />
                        <span>Unduh</span>
                    </a>
                    <button type="button" onclick="closeLetterDetailModal()" class="inline-flex min-h-[36px] items-center rounded-md border border-slate-300 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentDetailLetter = null;
    let currentDetailType = 'surat-masuk'; // 'surat-masuk' | 'surat-keluar'

    async function openLetterDetailModal(type, id) {
        currentDetailType = type;
        const modal = document.getElementById('split-letter-detail-modal');
        const loadingState = document.getElementById('modal-detail-loading');
        const contentState = document.getElementById('modal-detail-content');
        
        // Reset states
        previousFocusedElement = document.activeElement;
        document.addEventListener('keydown', trapDetailModalFocus);
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        loadingState.classList.remove('hidden');
        contentState.classList.add('opacity-40');
        
        // Reset viewer states
        setViewerState('loading');
        document.getElementById('modal-btn-download').classList.add('hidden');
        document.getElementById('modal-btn-external').classList.add('hidden');
        document.getElementById('modal-btn-edit').classList.add('hidden');
        document.getElementById('modal-btn-delete').classList.add('hidden');

        try {
            const endpoint = type === 'surat-masuk' ? `/api/v1/surat-masuk/${id}` : `/api/v1/surat-keluar/${id}`;
            const res = await fetch(endpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!res.ok) {
                throw new Error('Gagal memuat detail surat.');
            }

            const json = await res.json();
            const letter = json.data;
            currentDetailLetter = letter;

            populateLetterDetail(type, letter);

        } catch (err) {
            alert(err.message || 'Terjadi kesalahan saat memuat data surat.');
            closeLetterDetailModal();
        } finally {
            loadingState.classList.add('hidden');
            contentState.classList.remove('opacity-40');
        }
    }

    function populateLetterDetail(type, letter) {
        // Badges & Header
        document.getElementById('modal-agenda-badge').textContent = `Agenda #${letter.id}`;
        document.getElementById('modal-division-badge').textContent = `${letter.division_code || 'DIVISI'}`;
        document.getElementById('modal-letter-title').textContent = letter.nomor_surat || letter.letter_number;

        if (type === 'surat-keluar' && letter.letter_year) {
            const yearBadge = document.getElementById('modal-year-badge');
            yearBadge.textContent = `Tahun ${letter.letter_year}`;
            yearBadge.classList.remove('hidden');
        } else {
            document.getElementById('modal-year-badge').classList.add('hidden');
        }

        // Fields
        if (type === 'surat-masuk') {
            document.getElementById('mobile-modal-type-badge').textContent = 'Detail Surat Masuk';
            document.getElementById('modal-row-agency').classList.remove('hidden');
            document.getElementById('modal-field-agency').textContent = letter.asal_surat || letter.origin_agency || '-';
            document.getElementById('modal-row-received-date').classList.remove('hidden');
            document.getElementById('modal-field-received-date').textContent = letter.tanggal_diterima_formatted || letter.tanggal_diterima || '-';
            document.getElementById('modal-row-share-date').classList.add('hidden');
        } else {
            document.getElementById('mobile-modal-type-badge').textContent = 'Detail Surat Keluar';
            document.getElementById('modal-row-agency').classList.add('hidden');
            document.getElementById('modal-row-received-date').classList.add('hidden');
            document.getElementById('modal-row-share-date').classList.remove('hidden');
            document.getElementById('modal-row-share-date').classList.add('grid');
            document.getElementById('modal-field-share-date').textContent = letter.tanggal_share_formatted || letter.tanggal_share || '-';
        }

        document.getElementById('modal-field-letter-date').textContent = letter.tanggal_surat_formatted || letter.tanggal_surat || '-';
        document.getElementById('modal-field-division').textContent = `${letter.division_name || '-'} (${letter.division_code || '-'})`;
        document.getElementById('modal-field-created-by').textContent = letter.created_by || 'Sistem';
        document.getElementById('modal-field-created-at').textContent = letter.created_at_formatted || '-';
        document.getElementById('modal-field-subject').textContent = letter.perihal || letter.subject || '-';

        // Actions Permission
        if (letter.can_edit) {
            document.getElementById('modal-btn-edit').classList.remove('hidden');
        }
        if (letter.can_delete) {
            document.getElementById('modal-btn-delete').classList.remove('hidden');
        }

        // Attachment Preview
        if (letter.attachment) {
            const att = letter.attachment;
            document.getElementById('modal-attachment-name').textContent = att.original_name;
            document.getElementById('modal-attachment-meta').textContent = `${att.file_size_formatted || ''} • ${att.mime_type || ''}`;

            const downloadUrl = att.download_url || `/api/v1/attachments/${att.uuid}/download`;
            const previewUrl = att.preview_url || `/api/v1/attachments/${att.uuid}/preview`;

            const downloadBtn = document.getElementById('modal-btn-download');
            downloadBtn.href = downloadUrl;
            downloadBtn.setAttribute('download', att.original_name);
            downloadBtn.classList.remove('hidden');
            downloadBtn.classList.add('inline-flex');

            const mobileStickyDownloadBtn = document.getElementById('mobile-sticky-btn-download');
            const mobileStickyAttachmentName = document.getElementById('mobile-sticky-attachment-name');
            if (mobileStickyDownloadBtn) {
                mobileStickyDownloadBtn.href = downloadUrl;
                mobileStickyDownloadBtn.setAttribute('download', att.original_name);
                mobileStickyDownloadBtn.classList.remove('hidden');
                mobileStickyDownloadBtn.classList.add('inline-flex');
            }
            if (mobileStickyAttachmentName) {
                mobileStickyAttachmentName.textContent = att.original_name;
            }

            const extBtn = document.getElementById('modal-btn-external');
            extBtn.href = previewUrl;
            extBtn.classList.remove('hidden');
            extBtn.classList.add('inline-flex');

            if (att.is_missing) {
                setViewerState('missing');
                const driveLink = document.getElementById('missing-legacy-link');
                if (att.legacy_drive_url) {
                    driveLink.href = att.legacy_drive_url;
                    driveLink.classList.remove('hidden');
                    driveLink.classList.add('inline-flex');
                } else {
                    driveLink.classList.add('hidden');
                }
            } else if (att.mime_type === 'application/pdf') {
                setViewerState('pdf');
                document.getElementById('preview-state-pdf').src = previewUrl;
            } else if (att.mime_type && att.mime_type.startsWith('image/')) {
                setViewerState('image');
                document.getElementById('preview-img-element').src = previewUrl;
            } else {
                setViewerState('fallback');
                document.getElementById('fallback-filename').textContent = att.original_name;
                const fbBtn = document.getElementById('fallback-btn-download');
                fbBtn.href = downloadUrl;
                fbBtn.setAttribute('download', att.original_name);
            }
        } else {
            document.getElementById('modal-attachment-name').textContent = 'Tanpa Berkas Lampiran';
            document.getElementById('modal-attachment-meta').textContent = 'Tidak ada lampiran dokumen diunggah.';
            const mobileStickyDownloadBtn = document.getElementById('mobile-sticky-btn-download');
            const mobileStickyAttachmentName = document.getElementById('mobile-sticky-attachment-name');
            if (mobileStickyDownloadBtn) {
                mobileStickyDownloadBtn.classList.add('hidden');
                mobileStickyDownloadBtn.classList.remove('inline-flex');
            }
            if (mobileStickyAttachmentName) {
                mobileStickyAttachmentName.textContent = 'Tanpa Lampiran';
            }
            setViewerState('empty');
        }
    }

    function setViewerState(state) {
        const states = ['loading', 'pdf', 'image', 'fallback', 'missing', 'empty'];
        states.forEach(s => {
            const el = document.getElementById(`preview-state-${s}`);
            if (el) {
                if (s === state) {
                    el.classList.remove('hidden');
                    if (s === 'fallback' || s === 'missing' || s === 'empty' || s === 'loading') {
                        el.classList.add('flex');
                    }
                } else {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }
            }
        });

        // Clean src when not in use to avoid phantom sound/downloads
        if (state !== 'pdf') {
            document.getElementById('preview-state-pdf').src = '';
        }
        if (state !== 'image') {
            document.getElementById('preview-img-element').src = '';
        }
    }

    let previousFocusedElement = null;

    function trapDetailModalFocus(e) {
        const modal = document.getElementById('split-letter-detail-modal');
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

    function closeLetterDetailModal() {
        const modal = document.getElementById('split-letter-detail-modal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        document.removeEventListener('keydown', trapDetailModalFocus);
        setViewerState('empty');
        if (previousFocusedElement && typeof previousFocusedElement.focus === 'function') {
            previousFocusedElement.focus();
        }
    }

    function triggerEditFromDetail() {
        if (!currentDetailLetter) return;
        const letter = currentDetailLetter;
        const type = currentDetailType;
        closeLetterDetailModal();
        if (typeof openLetterFormModal === 'function') {
            openLetterFormModal(type, 'edit', letter);
        } else {
            window.location.href = `/${type}/${letter.id}/edit`;
        }
    }

    function triggerDeleteFromDetail() {
        if (!currentDetailLetter) return;
        const letter = currentDetailLetter;
        const type = currentDetailType;
        closeLetterDetailModal();
        if (typeof openLetterDeleteModal === 'function') {
            openLetterDeleteModal(type, letter.id, letter.nomor_surat || letter.letter_number);
        } else if (typeof window.showConfirmDialog === 'function') {
            window.showConfirmDialog({
                title: 'Konfirmasi Hapus Surat',
                message: `Hapus arsip surat ${letter.nomor_surat || letter.letter_number}? Tindakan ini akan memindahkan surat ke arsip terhapus.`,
                variant: 'danger',
                confirmText: 'Ya, Hapus Surat'
            }).then((confirmed) => {
                if (confirmed) {
                    fetch(`/${type}/${letter.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json'
                        }
                    }).then(() => window.location.reload());
                }
            });
        }
    }

    // Keyboard shortcut ESC to close & Tab trap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const detailModal = document.getElementById('split-letter-detail-modal');
            if (detailModal && !detailModal.classList.contains('hidden')) {
                closeLetterDetailModal();
            }
        }
    });
</script>
