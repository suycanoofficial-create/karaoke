<?php
/**
 * Admin - Dashboard
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

// Get stats
$stats = [];

$stmt = db()->query("SELECT COUNT(*) as cnt FROM rooms WHERE status = 'active'");
$stats['active_rooms'] = $stmt->fetch()['cnt'];

$stmt = db()->query("SELECT COUNT(*) as cnt FROM rooms");
$total_rooms = $stmt->fetch()['cnt'];
$stats['total_rooms'] = $total_rooms;

$stmt = db()->query("SELECT COUNT(*) as cnt FROM songs_queue WHERE status = 'pending'");
$stats['pending_songs'] = $stmt->fetch()['cnt'];

$stmt = db()->query("SELECT COUNT(*) as cnt FROM songs_queue");
$stats['total_songs'] = $stmt->fetch()['cnt'];

$stmt = db()->query("SELECT COUNT(*) as cnt FROM rooms WHERE DATE(created_at) = CURDATE()");
$stats['today_rooms'] = $stmt->fetch()['cnt'];

// Recent rooms
$stmt = db()->query("SELECT r.*, (SELECT COUNT(*) FROM songs_queue sq WHERE sq.room_id = r.id) as song_count FROM rooms r ORDER BY r.created_at DESC LIMIT 20");
$recent_rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?= sanitize(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= ASSETS_VERSION ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎤</text></svg>">
    <script src="../assets/js/app.js?v=<?= ASSETS_VERSION ?>" defer></script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <a href="<?= BASE_URL ?>" class="site-logo" style="display:block;margin-bottom:var(--space-xl)">
                <?= sanitize(SITE_NAME) ?>
            </a>
            <div class="text-small text-muted text-upper" style="letter-spacing:0.15em;margin-bottom:var(--space-sm)">Administration</div>
            
            <nav class="admin-nav">
                <a href="../admin/" class="admin-nav-item active">
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
                <a href="../admin/playlist.php" class="admin-nav-item">
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

            <div style="margin-top:auto;padding-top:var(--space-2xl);border-top:1px solid rgba(255,255,255,0.05);margin-top:var(--space-3xl)">
                <div style="display:flex;align-items:center;gap:var(--space-sm);color:var(--muted);font-size:0.85rem;margin-bottom:var(--space-md)">
                    <div style="width:32px;height:32px;border-radius:50%;background:rgba(212,175,55,0.15);display:flex;align-items:center;justify-content:center;color:var(--gold);font-weight:600;font-size:0.8rem">
                        <?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:500;color:var(--platinum)"><?= sanitize($_SESSION['admin_username'] ?? 'Admin') ?></div>
                        <div style="font-size:0.75rem"><?= sanitize($_SESSION['admin_role'] ?? 'admin') ?></div>
                    </div>
                </div>
                <a href="../admin/logout.php" class="btn btn-ghost btn-sm" style="width:100%">Sign Out</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-page-header" style="padding-bottom:var(--space-md)">
                <h1 class="heading-lg" style="margin:0">Dashboard</h1>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="glass-card stat-card stat-card-active">
                    <div class="stat-icon stat-icon-active">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-value"><?= $stats['active_rooms'] ?></div>
                        <div class="stat-label">Active Rooms</div>
                    </div>
                </div>
                <div class="glass-card stat-card stat-card-today">
                    <div class="stat-icon stat-icon-today">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-value"><?= $stats['today_rooms'] ?></div>
                        <div class="stat-label">Created Today</div>
                    </div>
                </div>
                <div class="glass-card stat-card stat-card-pending">
                    <div class="stat-icon stat-icon-pending">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-value"><?= $stats['pending_songs'] ?></div>
                        <div class="stat-label">Pending Songs</div>
                    </div>
                </div>
                <div class="glass-card stat-card stat-card-total">
                    <div class="stat-icon stat-icon-total">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-value"><?= $stats['total_rooms'] ?></div>
                        <div class="stat-label">Total Rooms</div>
                    </div>
                </div>
            </div>

            <!-- Recent Rooms -->
            <div class="glass-card" style="padding:var(--space-md)">
                <h3 class="heading-sm" style="font-size:0.9rem;margin-bottom:var(--space-sm)">Recent Rooms</h3>
                <div style="overflow-x:auto">
                    <table class="data-table" style="font-size:0.8rem">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Songs</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_rooms)): ?>
                            <tr>
                                <td colspan="5" class="text-muted text-center" style="padding:var(--space-xl)">No rooms created yet</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_rooms as $r): ?>
                            <tr data-room-id="<?= $r['id'] ?>">
                                <td>
                                    <span style="font-weight:600;letter-spacing:0.1em;color:var(--gold);font-size:0.85rem"><?= sanitize($r['room_code']) ?></span>
                                </td>
                                <td><span class="badge badge-<?= $r['status'] ?>" style="font-size:0.7rem"><?= $r['status'] ?></span></td>
                                <td class="text-muted"><?= $r['song_count'] ?></td>
                                <td class="text-muted text-small"><?= date('M j, g:i A', strtotime($r['created_at'])) ?></td>
                                <td>
                                    <?php if ($r['status'] === 'active'): ?>
                                    <button class="btn btn-danger btn-sm" style="font-size:0.7rem;padding:2px 10px" onclick="closeRoomAdmin(<?= $r['id'] ?>, '<?= sanitize($r['room_code']) ?>')">Close</button>
                                    <?php else: ?>
                                    <span class="text-muted text-small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <!-- Confirm Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal glass-card" style="padding:var(--space-xl);text-align:center">
            <div id="confirmModalIcon" style="font-size:2.5rem;margin-bottom:var(--space-md)"></div>
            <h3 id="confirmModalTitle" class="heading-md" style="margin-bottom:var(--space-sm)"></h3>
            <p id="confirmModalMessage" class="text-muted" style="margin-bottom:var(--space-xl)"></p>
            <div class="flex gap-md" style="justify-content:center">
                <button class="btn btn-ghost" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn btn-danger" id="confirmModalAction">Confirm</button>
            </div>
        </div>
    </div>

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

    function showConfirmModal(icon, title, message, onConfirm) {
        document.getElementById('confirmModalIcon').textContent = icon;
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMessage').textContent = message;
        document.getElementById('confirmModal').classList.add('active');
        var btn = document.getElementById('confirmModalAction');
        var newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function() {
            closeConfirmModal();
            onConfirm();
        });
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.remove('active');
    }

    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeConfirmModal();
    });
    </script>
    <script>
        async function closeRoomAdmin(roomId, roomCode) {
            showConfirmModal('🔒', 'Close Room "' + roomCode + '"?', 'The room will be closed and active songs will be stopped.', async function() {
                try {
                    const res = await fetch('../api/admin_close_room.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ room_id: roomId })
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast('Room ' + roomCode + ' closed', 'success');
                        var row = document.querySelector('tr[data-room-id="' + roomId + '"]');
                        if (row) {
                            var badge = row.querySelector('.badge');
                            if (badge) { badge.className = 'badge badge-closed'; badge.textContent = 'closed'; }
                            var btn = row.querySelector('.btn-danger');
                            if (btn) btn.remove();
                        }
                    } else {
                        showToast(data.error || 'Failed to close room', 'error');
                    }
                } catch (err) {
                    showToast('Connection error', 'error');
                }
            });
        }

        // Auto-refresh every 10 seconds
        setInterval(() => location.reload(), 10000);

        // Trigger cleanup once on page load
        fetch('../api/cleanup.php').catch(() => {});
    </script>
</body>
</html>
