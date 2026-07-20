<?php
/**
 * Admin - Queue Management
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = max(5, min(50, intval($_GET['per_page'] ?? 10)));
$offset = ($page - 1) * $perPage;

$where = '';
if ($filter === 'pending') $where = "WHERE sq.status = 'pending'";
elseif ($filter === 'playing') $where = "WHERE sq.status = 'playing'";
elseif ($filter === 'completed') $where = "WHERE sq.status = 'completed'";
elseif ($filter === 'skipped') $where = "WHERE sq.status = 'skipped'";

$countStmt = db()->query("
    SELECT COUNT(*)
    FROM songs_queue sq
    JOIN rooms r ON sq.room_id = r.id
    $where
");
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->query("
    SELECT sq.*, r.room_code,
        (SELECT COUNT(*) FROM rooms WHERE status = 'active') as active_room_count
    FROM songs_queue sq
    JOIN rooms r ON sq.room_id = r.id
    $where
    ORDER BY sq.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$queue = $stmt->fetchAll();

// Get active rooms for the move modal
$stmt = db()->query("SELECT id, room_code FROM rooms WHERE status = 'active' ORDER BY room_code");
$active_rooms = $stmt->fetchAll();

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
    <title>Queue Management — Admin</title>
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
        .search-filter {
            display:flex; align-items:center; gap:6px; flex-shrink:0;
        }
        .search-filter select,
        select.per-page {
            background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12);
            color:rgba(255,255,255,0.65); padding:4px 10px; border-radius:var(--radius-sm);
            font-size:0.7rem; cursor:pointer; accent-color:var(--gold);
        }
        .search-filter select option,
        select.per-page option { background:#1F2833; color:#F8F9FA; }
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
        @media (max-width: 768px) {
            .hide-mobile { display: none !important; }
            .data-table td, .data-table th { padding: 6px 8px !important; }
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
                <a href="../admin/queue.php" class="admin-nav-item active">
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
                    <h1 class="heading-lg">Queue</h1>
                </div>
                <div class="flex gap-xs" style="flex-wrap:wrap">
                    <a href="../admin/queue.php?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-ghost' ?> btn-sm" style="font-size:0.7rem;padding:4px 10px">All</a>
                    <a href="../admin/queue.php?filter=pending" class="btn <?= $filter === 'pending' ? 'btn-primary' : 'btn-ghost' ?> btn-sm" style="font-size:0.7rem;padding:4px 10px">Pending</a>
                    <a href="../admin/queue.php?filter=skipped" class="btn <?= $filter === 'skipped' ? 'btn-primary' : 'btn-ghost' ?> btn-sm" style="font-size:0.7rem;padding:4px 10px">Skipped</a>
                    <a href="../admin/queue.php?filter=playing" class="btn <?= $filter === 'playing' ? 'btn-primary' : 'btn-ghost' ?> btn-sm" style="font-size:0.7rem;padding:4px 10px">Playing</a>
                    <a href="../admin/queue.php?filter=completed" class="btn <?= $filter === 'completed' ? 'btn-primary' : 'btn-ghost' ?> btn-sm" style="font-size:0.7rem;padding:4px 10px">Done</a>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-bar" id="searchBar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Search songs, rooms, added by..." oninput="filterTable()">
                <span class="search-clear" id="searchClear" onclick="clearSearch()">✕</span>
                <div class="search-filter">
                    <select id="roomFilter" onchange="filterTable()">
                        <option value="">All Rooms</option>
                        <?php
                        $roomCodes = [];
                        foreach ($queue as $q) { $roomCodes[$q['room_code']] = true; }
                        foreach (array_keys($roomCodes) as $code): ?>
                        <option value="<?= sanitize($code) ?>"><?= sanitize($code) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="search-results-count" id="resultsCount"><?= count($queue) ?> songs</span>
            </div>

            <!-- Bulk Actions Bar -->
            <div id="bulkActions" style="display:none;padding:8px 14px;margin-bottom:var(--space-sm);border-radius:var(--radius-md);background:rgba(212,175,55,0.08);border:1px solid rgba(212,175,55,0.15)">
                <div class="flex-between" style="align-items:center">
                    <span style="font-size:0.75rem;color:var(--gold)"><span id="selectedCount">0</span> selected</span>
                    <div class="flex gap-xs">
                        <button class="btn-icon-only" id="bulkPlaylistBtn" onclick="openBulkPlaylistModal()" style="display:none" title="Add to playlist">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        </button>
                        <button class="btn-icon-only" id="recoverSelectedBtn" onclick="openMoveModal()" style="display:none" title="Move to room">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        </button>
                        <button class="btn-icon-only btn-danger-icon" onclick="confirmBulkDelete()" title="Delete selected">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="glass-card" style="padding:0;overflow:hidden">
                <table style="width:100%;border-collapse:collapse;font-size:0.8rem">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.06)">
                            <th style="width:32px;padding:8px 10px;text-align:left"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="accent-color:var(--gold)"></th>
                            <th style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Song</th>
                            <th class="hide-mobile" style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Room</th>
                            <th style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Status</th>
                            <th class="hide-mobile" style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Added By</th>
                            <th class="hide-mobile" style="padding:8px 10px;text-align:left;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.35);font-weight:600">Date</th>
                            <th style="padding:8px 10px;width:60px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($queue)): ?>
                        <tr>
                            <td colspan="7" style="padding:var(--space-2xl);text-align:center;color:rgba(255,255,255,0.3)">No songs in queue</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($queue as $q): ?>
                        <tr data-id="<?= $q['id'] ?>" data-room="<?= $q['room_id'] ?>" style="border-bottom:1px solid rgba(255,255,255,0.04);transition:background 0.15s" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                            <td style="padding:6px 10px"><input type="checkbox" class="queue-checkbox" value="<?= $q['id'] ?>" onchange="updateBulkActions()" style="accent-color:var(--gold)"></td>
                            <td style="padding:6px 10px">
                                <div style="font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;font-size:0.82rem" title="<?= sanitize($q['video_title']) ?>"><?= sanitize(mb_substr($q['video_title'], 0, 45)) ?></div>
                                <div class="hide-desktop" style="font-size:0.7rem;color:rgba(255,255,255,0.3)"><?= sanitize($q['room_code']) ?> &middot; <?= date('M j', strtotime($q['created_at'])) ?></div>
                            </td>
                            <td class="hide-mobile" style="padding:6px 10px"><span style="color:var(--gold);font-weight:600;font-size:0.75rem;letter-spacing:0.05em"><?= sanitize($q['room_code']) ?></span></td>
                            <td style="padding:6px 10px">
                                <?php
                                $statusColors = ['pending'=>'rgba(255,255,255,0.15)','playing'=>'rgba(212,175,55,0.2)','completed'=>'rgba(39,174,96,0.2)','skipped'=>'rgba(231,76,60,0.2)'];
                                $statusText = ['pending'=>'rgba(255,255,255,0.6)','playing'=>'#D4AF37','completed'=>'#27AE60','skipped'=>'#E74C3C'];
                                ?>
                                <span style="font-size:0.68rem;padding:2px 8px;border-radius:var(--radius-full);background:<?= $statusColors[$q['status']] ?? 'rgba(255,255,255,0.1)' ?>;color:<?= $statusText[$q['status']] ?? 'rgba(255,255,255,0.5)' ?>;font-weight:500"><?= $q['status'] ?></span>
                            </td>
                            <td class="hide-mobile" style="padding:6px 10px;font-size:0.75rem;color:rgba(255,255,255,0.5)"><?= sanitize($q['added_by']) ?></td>
                            <td class="hide-mobile" style="padding:6px 10px;font-size:0.75rem;color:rgba(255,255,255,0.3)"><?= date('M j, g:i A', strtotime($q['created_at'])) ?></td>
                            <td style="padding:6px 10px">
                                <div class="flex gap-xs" style="justify-content:flex-end">
                                    <button class="btn-icon-only" onclick="addToPlaylist(<?= $q['id'] ?>, '<?= sanitize(addslashes(mb_substr($q['video_title'], 0, 30))) ?>')" title="Add to playlist">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                    </button>
                                    <button class="btn-icon-only" onclick="openMoveModalSingle(<?= $q['id'] ?>)" title="Move to room">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                    </button>
                                    <button class="btn-icon-only btn-danger-icon" onclick="confirmDeleteSingle(<?= $q['id'] ?>, '<?= sanitize(addslashes(mb_substr($q['video_title'], 0, 30))) ?>')" title="Delete">
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
                    <span style="font-size:0.72rem;color:rgba(255,255,255,0.35)"><?= $totalRows ?> song<?= $totalRows !== 1 ? 's' : '' ?></span>
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

    <!-- Playlist Profile Selector Modal -->
    <div class="modal-overlay" id="playlistModal">
        <div class="modal glass-card" style="padding:var(--space-xl);max-width:400px">
            <h3 class="heading-md" style="margin-bottom:var(--space-md)">Add to Playlist</h3>
            <p class="text-muted" style="margin-bottom:var(--space-lg)">Select a profile for "<span id="plSongTitle"></span>"</p>
            <div id="plProfileList" style="margin-bottom:var(--space-md);max-height:200px;overflow-y:auto"></div>
            <div style="margin-bottom:var(--space-xl)">
                <div class="flex gap-sm" style="align-items:center">
                    <input type="text" id="newProfileName" placeholder="New profile name..." style="flex:1;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.85);padding:6px 12px;border-radius:var(--radius-sm);font-size:0.8rem;outline:none;font-family:var(--font-body)">
                    <button class="btn-icon-only" onclick="createProfile()" title="Create profile">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex gap-md" style="justify-content:flex-end">
                <button class="btn btn-ghost" onclick="closePlaylistModal()">Cancel</button>
                <button class="btn btn-primary" onclick="addSongToProfile()">Save</button>
            </div>
        </div>
    </div>

    <!-- Move to Room Modal -->
    <div class="modal-overlay" id="moveModal">
        <div class="modal glass-card" style="padding:var(--space-xl);max-width:400px">
            <h3 class="heading-md" style="margin-bottom:var(--space-md)">Move to Room</h3>
            <p class="text-muted" style="margin-bottom:var(--space-lg)">Select an active room to move <span id="moveSongCount">0</span> song(s) to:</p>
            <div id="moveRoomList" style="margin-bottom:var(--space-xl);max-height:200px;overflow-y:auto">
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
                <button class="btn btn-ghost" onclick="closeMoveModal()">Cancel</button>
                <button class="btn btn-primary" id="moveConfirmBtn" onclick="executeMove()" <?= empty($active_rooms) ? 'disabled' : '' ?>>Move Songs</button>
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

    // Playlist Profile Selector
    var plSongId = null;

    async function addToPlaylist(id, title) {
        plSongId = id;
        document.getElementById('plSongTitle').textContent = title;
        document.getElementById('playlistModal').classList.add('active');
        await loadProfiles();
    }

    function closePlaylistModal() {
        document.getElementById('playlistModal').classList.remove('active');
        plSongId = null;
        _bulkPlaylistIds = null;
    }

    var _bulkPlaylistIds = null;

    function openBulkPlaylistModal() {
        _bulkPlaylistIds = getSelectedIds();
        if (_bulkPlaylistIds.length === 0) return;
        document.getElementById('plSongTitle').textContent = _bulkPlaylistIds.length + ' song(s)';
        document.getElementById('playlistModal').classList.add('active');
        loadProfiles();
    }

    document.getElementById('playlistModal').addEventListener('click', function(e) {
        if (e.target === this) closePlaylistModal();
    });

    async function loadProfiles() {
        try {
            var res = await fetch('../api/playlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_profiles' })
            });
            var data = await res.json();
            var container = document.getElementById('plProfileList');
            if (data.success && data.profiles.length > 0) {
                container.innerHTML = data.profiles.map(function(p) {
                    return '<label class="flex gap-sm" style="padding:8px 12px;border-radius:var(--radius-md);cursor:pointer;transition:background 0.2s;border:1px solid rgba(255,255,255,0.05);margin-bottom:4px" onmouseover="this.style.background=\'rgba(212,175,55,0.05)\'" onmouseout="this.style.background=\'transparent\'">' +
                        '<input type="radio" name="pl_profile" value="' + p.id + '" style="accent-color:var(--gold)">' +
                        '<span style="font-weight:500;color:rgba(255,255,255,0.85)">' + p.name + '</span>' +
                        '<span style="margin-left:auto;font-size:0.7rem;color:rgba(255,255,255,0.3)">' + p.song_count + ' songs</span>' +
                        '</label>';
                }).join('');
            } else {
                container.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.3);font-size:0.8rem;padding:12px">No profiles yet. Create one below.</p>';
            }
        } catch(e) {
            document.getElementById('plProfileList').innerHTML = '<p style="text-align:center;color:rgba(231,76,60,0.6);font-size:0.8rem">Failed to load profiles</p>';
        }
    }

    async function createProfile() {
        var name = document.getElementById('newProfileName').value.trim();
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
                document.getElementById('newProfileName').value = '';
                await loadProfiles();
            } else {
                showToast(data.error || 'Failed to create profile', 'error');
            }
        } catch(e) { showToast('Connection error', 'error'); }
    }

    async function addSongToProfile() {
        var radio = document.querySelector('input[name="pl_profile"]:checked');
        if (!radio) { showToast('Select a profile', 'error'); return; }
        var profileId = parseInt(radio.value);
        try {
            if (_bulkPlaylistIds) {
                var added = 0;
                for (var i = 0; i < _bulkPlaylistIds.length; i++) {
                    var res = await fetch('../api/playlist.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'add_to_profile', id: _bulkPlaylistIds[i], profile_id: profileId })
                    });
                    var data = await res.json();
                    if (data.success) added++;
                }
                showToast(added + ' song(s) added to playlist', 'success');
                _bulkPlaylistIds = null;
                closePlaylistModal();
            } else {
                var res = await fetch('../api/playlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add_to_profile', id: plSongId, profile_id: profileId })
                });
                var data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    closePlaylistModal();
                } else {
                    showToast(data.error || 'Failed to add song', 'error');
                }
            }
        } catch(e) { showToast('Connection error', 'error'); }
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
        var roomFilter = document.getElementById('roomFilter').value;
        var rows = document.querySelectorAll('tbody tr[data-id]');
        var visible = 0;

        rows.forEach(function(row) {
            var title = (row.querySelector('td:nth-child(2) div:first-child')?.textContent || '').toLowerCase();
            var room = (row.querySelector('td:nth-child(3) span')?.textContent || '').trim();
            var addedBy = (row.querySelector('td:nth-child(5)')?.textContent || '').toLowerCase();
            var matchSearch = !query || title.includes(query) || room.toLowerCase().includes(query) || addedBy.includes(query);
            var matchRoom = !roomFilter || room === roomFilter;
            if (matchSearch && matchRoom) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('resultsCount').textContent = visible + ' song' + (visible !== 1 ? 's' : '');
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
        document.querySelectorAll('.queue-checkbox').forEach(function(cb) { cb.checked = el.checked; });
        updateBulkActions();
    }

    function updateBulkActions() {
        var checked = document.querySelectorAll('.queue-checkbox:checked');
        var count = checked.length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('bulkActions').style.display = count > 0 ? 'block' : 'none';
        document.getElementById('recoverSelectedBtn').style.display = count > 0 ? 'inline-flex' : 'none';
        document.getElementById('bulkPlaylistBtn').style.display = count > 0 ? 'inline-flex' : 'none';
    }

    function getSelectedIds() {
        var ids = [];
        document.querySelectorAll('.queue-checkbox:checked').forEach(function(cb) { ids.push(parseInt(cb.value)); });
        return ids;
    }

    // Delete single
    function confirmDeleteSingle(id, title) {
        showConfirmModal('🗑️', 'Delete Song?', '"' + title + '" will be permanently removed from the queue.', function() {
            apiCall('delete', [id]);
        });
    }

    // Bulk delete
    function confirmBulkDelete() {
        var ids = getSelectedIds();
        if (ids.length === 0) return;
        showConfirmModal('🗑️', 'Delete ' + ids.length + ' song(s)?', 'This cannot be undone.', function() {
            apiCall('bulk_delete', ids);
        });
    }

    // API call
    async function apiCall(action, ids, targetRoomId) {
        try {
            var body = { action: action, ids: ids };
            if (targetRoomId) body.target_room_id = targetRoomId;
            var res = await fetch('../api/admin_queue_manage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
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
                    document.querySelector('tbody').innerHTML = '<tr><td colspan="9" class="text-muted text-center" style="padding:var(--space-2xl)">No songs in queue</td></tr>';
                }
            } else {
                showToast(data.error || 'Operation failed', 'error');
            }
        } catch(e) { showToast('Connection error', 'error'); }
    }

    // Move modal
    function openMoveModal() {
        var ids = getSelectedIds();
        if (ids.length === 0) return;
        document.getElementById('moveSongCount').textContent = ids.length;
        document.getElementById('moveModal').classList.add('active');
    }

    function openMoveModalSingle(id) {
        document.getElementById('moveSongCount').textContent = 1;
        document.getElementById('moveModal').classList.add('active');
        // Store single ID temporarily
        window._moveSingleId = id;
    }

    function closeMoveModal() {
        document.getElementById('moveModal').classList.remove('active');
        window._moveSingleId = null;
    }

    document.getElementById('moveModal').addEventListener('click', function(e) {
        if (e.target === this) closeMoveModal();
    });

    function executeMove() {
        var target = document.querySelector('input[name="target_room"]:checked');
        if (!target) { showToast('Please select a room', 'error'); return; }

        var ids = window._moveSingleId ? [window._moveSingleId] : getSelectedIds();
        if (ids.length === 0) return;

        apiCall('move_to_room', ids, parseInt(target.value));
        closeMoveModal();
    }

    // Trigger cleanup on page load
    fetch('../api/cleanup.php').catch(function(){});
    </script>
</body>
</html>
