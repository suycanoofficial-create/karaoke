<?php
/**
 * Admin - Playlist Management (Profile-based)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

// Get active rooms for recover modal
$stmt = db()->query("SELECT id, room_code FROM rooms WHERE status = 'active' ORDER BY room_code");
$active_rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playlist — Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= ASSETS_VERSION ?>">
    <style>
        .search-bar {
            display:flex; align-items:center; gap:8px;
            padding:6px 12px; border-radius:var(--radius-full);
            background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
            transition:all 0.2s; margin-bottom:var(--space-sm);
        }
        .search-bar:focus-within { border-color:rgba(212,175,55,0.3); background:rgba(255,255,255,0.06); }
        .search-bar svg { flex-shrink:0; color:rgba(255,255,255,0.3); }
        .search-bar:focus-within svg { color:var(--gold); }
        .search-bar input {
            flex:1; background:none; border:none; outline:none;
            color:rgba(255,255,255,0.85); font-size:0.8rem;
            font-family:var(--font-body); min-width:0;
        }
        .search-bar input::placeholder { color:rgba(255,255,255,0.25); }
        .search-clear {
            display:none; align-items:center; justify-content:center;
            width:18px; height:18px; border-radius:50%; cursor:pointer;
            background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.5);
            font-size:0.65rem; line-height:1; transition:all 0.15s;
        }
        .search-clear:hover { background:rgba(255,255,255,0.2); color:#fff; }
        .search-results-count {
            font-size:0.68rem; color:rgba(255,255,255,0.25); flex-shrink:0;
        }
        .btn-icon-only {
            display:inline-flex; align-items:center; justify-content:center;
            width:28px; height:28px; border-radius:var(--radius-sm);
            background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08);
            color:rgba(255,255,255,0.5); cursor:pointer; transition:all 0.15s;
        }
        .btn-icon-only:hover { background:rgba(255,255,255,0.1); color:#fff; }
        .btn-danger-icon { border-color:rgba(231,76,60,0.2); color:rgba(231,76,60,0.6); }
        .btn-danger-icon:hover { background:rgba(231,76,60,0.15); color:#E74C3C; border-color:rgba(231,76,60,0.3); }
        .profile-tabs {
            display:flex; gap:6px; flex-wrap:wrap; margin-bottom:var(--space-sm);
        }
        .profile-tab {
            padding:6px 14px; border-radius:var(--radius-full);
            background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
            color:rgba(255,255,255,0.5); font-size:0.75rem; cursor:pointer;
            transition:all 0.15s; font-family:var(--font-body); font-weight:500;
        }
        .profile-tab:hover { background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.8); }
        .profile-tab.active { background:var(--gold); color:#0B0C10; border-color:var(--gold); }
        .profile-tab .count { opacity:0.6; font-size:0.65rem; margin-left:4px; }
        .profile-tab .delete-profile {
            display:inline-flex; align-items:center; justify-content:center;
            width:14px; height:14px; border-radius:50%; margin-left:6px;
            background:rgba(231,76,60,0.15); color:#E74C3C; font-size:0.6rem;
            transition:all 0.15s;
        }
        .profile-tab .delete-profile:hover { background:rgba(231,76,60,0.3); }
        @media (max-width: 768px) {
            .hide-mobile { display: none !important; }
        }
        @media (min-width: 769px) {
            .hide-desktop { display: none !important; }
        }
    </style>
    <script src="../assets/js/app.js?v=<?= ASSETS_VERSION ?>" defer></script>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a href="<?= BASE_URL ?>" class="site-logo" style="display:block;margin-bottom:var(--space-xl)"><?= sanitize(SITE_NAME) ?></a>
            <div class="text-small text-muted text-upper" style="letter-spacing:0.15em;margin-bottom:var(--space-sm)">Administration</div>
            <nav class="admin-nav">
                <a href="../admin/" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="../admin/rooms.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Rooms
                </a>
                <a href="../admin/queue.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Queue
                </a>
                <a href="../admin/playlist.php" class="admin-nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    Playlist
                </a>
                <a href="../admin/settings.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </a>
                <a href="../admin/seo.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    SEO & Meta
                </a>
                <a href="../admin/password.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Security
                </a>
            </nav>
            <div style="margin-top:auto;padding-top:var(--space-xl);border-top:1px solid rgba(255,255,255,0.05)">
                <a href="../admin/logout.php" class="btn btn-ghost btn-sm" style="width:100%">Sign Out</a>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-page-header flex-between">
                <div>
                    <h1 class="heading-lg">Playlist</h1>
                </div>
                <div class="flex gap-sm" style="align-items:center">
                    <input type="text" id="newProfileInput" placeholder="New profile name..." style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.85);padding:6px 12px;border-radius:var(--radius-sm);font-size:0.78rem;outline:none;font-family:var(--font-body);width:160px">
                    <button class="btn btn-primary btn-sm" onclick="createProfile()" style="font-size:0.7rem;padding:4px 10px">+ Profile</button>
                </div>
            </div>

            <!-- Profile Tabs -->
            <div class="profile-tabs" id="profileTabs"></div>

            <!-- Search Bar -->
            <div class="search-bar" id="searchBar" style="display:none">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Search songs, added by..." oninput="filterSongs()">
                <span class="search-clear" id="searchClear" onclick="clearSearch()">✕</span>
                <span class="search-results-count" id="resultsCount"></span>
            </div>

            <!-- Bulk Actions Bar -->
            <div id="bulkActions" style="display:none;padding:8px 14px;margin-bottom:var(--space-sm);border-radius:var(--radius-md);background:rgba(212,175,55,0.08);border:1px solid rgba(212,175,55,0.15)">
                <div class="flex-between" style="align-items:center">
                    <span style="font-size:0.75rem;color:var(--gold)"><span id="selectedCount">0</span> selected</span>
                    <div class="flex gap-xs">
                        <button class="btn-icon-only" id="recoverSelectedBtn" onclick="openRecoverModal()" style="display:none" title="Send to room">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        </button>
                        <button class="btn-icon-only btn-danger-icon" onclick="confirmBulkDelete()" title="Delete selected">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Songs Table -->
            <div class="glass-card" style="padding:0;overflow:hidden">
                <table style="width:100%;border-collapse:collapse;font-size:0.8rem">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.06)">
                            <th style="width:32px;padding:8px 10px;text-align:left"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="accent-color:var(--gold)"></th>
                            <th style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Song</th>
                            <th class="hide-mobile" style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Added By</th>
                            <th class="hide-mobile" style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Date</th>
                            <th style="padding:8px 10px;width:60px"></th>
                        </tr>
                    </thead>
                    <tbody id="songsTableBody">
                        <tr>
                            <td colspan="5" style="padding:var(--space-2xl);text-align:center;color:rgba(255,255,255,0.3)">Select a profile above</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Recover to Room Modal -->
    <div class="modal-overlay" id="recoverModal">
        <div class="modal glass-card" style="padding:var(--space-xl);max-width:400px">
            <h3 class="heading-md" style="margin-bottom:var(--space-md)">Send to Room</h3>
            <p class="text-muted" style="margin-bottom:var(--space-lg)">Select an active room to send <span id="recoverSongCount">0</span> song(s) to. Songs stay in playlist.</p>
            <div id="recoverRoomList" style="margin-bottom:var(--space-xl);max-height:200px;overflow-y:auto">
                <?php if (empty($active_rooms)): ?>
                <p class="text-muted text-center">No active rooms available</p>
                <?php else: ?>
                <?php foreach ($active_rooms as $ar): ?>
                <label class="flex gap-sm" style="padding:var(--space-sm) var(--space-md);border-radius:var(--radius-md);cursor:pointer;transition:background 0.2s;border:1px solid rgba(255,255,255,0.05);margin-bottom:var(--space-xs)" onmouseover="this.style.background='rgba(212,175,55,0.05)'" onmouseout="this.style.background='transparent'">
                    <input type="radio" name="target_room" value="<?= $ar['id'] ?>" style="accent-color:var(--gold)">
                    <span style="font-weight:500;letter-spacing:0.05em;color:var(--gold)"><?= sanitize($ar['room_code']) ?></span>
                </label>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="flex gap-md" style="justify-content:flex-end">
                <button class="btn btn-ghost" onclick="closeRecoverModal()">Cancel</button>
                <button class="btn btn-primary" id="recoverConfirmBtn" onclick="executeRecover()" <?= empty($active_rooms) ? 'disabled' : '' ?>>Send</button>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal glass-card" style="padding:var(--space-xl);text-align:center">
            <div id="confirmModalIcon" style="font-size:2.5rem;margin-bottom:var(--space-md)"></div>
            <h3 id="confirmModalTitle" class="heading-md" style="margin-bottom:var(--space-sm)"></h3>
            <p id="confirmModalMessage" class="text-muted" style="margin-bottom:var(--space-xl)"></p>
            <div class="flex gap-md" style="justify-content:center">
                <button class="btn btn-ghost" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn btn-primary" id="confirmModalAction">Confirm</button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <script>
    if (typeof showToast !== 'function') {
        window.showToast = function(msg, type) {
            var c = document.getElementById('toastContainer');
            if (!c) return;
            var t = document.createElement('div');
            t.className = 'toast toast-' + (type || 'info');
            t.textContent = msg;
            c.appendChild(t);
            setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3000);
        };
    }

    var activeProfileId = null;

    // Load profiles as tabs
    async function loadProfiles() {
        try {
            var res = await fetch('../api/playlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_profiles' })
            });
            var data = await res.json();
            var container = document.getElementById('profileTabs');
            if (data.success && data.profiles.length > 0) {
                container.innerHTML = data.profiles.map(function(p) {
                    var isActive = p.id == activeProfileId;
                    return '<div class="profile-tab' + (isActive ? ' active' : '') + '" onclick="selectProfile(' + p.id + ', \'' + p.name.replace(/'/g, "\\'") + '\')">' +
                        p.name + '<span class="count">' + p.song_count + '</span>' +
                        '<span class="delete-profile" onclick="event.stopPropagation();deleteProfile(' + p.id + ', \'' + p.name.replace(/'/g, "\\'") + '\')" title="Delete profile">✕</span>' +
                        '</div>';
                }).join('');

                if (!activeProfileId && data.profiles.length > 0) {
                    selectProfile(data.profiles[0].id, data.profiles[0].name);
                }
            } else {
                container.innerHTML = '<p style="font-size:0.78rem;color:rgba(255,255,255,0.3);padding:8px 0">No playlists yet. Create a profile to start adding songs.</p>';
                document.getElementById('searchBar').style.display = 'none';
                document.getElementById('songsTableBody').innerHTML = '<tr><td colspan="5" style="padding:var(--space-2xl);text-align:center;color:rgba(255,255,255,0.3)">No profiles created yet</td></tr>';
            }
        } catch(e) { showToast('Failed to load profiles', 'error'); }
    }

    async function createProfile() {
        var input = document.getElementById('newProfileInput');
        var name = input.value.trim();
        if (!name) { showToast('Enter a profile name', 'error'); return; }
        try {
            var res = await fetch('../api/playlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create_profile', name: name })
            });
            var data = await res.json();
            if (data.success) {
                showToast('Profile "' + name + '" created', 'success');
                input.value = '';
                activeProfileId = data.id;
                await loadProfiles();
                await loadSongs(data.id);
            } else {
                showToast(data.error || 'Failed to create profile', 'error');
            }
        } catch(e) { showToast('Connection error', 'error'); }
    }

    function selectProfile(id, name) {
        activeProfileId = id;
        document.querySelectorAll('.profile-tab').forEach(function(t) { t.classList.remove('active'); });
        loadSongs(id);
        document.getElementById('searchBar').style.display = 'flex';
    }

    async function loadSongs(profileId) {
        document.getElementById('searchInput').value = '';
        document.getElementById('searchClear').style.display = 'none';
        try {
            var res = await fetch('../api/playlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_songs', profile_id: profileId })
            });
            var data = await res.json();
            var tbody = document.getElementById('songsTableBody');
            if (data.success && data.songs.length > 0) {
                tbody.innerHTML = data.songs.map(function(s) {
                    return '<tr data-id="' + s.id + '" style="border-bottom:1px solid rgba(255,255,255,0.04);transition:background 0.15s" onmouseover="this.style.background=\'rgba(255,255,255,0.02)\'" onmouseout="this.style.background=\'transparent\'">' +
                        '<td style="padding:6px 10px"><input type="checkbox" class="pl-checkbox" value="' + s.id + '" onchange="updateBulkActions()" style="accent-color:var(--gold)"></td>' +
                        '<td style="padding:6px 10px"><div style="font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px;font-size:0.82rem" title="' + escHtml(s.video_title) + '">' + escHtml(s.video_title.substring(0, 50)) + '</div>' +
                        '<div class="hide-desktop" style="font-size:0.7rem;color:rgba(255,255,255,0.3)">' + escHtml(s.added_by) + ' &middot; ' + fmtDate(s.created_at) + '</div></td>' +
                        '<td class="hide-mobile" style="padding:6px 10px;font-size:0.75rem;color:rgba(255,255,255,0.5)">' + escHtml(s.added_by) + '</td>' +
                        '<td class="hide-mobile" style="padding:6px 10px;font-size:0.75rem;color:rgba(255,255,255,0.3)">' + fmtDate(s.created_at) + '</td>' +
                        '<td style="padding:6px 10px"><div class="flex gap-xs" style="justify-content:flex-end">' +
                        '<button class="btn-icon-only" onclick="openRecoverSingle(' + s.id + ')" title="Send to room"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg></button>' +
                        '<button class="btn-icon-only btn-danger-icon" onclick="confirmDeleteSingle(' + s.id + ', \'' + escHtml(s.video_title.substring(0, 30).replace(/'/g, "\\'")) + '\')" title="Delete"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>' +
                        '</div></td></tr>';
                }).join('');
                document.getElementById('resultsCount').textContent = data.songs.length + ' song' + (data.songs.length !== 1 ? 's' : '');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="padding:var(--space-2xl);text-align:center;color:rgba(255,255,255,0.3)">No songs in this playlist</td></tr>';
                document.getElementById('resultsCount').textContent = '0 songs';
            }
            document.getElementById('selectAll').checked = false;
            document.getElementById('bulkActions').style.display = 'none';
        } catch(e) { showToast('Failed to load songs', 'error'); }
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function fmtDate(dateStr) {
        var d = new Date(dateStr);
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var h = d.getHours();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + h + ':' + String(d.getMinutes()).padStart(2, '0') + ' ' + ampm;
    }

    // Search & Filter
    function filterSongs() {
        var query = document.getElementById('searchInput').value.toLowerCase().trim();
        var rows = document.querySelectorAll('tbody tr[data-id]');
        var visible = 0;
        rows.forEach(function(row) {
            var title = (row.querySelector('td:nth-child(2) div:first-child')?.textContent || '').toLowerCase();
            var addedBy = (row.querySelector('td:nth-child(3)')?.textContent || '').toLowerCase();
            var match = !query || title.includes(query) || addedBy.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        document.getElementById('resultsCount').textContent = visible + ' song' + (visible !== 1 ? 's' : '');
        document.getElementById('searchClear').style.display = query ? 'flex' : 'none';
    }

    function clearSearch() {
        document.getElementById('searchInput').value = '';
        filterSongs();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT') {
            e.preventDefault();
            document.getElementById('searchInput').focus();
        }
    });

    // Confirm Modal
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
    document.getElementById('confirmModal').addEventListener('click', function(e) { if (e.target === this) closeConfirmModal(); });

    // Select All
    function toggleSelectAll(el) {
        document.querySelectorAll('.pl-checkbox').forEach(function(cb) { cb.checked = el.checked; });
        updateBulkActions();
    }
    function updateBulkActions() {
        var checked = document.querySelectorAll('.pl-checkbox:checked');
        var count = checked.length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('bulkActions').style.display = count > 0 ? 'block' : 'none';
        document.getElementById('recoverSelectedBtn').style.display = count > 0 ? 'inline-flex' : 'none';
    }
    function getSelectedIds() {
        var ids = [];
        document.querySelectorAll('.pl-checkbox:checked').forEach(function(cb) { ids.push(parseInt(cb.value)); });
        return ids;
    }

    // Recover Modal
    var recoverIds = [];
    function openRecoverSingle(id) { recoverIds = [id]; document.getElementById('recoverSongCount').textContent = '1'; document.getElementById('recoverModal').classList.add('active'); }
    function openRecoverModal() {
        recoverIds = getSelectedIds();
        if (recoverIds.length === 0) return;
        document.getElementById('recoverSongCount').textContent = recoverIds.length;
        document.getElementById('recoverModal').classList.add('active');
    }
    function closeRecoverModal() { document.getElementById('recoverModal').classList.remove('active'); }
    document.getElementById('recoverModal').addEventListener('click', function(e) { if (e.target === this) closeRecoverModal(); });

    async function executeRecover() {
        var roomRadio = document.querySelector('input[name="target_room"]:checked');
        if (!roomRadio) { showToast('Select a room', 'error'); return; }
        var targetRoomId = parseInt(roomRadio.value);
        closeRecoverModal();
        var action = recoverIds.length === 1 ? 'recover' : 'bulk_recover';
        var body = { action: action, target_room_id: targetRoomId };
        if (recoverIds.length === 1) { body.id = recoverIds[0]; } else { body.ids = recoverIds; }
        try {
            var res = await fetch('../api/playlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            var data = await res.json();
            if (data.success) { showToast(data.message, 'success'); }
            else { showToast(data.error || 'Failed to send', 'error'); }
        } catch(e) { showToast('Connection error', 'error'); }
    }

    // Delete Single
    function confirmDeleteSingle(id, title) {
        showConfirmModal('🗑️', 'Remove from playlist?', '"' + title + '" will be removed.', async function() {
            try {
                var res = await fetch('../api/playlist.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                });
                var data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    if (activeProfileId) loadSongs(activeProfileId);
                } else { showToast(data.error || 'Failed', 'error'); }
            } catch(e) { showToast('Connection error', 'error'); }
        });
    }

    // Bulk Delete
    function confirmBulkDelete() {
        var ids = getSelectedIds();
        if (ids.length === 0) return;
        showConfirmModal('🗑️', 'Remove ' + ids.length + ' song(s)?', 'These songs will be removed from this playlist.', async function() {
            try {
                var res = await fetch('../api/playlist.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'bulk_delete', ids: ids })
                });
                var data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    if (activeProfileId) loadSongs(activeProfileId);
                } else { showToast(data.error || 'Failed', 'error'); }
            } catch(e) { showToast('Connection error', 'error'); }
        });
    }

    // Delete Profile
    function deleteProfile(id, name) {
        showConfirmModal('🗑️', 'Delete "' + name + '"?', 'This profile and all its songs will be permanently deleted.', async function() {
            try {
                var res = await fetch('../api/playlist.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_profile', id: id })
                });
                var data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    if (activeProfileId == id) activeProfileId = null;
                    await loadProfiles();
                } else { showToast(data.error || 'Failed', 'error'); }
            } catch(e) { showToast('Connection error', 'error'); }
        });
    }

    // Init
    loadProfiles();
    </script>
</body>
</html>
