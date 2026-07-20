const TOKEN = localStorage.getItem('admin_token');
if (!TOKEN) { window.location.href = '/admin/login.html'; }

async function api(url, opts = {}) {
    opts.headers = { ...opts.headers, 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json' };
    if (opts.body && typeof opts.body === 'object') opts.body = JSON.stringify(opts.body);
    const res = await fetch(url, opts);
    const data = await res.json();
    if (!data.success && data.error === 'Unauthorized') {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        window.location.href = '/admin/login.html';
    }
    return data;
}

const ADMIN_NAV = [
    { href: '/admin/', label: 'Dashboard', icon: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>' },
    { href: '/admin/rooms.html', label: 'Rooms', icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>' },
    { href: '/admin/queue.html', label: 'Queue', icon: '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>' },
    { href: '/admin/playlist.html', label: 'Playlist', icon: '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>' },
    { href: '/admin/settings.html', label: 'Settings', icon: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>' },
];

function renderSidebar() {
    const user = JSON.parse(localStorage.getItem('admin_user') || '{}');
    const currentPage = window.location.pathname;
    let html = '<a href="/" class="site-logo" style="display:block;margin-bottom:var(--space-xl)">KTV LOUNGE</a>';
    html += '<div class="text-small text-muted text-upper" style="letter-spacing:0.15em;margin-bottom:var(--space-sm)">Administration</div>';
    html += '<nav class="admin-nav">';
    ADMIN_NAV.forEach(item => {
        const active = currentPage === item.href || (item.href !== '/admin/' && item.href.replace('/admin/', '') !== '' && currentPage.startsWith(item.href)) ? ' active' : '';
        if (item.href === '/admin/' && currentPage === '/admin/') {
            html += '<a href="' + item.href + '" class="admin-nav-item active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + item.icon + '</svg>' + item.label + '</a>';
        } else {
            html += '<a href="' + item.href + '" class="admin-nav-item' + active + '"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + item.icon + '</svg>' + item.label + '</a>';
        }
    });
    html += '</nav>';
    html += '<div style="margin-top:auto;padding-top:var(--space-2xl);border-top:1px solid rgba(255,255,255,0.05);margin-top:var(--space-3xl)">';
    html += '<div style="display:flex;align-items:center;gap:var(--space-sm);color:var(--muted);font-size:0.85rem;margin-bottom:var(--space-md)">';
    html += '<div style="width:32px;height:32px;border-radius:50%;background:rgba(212,175,55,0.15);display:flex;align-items:center;justify-content:center;color:var(--gold);font-weight:600;font-size:0.8rem">' + (user.username ? user.username[0].toUpperCase() : 'A') + '</div>';
    html += '<div><div style="font-weight:500;color:var(--platinum)">' + (user.username || 'Admin') + '</div><div style="font-size:0.75rem">' + (user.role || 'admin') + '</div></div></div>';
    html += '<button class="btn btn-ghost btn-sm" style="width:100%" onclick="logout()">Sign Out</button></div>';
    document.getElementById('adminSidebar').innerHTML = html;
}

function logout() {
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    window.location.href = '/admin/login.html';
}

function showToast(msg, type) {
    var c = document.getElementById('toastContainer');
    if (!c) return;
    var t = document.createElement('div');
    t.className = 'toast toast-' + (type || 'info');
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3000);
}

function showConfirmModal(icon, title, message, onConfirm) {
    document.getElementById('confirmModalIcon').textContent = icon;
    document.getElementById('confirmModalTitle').textContent = title;
    document.getElementById('confirmModalMessage').textContent = message;
    document.getElementById('confirmModal').classList.add('active');
    var btn = document.getElementById('confirmModalAction');
    var newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
    newBtn.addEventListener('click', function() { closeConfirmModal(); onConfirm(); });
}

function closeConfirmModal() { document.getElementById('confirmModal').classList.remove('active'); }
