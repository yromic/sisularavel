<!-- Modal Split Detail Tanda Terima Piagam & Live Tanda Tangan Digital (Desktop: Kiri-Kanan, Mobile: Atas-Bawah) -->
<div id="split-piagam-detail-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 p-2 sm:p-4 backdrop-blur-xs md:p-6" role="dialog" aria-modal="true" aria-labelledby="modal-piagam-title">
    <div class="flex min-h-full items-center justify-center">
        <div class="relative flex w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl transition-all duration-300 max-h-[92vh]">
            
            <!-- Mobile Header Bar: Judul Singkat & Tombol Tutup -->
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-2.5 lg:hidden">
                <span id="mobile-piagam-type-badge" class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Tanda Terima Piagam</span>
                <button type="button" onclick="closePiagamDetailModal()" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 hover:text-slate-700" aria-label="Tutup modal piagam">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <!-- Responsive Body: Mobile Vertical Stack (flex-col), Desktop Side-by-Side (lg:flex-row) -->
            <div class="flex flex-col lg:flex-row flex-1 overflow-y-auto lg:overflow-hidden">
                
                <!-- ============================================================ -->
                <!-- SISI KIRI (DESKTOP) / SISI ATAS (MOBILE): INFORMASI PENERIMA -->
                <!-- ============================================================ -->
                <div class="w-full lg:w-5/12 overflow-y-auto border-b lg:border-b-0 lg:border-r border-slate-200 p-5 lg:p-6 bg-white shrink-0">
                    <!-- Badges -->
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <span id="modal-piagam-id-badge" class="rounded bg-slate-100 px-2.5 py-0.5 text-xs font-mono font-semibold text-slate-700">Resi #</span>
                            <span id="modal-piagam-division-badge" class="rounded bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800 border border-teal-200">DIVISI</span>
                        </div>
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                            Tercatat Sah
                        </span>
                    </div>

                    <!-- Judul Piagam / Siswa -->
                    <h2 id="modal-piagam-title" class="text-lg lg:text-xl font-bold text-slate-900 break-words leading-snug">
                        -
                    </h2>

                    <!-- Loading Indicator -->
                    <div id="modal-piagam-loading" class="my-4 flex items-center gap-2 text-xs text-slate-500">
                        <x-icon name="loader" class="h-4 w-4 animate-spin text-emerald-700" />
                        <span>Mengambil data tanda terima piagam...</span>
                    </div>

                    <!-- Metadata Grid -->
                    <dl id="modal-piagam-content" class="mt-4 space-y-3 text-xs">
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Waktu Penyerahan</dt>
                            <dd id="modal-piagam-time" class="col-span-2 font-semibold text-slate-900">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Asal Sekolah / Instansi</dt>
                            <dd id="modal-piagam-school" class="col-span-2 text-slate-800 font-medium break-words">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">NPSN / Kode</dt>
                            <dd id="modal-piagam-npsn" class="col-span-2 font-mono text-slate-700">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Bidang Pengampu</dt>
                            <dd id="modal-piagam-division" class="col-span-2 text-emerald-700 font-semibold">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Kategori / Acara</dt>
                            <dd id="modal-piagam-competition" class="col-span-2 text-slate-800 break-words">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Tahun Lomba</dt>
                            <dd id="modal-piagam-year" class="col-span-2 text-slate-700">-</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                            <dt class="font-medium text-slate-500">Kandidat Siswa</dt>
                            <dd id="modal-piagam-student" class="col-span-2 font-bold text-slate-900 break-words">-</dd>
                        </div>

                        <!-- Bagian Identitas Pengambil -->
                        <div class="border-t-2 border-slate-100 pt-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Identitas Pengambil Fisik</h3>
                            <div class="rounded-lg bg-slate-50 border border-slate-200/80 p-3 space-y-2">
                                <div>
                                    <span class="text-[11px] text-slate-500 block">Nama Penerima</span>
                                    <span id="modal-piagam-recipient-name" class="font-bold text-slate-900 text-xs block">-</span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-500 block">Jabatan / Posisi</span>
                                    <span id="modal-piagam-recipient-pos" class="font-medium text-slate-800 text-xs block">-</span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-500 block">Unit Kerja</span>
                                    <span id="modal-piagam-recipient-unit" class="text-slate-700 text-xs block">-</span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-500 block">Jenis Pengambilan</span>
                                    <span id="modal-piagam-pickup-type" class="inline-flex rounded bg-emerald-50 border border-emerald-200 px-2 py-0.5 font-semibold text-emerald-800 text-[11px] mt-0.5">-</span>
                                </div>
                            </div>
                        </div>
                    </dl>

                    <!-- Aksi Metadata Footer -->
                    <div class="mt-5 pt-4 border-t border-slate-200 flex flex-wrap items-center gap-2">
                        <a id="modal-piagam-print-btn" href="#" target="_blank" class="inline-flex min-h-[38px] items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs">
                            <x-icon name="file-text" class="h-4 w-4 text-slate-500" />
                            <span>Buka Halaman Penuh</span>
                        </a>
                        <button type="button" onclick="closePiagamDetailModal()" class="inline-flex min-h-[38px] items-center rounded-md border border-slate-300 bg-white px-4 text-xs font-medium text-slate-700 hover:bg-slate-100 ml-auto">
                            Tutup
                        </button>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- SISI KANAN (DESKTOP) / SISI BAWAH (MOBILE): TANDA TANGAN     -->
                <!-- ============================================================ -->
                <div class="w-full lg:w-7/12 flex flex-col bg-slate-900 text-slate-100 min-h-[380px] lg:min-h-[550px] overflow-hidden">
                    
                    <!-- Toolbar Atas Viewer -->
                    <div class="flex items-center justify-between border-b border-slate-800 bg-slate-950/80 px-4 py-3 shrink-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <x-icon name="signature" class="h-4 w-4 text-emerald-400 shrink-0" />
                            <span class="truncate text-xs font-semibold text-slate-200">
                                Bukti Tanda Tangan Digital Pengambil
                            </span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Direct Download Button -->
                            <a id="modal-piagam-btn-download" href="#" class="hidden inline-flex min-h-[32px] items-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-600 focus:outline-hidden" title="Unduh gambar tanda tangan">
                                <x-icon name="download" class="h-3.5 w-3.5" />
                                <span>Unduh TTD</span>
                            </a>

                            <!-- Open in New Tab Button -->
                            <a id="modal-piagam-btn-external" href="#" target="_blank" rel="noopener noreferrer" class="hidden inline-flex min-h-[32px] items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white" title="Buka gambar tanda tangan di tab baru">
                                <x-icon name="external-link" class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">Ukuran Penuh</span>
                            </a>

                            <!-- Desktop Close Button -->
                            <button type="button" onclick="closePiagamDetailModal()" class="hidden h-8 w-8 items-center justify-center rounded-md text-slate-400 hover:bg-slate-800 hover:text-white lg:flex" title="Tutup modal (Esc)">
                                <x-icon name="x" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Viewer Content Area -->
                    <div class="relative flex flex-1 items-center justify-center p-4 sm:p-6 bg-slate-900 overflow-y-auto">
                        
                        <!-- State 1: Loading Signature -->
                        <div id="modal-piagam-sig-loading" class="flex flex-col items-center gap-2 text-xs text-slate-400">
                            <x-icon name="loader" class="h-6 w-6 animate-spin text-emerald-400" />
                            <span>Memuat tanda tangan digital...</span>
                        </div>

                        <!-- State 2: Active Signature Display -->
                        <div id="modal-piagam-sig-container" class="hidden w-full max-w-md flex-col items-center text-center">
                            <div class="w-full rounded-xl border border-slate-700 bg-white p-4 shadow-xl">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">Tanda Tangan Pengambil Fisik</p>
                                <div class="flex h-56 w-full items-center justify-center rounded-lg bg-slate-50 border border-dashed border-slate-200 p-2">
                                    <img id="modal-piagam-sig-image" src="" alt="Tanda Tangan Pengambil" class="max-h-full max-w-full object-contain filter contrast-125">
                                </div>
                                <div class="mt-3 border-t border-slate-100 pt-2.5 text-xs text-slate-600">
                                    <span>Tercatat oleh: </span>
                                    <strong id="modal-piagam-sig-signer" class="text-slate-900">-</strong>
                                </div>
                            </div>
                            <p class="mt-3 text-[11px] text-slate-400">
                                Tanda tangan ini tersimpan di storage privat terenkripsi sebagai bukti serah terima resmi.
                            </p>
                        </div>

                        <!-- State 3: Missing / Legacy Drive File -->
                        <div id="modal-piagam-sig-legacy" class="hidden w-full max-w-md text-center p-6 rounded-xl border border-amber-600/50 bg-amber-950/30 text-amber-200">
                            <x-icon name="alert-triangle" class="mx-auto h-8 w-8 text-amber-400 mb-2" />
                            <h4 class="font-bold text-sm text-amber-300">Tanda Tangan Legacy Drive</h4>
                            <p class="mt-1 text-xs text-amber-200/80">
                                Berkas tanda tangan ini berasal dari arsip migrasi Google Drive.
                            </p>
                            <a id="modal-piagam-legacy-link" href="#" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex min-h-[36px] items-center gap-1.5 rounded-md bg-amber-500 px-4 text-xs font-semibold text-slate-950 hover:bg-amber-400">
                                <x-icon name="external-link" class="h-3.5 w-3.5" />
                                <span>Buka di Google Drive</span>
                            </a>
                        </div>

                        <!-- State 4: No Signature -->
                        <div id="modal-piagam-sig-empty" class="hidden w-full max-w-md text-center p-8 rounded-xl border border-slate-800 bg-slate-950/40 text-slate-400">
                            <x-icon name="signature" class="mx-auto h-10 w-10 text-slate-600 mb-3" />
                            <h4 class="font-semibold text-sm text-slate-300">Belum Ada Tanda Tangan</h4>
                            <p class="mt-1 text-xs text-slate-500">
                                Piagam ini belum ditandatangani saat serah terima atau diambil tanpa pencatatan tanda tangan digital.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPiagamDetailId = null;

    async function openPiagamDetailModal(piagamId) {
        currentPiagamDetailId = piagamId;
        const modal = document.getElementById('split-piagam-detail-modal');
        const loading = document.getElementById('modal-piagam-loading');
        const content = document.getElementById('modal-piagam-content');
        const sigLoading = document.getElementById('modal-piagam-sig-loading');
        const sigContainer = document.getElementById('modal-piagam-sig-container');
        const sigLegacy = document.getElementById('modal-piagam-sig-legacy');
        const sigEmpty = document.getElementById('modal-piagam-sig-empty');
        const btnDownload = document.getElementById('modal-piagam-btn-download');
        const btnExternal = document.getElementById('modal-piagam-btn-external');
        const printBtn = document.getElementById('modal-piagam-print-btn');

        // Reset UI States
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        loading.classList.remove('hidden');
        content.classList.add('opacity-40');
        sigLoading.classList.remove('hidden');
        sigContainer.classList.add('hidden');
        sigLegacy.classList.add('hidden');
        sigEmpty.classList.add('hidden');
        btnDownload.classList.add('hidden');
        btnExternal.classList.add('hidden');
        printBtn.href = `/piagam/${piagamId}`;

        document.getElementById('modal-piagam-id-badge').textContent = `Resi #${piagamId}`;
        document.getElementById('modal-piagam-title').textContent = 'Memuat rincian...';

        try {
            const response = await fetch(`/api/v1/piagam/${piagamId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                if (response.status === 403) {
                    alert('Anda tidak memiliki izin untuk melihat piagam divisi ini.');
                } else {
                    alert('Gagal mengambil data tanda terima piagam.');
                }
                closePiagamDetailModal();
                return;
            }

            const result = await response.json();
            const data = result.data;

            // Populate Metadata
            document.getElementById('modal-piagam-title').textContent = data.piagam_description || data.student_name;
            document.getElementById('modal-piagam-division-badge').textContent = data.division_code || 'DIVISI';
            document.getElementById('modal-piagam-time').textContent = data.created_at_human || data.created_at_formatted;
            document.getElementById('modal-piagam-school').textContent = data.unit_display_name || data.school_origin || '-';
            document.getElementById('modal-piagam-npsn').textContent = data.npsn || '-';
            document.getElementById('modal-piagam-division').textContent = `${data.division_code} — ${data.division_name}`;
            document.getElementById('modal-piagam-competition').textContent = data.competition_category_name || '-';
            document.getElementById('modal-piagam-year').textContent = data.competition_year || '-';
            document.getElementById('modal-piagam-student').textContent = data.student_name || '-';

            document.getElementById('modal-piagam-recipient-name').textContent = data.recipient_name || '-';
            document.getElementById('modal-piagam-recipient-pos').textContent = data.recipient_position || '-';
            document.getElementById('modal-piagam-recipient-unit').textContent = data.recipient_unit || data.school_origin || '-';
            document.getElementById('modal-piagam-pickup-type').textContent = data.pickup_category_name || 'Diambil Langsung';

            loading.classList.add('hidden');
            content.classList.remove('opacity-40');
            sigLoading.classList.add('hidden');

            // Handle Signature Preview
            if (data.signature && !data.signature.is_missing) {
                sigContainer.classList.remove('hidden');
                document.getElementById('modal-piagam-sig-image').src = data.signature.preview_url;
                document.getElementById('modal-piagam-sig-signer').textContent = data.recipient_name || 'Pengambil';

                btnDownload.href = data.signature.download_url;
                btnDownload.classList.remove('hidden');

                btnExternal.href = data.signature.preview_url;
                btnExternal.classList.remove('hidden');
            } else if (data.signature && data.signature.is_missing && data.signature.legacy_drive_url) {
                sigLegacy.classList.remove('hidden');
                document.getElementById('modal-piagam-legacy-link').href = data.signature.legacy_drive_url;
            } else {
                sigEmpty.classList.remove('hidden');
            }

        } catch (err) {
            console.error('Error fetching piagam detail:', err);
            alert('Terjadi kesalahan jaringan saat memuat detail piagam.');
            closePiagamDetailModal();
        }
    }

    function closePiagamDetailModal() {
        const modal = document.getElementById('split-piagam-detail-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            currentPiagamDetailId = null;
        }
    }

    // Keyboard support: Close on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const piagamModal = document.getElementById('split-piagam-detail-modal');
            if (piagamModal && !piagamModal.classList.contains('hidden')) {
                closePiagamDetailModal();
            }
        }
    });
</script>
