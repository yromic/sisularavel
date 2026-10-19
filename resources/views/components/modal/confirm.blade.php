<!-- Komponen Modal Dialog Konfirmasi Terpadu (UX2-008, WCAG 2.2 Level AA) -->
<div id="global-confirm-modal"
     class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs transition-opacity duration-200"
     role="dialog"
     aria-modal="true"
     aria-labelledby="global-confirm-modal-title"
     aria-describedby="global-confirm-modal-message">
    <div id="global-confirm-card" class="relative w-full max-w-md transform overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-2xl transition-all">
        <div class="flex items-start gap-4">
            <div id="global-confirm-icon-box" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <svg id="global-confirm-icon-danger" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <svg id="global-confirm-icon-info" class="hidden h-6 w-6 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg id="global-confirm-icon-amber" class="hidden h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 id="global-confirm-modal-title" class="text-base font-bold text-slate-900">Konfirmasi Tindakan</h3>
                <p id="global-confirm-modal-message" class="mt-1.5 text-xs text-slate-600 leading-relaxed">
                    Apakah Anda yakin ingin melanjutkan tindakan ini?
                </p>
                <div id="global-confirm-modal-extra" class="hidden mt-2 rounded-md border border-slate-200 bg-slate-50 p-2 text-xs text-slate-600 font-mono"></div>
            </div>
        </div>
        <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-3">
            <button type="button"
                    id="global-confirm-btn-cancel"
                    onclick="window.dismissGlobalConfirm(false)"
                    class="min-h-[38px] rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-100 focus:ring-2 focus:ring-slate-300 focus:outline-hidden">
                Batal
            </button>
            <button type="button"
                    id="global-confirm-btn-ok"
                    onclick="window.dismissGlobalConfirm(true)"
                    class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-md bg-rose-600 px-4 text-xs font-semibold text-white shadow-xs hover:bg-rose-700 focus:ring-2 focus:ring-rose-500 focus:outline-hidden">
                <span id="global-confirm-btn-ok-text">Ya, Lanjutkan</span>
            </button>
        </div>
    </div>
</div>

<script>
    (function() {
        let confirmResolver = null;
        let lastFocusedElement = null;

        window.showConfirmDialog = function(options = {}) {
            return new Promise((resolve) => {
                confirmResolver = resolve;
                lastFocusedElement = document.activeElement;

                const modal = document.getElementById('global-confirm-modal');
                const titleEl = document.getElementById('global-confirm-modal-title');
                const msgEl = document.getElementById('global-confirm-modal-message');
                const extraEl = document.getElementById('global-confirm-modal-extra');
                const iconBox = document.getElementById('global-confirm-icon-box');
                const iconDanger = document.getElementById('global-confirm-icon-danger');
                const iconInfo = document.getElementById('global-confirm-icon-info');
                const iconAmber = document.getElementById('global-confirm-icon-amber');
                const okBtn = document.getElementById('global-confirm-btn-ok');
                const okBtnText = document.getElementById('global-confirm-btn-ok-text');
                const cancelBtn = document.getElementById('global-confirm-btn-cancel');

                if (!modal) {
                    const fallback = window.confirm(options.message || 'Apakah Anda yakin?');
                    resolve(fallback);
                    return;
                }

                titleEl.textContent = options.title || 'Konfirmasi Tindakan';
                msgEl.textContent = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';

                if (options.extra) {
                    extraEl.textContent = options.extra;
                    extraEl.classList.remove('hidden');
                } else {
                    extraEl.classList.add('hidden');
                }

                cancelBtn.textContent = options.cancelText || 'Batal';
                okBtnText.textContent = options.confirmText || 'Ya, Lanjutkan';

                // Variant styling
                const variant = options.variant || 'danger';
                iconDanger.classList.add('hidden');
                iconInfo.classList.add('hidden');
                iconAmber.classList.add('hidden');

                okBtn.className = 'inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-md px-4 text-xs font-semibold text-white shadow-xs focus:ring-2 focus:outline-hidden';

                if (variant === 'info' || variant === 'emerald' || variant === 'teal' || variant === 'primary') {
                    iconBox.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700';
                    iconInfo.classList.remove('hidden');
                    okBtn.classList.add('bg-[#147D73]', 'hover:bg-[#0F635B]', 'focus:ring-teal-500');
                } else if (variant === 'warning' || variant === 'amber') {
                    iconBox.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700';
                    iconAmber.classList.remove('hidden');
                    okBtn.classList.add('bg-amber-600', 'hover:bg-amber-700', 'focus:ring-amber-500');
                } else {
                    // default danger
                    iconBox.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600';
                    iconDanger.classList.remove('hidden');
                    okBtn.classList.add('bg-rose-600', 'hover:bg-rose-700', 'focus:ring-rose-500');
                }

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');

                // Auto-focus OK button or cancel button
                if (variant === 'danger') {
                    cancelBtn.focus();
                } else {
                    okBtn.focus();
                }
            });
        };

        window.dismissGlobalConfirm = function(result) {
            const modal = document.getElementById('global-confirm-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
            if (confirmResolver) {
                confirmResolver(result);
                confirmResolver = null;
            }
        };

        window.confirmAction = function(event, message, title = 'Konfirmasi Tindakan', variant = 'danger', confirmText = 'Ya, Lanjutkan') {
            if (event) {
                event.preventDefault();
                const target = event.currentTarget || event.target;
                const form = target.tagName === 'FORM' ? target : target.closest('form');
                window.showConfirmDialog({ title, message, variant, confirmText }).then((confirmed) => {
                    if (confirmed && form) {
                        form.submit();
                    }
                });
            }
            return false;
        };

        // Trap focus & keyboard events
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('global-confirm-modal');
            if (!modal || modal.classList.contains('hidden')) return;

            if (e.key === 'Escape') {
                e.preventDefault();
                window.dismissGlobalConfirm(false);
                return;
            }

            if (e.key === 'Tab') {
                const cancelBtn = document.getElementById('global-confirm-btn-cancel');
                const okBtn = document.getElementById('global-confirm-btn-ok');
                const focusables = [cancelBtn, okBtn];
                const active = document.activeElement;

                if (e.shiftKey) {
                    if (active === cancelBtn) {
                        e.preventDefault();
                        okBtn.focus();
                    }
                } else {
                    if (active === okBtn) {
                        e.preventDefault();
                        cancelBtn.focus();
                    }
                }
            }
        });

        // Click outside card to dismiss
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('global-confirm-modal');
            if (modal && !modal.classList.contains('hidden')) {
                const card = document.getElementById('global-confirm-card');
                if (card && !card.contains(e.target) && modal.contains(e.target)) {
                    window.dismissGlobalConfirm(false);
                }
            }
        });
    })();
</script>
