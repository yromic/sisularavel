/**
 * SISURAT Bulk Operations UI Engine
 * Handles multi-select, two-level selection, dry-run impact preview,
 * idempotency key generation, and resilient partial result reporting.
 */
class BulkSelectEngine {
    constructor(options = {}) {
        this.entityName = options.entityName || 'item';
        this.previewUrl = options.previewUrl || '';
        this.previewRestoreUrl = options.previewRestoreUrl || '';
        this.deleteUrl = options.deleteUrl || '';
        this.restoreUrl = options.restoreUrl || '';
        this.totalFilteredCount = parseInt(options.totalFilteredCount || 0, 10);
        this.filterParams = options.filterParams || {};
        this.confirmThreshold = parseInt(options.confirmThreshold || 50, 10);

        this.selectedIds = new Set();
        this.selectAllFiltered = false;
        this.currentPreviewData = null;
        this.currentAction = 'delete'; // 'delete' | 'restore'

        this.initDOMElements();
        this.bindEvents();
    }

    initDOMElements() {
        this.toolbar = document.getElementById('bulk-action-toolbar');
        this.selectedCountEl = document.getElementById('bulk-selected-count');
        this.allFilterPrompt = document.getElementById('bulk-all-filter-prompt');
        this.allSelectedBanner = document.getElementById('bulk-all-selected-banner');
        this.totalFilterCountEl = document.getElementById('bulk-total-filter-count');
        this.btnSelectAllFiltered = document.getElementById('btn-select-all-filtered');
        this.btnClearAllFilterSelect = document.getElementById('btn-clear-all-filter-select');
        this.btnBulkCancel = document.getElementById('btn-bulk-cancel');
        this.btnBulkDelete = document.getElementById('btn-bulk-delete');
        this.btnBulkRestore = document.getElementById('btn-bulk-restore');

        this.masterCheckbox = document.getElementById('bulk-select-all-page') || document.getElementById('bulk-select-all');
        this.rowCheckboxes = Array.from(document.querySelectorAll('.bulk-row-checkbox'));

        // Impact Modal Elements
        this.impactModal = document.getElementById('bulk-impact-modal');
        this.impactTitle = document.getElementById('bulk-impact-title');
        this.impactSubtitle = document.getElementById('bulk-impact-subtitle');
        this.impactLoading = document.getElementById('bulk-impact-loading');
        this.impactContent = document.getElementById('bulk-impact-content');
        this.previewDeletableCount = document.getElementById('bulk-preview-deletable-count');
        this.previewDeletableLabel = document.getElementById('bulk-preview-deletable-label');
        this.modalIconContainer = document.getElementById('bulk-modal-icon-container');
        this.previewSkippedCount = document.getElementById('bulk-preview-skipped-count');
        this.previewSkippedSection = document.getElementById('bulk-preview-skipped-section');
        this.previewSkippedList = document.getElementById('bulk-preview-skipped-list');
        this.previewSkippedTotalLabel = document.getElementById('bulk-preview-skipped-total-label');
        this.confirmPhraseSection = document.getElementById('bulk-confirm-phrase-section');
        this.confirmPhraseExpected = document.getElementById('bulk-confirm-phrase-expected');
        this.confirmPhraseInput = document.getElementById('bulk-confirm-phrase-input');
        this.modalErrorBox = document.getElementById('bulk-modal-error');
        this.btnModalCancel = document.getElementById('bulk-btn-modal-cancel');
        this.btnModalConfirm = document.getElementById('bulk-btn-modal-confirm');
        this.btnModalConfirmText = document.getElementById('bulk-btn-modal-confirm-text');

        // Result Modal Elements
        this.resultModal = document.getElementById('bulk-result-modal');
        this.resultSuccessCount = document.getElementById('bulk-result-success-count');
        this.resultSkippedCount = document.getElementById('bulk-result-skipped-count');
        this.resultSkippedBox = document.getElementById('bulk-result-skipped-box');
        this.resultSkippedList = document.getElementById('bulk-result-skipped-list');
        this.btnDownloadCsv = document.getElementById('btn-bulk-download-csv');
        this.btnResultClose = document.getElementById('btn-bulk-result-close');

        if (this.totalFilterCountEl) {
            this.totalFilterCountEl.textContent = this.totalFilteredCount.toLocaleString('id-ID');
        }
    }

    bindEvents() {
        if (this.masterCheckbox) {
            this.masterCheckbox.addEventListener('change', () => this.handleMasterCheckboxChange());
        }

        this.rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', (e) => this.handleRowCheckboxChange(e.target));
        });

        if (this.btnSelectAllFiltered) {
            this.btnSelectAllFiltered.addEventListener('click', () => this.handleSelectAllFiltered());
        }

        if (this.btnClearAllFilterSelect) {
            this.btnClearAllFilterSelect.addEventListener('click', () => this.handleClearAllFilterSelect());
        }

        if (this.btnBulkCancel) {
            this.btnBulkCancel.addEventListener('click', () => this.clearSelection());
        }

        if (this.btnBulkDelete) {
            this.btnBulkDelete.addEventListener('click', () => this.openImpactModal('delete'));
        }

        if (this.btnBulkRestore) {
            this.btnBulkRestore.addEventListener('click', () => this.openImpactModal('restore'));
        }

        if (this.btnModalCancel) {
            this.btnModalCancel.addEventListener('click', () => this.closeImpactModal());
        }

        if (this.btnModalConfirm) {
            this.btnModalConfirm.addEventListener('click', () => this.executeBulkAction());
        }

        if (this.confirmPhraseInput) {
            this.confirmPhraseInput.addEventListener('input', () => this.validateConfirmPhrase());
        }

        if (this.btnResultClose) {
            this.btnResultClose.addEventListener('click', () => {
                this.closeResultModal();
                window.location.reload();
            });
        }

        if (this.btnDownloadCsv) {
            this.btnDownloadCsv.addEventListener('click', () => this.downloadSkippedCsv());
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (this.impactModal && !this.impactModal.classList.contains('hidden')) {
                    this.closeImpactModal();
                }
            }
        });
    }

    handleMasterCheckboxChange() {
        const checked = this.masterCheckbox.checked;
        this.rowCheckboxes.forEach(cb => {
            cb.checked = checked;
            const id = parseInt(cb.value, 10);
            if (checked) {
                this.selectedIds.add(id);
            } else {
                this.selectedIds.delete(id);
            }
        });

        this.selectAllFiltered = false;
        this.syncToolbar();
    }

    handleRowCheckboxChange(checkbox) {
        const id = parseInt(checkbox.value, 10);
        if (checkbox.checked) {
            this.selectedIds.add(id);
        } else {
            this.selectedIds.delete(id);
            this.selectAllFiltered = false;
        }

        this.syncMasterCheckbox();
        this.syncToolbar();
    }

    handleSelectAllFiltered() {
        this.selectAllFiltered = true;
        this.syncToolbar();
    }

    handleClearAllFilterSelect() {
        this.selectAllFiltered = false;
        this.syncToolbar();
    }

    syncMasterCheckbox() {
        if (!this.masterCheckbox) return;
        const totalRows = this.rowCheckboxes.length;
        const checkedRows = this.rowCheckboxes.filter(cb => cb.checked).length;

        if (checkedRows === 0) {
            this.masterCheckbox.checked = false;
            this.masterCheckbox.indeterminate = false;
        } else if (checkedRows === totalRows) {
            this.masterCheckbox.checked = true;
            this.masterCheckbox.indeterminate = false;
        } else {
            this.masterCheckbox.checked = false;
            this.masterCheckbox.indeterminate = true;
        }
    }

    syncToolbar() {
        const count = this.selectAllFiltered ? this.totalFilteredCount : this.selectedIds.size;

        if (count > 0) {
            if (this.toolbar) this.toolbar.classList.remove('hidden');
            if (this.selectedCountEl) this.selectedCountEl.textContent = count.toLocaleString('id-ID');

            const allRowsOnPageSelected = this.rowCheckboxes.length > 0 &&
                this.rowCheckboxes.every(cb => cb.checked);

            if (this.allFilterPrompt) {
                if (allRowsOnPageSelected && !this.selectAllFiltered && this.totalFilteredCount > this.rowCheckboxes.length) {
                    this.allFilterPrompt.classList.remove('hidden');
                } else {
                    this.allFilterPrompt.classList.add('hidden');
                }
            }

            if (this.allSelectedBanner) {
                if (this.selectAllFiltered) {
                    this.allSelectedBanner.classList.remove('hidden');
                } else {
                    this.allSelectedBanner.classList.add('hidden');
                }
            }
        } else {
            if (this.toolbar) this.toolbar.classList.add('hidden');
            if (this.allFilterPrompt) this.allFilterPrompt.classList.add('hidden');
            if (this.allSelectedBanner) this.allSelectedBanner.classList.add('hidden');
        }
    }

    clearSelection() {
        this.selectedIds.clear();
        this.selectAllFiltered = false;
        this.rowCheckboxes.forEach(cb => { cb.checked = false; });
        if (this.masterCheckbox) {
            this.masterCheckbox.checked = false;
            this.masterCheckbox.indeterminate = false;
        }
        this.syncToolbar();
    }

    async openImpactModal(action = 'delete') {
        this.currentAction = action;
        if (!this.impactModal) return;

        this.impactModal.classList.remove('hidden');
        this.impactLoading.classList.remove('hidden');
        this.impactContent.classList.add('hidden');
        this.modalErrorBox.classList.add('hidden');
        this.btnModalConfirm.disabled = true;

        if (action === 'restore') {
            this.impactTitle.textContent = 'Konfirmasi Pemulihan Massal';
            this.impactSubtitle.textContent = 'Data yang dipilih akan dipulihkan kembali ke daftar aktif.';
            this.btnModalConfirmText.textContent = 'Eksekusi Pemulihan';
            this.btnModalConfirm.className = 'inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 disabled:opacity-50';
            if (this.previewDeletableLabel) this.previewDeletableLabel.textContent = 'Dapat Dipulihkan';
            if (this.modalIconContainer) {
                this.modalIconContainer.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600';
            }
        } else {
            this.impactTitle.textContent = 'Konfirmasi Hapus Massal';
            this.impactSubtitle.textContent = 'Sistem memverifikasi integritas data sebelum penghapusan.';
            this.btnModalConfirmText.textContent = 'Eksekusi Hapus';
            this.btnModalConfirm.className = 'inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-lg bg-rose-600 px-4 text-xs font-semibold text-white shadow-xs hover:bg-rose-700 disabled:opacity-50';
            if (this.previewDeletableLabel) this.previewDeletableLabel.textContent = 'Dapat Dihapus';
            if (this.modalIconContainer) {
                this.modalIconContainer.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600';
            }
        }

        let targetPreviewUrl = action === 'restore'
            ? (this.previewRestoreUrl || this.previewUrl)
            : this.previewUrl;

        if (targetPreviewUrl && !targetPreviewUrl.endsWith('/preview')) {
            targetPreviewUrl = targetPreviewUrl.replace(/\/(bulk-delete|bulk-restore)$/, '/$1/preview');
        }

        try {
            const payload = this.buildPayload(true);
            const res = await fetch(targetPreviewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || (action === 'restore' ? 'Gagal menganalisis dampak pemulihan.' : 'Gagal menganalisis dampak penghapusan.'));
            }

            this.renderImpactPreview(data);
        } catch (err) {
            this.impactLoading.classList.add('hidden');
            this.impactContent.classList.remove('hidden');
            this.modalErrorBox.textContent = err.message;
            this.modalErrorBox.classList.remove('hidden');
            this.btnModalConfirm.disabled = true;
        }
    }

    renderImpactPreview(data) {
        this.currentPreviewData = data;
        this.impactLoading.classList.add('hidden');
        this.impactContent.classList.remove('hidden');

        const deletable = data.deleted_count || 0;
        const skipped = data.skipped_count || 0;
        const total = deletable + skipped;

        this.previewDeletableCount.textContent = deletable.toLocaleString('id-ID');
        this.previewSkippedCount.textContent = skipped.toLocaleString('id-ID');

        if (skipped > 0 && Array.isArray(data.skipped)) {
            this.previewSkippedSection.classList.remove('hidden');
            this.previewSkippedTotalLabel.textContent = `(${skipped} item)`;
            this.previewSkippedList.innerHTML = data.skipped.map(item => `
                <li><strong>${this.escapeHtml(item.label || '#' + item.id)}</strong>: ${this.escapeHtml(item.reason)}</li>
            `).join('');
        } else {
            this.previewSkippedSection.classList.add('hidden');
        }

        // High-risk threshold confirmation
        if (total >= this.confirmThreshold && this.currentAction === 'delete') {
            const phrase = `HAPUS ${total}`;
            this.confirmPhraseSection.classList.remove('hidden');
            this.confirmPhraseExpected.textContent = phrase;
            this.confirmPhraseInput.value = '';
            this.btnModalConfirm.disabled = true;
        } else {
            this.confirmPhraseSection.classList.add('hidden');
            this.btnModalConfirm.disabled = deletable === 0;
        }
    }

    validateConfirmPhrase() {
        const total = (this.currentPreviewData?.deleted_count || 0) + (this.currentPreviewData?.skipped_count || 0);
        const expected = `HAPUS ${total}`;
        const actual = this.confirmPhraseInput.value.trim();

        if (actual === expected && (this.currentPreviewData?.deleted_count || 0) > 0) {
            this.btnModalConfirm.disabled = false;
        } else {
            this.btnModalConfirm.disabled = true;
        }
    }

    async executeBulkAction() {
        const targetUrl = this.currentAction === 'restore' ? this.restoreUrl : this.deleteUrl;
        const idempotencyKey = this.generateUuidV4();
        const payload = this.buildPayload(false);

        if (this.confirmPhraseInput && !this.confirmPhraseSection.classList.contains('hidden')) {
            payload.confirm_phrase = this.confirmPhraseInput.value.trim();
        }

        this.btnModalConfirm.disabled = true;
        this.btnModalConfirmText.textContent = 'Memproses...';
        this.modalErrorBox.classList.add('hidden');

        try {
            const res = await fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Idempotency-Key': idempotencyKey,
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Operasi massal gagal diproses.');
            }

            this.closeImpactModal();
            this.handleExecutionSuccess(data);
        } catch (err) {
            this.modalErrorBox.textContent = err.message;
            this.modalErrorBox.classList.remove('hidden');
            this.btnModalConfirm.disabled = false;
            this.btnModalConfirmText.textContent = this.currentAction === 'restore' ? 'Eksekusi Pemulihan' : 'Eksekusi Hapus';
        }
    }

    handleExecutionSuccess(result) {
        const skipped = result.skipped_count || (result.skipped ? result.skipped.length : 0);
        const deleted = result.deleted_count || (result.deleted ? result.deleted.length : 0);

        if (skipped > 0 && this.resultModal) {
            this.resultSuccessCount.textContent = deleted.toLocaleString('id-ID');
            this.resultSkippedCount.textContent = skipped.toLocaleString('id-ID');

            if (Array.isArray(result.skipped) && result.skipped.length > 0) {
                this.resultSkippedBox.classList.remove('hidden');
                this.resultSkippedList.innerHTML = result.skipped.map(item => `
                    <li><strong>${this.escapeHtml(item.label || '#' + item.id)}</strong>: ${this.escapeHtml(item.reason)}</li>
                `).join('');
                this.currentSkippedData = result.skipped;
            } else {
                this.resultSkippedBox.classList.add('hidden');
            }

            this.resultModal.classList.remove('hidden');
        } else {
            const msg = this.currentAction === 'restore'
                ? `${deleted} data berhasil dipulihkan.`
                : `${deleted} data berhasil dipindahkan ke tempat sampah.`;

            sessionStorage.setItem('sisurat_toast', JSON.stringify({ message: msg, type: 'success' }));
            window.location.reload();
        }
    }

    downloadSkippedCsv() {
        if (!this.currentSkippedData || this.currentSkippedData.length === 0) return;

        let csv = 'ID,Label,Alasan\n';
        this.currentSkippedData.forEach(item => {
            const id = `"${String(item.id).replace(/"/g, '""')}"`;
            const label = `"${String(item.label || '').replace(/"/g, '""')}"`;
            const reason = `"${String(item.reason || '').replace(/"/g, '""')}"`;
            csv += `${id},${label},${reason}\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `laporan_item_dilewati_${Date.now()}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    buildPayload(isPreview = false) {
        const payload = {};
        if (this.selectAllFiltered) {
            payload.filter = this.filterParams;
            payload.expected_count = this.totalFilteredCount;
        } else {
            payload.ids = Array.from(this.selectedIds);
        }

        payload.action = this.currentAction;
        if (this.currentAction === 'restore') {
            payload.is_restore = true;
        }

        if (isPreview) {
            payload.preview = true;
        }

        return payload;
    }

    closeImpactModal() {
        if (this.impactModal) this.impactModal.classList.add('hidden');
    }

    closeResultModal() {
        if (this.resultModal) this.resultModal.classList.add('hidden');
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    generateUuidV4() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

window.BulkSelectEngine = BulkSelectEngine;
