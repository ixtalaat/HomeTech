import './bootstrap';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Toast a message (success teal, error rose, warning amber, info slate —
 * mirroring the badge gradients).
 *
 * Bottom corner, mirroring the reading direction: bottom-right in
 * English, bottom-left in Arabic (the <html> dir is set server-side).
 */
function toast(message, type = 'info') {
    const styles = {
        success: 'linear-gradient(to right, #0d9488, #0f766e)',
        error: 'linear-gradient(to right, #e11d48, #be123c)',
        warning: 'linear-gradient(to right, #f59e0b, #b45309)',
        info: 'linear-gradient(to right, #334155, #0f172a)',
    };
    const isRtl = document.documentElement.dir === 'rtl';

    Toastify({
        text: message,
        duration: 4500,
        close: true,
        gravity: 'bottom',
        position: isRtl ? 'left' : 'right',
        stopOnFocus: true,
        style: { background: styles[type] ?? styles.info, borderRadius: '0.875rem', boxShadow: '0 12px 28px rgb(2 6 23 / 0.28)' },
    }).showToast();
}

window.toast = toast;

/**
 * Render Laravel session flashes as toasts.
 */
document.addEventListener('DOMContentLoaded', () => {
    const payload = document.getElementById('flash-data');

    if (payload === null) {
        return;
    }

    let flashes = {};

    try {
        flashes = JSON.parse(payload.dataset.flash || '{}');
    } catch {
        flashes = {};
    }

    if (flashes.success) {
        toast(flashes.success, 'success');
    }

    if (flashes.error) {
        toast(flashes.error, 'error');
    }

    if (flashes.status && typeof flashes.status === 'string') {
        toast(flashes.status, 'info');
    }
});

/**
 * SweetAlert confirmations for destructive forms.
 *
 * Usage: <form method="POST" ... data-confirm="Delete this address?">
 */
document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');

    if (!form || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    Swal.fire({
        title: 'Are you sure?',
        text: form.dataset.confirm,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, continue',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        focusCancel: true,
    }).then((result) => {
        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.submit();
        }
    });
});
