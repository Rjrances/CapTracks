<div class="modal fade" id="globalDeleteConfirmModal" tabindex="-1" aria-labelledby="globalDeleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="globalDeleteConfirmModalLabel">
                    <i class="fas fa-triangle-exclamation text-danger me-2"></i>
                    Confirm deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="globalDeleteConfirmModalMessage">
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="globalDeleteConfirmModalProceed">
                    <i class="fas fa-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    function extractConfirmMessage(onsubmitValue) {
        if (!onsubmitValue) return null;
        const match = onsubmitValue.match(/confirm\((['"`])([\s\S]*?)\1\)/i);
        return match ? match[2] : null;
    }

    function setupDeleteModal() {
        const modalEl = document.getElementById('globalDeleteConfirmModal');
        const proceedBtn = document.getElementById('globalDeleteConfirmModalProceed');
        const messageEl = document.getElementById('globalDeleteConfirmModalMessage');
        if (!modalEl || !proceedBtn || !messageEl || typeof bootstrap === 'undefined') return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        let pendingForm = null;

        function showModalForForm(form, message) {
            pendingForm = form;
            messageEl.textContent = message || 'Are you sure you want to delete this item? This action cannot be undone.';
            modal.show();
        }

        function prepareForm(form) {
            if (!form || form.dataset.deleteConfirmBound === '1') return;
            form.dataset.deleteConfirmBound = '1';

            const inlineMsg = extractConfirmMessage(form.getAttribute('onsubmit') || '');
            if (inlineMsg !== null) {
                form.dataset.confirmMessage = inlineMsg;
                form.removeAttribute('onsubmit');
            }

            form.addEventListener('submit', (event) => {
                if (form.dataset.deleteConfirmed === '1') {
                    form.dataset.deleteConfirmed = '0';
                    return;
                }
                event.preventDefault();
                const message = form.dataset.confirmMessage || form.getAttribute('data-confirm-message');
                showModalForForm(form, message);
            });
        }

        function bindDeleteForms() {
            const forms = Array.from(document.querySelectorAll('form')).filter((form) => {
                const deleteMethodInput = form.querySelector('input[name="_method"][value="DELETE"]');
                const hasDeleteMethod = deleteMethodInput !== null;
                const hasInlineConfirm = /confirm\(/i.test(form.getAttribute('onsubmit') || '');
                const explicitDelete = form.dataset.confirmType === 'delete';
                return hasDeleteMethod || hasInlineConfirm || explicitDelete;
            });
            forms.forEach(prepareForm);
        }

        proceedBtn.addEventListener('click', () => {
            if (!pendingForm) return;
            pendingForm.dataset.deleteConfirmed = '1';
            modal.hide();
            pendingForm.requestSubmit();
            pendingForm = null;
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            pendingForm = null;
        });

        bindDeleteForms();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupDeleteModal);
    } else {
        setupDeleteModal();
    }
})();
</script>
