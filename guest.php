<?php
/**
 * KTV LOUNGE - Guest Room Workspace (Mobile Controller)
 */
require_once __DIR__ . '/config/app.php';

$code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';

if (!$code) {
    header('Location: ' . BASE_URL);
    exit;
}

$stmt = db()->prepare("SELECT id, status FROM rooms WHERE room_code = ?");
$stmt->execute([$code]);
$room = $stmt->fetch();

if (!$room || $room['status'] !== 'active') {
    $valid = false;
} else {
    $valid = true;
    $room_id = $room['id'];
}

$nickname = isset($_COOKIE['ktv_nickname']) ? $_COOKIE['ktv_nickname'] : '';
$show_now_playing = get_setting('show_now_playing', '1');

$has_playlists = false;
try {
    $stmt = db()->query("SELECT COUNT(*) as cnt FROM playlist_profiles");
    $has_playlists = (int)$stmt->fetch()['cnt'] > 0;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Guest Lounge — <?= sanitize(SITE_NAME) ?></title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= ASSETS_VERSION ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎤</text></svg>">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; padding:0; background:#0B0C10; color:#F8F9FA; font-family:var(--font-body); min-height:100dvh; display:flex; flex-direction:column; }

        .g-sticky-top {
            flex-shrink:0;
            background:rgba(10,11,13,0.97);
        }
        .g-queue-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:var(--space-xs) var(--space-md) var(--space-sm);
        }
        .g-queue-header .g-section-label { padding:0; margin:0; }

        .g-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:var(--space-sm) var(--space-md);
            background:rgba(11,12,16,0.95); backdrop-filter:blur(12px);
            border-bottom:1px solid rgba(255,255,255,0.05);
        }
        .g-fs-btn {
            display:flex; align-items:center; justify-content:center;
            width:30px; height:30px; border-radius:50%;
            background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08);
            color:rgba(255,255,255,0.5); cursor:pointer; transition:all 0.2s;
            margin-left:var(--space-sm); flex-shrink:0;
        }
        .g-fs-btn:hover { background:rgba(255,255,255,0.12); color:#fff; }
        .g-fs-btn:active { transform:scale(0.92); }

        .g-back {
            display:inline-flex; align-items:center; gap:6px;
            font-size:0.8rem; color:rgba(255,255,255,0.6);
            text-decoration:none; padding:6px 12px; border-radius:var(--radius-full);
            border:1px solid rgba(255,255,255,0.1); transition:all 0.2s;
        }
        .g-back:hover { background:rgba(255,255,255,0.05); color:#fff; }
        .g-room {
            display:inline-flex; align-items:center; gap:6px;
            font-size:0.75rem; font-weight:600; letter-spacing:0.15em; color:var(--gold);
            padding:5px 14px; border-radius:var(--radius-full);
            background:rgba(212,175,55,0.1); border:1px solid rgba(212,175,55,0.25);
        }
        .g-room .label { font-size:0.55rem; font-weight:400; text-transform:uppercase; letter-spacing:0.1em; opacity:0.6; }

        .g-body { flex:1; overflow-y:auto; padding:var(--space-md); padding-bottom:80px; }

        .g-np {
            background:rgba(212,175,55,0.05); border:1px solid rgba(212,175,55,0.15);
            border-radius:var(--radius-lg); padding:var(--space-md); margin-bottom:var(--space-md);
            display:none;
        }
        .g-np-label { font-size:0.6rem; text-transform:uppercase; letter-spacing:0.15em; color:var(--gold); margin-bottom:6px; font-weight:600; }
        .g-np-title { font-weight:600; font-size:0.95rem; margin-bottom:2px; line-height:1.3; }
        .g-np-by { font-size:0.75rem; color:rgba(255,255,255,0.4); }
        .g-np-icon {
            width:32px; height:32px; border-radius:50%; background:var(--gold);
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        @keyframes eq { 0%,100%{height:4px} 50%{height:14px} }
        .g-eq { display:flex; align-items:flex-end; gap:2px; height:16px; }
        .g-eq span { width:3px; background:var(--midnight); border-radius:1px; animation:eq 0.8s ease-in-out infinite; }
        .g-eq span:nth-child(2) { animation-delay:0.15s; }
        .g-eq span:nth-child(3) { animation-delay:0.3s; }

        .g-search { margin-bottom:var(--space-md); position:relative; }
        .g-search-box {
            display:flex; align-items:center; gap:8px;
            background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
            border-radius:var(--radius-full); padding:8px 14px; transition:all 0.2s;
        }
        .g-search-box:focus-within { border-color:rgba(212,175,55,0.4); background:rgba(255,255,255,0.08); }
        .g-search-box svg { flex-shrink:0; color:rgba(255,255,255,0.3); }
        .g-search-box:focus-within svg { color:var(--gold); }
        .g-search-box input {
            flex:1; background:none; border:none; outline:none; color:#F8F9FA;
            font-size:0.85rem; font-family:var(--font-body); min-width:0;
        }
        .g-search-box input::placeholder { color:rgba(255,255,255,0.3); }
        .g-search-clear {
            display:none; align-items:center; justify-content:center;
            width:18px; height:18px; border-radius:50%; cursor:pointer;
            background:rgba(255,255,255,0.12); color:rgba(255,255,255,0.5);
            font-size:0.6rem; line-height:1; flex-shrink:0; transition:all 0.15s;
        }
        .g-search-clear:hover { background:rgba(255,255,255,0.2); color:#fff; }

        .g-search { position:relative; }
        .g-results {
            position:absolute; left:0; right:0; top:calc(100% + 4px);
            background:var(--obsidian); border:1px solid rgba(255,255,255,0.08);
            border-radius:var(--radius-lg); max-height:50vh; overflow-y:auto;
            z-index:200; display:none;
            box-shadow:0 12px 40px rgba(0,0,0,0.5);
        }
        .g-results.open { display:block; }
        .g-result {
            display:flex; align-items:center; gap:10px;
            padding:10px 14px; cursor:pointer; transition:background 0.15s;
            border-bottom:1px solid rgba(255,255,255,0.04);
        }
        .g-result:last-child { border-bottom:none; }
        .g-result:hover { background:rgba(212,175,55,0.06); }
        .g-result-thumb {
            width:52px; height:38px; border-radius:4px; overflow:hidden;
            flex-shrink:0; background:rgba(255,255,255,0.05);
        }
        .g-result-thumb img { width:100%; height:100%; object-fit:cover; }
        .g-result-info { flex:1; min-width:0; }
        .g-result-title {
            font-size:0.8rem; font-weight:500; white-space:nowrap;
            overflow:hidden; text-overflow:ellipsis; color:rgba(255,255,255,0.9);
        }
        .g-result-ch {
            font-size:0.68rem; color:rgba(255,255,255,0.35);
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .g-result-add {
            width:30px; height:30px; border-radius:50%; flex-shrink:0;
            background:rgba(212,175,55,0.15); color:var(--gold);
            border:1px solid rgba(212,175,55,0.3); cursor:pointer;
            display:flex; align-items:center; justify-content:center; transition:all 0.2s;
        }
        .g-result-add:hover, .g-result-add:active {
            background:var(--gold); color:var(--midnight); border-color:var(--gold);
            transform:scale(1.1);
        }
        .g-search-loading {
            text-align:center; padding:20px; color:rgba(255,255,255,0.3); font-size:0.8rem;
        }

        .g-section-label {
            font-size:0.6rem; text-transform:uppercase; letter-spacing:0.15em;
            font-weight:600; padding:var(--space-xs) 0; margin-bottom:var(--space-xs);
        }

        .g-queue-item {
            display:flex; align-items:center; gap:var(--space-sm);
            padding:var(--space-sm) var(--space-sm); border-radius:var(--radius-md);
            margin-bottom:2px; transition:background 0.15s; user-select:none;
        }
        .g-queue-item.playing {
            background:rgba(212,175,55,0.06); border-left:2px solid var(--gold);
        }
        .g-queue-item.dragging { opacity:0.4; background:rgba(212,175,55,0.1); }
        .g-queue-item.drag-over-top { border-top:2px solid var(--gold); }
        .g-queue-item.drag-over-bottom { border-bottom:2px solid var(--gold); }
        .g-queue-item .drag-handle {
            cursor:grab; color:rgba(255,255,255,0.2); display:flex; align-items:center;
            flex-shrink:0; padding:4px 2px; border-radius:4px; transition:color 0.15s;
            touch-action:none;
        }
        .g-queue-item:hover .drag-handle { color:rgba(255,255,255,0.4); }
        .g-queue-item .drag-handle:active { cursor:grabbing; }
        .g-queue-num {
            width:24px; text-align:center; font-size:0.75rem; color:rgba(255,255,255,0.3);
            flex-shrink:0; font-weight:500;
        }
        .g-queue-item.playing .g-queue-num { color:var(--gold); }
        .g-queue-info { flex:1; min-width:0; }
        .g-queue-title { font-size:0.85rem; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .g-queue-by { font-size:0.7rem; color:rgba(255,255,255,0.35); }
        .g-queue-remove {
            display:flex; align-items:center; justify-content:center;
            width:26px; height:26px; border-radius:50%; flex-shrink:0;
            background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08);
            color:rgba(255,255,255,0.3); cursor:pointer; transition:all 0.2s;
        }
        .g-queue-remove:hover {
            background:rgba(239,68,68,0.15); border-color:rgba(239,68,68,0.3); color:#ef4444;
        }
        .g-drag-ghost {
            position:fixed; pointer-events:none; z-index:9999;
            background:rgba(20,22,28,0.96); border:1px solid rgba(212,175,55,0.4);
            border-radius:var(--radius-lg); padding:12px 16px;
            width:calc(100vw - 32px); max-width:320px; left:16px;
            font-size:0.85rem; color:#F8F9FA;
            box-shadow:0 12px 40px rgba(0,0,0,0.7), 0 0 0 1px rgba(212,175,55,0.15);
            transform:scale(1.03); transition:transform 0.15s ease;
            backdrop-filter:blur(12px);
        }
        .g-drag-ghost .ghost-title { font-weight:600; margin-bottom:2px; }
        .g-drag-ghost .ghost-by { font-size:0.7rem; color:rgba(255,255,255,0.4); }
        .g-drag-ghost .ghost-badge {
            display:inline-block; margin-top:6px; font-size:0.6rem;
            text-transform:uppercase; letter-spacing:0.1em; color:var(--gold);
            background:rgba(212,175,55,0.12); padding:2px 8px; border-radius:var(--radius-full);
        }
        .g-drag-active {
            transform:scale(0.97); transition:transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            background:rgba(212,175,55,0.08);
            border:1px solid rgba(212,175,55,0.2);
        }

        .g-reorder-toggle {
            display:inline-flex; align-items:center; gap:4px;
            padding:4px 12px; border-radius:var(--radius-full);
            border:1px solid rgba(212,175,55,0.3);
            background:rgba(212,175,55,0.1);
            color:var(--gold); font-size:0.65rem; font-weight:600;
            cursor:pointer; transition:all 0.2s;
            font-family:var(--font-body); letter-spacing:0.05em;
            touch-action:manipulation;
        }
        .g-reorder-toggle:hover { background:rgba(212,175,55,0.18); }
        .g-reorder-toggle.active { background:var(--gold); color:#0a0b0d; border-color:var(--gold); }

        .g-reorder-arrows {
            display:none; align-items:center; gap:3px; flex-shrink:0;
        }
        .g-reorder-mode .g-reorder-arrows { display:flex; }
        .g-reorder-mode .g-queue-remove { display:none; }

        .g-reorder-arrow {
            display:flex; align-items:center; justify-content:center;
            width:30px; height:30px; border-radius:50%;
            background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);
            color:rgba(255,255,255,0.5); cursor:pointer; transition:all 0.15s;
            touch-action:manipulation;
        }
        .g-reorder-arrow:active { background:rgba(212,175,55,0.2); border-color:rgba(212,175,55,0.3); color:var(--gold); transform:scale(0.9); }
        .g-reorder-arrow.disabled { opacity:0.2; pointer-events:none; }

        .g-reorder-mode .g-queue-item { border-left:2px solid transparent; transition:border 0.2s; }
        .g-reorder-mode .g-queue-item:active { border-left-color:var(--gold); background:rgba(212,175,55,0.06); }

        .g-skip-btn {
            display:none; align-items:center; justify-content:center; gap:4px;
            padding:6px 12px; border-radius:var(--radius-full); flex-shrink:0;
            background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);
            color:#ef4444; font-size:0.7rem; cursor:pointer; transition:all 0.2s;
            font-family:var(--font-body); font-weight:500;
        }
        .g-skip-btn:hover { background:rgba(239,68,68,0.2); border-color:rgba(239,68,68,0.4); }
        .g-skip-btn:active { transform:scale(0.95); }
        .g-skip-btn.show { display:inline-flex; }

        .g-empty { text-align:center; padding:var(--space-2xl) var(--space-lg); color:rgba(255,255,255,0.3); }
        .g-empty-icon { font-size:2rem; margin-bottom:var(--space-sm); }
        .g-empty-text { font-size:0.85rem; }

        .g-footer {
            position:fixed; bottom:0; left:0; right:0;
            padding:var(--space-sm) var(--space-md);
            background:linear-gradient(transparent, rgba(11,12,16,0.95) 30%);
            z-index:50;
        }
        .g-nick-btn {
            display:inline-flex; align-items:center; gap:6px; width:auto; max-width:100%;
            padding:6px 14px; border-radius:var(--radius-full);
            background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08);
            color:rgba(255,255,255,0.4); font-size:0.7rem; cursor:pointer;
            font-family:var(--font-body); transition:all 0.2s;
        }
        .g-nick-btn:hover { background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.6); }
        .g-nick-dot { width:6px; height:6px; border-radius:50%; background:var(--gold); flex-shrink:0; }
        .g-nick-name { color:var(--gold); font-weight:600; }

        /* Modal */
        .g-modal-overlay {
            position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);
            display:none; align-items:flex-end; justify-content:center; z-index:200;
        }
        .g-modal-overlay.active { display:flex; }
        .g-modal {
            position:relative;
            width:100%; max-width:420px; background:var(--obsidian);
            border-radius:var(--radius-xl) var(--radius-xl) 0 0;
            padding:var(--space-xl) var(--space-lg) var(--space-2xl);
            border:1px solid rgba(255,255,255,0.08);
            border-bottom:none;
        }
        .g-modal-title { font-family:var(--font-display); font-size:1.2rem; font-weight:600; margin-bottom:var(--space-xs); text-align:center; }
        .g-modal-sub { font-size:0.8rem; color:rgba(255,255,255,0.4); text-align:center; margin-bottom:var(--space-lg); }
        .g-modal-input {
            width:100%; padding:12px 16px; border-radius:var(--radius-full);
            background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12);
            color:#F8F9FA; font-size:0.95rem; font-family:var(--font-body);
            outline:none; text-align:center; margin-bottom:var(--space-md);
        }
        .g-modal-input:focus { border-color:var(--gold); }
        .g-modal-input::placeholder { color:rgba(255,255,255,0.25); }
        .g-modal-btn {
            width:100%; padding:12px; border-radius:var(--radius-full);
            background:var(--gold); color:var(--midnight); font-weight:700;
            font-size:0.9rem; border:none; cursor:pointer; font-family:var(--font-body);
            transition:opacity 0.2s;
        }
        .g-modal-btn:active { opacity:0.8; }

        .toast-container { position:fixed; top:var(--space-lg); right:var(--space-lg); z-index:300; display:flex; flex-direction:column; gap:var(--space-sm); }
        .toast { padding:10px 18px; border-radius:var(--radius-md); font-size:0.8rem; font-weight:500; opacity:1; transition:opacity 0.3s; }
        .toast-success { background:rgba(39,174,96,0.9); color:#fff; }
        .toast-error { background:rgba(231,76,60,0.9); color:#fff; }
        .toast-info { background:rgba(255,255,255,0.15); color:#fff; backdrop-filter:blur(8px); }

        .g-playlist-toggle {
            display:flex; align-items:center; gap:8px;
            padding:var(--space-sm) var(--space-md); margin-top:var(--space-md);
            border-radius:var(--radius-md); cursor:pointer;
            background:rgba(212,175,55,0.06); border:1px solid rgba(212,175,55,0.12);
            transition:all 0.2s; font-size:0.75rem; font-weight:600;
            text-transform:uppercase; letter-spacing:0.1em; color:var(--gold);
        }
        .g-playlist-toggle:hover { background:rgba(212,175,55,0.1); }
        .g-playlist-toggle svg { transition:transform 0.2s; }
        .g-playlist-toggle.open svg { transform:rotate(90deg); }

        .g-playlist-section { display:none; margin-top:var(--space-sm); }
        .g-playlist-section.open { display:block; }

        .g-pl-profile {
            display:flex; align-items:center; gap:8px;
            padding:8px 12px; border-radius:var(--radius-md); cursor:pointer;
            transition:background 0.15s; margin-bottom:2px;
        }
        .g-pl-profile:hover { background:rgba(255,255,255,0.04); }
        .g-pl-profile-name { flex:1; font-size:0.82rem; font-weight:500; }
        .g-pl-profile-count { font-size:0.7rem; color:rgba(255,255,255,0.35); }

        .g-pl-songs { display:none; padding-left:var(--space-md); }
        .g-pl-songs.open { display:block; }
        .g-pl-song {
            display:flex; align-items:center; gap:8px;
            padding:6px 8px; border-radius:var(--radius-sm);
            transition:background 0.15s;
        }
        .g-pl-song:hover { background:rgba(255,255,255,0.03); }
        .g-pl-song-title { flex:1; font-size:0.78rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .g-pl-song-add {
            width:26px; height:26px; border-radius:50%; flex-shrink:0;
            background:rgba(212,175,55,0.12); color:var(--gold);
            border:1px solid rgba(212,175,55,0.25); cursor:pointer;
            display:flex; align-items:center; justify-content:center; transition:all 0.2s;
        }
        .g-pl-song-add:hover, .g-pl-song-add:active {
            background:var(--gold); color:var(--midnight); border-color:var(--gold);
            transform:scale(1.1);
        }
        .g-pl-loading { text-align:center; padding:16px; color:rgba(255,255,255,0.3); font-size:0.78rem; }
    </style>
</head>
<body>

    <!-- Nickname Modal -->
    <div class="g-modal-overlay <?= $nickname ? '' : 'active' ?>" id="nicknameModal">
        <div class="g-modal">
            <button onclick="closeNickModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;color:rgba(255,255,255,0.4);cursor:pointer;padding:4px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="g-modal-title">Choose Your Name</div>
            <div class="g-modal-sub">This will be shown when you add songs</div>
            <form onsubmit="setNickname(event)">
                <input type="text" class="g-modal-input" id="nicknameInput" placeholder="Your nickname" maxlength="30" required autocomplete="off">
                <button type="submit" class="g-modal-btn">Enter Lounge</button>
            </form>
        </div>
    </div>

    <div class="g-sticky-top">

        <!-- Header -->
        <div class="g-header">
            <a href="<?= BASE_URL ?>" class="g-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
                Exit Room
            </a>
            <div class="g-room">
                <span class="label">Room</span>
                <span><?= sanitize($code) ?></span>
                <button class="g-fs-btn" onclick="toggleGuestFullscreen()" id="guestFsBtn" title="Full Screen" aria-label="Toggle Full Screen">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="guestFsEnter"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="guestFsExit" style="display:none"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/></svg>
                </button>
            </div>
        </div>

        <?php if ($valid): ?>
        <!-- Now Playing Card -->
        <?php if ($show_now_playing === '1'): ?>
        <div class="g-np" id="nowPlayingSection">
            <div style="display:flex;align-items:center;gap:var(--space-sm)">
                <div class="g-np-icon">
                    <div class="g-eq"><span></span><span></span><span></span></div>
                </div>
                <div style="flex:1;min-width:0">
                    <div class="g-np-label">Playing Now</div>
                    <div class="g-np-title" id="npTitle">-</div>
                    <div class="g-np-by" id="npBy"></div>
                </div>
                <button class="g-skip-btn" id="playPauseBtn" onclick="togglePlayback()" title="Play/Pause" style="margin-right:4px;display:none">
                    <svg id="playPauseIcon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </button>
                <button class="g-skip-btn" id="skipBtn" onclick="guestSkip()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                    Skip
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search -->
        <div class="g-search">
            <div class="g-search-box">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Search songs..." autocomplete="off">
                <span class="g-search-clear" id="searchClear" onclick="clearSearch()">✕</span>
            </div>
            <div class="g-results" id="searchResults"></div>
        </div>

        <!-- Queue Header (Up Next + Reorder) -->
        <div class="g-queue-header" id="queueHeader" style="display:none">
            <span class="g-section-label">Up Next</span>
            <button class="g-reorder-toggle" id="reorderToggle" onclick="toggleGuestReorder()">Reorder</button>
        </div>
        <?php endif; ?>

    </div>

    <!-- Body -->
    <div class="g-body">
        <?php if (!$valid): ?>
        <div class="g-empty" style="padding-top:20vh">
            <div class="g-empty-icon">😔</div>
            <div class="g-empty-text">Room not found or has been closed.</div>
            <a href="<?= BASE_URL ?>" class="g-back" style="margin-top:var(--space-lg);display:inline-flex">Return Home</a>
        </div>
        <?php else: ?>

        <!-- Queue -->
        <div id="queueContainer">
            <div class="g-empty" id="queueEmpty">
                <div class="g-empty-icon">🎶</div>
                <div class="g-empty-text">Queue is empty. Search and add a song!</div>
            </div>
            <div id="queueList"></div>
        </div>

        <?php if ($has_playlists): ?>
        <!-- Playlist -->
        <div class="g-playlist-toggle" id="plToggle" onclick="togglePlaylist()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            Playlist
        </div>
        <div class="g-playlist-section" id="plSection">
            <div id="plProfiles"></div>
            <div class="g-pl-loading" id="plLoading">Loading playlist...</div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php if ($valid): ?>
    <div class="g-footer">
        <button class="g-nick-btn" onclick="document.getElementById('nicknameModal').classList.add('active')">
            <span class="g-nick-dot"></span>
            <span id="nickDisplay"><?= sanitize($nickname) ?></span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Confirm Modal -->
    <div class="g-modal-overlay" id="confirmModal">
        <div class="g-modal" style="text-align:center">
            <div id="confirmModalIcon" style="font-size:2.5rem;margin-bottom:var(--space-md)"></div>
            <div id="confirmModalTitle" class="g-modal-title"></div>
            <div id="confirmModalMessage" class="g-modal-sub"></div>
            <button class="g-modal-btn" id="confirmModalAction" style="margin-bottom:var(--space-sm)">Confirm</button>
            <button class="g-modal-btn" onclick="closeConfirmModal()" style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7)">Cancel</button>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        const ROOM_CODE = '<?= sanitize($code) ?>';
        const ROOM_ID = <?= $valid ? $room_id : 'null' ?>;
        let nickname = '<?= sanitize($nickname) ?>';
        let searchTimeout = null;
        let pollInterval = null;

        function showToast(msg, type) {
            var c = document.getElementById('toastContainer');
            if (!c) return;
            var t = document.createElement('div');
            t.className = 'toast toast-' + (type || 'info');
            t.textContent = msg;
            c.appendChild(t);
            setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3000);
        }

        function escapeHtml(text) {
            var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' };
            return String(text).replace(/[&<>"]/g, function(m) { return map[m]; });
        }

        function escapeAttr(text) {
            return String(text).replace(/&/g,'&amp;').replace(/'/g,'&#39;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        // Remove song from queue (with confirmation)
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.g-queue-remove');
            if (!btn) return;
            var trackId = parseInt(btn.dataset.id);
            var title = btn.dataset.title;
            removeFromQueue(trackId, title);
        });

        function removeFromQueue(trackId, title) {
            showConfirmModal('🗑️', 'Remove song?', '"' + title + '" will be removed from the queue.', async function() {
                try {
                    var res = await fetch('api/remove_from_queue.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ room_id: ROOM_ID, track_id: trackId })
                    });
                    var data = await res.json();
                    if (data.success) {
                        showToast('Removed: ' + title, 'success');
                        fetchGuestQueue();
                    } else {
                        showToast(data.error || 'Failed to remove', 'error');
                    }
                } catch(e) { showToast('Connection error', 'error'); }
            });
        }

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

        // Nickname
        function closeNickModal() {
            document.getElementById('nicknameModal').classList.remove('active');
        }

        function setNickname(e) {
            e.preventDefault();
            nickname = document.getElementById('nicknameInput').value.trim();
            if (nickname) {
                document.cookie = 'ktv_nickname=' + encodeURIComponent(nickname) + ';path=/;max-age=86400';
                document.getElementById('nicknameModal').classList.remove('active');
                document.getElementById('nickDisplay').textContent = nickname;
                startPolling();
            }
        }

        // Search
        var searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                var q = this.value.trim();
                document.getElementById('searchClear').style.display = q ? 'flex' : 'none';
                if (q.length < 2) {
                    document.getElementById('searchResults').classList.remove('open');
                    document.getElementById('searchResults').innerHTML = '';
                    return;
                }
                searchTimeout = setTimeout(function() { searchSongs(q); }, 400);
            });
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('searchClear').style.display = 'none';
            document.getElementById('searchResults').classList.remove('open');
            document.getElementById('searchResults').innerHTML = '';
        }

        document.addEventListener('click', function(e) {
            var wrap = document.querySelector('.g-search');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('searchResults').classList.remove('open');
            }
        });

        async function searchSongs(query) {
            var container = document.getElementById('searchResults');
            container.innerHTML = '<div class="g-search-loading">Searching...</div>';
            container.classList.add('open');

            try {
                var res = await fetch('api/search_songs.php?q=' + encodeURIComponent(query));
                var data = await res.json();

                if (!data.success || !data.results.length) {
                    container.innerHTML = '<div class="g-search-loading">No results found</div>';
                    return;
                }

                var html = '';
                data.results.forEach(function(video) {
                    var safeId = video.id.replace(/'/g, "\\'");
                    var safeTitle = video.title.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    html += '<div class="g-result" onclick="addSong(\'' + safeId + '\', \'' + safeTitle + '\')">'
                        + '<div class="g-result-thumb"><img src="' + escapeAttr(video.thumbnail) + '" alt="" loading="lazy"></div>'
                        + '<div class="g-result-info">'
                        + '<div class="g-result-title">' + escapeHtml(video.title) + '</div>'
                        + '<div class="g-result-ch">' + escapeHtml(video.channel) + '</div>'
                        + '</div>'
                        + '<button class="g-result-add" onclick="event.stopPropagation();addSong(\'' + safeId + '\', \'' + safeTitle + '\')" title="Add to queue">'
                        + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
                        + '</button></div>';
                });
                container.innerHTML = html;
            } catch (err) {
                container.innerHTML = '<div class="g-search-loading">Search error. Try again.</div>';
            }
        }

        async function addSong(youtubeId, title) {
            if (!nickname) {
                document.getElementById('nicknameModal').classList.add('active');
                return;
            }

            try {
                var res = await fetch('api/add_to_queue.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: ROOM_ID, youtube_id: youtubeId, video_title: title, added_by: nickname })
                });
                var data = await res.json();

                if (data.success) {
                    showToast('Added: ' + title, 'success');
                    document.getElementById('searchInput').value = '';
                    document.getElementById('searchClear').style.display = 'none';
                    document.getElementById('searchResults').classList.remove('open');
                    document.getElementById('searchResults').innerHTML = '';
                    fetchGuestQueue();
                } else {
                    showToast(data.error || 'Could not add song', 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            }
        }

        async function fetchGuestQueue() {
            if (!ROOM_ID) return;
            try {
                var res = await fetch('api/get_queue.php?room_id=' + ROOM_ID);
                var data = await res.json();
                if (!data.success) return;

                var tracks = data.tracks || [];
                var playing = tracks.find(function(t) { return t.status === 'playing'; });
                var pendingTracks = tracks.filter(function(t) { return t.status === 'pending'; });
                guestPendingTracks = pendingTracks;

                // Now Playing card
                var npSection = document.getElementById('nowPlayingSection');
                if (npSection) {
                    if (playing) {
                        npSection.style.display = 'block';
                        document.getElementById('npTitle').textContent = playing.video_title;
                        document.getElementById('npBy').textContent = 'by ' + playing.added_by;
                        document.getElementById('skipBtn').classList.add('show');
                        var ppBtn = document.getElementById('playPauseBtn');
                        if (ppBtn) ppBtn.style.display = 'inline-flex';
                    } else {
                        npSection.style.display = 'none';
                        var ppBtn2 = document.getElementById('playPauseBtn');
                        if (ppBtn2) ppBtn2.style.display = 'none';
                    }
                }

                // Queue
                var list = document.getElementById('queueList');
                var empty = document.getElementById('queueEmpty');

                if (!playing && pendingTracks.length === 0) {
                    if (empty) empty.style.display = '';
                    if (list) list.innerHTML = '';
                    var qh = document.getElementById('queueHeader');
                    if (qh) qh.style.display = 'none';
                    return;
                }

                if (empty) empty.style.display = 'none';

                var html = '';
                if (playing) {
                    html += '<div class="g-section-label" style="color:var(--gold)">Playing Now</div>';
                    html += '<div class="g-queue-item playing">'
                        + '<div class="g-queue-num">&#9654;</div>'
                        + '<div class="g-queue-info">'
                        + '<div class="g-queue-title">' + escapeHtml(playing.video_title) + '</div>'
                        + '<div class="g-queue-by">by ' + escapeHtml(playing.added_by) + '</div>'
                        + '</div></div>';
                }
                var qHeader = document.getElementById('queueHeader');
                var rToggle = document.getElementById('reorderToggle');
                if (pendingTracks.length > 0) {
                    if (qHeader) qHeader.style.display = 'flex';
                    if (rToggle) {
                        if (pendingTracks.length > 1) {
                            rToggle.style.display = '';
                            rToggle.className = 'g-reorder-toggle' + (guestReorderMode ? ' active' : '');
                            rToggle.innerHTML = guestReorderMode
                                ? '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg> Done'
                                : 'Reorder';
                        } else {
                            rToggle.style.display = 'none';
                        }
                    }
                } else {
                    if (qHeader) qHeader.style.display = 'none';
                }
                if (pendingTracks.length > 0) {
                    pendingTracks.forEach(function(track, i) {
                        var firstCls = i === 0 ? ' disabled' : '';
                        var lastCls = i === pendingTracks.length - 1 ? ' disabled' : '';
                        html += '<div class="g-queue-item' + (guestReorderMode ? ' g-reorder-item' : '') + '" draggable="true" data-id="' + track.id + '" data-title="' + escapeHtml(track.video_title) + '" data-index="' + i + '">'
                            + '<span class="drag-handle" draggable="false"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg></span>'
                            + '<div class="g-queue-num">' + (i + 1) + '</div>'
                            + '<div class="g-queue-info">'
                            + '<div class="g-queue-title">' + escapeHtml(track.video_title) + '</div>'
                            + '<div class="g-queue-by">by ' + escapeHtml(track.added_by) + '</div>'
                            + '</div>'
                            + (guestReorderMode
                                ? '<div class="g-reorder-arrows">'
                                    + '<button class="g-reorder-arrow up' + firstCls + '" onclick="guestMoveItem(' + i + ', ' + (i - 1) + ')" title="Move up"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="18 15 12 9 6 15"/></svg></button>'
                                    + '<button class="g-reorder-arrow down' + lastCls + '" onclick="guestMoveItem(' + i + ', ' + (i + 1) + ')" title="Move down"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg></button>'
                                    + '</div>'
                                : '<button class="g-queue-remove" data-id="' + track.id + '" data-title="' + escapeHtml(track.video_title) + '" title="Remove"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>')
                            + '</div>';
                    });
                }
                if (list) list.innerHTML = html;
                if (guestReorderMode) list.classList.add('g-reorder-mode');
                else list.classList.remove('g-reorder-mode');
                initGuestDragDrop();
            } catch (err) {
                console.error('Queue fetch error:', err);
            }
        }

        async function guestSkip() {
            var btn = document.getElementById('skipBtn');
            btn.disabled = true;
            btn.textContent = '...';
            try {
                var res = await fetch('api/guest_skip.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_code: ROOM_CODE })
                });
                var data = await res.json();
                if (data.success) {
                    showToast('Song skipped', 'success');
                } else {
                    showToast(data.error || 'Could not skip', 'error');
                }
            } catch (e) {
                showToast('Connection error', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg> Skip';
        }

        var guestPlayState = 'play'; // 'play' or 'pause'
        function togglePlayback() {
            var icon = document.getElementById('playPauseIcon');
            var action = guestPlayState === 'play' ? 'pause' : 'play';
            guestPlayState = action;
            if (action === 'pause') {
                icon.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
            } else {
                icon.innerHTML = '<path d="M8 5v14l11-7z"/>';
            }
            fetch('api/guest_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_id: ROOM_ID, action: action })
            }).catch(function(){});
        }

        var guestReorderMode = false;
        var guestPendingTracks = [];

        function toggleGuestReorder() {
            guestReorderMode = !guestReorderMode;
            fetchGuestQueue();
        }

        function guestMoveItem(fromIdx, toIdx) {
            if (toIdx < 0 || toIdx >= guestPendingTracks.length) return;
            var ids = guestPendingTracks.map(function(t) { return t.id; });
            var temp = ids[fromIdx];
            ids[fromIdx] = ids[toIdx];
            ids[toIdx] = temp;
            fetch('api/reorder_queue.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_id: ROOM_ID, ordered_ids: ids })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) fetchGuestQueue();
            }).catch(function() {});
        }

        function startPolling() {
            fetchGuestQueue();
            pollInterval = setInterval(fetchGuestQueue, 5000);
            sendHeartbeat();
            setInterval(sendHeartbeat, 5000);
        }

        function getGuestId() {
            var match = document.cookie.match(/ktv_guest_id=([^;]+)/);
            if (match) return match[1];
            var id = Math.random().toString(36).substring(2) + Date.now().toString(36);
            document.cookie = 'ktv_guest_id=' + id + ';path=/;max-age=86400';
            return id;
        }

        async function sendHeartbeat() {
            if (!ROOM_ID || !nickname) return;
            try {
                await fetch('api/guest_heartbeat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: ROOM_ID, guest_id: getGuestId(), nickname: nickname })
                });
            } catch(e) {}
        }

        if (ROOM_ID && nickname) {
            startPolling();
        }

        // ── Playlist ─────────────────────────────────────────────
        var plProfilesLoaded = false;

        function togglePlaylist() {
            var section = document.getElementById('plSection');
            var toggle = document.getElementById('plToggle');
            var isOpen = section.classList.toggle('open');
            toggle.classList.toggle('open', isOpen);
            if (isOpen && !plProfilesLoaded) {
                loadPlProfiles();
            }
        }

        async function loadPlProfiles() {
            var container = document.getElementById('plProfiles');
            var loading = document.getElementById('plLoading');
            loading.style.display = 'block';
            try {
                var res = await fetch('api/playlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_profiles' })
                });
                var data = await res.json();
                loading.style.display = 'none';
                if (data.success && data.profiles.length > 0) {
                    container.innerHTML = data.profiles.map(function(p) {
                        var html = '<div class="g-pl-profile" onclick="togglePlSongs(' + p.id + ', this)">'
                            + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;color:var(--gold)"><polyline points="9 18 15 12 9 6"/></svg>'
                            + '<span class="g-pl-profile-name">' + escapeHtml(p.name) + '</span>'
                            + '<span class="g-pl-profile-count">' + p.song_count + ' songs</span>'
                            + '</div>'
                            + '<div class="g-pl-songs" id="plSongs' + p.id + '"></div>';
                        return html;
                    }).join('');
                    plProfilesLoaded = true;
                } else {
                    container.innerHTML = '<div style="text-align:center;padding:16px;color:rgba(255,255,255,0.3);font-size:0.78rem">No playlists available</div>';
                }
            } catch(e) {
                loading.style.display = 'none';
                container.innerHTML = '<div style="text-align:center;padding:16px;color:rgba(231,76,60,0.6);font-size:0.78rem">Failed to load playlist</div>';
            }
        }

        async function togglePlSongs(profileId, el) {
            var songsDiv = document.getElementById('plSongs' + profileId);
            var isOpen = songsDiv.classList.toggle('open');
            var arrow = el.querySelector('svg');
            if (arrow) {
                arrow.style.transform = isOpen ? 'rotate(90deg)' : '';
                arrow.style.transition = 'transform 0.2s';
            }
            if (isOpen && !songsDiv.dataset.loaded) {
                songsDiv.innerHTML = '<div class="g-pl-loading">Loading songs...</div>';
                try {
                    var res = await fetch('api/playlist.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'get_songs', profile_id: profileId })
                    });
                    var data = await res.json();
                    if (data.success && data.songs.length > 0) {
                        songsDiv.innerHTML = data.songs.map(function(s) {
                            return '<div class="g-pl-song">'
                                + '<span class="g-pl-song-title" title="' + escapeHtml(s.video_title) + '">' + escapeHtml(s.video_title) + '</span>'
                                + '<button class="g-pl-song-add" onclick="recoverFromPlaylist(' + s.id + ', \'' + escapeAttr(s.video_title) + '\')" title="Add to queue">'
                                + '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
                                + '</button></div>';
                        }).join('');
                        songsDiv.dataset.loaded = '1';
                    } else {
                        songsDiv.innerHTML = '<div style="text-align:center;padding:12px;color:rgba(255,255,255,0.25);font-size:0.75rem">No songs in this playlist</div>';
                        songsDiv.dataset.loaded = '1';
                    }
                } catch(e) {
                    songsDiv.innerHTML = '<div style="text-align:center;padding:12px;color:rgba(231,76,60,0.6);font-size:0.75rem">Failed to load songs</div>';
                }
            }
        }

        async function recoverFromPlaylist(songId, title) {
            if (!nickname) {
                document.getElementById('nicknameModal').classList.add('active');
                return;
            }
            try {
                var res = await fetch('api/playlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'recover', id: songId, target_room_id: ROOM_ID })
                });
                var data = await res.json();
                if (data.success) {
                    if (data.skipped) {
                        showToast('Already in queue: ' + title, 'info');
                    } else {
                        showToast('Added: ' + title, 'success');
                    }
                    fetchGuestQueue();
                } else {
                    showToast(data.error || 'Failed to add song', 'error');
                }
            } catch(e) {
                showToast('Connection error', 'error');
            }
        }

        // ── Drag & Drop Reorder (Desktop + Mobile) ──────
        var guestDragId = null;
        var guestDragGhost = null;
        var guestDragItem = null;
        var guestDragTouchId = null;
        var guestDragActive = false;
        var guestLongPressTimer = null;
        var guestDragOffsetY = 0;
        var guestAutoScroll = null;

        function initGuestDragDrop() {
            document.querySelectorAll('#queueList .g-queue-item[draggable="true"]').forEach(function(el) {
                el.removeEventListener('dragstart', gDragStart);
                el.removeEventListener('dragend', gDragEnd);
                el.removeEventListener('dragenter', gDragEnter);
                el.removeEventListener('dragover', gDragOver);
                el.removeEventListener('dragleave', gDragLeave);
                el.removeEventListener('drop', gDrop);
                el.addEventListener('dragstart', gDragStart);
                el.addEventListener('dragend', gDragEnd);
                el.addEventListener('dragenter', gDragEnter);
                el.addEventListener('dragover', gDragOver);
                el.addEventListener('dragleave', gDragLeave);
                el.addEventListener('drop', gDrop);

                // Touch: long-press anywhere on item to start drag
                el.removeEventListener('touchstart', gTouchStart);
                el.removeEventListener('touchmove', gTouchMovePre);
                el.removeEventListener('touchend', gTouchEndPre);
                el.addEventListener('touchstart', gTouchStart, {passive:false});
                el.addEventListener('touchmove', gTouchMovePre, {passive:false});
                el.addEventListener('touchend', gTouchEndPre, {passive:false});
            });
        }

        // ── Desktop HTML5 Drag ────────────────────────────
        function gDragStart(e) {
            guestDragId = parseInt(this.dataset.id);
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
        }
        function gDragEnd(e) {
            this.classList.remove('dragging');
            document.querySelectorAll('#queueList .g-queue-item').forEach(function(el) {
                el.classList.remove('drag-over-top', 'drag-over-bottom');
            });
        }
        function gDragEnter(e) {
            e.preventDefault();
            var rect = this.getBoundingClientRect();
            this.classList.add(e.clientY < rect.top + rect.height / 2 ? 'drag-over-top' : 'drag-over-bottom');
        }
        function gDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var rect = this.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) {
                this.classList.add('drag-over-top'); this.classList.remove('drag-over-bottom');
            } else {
                this.classList.add('drag-over-bottom'); this.classList.remove('drag-over-top');
            }
        }
        function gDragLeave(e) {
            this.classList.remove('drag-over-top', 'drag-over-bottom');
        }
        function gDrop(e) {
            e.preventDefault();
            this.classList.remove('drag-over-top', 'drag-over-bottom');
            var dropId = parseInt(this.dataset.id);
            if (dropId && dropId !== guestDragId) commitGuestReorder(dropId, e.clientY, this);
        }

        // ── Mobile Touch Drag ─────────────────────────────
        function gTouchStart(e) {
            var el = e.currentTarget;
            guestDragItem = el;

            // Cancel if touching remove button
            if (e.target.closest('.g-queue-remove')) return;

            var touch = e.touches[0];
            guestDragTouchId = touch.identifier;
            guestDragOffsetY = touch.clientY - el.getBoundingClientRect().top;
            guestDragActive = false;

            // In reorder mode, start drag immediately; otherwise use long-press
            if (guestReorderMode) {
                guestDragActive = true;
                guestDragId = parseInt(el.dataset.id);
                el.classList.add('g-drag-active');
                el.classList.add('dragging');
                guestDragGhost = buildDragGhost(el, touch);
                document.addEventListener('touchmove', gTouchMove, {passive:false});
                document.addEventListener('touchend', gTouchEnd, {passive:false});
                document.addEventListener('touchcancel', gTouchEnd, {passive:false});
                return;
            }

            // Long-press timer
            clearTimeout(guestLongPressTimer);
            guestLongPressTimer = setTimeout(function() {
                guestDragActive = true;
                guestDragId = parseInt(el.dataset.id);

                // Subtle haptic feedback
                if (navigator.vibrate) navigator.vibrate(10);

                // Lift animation
                el.classList.add('g-drag-active');
                el.classList.add('dragging');

                guestDragGhost = buildDragGhost(el, touch);

                // Bring ghost to current finger position smoothly
                requestAnimationFrame(function() {
                    if (guestDragGhost) guestDragGhost.style.transform = 'scale(1)';
                });

                document.addEventListener('touchmove', gTouchMove, {passive:false});
                document.addEventListener('touchend', gTouchEnd, {passive:false});
                document.addEventListener('touchcancel', gTouchEnd, {passive:false});
            }, 300);
        }

        function gTouchMovePre(e) {
            // Cancel long-press if finger moves too far before activation
            if (!guestDragActive && guestDragItem) {
                var touch = e.touches[0];
                var dy = Math.abs(touch.clientY - guestDragItem.getBoundingClientRect().top - guestDragOffsetY);
                if (dy > 10) {
                    clearTimeout(guestLongPressTimer);
                }
            }
        }

        function gTouchEndPre(e) {
            clearTimeout(guestLongPressTimer);
        }

        function gTouchMove(e) {
            e.preventDefault();
            var touch = getTouch(e);
            if (!touch || !guestDragGhost) return;

            // Move ghost
            guestDragGhost.style.top = (touch.clientY - 12) + 'px';

            // Auto-scroll near viewport edges
            var viewportH = window.innerHeight;
            var scrollSpeed = 0;
            if (touch.clientY < 60) scrollSpeed = -8;
            else if (touch.clientY > viewportH - 60) scrollSpeed = 8;

            if (guestAutoScroll) clearInterval(guestAutoScroll);
            if (scrollSpeed) {
                guestAutoScroll = setInterval(function() {
                    window.scrollBy(0, scrollSpeed);
                }, 16);
            } else {
                guestAutoScroll = null;
            }

            // Highlight drop target
            var list = document.getElementById('queueList');
            if (!list) return;
            var items = list.querySelectorAll('.g-queue-item');
            items.forEach(function(item) {
                item.classList.remove('drag-over-top', 'drag-over-bottom');
                if (item === guestDragItem) return;
                var r = item.getBoundingClientRect();
                if (touch.clientY >= r.top && touch.clientY <= r.bottom) {
                    item.classList.add(touch.clientY < r.top + r.height / 2 ? 'drag-over-top' : 'drag-over-bottom');
                }
            });
        }

        function gTouchEnd(e) {
            if (guestAutoScroll) { clearInterval(guestAutoScroll); guestAutoScroll = null; }
            document.removeEventListener('touchmove', gTouchMove);
            document.removeEventListener('touchend', gTouchEnd);
            document.removeEventListener('touchcancel', gTouchEnd);

            if (guestDragGhost) { guestDragGhost.remove(); guestDragGhost = null; }
            if (guestDragItem) {
                guestDragItem.classList.remove('dragging', 'g-drag-active');
            }

            var touch = getTouch(e);
            if (touch && guestDragId) {
                var target = null;
                var items = document.querySelectorAll('#queueList .g-queue-item');
                items.forEach(function(item) {
                    item.classList.remove('drag-over-top', 'drag-over-bottom');
                    if (!target) {
                        var r = item.getBoundingClientRect();
                        if (touch.clientY >= r.top && touch.clientY <= r.bottom) {
                            target = item;
                        }
                    }
                });
                if (target) {
                    var dropId = parseInt(target.dataset.id);
                    if (dropId && dropId !== guestDragId) commitGuestReorder(dropId, touch.clientY, target);
                }
            }

            guestDragId = null;
            guestDragItem = null;
            guestDragTouchId = null;
            guestDragActive = false;
        }

        function getTouch(e) {
            for (var i = 0; i < (e.changedTouches || []).length; i++) {
                if (e.changedTouches[i].identifier === guestDragTouchId) return e.changedTouches[i];
            }
            return null;
        }

        function buildDragGhost(el, touch) {
            var title = el.dataset.title || '';
            var byEl = el.querySelector('.g-queue-by');
            var by = byEl ? byEl.textContent : '';
            var ghost = document.createElement('div');
            ghost.className = 'g-drag-ghost';
            ghost.innerHTML = '<div class="ghost-title">' + escapeHtml(title) + '</div>'
                + '<div class="ghost-by">' + escapeHtml(by) + '</div>'
                + '<div class="ghost-badge">Reordering</div>';
            ghost.style.top = (touch.clientY - 12) + 'px';
            document.body.appendChild(ghost);
            requestAnimationFrame(function() {
                if (ghost) ghost.style.transform = 'scale(1)';
            });
            return ghost;
        }

        function commitGuestReorder(dropId, clientY, targetEl) {
            var items = document.querySelectorAll('#queueList .g-queue-item[draggable="true"]');
            var ids = [];
            var inserted = false;
            var rect = targetEl.getBoundingClientRect();
            var before = clientY < rect.top + rect.height / 2;

            items.forEach(function(el) {
                var id = parseInt(el.dataset.id);
                if (id === guestDragId) return;
                if (id === dropId) {
                    if (before) { ids.push(guestDragId); ids.push(id); }
                    else { ids.push(id); ids.push(guestDragId); }
                    inserted = true;
                } else { ids.push(id); }
            });
            if (!inserted) ids.push(guestDragId);

            fetch('api/reorder_queue.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_id: ROOM_ID, ordered_ids: ids })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) fetchGuestQueue();
            }).catch(function() {});
        }

        // ── Fullscreen ──────────────────────────────────
        function toggleGuestFullscreen() {
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                var el = document.documentElement;
                if (el.requestFullscreen) el.requestFullscreen();
                else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
                else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
            }
        }

        function updateGuestFsBtn() {
            var isFs = !!(document.fullscreenElement || document.webkitFullscreenElement);
            var enter = document.getElementById('guestFsEnter');
            var exit = document.getElementById('guestFsExit');
            var btn = document.getElementById('guestFsBtn');
            if (enter) enter.style.display = isFs ? 'none' : '';
            if (exit) exit.style.display = isFs ? '' : 'none';
            if (btn) btn.title = isFs ? 'Exit Full Screen' : 'Full Screen';
        }

        document.addEventListener('fullscreenchange', updateGuestFsBtn);
        document.addEventListener('webkitfullscreenchange', updateGuestFsBtn);

    </script>
    <script src="assets/js/guest_features.js?v=<?= ASSETS_VERSION ?>"></script>
</body>
</html>
