import './bootstrap';
import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.bootstrap = bootstrap;
window.Alpine = Alpine;
window.Swal = Swal;

Alpine.start();

/**
 * Global Auto CNIC Formatter (XXXXX-XXXXXXX-X, max 13 digits / 15 chars)
 */
export function formatCNIC(val) {
    let digits = (val || '').replace(/\D/g, '');
    if (digits.length > 13) {
        digits = digits.substring(0, 13);
    }
    let formatted = '';
    if (digits.length > 0) {
        formatted += digits.substring(0, Math.min(5, digits.length));
    }
    if (digits.length > 5) {
        formatted += '-' + digits.substring(5, Math.min(12, digits.length));
    }
    if (digits.length > 12) {
        formatted += '-' + digits.substring(12, 13);
    }
    return formatted;
}

export function attachCnicFormatter(inputEl) {
    if (!inputEl || inputEl.dataset.cnicFormatted) return;
    inputEl.dataset.cnicFormatted = 'true';

    inputEl.setAttribute('maxlength', '15');
    inputEl.setAttribute('autocomplete', 'off');
    if (!inputEl.getAttribute('placeholder')) {
        inputEl.setAttribute('placeholder', '12345-1234567-1');
    }

    inputEl.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace') {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            if (start === end && (start === 6 || start === 14)) {
                e.preventDefault();
                const val = this.value;
                const newVal = val.substring(0, start - 2) + val.substring(start);
                this.value = formatCNIC(newVal);
                const newPos = Math.max(0, start - 2);
                this.setSelectionRange(newPos, newPos);
            }
        }
    });

    inputEl.addEventListener('input', function() {
        const start = this.selectionStart;
        const oldVal = this.value;
        const formatted = formatCNIC(oldVal);

        if (oldVal !== formatted) {
            let digitsBefore = oldVal.substring(0, start).replace(/\D/g, '').length;
            if (digitsBefore > 13) digitsBefore = 13;

            this.value = formatted;

            let newPos = 0;
            let count = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    count++;
                }
                if (count >= digitsBefore) {
                    newPos = i + 1;
                    break;
                }
            }
            if (digitsBefore === 0) newPos = 0;
            this.setSelectionRange(newPos, newPos);
        }
    });

    inputEl.addEventListener('paste', function(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        this.value = formatCNIC(text);
    });

    if (inputEl.value) {
        inputEl.value = formatCNIC(inputEl.value);
    }
}

function initCnicFormatting() {
    const selector = 'input#cnic, input[name="cnic"], input[data-cnic-format]';
    document.querySelectorAll(selector).forEach(attachCnicFormatter);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCnicFormatting);
} else {
    initCnicFormatting();
}

