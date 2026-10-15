<!-- Modal Dialog Konfirmasi Hapus Surat -->
<div id="letter-delete-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
    <div class="flex min-h-full items-center justify-center">
        <div class="relative w-full max-w-md overflow-hidden rounded-xl bg-white p-6 shadow-2xl transition-all duration-300">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <x-icon name="trash" class="h-6 w-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="delete-modal-title" class="text-base font-bold text-slate-900">Konfirmasi Hapus Arsip</h3>
                    <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                        Apakah Anda yakin ingin menghapus arsip surat <span id="delete-modal-letter-number" class="font-bold text-slate-900">-</span>? Tindakan ini akan memindahkan data ke status arsip terhapus (soft-delete).
                    </p>
                </div>
            </div>

            <div id="delete-modal-error" class="hidden mt-4 rounded-md border border-rose-300 bg-rose-50 p-2.5 text-xs text-rose-800"></div>

            <div class="mt-6 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeLetterDeleteModal()" class="min-h-[38px] rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                    Batal
                </button>
                <button type="button" id="delete-modal-btn-confirm" onclick="executeDeleteLetter()" class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-md bg-rose-600 px-4 text-xs font-semibold text-white shadow-xs hover:bg-rose-700 focus:outline-hidden disabled:opacity-60">
                    <x-icon name="trash" class="h-4 w-4" />
                    <span id="delete-modal-btn-text">Ya, Hapus Arsip</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let pendingDeleteType = 'surat-masuk';
    let pendingDeleteId = null;
    let previousDeleteFocusedElement = null;

    function trapDeleteModalFocus(e) {
        const modal = document.getElementById('letter-delete-modal');
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

    function openLetterDeleteModal(type, id, letterNumber) {
        pendingDeleteType = type;
        pendingDeleteId = id;
        previousDeleteFocusedElement = document.activeElement;
        document.addEventListener('keydown', trapDeleteModalFocus);
        document.getElementById('delete-modal-letter-number').textContent = letterNumber || `#${id}`;
        document.getElementById('delete-modal-error').classList.add('hidden');
        document.getElementById('delete-modal-btn-confirm').disabled = false;
        document.getElementById('delete-modal-btn-text').textContent = 'Ya, Hapus Arsip';
        document.getElementById('letter-delete-modal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeLetterDeleteModal() {
        document.getElementById('letter-delete-modal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        document.removeEventListener('keydown', trapDeleteModalFocus);
        pendingDeleteId = null;
        if (previousDeleteFocusedElement && typeof previousDeleteFocusedElement.focus === 'function') {
            previousDeleteFocusedElement.focus();
        }
    }

    async function executeDeleteLetter() {
        if (!pendingDeleteId) return;
        const btn = document.getElementById('delete-modal-btn-confirm');
        const btnText = document.getElementById('delete-modal-btn-text');
        const errorBox = document.getElementById('delete-modal-error');

        btn.disabled = true;
        btnText.textContent = 'Menghapus...';
        errorBox.classList.add('hidden');

        try {
            const res = await fetch(`/${pendingDeleteType}/${pendingDeleteId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                }
            });

            if (res.ok) {
                sessionStorage.setItem('sisurat_toast', JSON.stringify({ message: 'Arsip surat berhasil dipindahkan ke kotak sampah.', type: 'success' }));
                closeLetterDeleteModal();
                window.location.reload();
            } else {
                const data = await res.json();
                errorBox.textContent = data.message || 'Gagal menghapus surat. Anda mungkin tidak memiliki izin.';
                errorBox.classList.remove('hidden');
                btn.disabled = false;
                btnText.textContent = 'Ya, Hapus Arsip';
            }
        } catch (err) {
            errorBox.textContent = 'Terjadi kesalahan jaringan saat menghapus surat.';
            errorBox.classList.remove('hidden');
            btn.disabled = false;
            btnText.textContent = 'Ya, Hapus Arsip';
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const delModal = document.getElementById('letter-delete-modal');
            if (delModal && !delModal.classList.contains('hidden')) {
                closeLetterDeleteModal();
            }
        }
    });
</script>
