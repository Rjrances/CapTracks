@php
    $flashMessages = [];
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if (session()->has($type)) {
            $flashMessages[] = [
                'type' => $type,
                'message' => (string) session($type),
            ];
        }
    }
@endphp

<div class="modal fade" id="globalAlertModal" tabindex="-1" aria-labelledby="globalAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center" id="globalAlertModalLabel">
                    <i id="globalAlertModalIcon" class="fas fa-circle-info me-2 text-info"></i>
                    <span id="globalAlertModalTitle">System Message</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p id="globalAlertModalBody" class="mb-0"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const queuedMessages = @json($flashMessages);

    const typeMap = {
        success: { title: 'Success', icon: 'fa-circle-check', iconClass: 'text-success' },
        error: { title: 'Error', icon: 'fa-circle-xmark', iconClass: 'text-danger' },
        danger: { title: 'Error', icon: 'fa-circle-xmark', iconClass: 'text-danger' },
        warning: { title: 'Warning', icon: 'fa-triangle-exclamation', iconClass: 'text-warning' },
        info: { title: 'Information', icon: 'fa-circle-info', iconClass: 'text-info' },
    };

    function normalizeType(rawType) {
        const lower = (rawType || '').toLowerCase();
        if (lower === 'danger') return 'error';
        return typeMap[lower] ? lower : 'info';
    }

    function addMessage(type, message) {
        if (!message) return;
        queuedMessages.push({ type: normalizeType(type), message: String(message) });
    }

    function collectInlineAlerts() {
        const inlineAlerts = Array.from(document.querySelectorAll('.alert'));
        inlineAlerts.forEach((alertEl) => {
            const text = (alertEl.textContent || '').replace(/\s+/g, ' ').trim();
            if (!text) return;

            let type = 'info';
            if (alertEl.classList.contains('alert-success')) type = 'success';
            else if (alertEl.classList.contains('alert-danger')) type = 'error';
            else if (alertEl.classList.contains('alert-warning')) type = 'warning';
            else if (alertEl.classList.contains('alert-info')) type = 'info';

            addMessage(type, text);
            alertEl.remove();
        });
    }

    function showNext() {
        if (!queuedMessages.length) return;

        const modalEl = document.getElementById('globalAlertModal');
        if (!modalEl) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const payload = queuedMessages.shift();
        const config = typeMap[normalizeType(payload.type)];

        const iconEl = document.getElementById('globalAlertModalIcon');
        const titleEl = document.getElementById('globalAlertModalTitle');
        const bodyEl = document.getElementById('globalAlertModalBody');

        if (!iconEl || !titleEl || !bodyEl) return;

        iconEl.className = `fas ${config.icon} me-2 ${config.iconClass}`;
        titleEl.textContent = config.title;
        bodyEl.textContent = payload.message;

        modal.show();
    }

    document.addEventListener('DOMContentLoaded', () => {
        collectInlineAlerts();

        const modalEl = document.getElementById('globalAlertModal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', showNext);
        }

        if (queuedMessages.length) {
            showNext();
        }
    });

    window.alert = function (message) {
        addMessage('info', message);
        const modalEl = document.getElementById('globalAlertModal');
        const isOpen = modalEl && modalEl.classList.contains('show');
        if (!isOpen) showNext();
    };
})();
</script>
