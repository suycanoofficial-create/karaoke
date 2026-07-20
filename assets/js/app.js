/**
 * KTV LOUNGE - Core Application JavaScript
 * Toast notifications, utilities, and shared functions
 */

// ── Toast Notifications ──────────────────────────────────────

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(30px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── Utility Functions ────────────────────────────────────────

function debounce(fn, delay) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeAttr(text) {
    if (!text) return '';
    return text.replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ── Room Code Formatting ─────────────────────────────────────

document.querySelectorAll('input[data-room-code]').forEach(input => {
    input.addEventListener('input', function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });
});

// ── Keyboard Shortcuts ───────────────────────────────────────

document.addEventListener('keydown', function (e) {
    // Space bar for play/pause (host view)
    if (e.code === 'Space' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        if (typeof togglePlayPause === 'function') {
            togglePlayPause();
        }
    }
    // Right arrow for skip
    if (e.code === 'ArrowRight' && e.target.tagName !== 'INPUT') {
        if (typeof skipTrack === 'function') {
            skipTrack();
        }
    }
});

// ── Fetch Wrapper ────────────────────────────────────────────

async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        const data = await response.json();
        return data;
    } catch (err) {
        console.error('API Request failed:', err);
        return { success: false, error: 'Network error' };
    }
}

// ── Clipboard Copy ───────────────────────────────────────────

async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copied to clipboard', 'success');
    } catch (err) {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('Copied to clipboard', 'success');
    }
}

// ── Mobile Menu Toggle ───────────────────────────────────────

function toggleAdminSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Close sidebar on overlay click (mobile)
document.addEventListener('click', function (e) {
    const sidebar = document.querySelector('.admin-sidebar.open');
    if (sidebar && !sidebar.contains(e.target) && !e.target.closest('[onclick*="toggleAdminSidebar"]')) {
        sidebar.classList.remove('open');
    }
});

// ── Date Formatting ──────────────────────────────────────────

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// ── Initialize ───────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss flash messages
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.3s ease';
            setTimeout(() => el.remove(), 300);
        }, parseInt(el.dataset.autoDismiss) || 5000);
    });
});
