<?php
/**
 * Admin - Rooms Management
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = max(5, min(50, intval($_GET['per_page'] ?? 10)));
$offset = ($page - 1) * $perPage;

$where = '';
if ($filter === 'active') $where = "WHERE r.status = 'active'";
elseif ($filter === 'closed') $where = "WHERE r.status = 'closed'";

$countStmt = db()->query("
    SELECT COUNT(*) FROM rooms r $where
");
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->query("
    SELECT r.*, 
        (SELECT COUNT(*) FROM songs_queue sq WHERE sq.room_id = r.id) as song_count,
        (SELECT COUNT(*) FROM songs_queue sq WHERE sq.room_id = r.id AND sq.status = 'pending') as pending_count
    FROM rooms r 
    $where
    ORDER BY r.created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$rooms = $stmt->fetchAll();

function buildUrl($params) {
    $query = array_merge($_GET, $params);
    return '?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms — Admin</title>
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
        select.per-page {
            background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12);
            color:rgba(255,255,255,0.65); padding:4px 10px; border-radius:var(--radius-sm);
            font-size:0.72rem; cursor:pointer; accent-color:var(--gold);
        }
        select.per-page option { background:#1F2833; color:#F8F9FA; }
        .btn-icon-only {
            display:inline-flex; align-items:center; justify-content:center;
            width:28px; height:28px; border-radius:var(--radius-sm);
            background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08);
            color:rgba(255,255,255,0.5); cursor:pointer; transition:all 0.15s;
        }
        .btn-icon-only:hover { background:rgba(255,255,255,0.1); color:#fff; }
        .btn-danger-icon { border-color:rgba(231,76,60,0.2); color:rgba(231,76,60,0.6); }
        .btn-danger-icon:hover { background:rgba(231,76,60,0.15); color:#E74C3C; border-color:rgba(231,76,60,0.3); }
        .btn-warn-icon { border-color:rgba(243,156,18,0.2); color:rgba(243,156,18,0.6); }
        .btn-warn-icon:hover { background:rgba(243,156,18,0.15); color:#F39C12; border-color:rgba(243,156,18,0.3); }
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
                <a href="../admin/rooms.php" class="admin-nav-item active">
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
            <div style="margin-top:auto;padding-top:var(--space-xl);border-top:1px solid rgba(255,255,255,0.05)">
                <a href="../admin/logout.php" class="btn btn-ghost btn-sm" style="width:100%">Sign Out</a>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-page-header flex-between">
                <div>
                    <h1 class="heading-lg">Rooms</h1>
                </div>
                <div class="flex gap-xs" style="flex-wrap:wrap">
                    <a href="../admin/rooms.php?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-ghost' ?> btn-sm" style="font-size:0.7rem;padding:4px 10px">All</a>
                    <a href="../admin/rooms.php?filter=active" class="btn <?= $filter === 'active' ? 'btn-primary' : 'btn-ghost' ?> btn-sm" style="font-size:0.7rem;padding:4px 10px">Active</a>
                    <a href="../admin/rooms.php?filter=closed" class="btn <?= $filter === 'closed' ? 'btn-primary' : 'btn-ghost' ?> btn-sm" style="font-size:0.7rem;padding:4px 10px">Closed</a>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-bar" id="searchBar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Search rooms..." oninput="filterTable()">
                <span class="search-clear" id="searchClear" onclick="clearSearch()">✕</span>
                <span class="search-results-count" id="resultsCount"><?= count($rooms) ?> rooms</span>
            </div>

            <!-- Bulk Actions Bar -->
            <div id="bulkActions" style="display:none;padding:8px 14px;margin-bottom:var(--space-sm);border-radius:var(--radius-md);background:rgba(231,76,60,0.08);border:1px solid rgba(231,76,60,0.15)">
                <div class="flex-between" style="align-items:center">
                    <span style="font-size:0.75rem;color:#E74C3C"><span id="selectedCount">0</span> selected</span>
                    <button class="btn-icon-only btn-danger-icon" onclick="confirmDeleteSelected()" title="Delete selected">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            </div>

            <div class="glass-card" style="padding:0;overflow:hidden">
                <table style="width:100%;border-collapse:collapse;font-size:0.8rem">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.06)">
                            <th style="width:32px;padding:8px 10px;text-align:left"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="accent-color:var(--gold)"></th>
                            <th style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Room</th>
                            <th style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Status</th>
                            <th class="hide-mobile" style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Songs</th>
                            <th class="hide-mobile" style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Pending</th>
                            <th class="hide-mobile" style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Created</th>
                            <th style="padding:8px 10px;width:60px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="7" style="padding:var(--space-2xl);text-align:center;color:rgba(255,255,255,0.3)">No rooms found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($rooms as $r): ?>
                        <tr data-id="<?= $r['id'] ?>" style="border-bottom:1px solid rgba(255,255,255,0.04);transition:background 0.15s" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                            <td style="padding:6px 10px"><input type="checkbox" class="room-checkbox" value="<?= $r['id'] ?>" onchange="updateBulkActions()" style="accent-color:var(--gold)"></td>
                            <td style="padding:6px 10px">
                                <div style="font-weight:600;letter-spacing:0.08em;color:var(--gold);font-size:0.85rem"><?= sanitize($r['room_code']) ?></div>
                                <div class="hide-desktop" style="font-size:0.7rem;color:rgba(255,255,255,0.3)"><?= $r['song_count'] ?> songs · <?= $r['pending_count'] ?> pending</div>
                            </td>
                            <td style="padding:6px 10px">
                                <?php
                                $sColor = $r['status'] === 'active' ? 'rgba(39,174,96,0.2)' : 'rgba(255,255,255,0.08)';
                                $sText = $r['status'] === 'active' ? '#27AE60' : 'rgba(255,255,255,0.4)';
                                ?>
                                <span style="font-size:0.68rem;padding:2px 8px;border-radius:var(--radius-full);background:<?= $sColor ?>;color:<?= $sText ?>;font-weight:500"><?= $r['status'] ?></span>
                            </td>
                            <td class="hide-mobile" style="padding:6px 10px;font-size:0.8rem;color:rgba(255,255,255,0.5)"><?= $r['song_count'] ?></td>
                            <td class="hide-mobile" style="padding:6px 10px;font-size:0.8rem;color:rgba(255,255,255,0.5)"><?= $r['pending_count'] ?></td>
                            <td class="hide-mobile" style="padding:6px 10px;font-size:0.75rem;color:rgba(255,255,255,0.3)"><?= date('M j, g:i A', strtotime($r['created_at'])) ?></td>
                            <td style="padding:6px 10px">
                                <div class="flex gap-xs" style="justify-content:flex-end">
                                    <?php if ($r['status'] === 'active'): ?>
                                    <button class="btn-icon-only btn-warn-icon" onclick="closeRoom(<?= $r['id'] ?>, '<?= sanitize($r['room_code']) ?>')" title="Close room">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn-icon-only btn-danger-icon" onclick="confirmDelete([<?= $r['id'] ?>], '<?= sanitize($r['room_code']) ?>')" title="Delete room">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1 || $totalRows > 0): ?>
            <div class="flex-between" style="margin-top:var(--space-sm);flex-wrap:wrap;gap:var(--space-sm)">
                <div class="flex gap-xs" style="align-items:center">
                    <span style="font-size:0.72rem;color:rgba(255,255,255,0.35)"><?= $totalRows ?> room<?= $totalRows !== 1 ? 's' : '' ?></span>
                    <span style="font-size:0.72rem;color:rgba(255,255,255,0.2)">·</span>
                    <select id="perPageSelect" class="per-page" onchange="changePerPage(this.value)">
                        <option value="5"<?= $perPage === 5 ? ' selected' : '' ?>>5</option>
                        <option value="10"<?= $perPage === 10 ? ' selected' : '' ?>>10</option>
                        <option value="20"<?= $perPage === 20 ? ' selected' : '' ?>>20</option>
                        <option value="50"<?= $perPage === 50 ? ' selected' : '' ?>>50</option>
                    </select>
                    <span style="font-size:0.72rem;color:rgba(255,255,255,0.35)">per page</span>
                </div>
                <div class="flex gap-xs">
                    <?php if ($page > 1): ?>
                    <a href="<?= buildUrl(['page' => $page - 1]) ?>" class="btn-icon-only" style="text-decoration:none">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    if ($start > 1): ?>
                    <a href="<?= buildUrl(['page' => 1]) ?>" class="btn-icon-only" style="text-decoration:none;font-size:0.7rem">1</a>
                    <?php if ($start > 2): ?>
                    <span style="color:rgba(255,255,255,0.2);padding:0 2px">...</span>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="<?= buildUrl(['page' => $i]) ?>" class="btn-icon-only" style="text-decoration:none;font-size:0.7rem;<?= $i === $page ? 'background:var(--gold);color:#0B0C10;border-color:var(--gold)' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                    <span style="color:rgba(255,255,255,0.2);padding:0 2px">...</span>
                    <?php endif; ?>
                    <a href="<?= buildUrl(['page' => $totalPages]) ?>" class="btn-icon-only" style="text-decoration:none;font-size:0.7rem"><?= $totalPages ?></a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="<?= buildUrl(['page' => $page + 1]) ?>" class="btn-icon-only" style="text-decoration:none">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

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

    function changePerPage(val) {
        var url = new URL(window.location);
        url.searchParams.set('per_page', val);
        url.searchParams.set('page', '1');
        window.location = url;
    }

    // Search & Filter
    function filterTable() {
        var query = document.getElementById('searchInput').value.toLowerCase().trim();
        var rows = document.querySelectorAll('tbody tr[data-id]');
        var visible = 0;

        rows.forEach(function(row) {
            var code = (row.querySelector('td:nth-child(2) div:first-child')?.textContent || '').toLowerCase();
            var status = (row.querySelector('td:nth-child(3) span')?.textContent || '').toLowerCase();
            var matchSearch = !query || code.includes(query) || status.includes(query);
            if (matchSearch) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('resultsCount').textContent = visible + ' room' + (visible !== 1 ? 's' : '');
        document.getElementById('searchClear').style.display = query ? 'flex' : 'none';
    }

    function clearSearch() {
        document.getElementById('searchInput').value = '';
        filterTable();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT') {
            e.preventDefault();
            document.getElementById('searchInput').focus();
        }
    });

    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeConfirmModal();
    });

    // Select All
    function toggleSelectAll(el) {
        document.querySelectorAll('.room-checkbox').forEach(function(cb) { cb.checked = el.checked; });
        updateBulkActions();
    }

    function updateBulkActions() {
        var checked = document.querySelectorAll('.room-checkbox:checked');
        var count = checked.length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('bulkActions').style.display = count > 0 ? 'block' : 'none';
    }

    function getSelectedIds() {
        var ids = [];
        document.querySelectorAll('.room-checkbox:checked').forEach(function(cb) { ids.push(parseInt(cb.value)); });
        return ids;
    }

    // Close room
    function closeRoom(id, code) {
        showConfirmModal('🔒', 'Close Room "' + code + '"?', 'The room will be closed and active songs will be stopped.', async function() {
            try {
                var res = await fetch('../api/admin_close_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: id })
                });
                var data = await res.json();
                if (data.success) {
                    showToast('Room ' + code + ' closed', 'success');
                    var row = document.querySelector('tr[data-id="' + id + '"]');
                    if (row) {
                        var badge = row.querySelector('.badge');
                        if (badge) { badge.className = 'badge badge-closed'; badge.textContent = 'closed'; }
                        var actions = row.querySelector('.flex.gap-xs');
                        if (actions) {
                            var closeBtn = actions.querySelector('.btn-ghost');
                            if (closeBtn) closeBtn.remove();
                        }
                    }
                } else {
                    showToast(data.error || 'Failed to close room', 'error');
                }
            } catch(e) { showToast('Connection error', 'error'); }
        });
    }

    // Delete single
    function confirmDelete(ids, code) {
        var msg = ids.length === 1
            ? 'Permanently delete room "' + code + '" and all its songs? This cannot be undone.'
            : 'Permanently delete ' + ids.length + ' rooms and all their songs? This cannot be undone.';
        showConfirmModal('🗑️', 'Delete Room' + (ids.length > 1 ? 's' : '') + '?', msg, function() {
            deleteRooms(ids);
        });
    }

    // Delete selected
    function confirmDeleteSelected() {
        var ids = getSelectedIds();
        if (ids.length === 0) return;
        confirmDelete(ids, '');
    }

    async function deleteRooms(ids) {
        try {
            var res = await fetch('../api/admin_delete_room.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_ids: ids })
            });
            var data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                ids.forEach(function(id) {
                    var row = document.querySelector('tr[data-id="' + id + '"]');
                    if (row) row.remove();
                });
                document.getElementById('selectAll').checked = false;
                updateBulkActions();
                if (document.querySelectorAll('tbody tr').length === 0) {
                    var tbody = document.querySelector('tbody');
                    tbody.innerHTML = '<tr><td colspan="8" class="text-muted text-center" style="padding:var(--space-2xl)">No rooms found</td></tr>';
                }
            } else {
                showToast(data.error || 'Failed to delete', 'error');
            }
        } catch(e) { showToast('Connection error', 'error'); }
    }

    // Trigger cleanup on page load
    fetch('../api/cleanup.php').catch(function(){});
    </script>
</body>
</html>
