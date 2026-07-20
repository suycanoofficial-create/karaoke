<?php
/**
 * KTV LOUNGE - Host Room Workspace (TV/Monitor Display)
 */
require_once __DIR__ . '/config/app.php';

$code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$code || !$token) {
    header('Location: ' . BASE_URL);
    exit;
}

// Verify room exists and token matches
$stmt = db()->prepare("SELECT id, status FROM rooms WHERE room_code = ? AND host_session_token = ?");
$stmt->execute([$code, $token]);
$room = $stmt->fetch();

if (!$room || $room['status'] !== 'active') {
    header('Location: ' . BASE_URL);
    exit;
}

$room_id = $room['id'];
$yt_key = YOUTUBE_API_KEY;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Lounge — <?= sanitize(SITE_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= ASSETS_VERSION ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎤</text></svg>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        /* ── Layout ─────────────────────────────────── */
        .host-layout {
            display: flex;
            height: calc(100vh - 56px);
            margin-top: 56px;
        }

        /* ── Header ─────────────────────────────────── */
        .host-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 var(--space-lg);
            background: linear-gradient(180deg, rgba(11,12,16,0.95) 0%, rgba(11,12,16,0.85) 80%, transparent 100%);
            backdrop-filter: blur(12px);
            z-index: 9999;
            pointer-events: auto;
            height: 56px;
            gap: var(--space-md);
        }
        .host-header-left {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            flex: 1;
            min-width: 0;
        }
        .host-now-playing {
            flex: 1;
            text-align: center;
            min-width: 0;
            padding: 0 var(--space-md);
        }
        .hnp-title {
            display: inline;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #F8F9FA 0%, var(--gold) 50%, #F8F9FA 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: hnp-shimmer 4s ease-in-out infinite;
        }
        .hnp-by {
            display: inline;
            font-size: 1rem;
            font-weight: 500;
            margin-left: 0.4em;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: linear-gradient(135deg, rgba(255,255,255,0.5) 0%, var(--gold) 50%, rgba(255,255,255,0.5) 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: hnp-shimmer 4s ease-in-out infinite;
        }
        @keyframes hnp-shimmer {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }

        .host-header-center {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
            justify-content: center;
        }
        .host-header-right {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            flex-shrink: 0;
            flex: 0 0 auto;
        }
        .host-ctrl-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.8);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .host-ctrl-btn:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.2);
            color: #fff;
        }
        .host-ctrl-btn.primary {
            width: 42px;
            height: 42px;
            background: var(--gold);
            border-color: var(--gold);
            color: var(--midnight);
        }
        .host-ctrl-btn.primary:hover {
            background: #e5c04a;
            transform: scale(1.05);
        }
        .host-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.6);
        }
        .host-pill .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4ade80;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .host-room-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 14px;
            background: rgba(212,175,55,0.1);
            border: 1px solid rgba(212,175,55,0.3);
            border-radius: var(--radius-full);
            color: var(--gold);
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.15em;
        }
        .host-room-badge .label {
            font-size: 0.6rem;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            opacity: 0.6;
        }
        .host-action-btn {
            padding: 5px 14px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .host-action-btn:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .host-action-btn.danger {
            border-color: rgba(239,68,68,0.3);
            color: #ef4444;
        }
        .host-action-btn.danger:hover {
            background: rgba(239,68,68,0.15);
        }

        /* ── Video Area ─────────────────────────────── */
        .host-video-full {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--midnight);
            position: relative;
            overflow: hidden;
            min-width: 0;
        }
        .kv-grid {
            position: absolute;
            inset: 0;
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 1px;
            pointer-events: none;
            opacity: 0.7;
        }
        .kv-grid img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7) saturate(0.9);
        }
        .kv-grid img:nth-child(odd) { animation: kv-drift 30s ease-in-out infinite alternate; }
        .kv-grid img:nth-child(even) { animation: kv-drift 25s ease-in-out infinite alternate-reverse; }
        @keyframes kv-drift {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.12) translate(8px, -6px); }
        }
        .host-video-full::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 40%, rgba(212,175,55,0.12) 0%, transparent 55%),
                        radial-gradient(ellipse at 70% 60%, rgba(200,160,40,0.08) 0%, transparent 50%);
            animation: kv-pulse 6s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: 1;
        }
        @keyframes kv-pulse {
            0% { opacity: 0.4; transform: scale(1); }
            100% { opacity: 1; transform: scale(1.05); }
        }
        .host-video-full::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(11,12,16,0.2) 0%, rgba(11,12,16,0.4) 50%, rgba(11,12,16,0.7) 100%);
            pointer-events: none;
            z-index: 2;
        }
        .host-video-full .video-wrapper {
            width: 100%;
            height: 100%;
            max-width: none;
            border-radius: 0;
            position: relative;
            z-index: 3;
        }
        .host-empty-search {
            position: relative;
            max-width: 520px;
            width: 100%;
            margin: var(--space-xl) auto 0;
        }
        .host-empty-search .host-search {
            padding: 12px 20px;
            font-size: 0.95rem;
            border-radius: var(--radius-lg);
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.15);
        }
        .host-empty-search .host-search input {
            font-size: 0.95rem;
        }
        .host-empty-search .host-search-results {
            top: calc(100% + 8px);
            max-height: 400px;
        }
        .host-empty-search .host-search svg {
            width: 18px;
            height: 18px;
            opacity: 0.5;
        }
        .queue-item-remove {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.3);
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .queue-item:hover .queue-item-remove { opacity: 1; }
        .queue-item-remove:hover {
            background: rgba(239,68,68,0.15);
            border-color: rgba(239,68,68,0.3);
            color: #ef4444;
        }

        /* ── Sidebar ────────────────────────────────── */
        .host-sidebar {
            width: 360px;
            flex-shrink: 0;
            background: rgba(15, 17, 22, 0.98);
            border-left: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .sb-section {
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        /* QR */
        .sb-qr {
            text-align: center;
            padding: var(--space-md);
        }
        .sb-qr h3 {
            font-family: var(--font-display);
            font-size: 0.65rem;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: var(--space-sm);
        }
        #qrcode {
            display: inline-block;
            padding: 6px;
            background: #fff;
            border-radius: var(--radius-md);
        }
        #qrcode img { display: block; width: 120px; height: 120px; }
        .sb-room-code {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: 0.3em;
            margin-top: var(--space-xs);
        }
        /* Search */
        .sb-search {
            padding: var(--space-sm) var(--space-md);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sb-search .host-action-btn {
            flex-shrink: 0;
        }
        .sb-search .host-search {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--radius-full);
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            font-size: 0.75rem;
            transition: all 0.2s;
        }
        .sb-search .host-search:focus-within {
            border-color: rgba(212,175,55,0.4);
            background: rgba(255,255,255,0.1);
        }
        .sb-search .host-search svg { flex-shrink: 0; opacity: 0.4; }
        .sb-search .host-search:focus-within svg { opacity: 0.8; color: var(--gold); }
        .sb-search .host-search input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            color: rgba(255,255,255,0.9);
            font-size: 0.75rem;
            font-family: var(--font-body);
            min-width: 0;
        }
        .sb-search .host-search input::placeholder { color: rgba(255,255,255,0.3); }
        .sb-search .host-search-clear {
            display: none;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            cursor: pointer;
            background: rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.5);
            font-size: 0.55rem;
            line-height: 1;
            flex-shrink: 0;
        }
        .sb-search .host-search-clear:hover { background: rgba(255,255,255,0.25); color: #fff; }
        .sb-search .host-search-results {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: var(--obsidian);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-md);
            max-height: 260px;
            overflow-y: auto;
            z-index: 10001;
            box-shadow: 0 12px 40px rgba(0,0,0,0.6);
        }
        .sb-search .host-search-results.open { display: block; }
        .sb-search .host-search-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            cursor: pointer;
            transition: background 0.15s;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .sb-search .host-search-item:last-child { border-bottom: none; }
        .sb-search .host-search-item:hover { background: rgba(212,175,55,0.08); }
        .sb-search .host-search-item img {
            width: 40px;
            height: 30px;
            object-fit: cover;
            border-radius: 4px;
            flex-shrink: 0;
        }
        .sb-search .host-search-item-info { flex: 1; min-width: 0; }
        .sb-search .host-search-item-title {
            font-size: 0.72rem;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sb-search .host-search-item-channel {
            font-size: 0.62rem;
            color: rgba(255,255,255,0.35);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sb-search .host-search-item-add {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(212,175,55,0.15);
            color: var(--gold);
            border: 1px solid rgba(212,175,55,0.3);
            cursor: pointer;
            transition: all 0.2s;
        }
        .sb-search .host-search-item-add:hover {
            background: var(--gold);
            color: var(--midnight);
            border-color: var(--gold);
            transform: scale(1.1);
        }
        .host-search-loading {
            text-align: center;
            padding: 12px;
            color: rgba(255,255,255,0.3);
            font-size: 0.7rem;
        }
        .host-search-empty {
            text-align: center;
            padding: 12px;
            color: rgba(255,255,255,0.25);
            font-size: 0.7rem;
        }
        /* Actions */
        .sb-actions {
            padding: var(--space-sm) var(--space-md);
            display: flex;
            gap: var(--space-sm);
        }
        .sb-actions .host-action-btn {
            justify-content: center;
        }
        /* Queue */
        .sb-queue-header {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: var(--space-xs) var(--space-md);
            flex-shrink: 0;
        }
        .sb-queue-header .label {
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
        }
        .sb-queue-header .count {
            font-size: 0.7rem;
            color: var(--gold);
            background: rgba(212,175,55,0.12);
            padding: 2px 8px;
            border-radius: var(--radius-full);
        }
        .sb-queue-list {
            flex: 1;
            overflow-y: auto;
            padding: var(--space-xs) 0;
        }
        .sb-section-label {
            padding: var(--space-xs) var(--space-md);
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 600;
        }
        .sb-queue-empty {
            text-align: center;
            padding: var(--space-xl) 0;
            color: rgba(255,255,255,0.25);
            font-size: 0.75rem;
        }
        .sb-queue-empty .icon { font-size: 1.5rem; margin-bottom: var(--space-xs); }
        .queue-item {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-md);
            margin-bottom: 2px;
            transition: background 0.15s;
            user-select: none;
        }
        .queue-item:hover { background: rgba(255,255,255,0.03); }
        .queue-item.playing {
            background: rgba(212,175,55,0.06);
            border-left: 2px solid var(--gold);
            cursor: default;
        }
        .queue-item.dragging {
            opacity: 0.4;
            background: rgba(212,175,55,0.1);
        }
        .queue-item.drag-over-top {
            border-top: 2px solid var(--gold);
        }
        .queue-item.drag-over-bottom {
            border-bottom: 2px solid var(--gold);
        }
        .queue-item .drag-handle {
            cursor: grab;
            color: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            flex-shrink: 0;
            padding: 2px;
            border-radius: 4px;
            transition: color 0.15s;
        }
        .queue-item:hover .drag-handle { color: rgba(255,255,255,0.4); }
        .queue-item .drag-handle:active { cursor: grabbing; }
        .queue-item-number {
            width: 22px;
            text-align: center;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.3);
            flex-shrink: 0;
            font-weight: 500;
        }
        .queue-item.playing .queue-item-number { color: var(--gold); }
        .queue-item-info { flex: 1; min-width: 0; }
        .queue-item-title {
            font-size: 0.78rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .queue-item-by {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.35);
        }
        .queue-item-play {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(212,175,55,0.12);
            color: var(--gold);
            flex-shrink: 0;
        }

        /* ── Seek Bar ──────────────────────────────── */
        .seek-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 20;
            padding: 4px 12px 8px;
            background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, transparent 100%);
            cursor: pointer;
            user-select: none;
            display: flex;
            flex-direction: column;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .seek-bar.show {
            opacity: 1;
            pointer-events: auto;
        }
        .seek-bar-track {
            position: relative;
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            cursor: pointer;
            transition: height 0.15s;
        }
        .seek-bar-track:hover { height: 6px; }
        .seek-bar-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: var(--gold);
            border-radius: 2px;
            pointer-events: none;
            width: 0%;
        }
        .seek-bar-thumb {
            position: absolute;
            top: 50%;
            width: 12px;
            height: 12px;
            background: var(--gold);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s;
            box-shadow: 0 0 6px rgba(212,175,55,0.5);
        }
        .seek-bar-track:hover .seek-bar-thumb { opacity: 1; }
        .seek-bar-time {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.6);
            margin-top: 3px;
            font-variant-numeric: tabular-nums;
        }

        /* ── Embed Error ───────────────────────────── */
        .embed-error {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--midnight);
            z-index: 10;
            padding: var(--space-lg);
            text-align: center;
        }
        .embed-error .error-icon { font-size: 3rem; margin-bottom: var(--space-md); opacity: 0.5; }
        .embed-error h3 { font-size: 1.1rem; margin-bottom: var(--space-sm); color: rgba(255,255,255,0.9); }
        .embed-error p { font-size: 0.85rem; color: rgba(255,255,255,0.5); margin-bottom: var(--space-lg); max-width: 400px; }
        .embed-error .watch-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 24px; border-radius: var(--radius-full);
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.9); font-size: 0.9rem; font-weight: 500;
            cursor: pointer; transition: all 0.2s; text-decoration: none;
        }
        .embed-error .watch-btn:hover { background: rgba(255,255,255,0.15); border-color: rgba(212,175,55,0.4); color: var(--gold); }
        .embed-error .skip-link {
            margin-top: var(--space-sm); font-size: 0.8rem; color: rgba(255,255,255,0.35);
            cursor: pointer; background: none; border: none; text-decoration: underline; text-underline-offset: 2px;
        }
        .embed-error .skip-link:hover { color: #ef4444; }

        /* ── Fullscreen ────────────────────────────── */
        :fullscreen .host-layout,
        :-webkit-full-screen .host-layout {
            height: 100vh; margin-top: 0;
        }
        :fullscreen .host-video-full,
        :-webkit-full-screen .host-video-full { height: 100vh; }
        :fullscreen .host-sidebar,
        :-webkit-full-screen .host-sidebar { display: none; }
        :fullscreen .host-header,
        :-webkit-full-screen .host-header {
            background: linear-gradient(180deg, rgba(11,12,16,0.65) 0%, rgba(11,12,16,0.45) 70%, transparent 100%);
        }
        :fullscreen .host-header:hover,
        :-webkit-full-screen .host-header:hover {
            background: linear-gradient(180deg, rgba(11,12,16,0.9) 0%, rgba(11,12,16,0.6) 70%, transparent 100%);
        }

        /* ── Responsive ────────────────────────────── */
        @media (max-width: 900px) {
            .host-sidebar { width: 300px; }
        }
        .host-mobile-controls {
            display: none;
            flex-direction: column;
            gap: var(--space-md);
            padding: var(--space-md);
            background: rgba(11,12,16,0.95);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .hmc-now {
            text-align: center;
            min-width: 0;
        }
        .hmc-next {
            font-size: 0.55rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--gold);
            font-weight: 600;
            margin-bottom: 2px;
        }
        .hmc-title {
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: linear-gradient(135deg, #F8F9FA 0%, var(--gold) 50%, #F8F9FA 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: hnp-shimmer 4s ease-in-out infinite;
        }
        .hmc-singer {
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: linear-gradient(135deg, rgba(255,255,255,0.5) 0%, var(--gold) 50%, rgba(255,255,255,0.5) 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: hnp-shimmer 4s ease-in-out infinite;
        }
        .hmc-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-sm);
        }
        .hmc-btn {
            font-size: 0; padding: 0; width: 36px; height: 36px;
            border-radius: 50%; justify-content: center; flex-shrink: 0;
        }
        .hmc-btn svg { width: 15px; height: 15px; margin: 0; }
        .host-mobile-qr {
            display: none;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
            background: rgba(11,12,16,0.95);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        @media (max-width: 768px) {
            .host-layout { flex-direction: column; height: auto; margin-top: 48px; }
            .host-video-full { height: 50vh; }
            .host-sidebar { width: 100%; border-left: none; border-top: 1px solid rgba(255,255,255,0.06); max-height: 50vh; }
            .sb-qr { display: none; }
            .host-header-center { display: none !important; }
            .host-mobile-controls.show { display: flex; }
            #qrcode-mobile img { width: 160px; height: 160px; }
            .host-header { height: 48px; padding: 0 var(--space-sm); }
            .host-ctrl-btn { width: 32px; height: 32px; }
            .host-ctrl-btn.primary { width: 38px; height: 38px; }
            .host-room-badge { padding: 4px 8px; font-size: 0.7rem; gap: 4px; }
            .host-room-badge .label { display: none; }
            .host-pill { padding: 4px 10px; font-size: 0.7rem; }
            .host-action-btn { padding: 4px 10px; font-size: 0.7rem; }
            .hnp-title { font-size: 0.85rem; }
            .hnp-by { font-size: 0.85rem; margin-left: 0.3em; }
            .hmc-title { font-size: 0.85rem; }
            .hmc-singer { font-size: 0.7rem; }
        }
        @media (max-width: 480px) {
            .host-header { padding: 0 6px; height: 44px; gap: 4px; }
            .host-room-badge { padding: 3px 6px; font-size: 0.65rem; gap: 3px; letter-spacing: 0.1em; }
            .host-action-btn {
                font-size: 0; padding: 0; width: 34px; height: 34px;
                border-radius: 50%; justify-content: center; flex-shrink: 0;
            }
            .host-action-btn svg { width: 15px; height: 15px; margin: 0; }
            .host-ctrl-btn { width: 30px; height: 30px; }
            .host-ctrl-btn.primary { width: 34px; height: 34px; }
            .host-ctrl-btn svg { width: 13px; height: 13px; }
            .hnp-title { font-size: 0.78rem; }
            .hnp-by { font-size: 0.78rem; margin-left: 0.2em; }
            .host-now-playing { padding: 0 var(--space-xs); }
            .host-header-center { gap: 3px; }
        }
    </style>
</head>
<body>
    <!-- Header Overlay -->
    <div class="host-header">
        <div class="host-header-left">
            <div class="host-room-badge">
                <span class="label">Room</span>
                <span><?= sanitize($code) ?></span>
            </div>
        </div>

        <div class="host-now-playing" id="hostNowPlaying">
            <div class="hnp-title" id="hnpTitle">No track playing</div>
            <div class="hnp-by" id="hnpBy"></div>
        </div>

        <div class="host-header-center" id="hostControls" style="display:none">
            <button class="host-ctrl-btn" onclick="retryVideo()" title="Replay from start">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35A7.96 7.96 0 0012 4C7.58 4 4.01 7.58 4.01 12S7.58 20 12 20c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
            </button>
            <button class="host-ctrl-btn primary" onclick="togglePlayPause()" id="playPauseBtn" title="Play/Pause">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" id="playIcon"><path d="M8 5v14l11-7z"/></svg>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" id="pauseIcon" style="display:none"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            </button>
            <button class="host-ctrl-btn" onclick="skipTrack()" title="Skip">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
            </button>
        </div>

        <div class="host-header-right">
            <button class="host-action-btn" id="hostLockBtn" onclick="toggleRoomLock()" title="Lock/unlock queue">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" id="lockIcon"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                <span id="lockLabel">Lock</span>
            </button>
            <div class="host-pill">
                <span class="dot"></span>
                <span id="guestCount">0 guests</span>
            </div>
            <button class="host-action-btn" onclick="toggleFullscreen()" id="fullscreenBtn" title="Full Screen" aria-label="Toggle Full Screen">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="fullscreenEnterIcon"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="fullscreenExitIcon" style="display:none"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/></svg>
            </button>
            <button class="host-action-btn danger" onclick="closeRoom()" aria-label="End Session">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                End
            </button>
        </div>
    </div>

    <!-- Main Layout: Video + Sidebar -->
    <div class="host-layout">

        <!-- Video Area -->
        <div class="host-video-full" id="hostVideoFull">
            <div class="kv-grid" id="kvGrid"></div>
            <div class="video-wrapper" id="videoWrapper">
                <div class="video-placeholder" id="videoPlaceholder">
                    <div class="icon" style="font-size:3rem">🎵</div>
                    <p style="font-size:1.1rem">Waiting for the first track...</p>
                    <p class="text-small text-muted">Use the sidebar to search for songs</p>
                </div>
                <div id="youtubePlayer" style="position:absolute;top:0;left:0;width:100%;height:100%;display:none"></div>
                <div class="play-overlay" id="playOverlay" style="display:none">
                    <button class="btn btn-primary btn-icon" onclick="startPlayback()" style="width:80px;height:80px;border-radius:50%;font-size:2rem">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                    <p class="text-muted" style="margin-top:var(--space-md)">Click to start playing</p>
                </div>
                <div class="embed-error" id="embedError" style="display:none">
                    <div class="error-icon">⛔</div>
                    <h3>Video Unavailable</h3>
                    <p>This video can't be played on this site because the uploader has disabled embedding. You can watch it directly on YouTube instead.</p>
                    <a class="watch-btn" id="watchOnYtBtn" target="_blank" rel="noopener">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0C.488 3.45.029 5.804 0 12c.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0C23.512 20.55 23.971 18.196 24 12c-.029-6.185-.484-8.549-4.385-8.816zM9 16V8l8 4-8 4z"/></svg>
                        Watch on YouTube
                    </a>
                    <button class="skip-link" onclick="skipAndNext()">Skip this song</button>
                </div>
                <div class="seek-bar" id="seekBar">
                    <div class="seek-bar-track" id="seekBarTrack">
                        <div class="seek-bar-fill" id="seekBarFill"></div>
                        <div class="seek-bar-thumb" id="seekBarThumb"></div>
                    </div>
                    <div class="seek-bar-time">
                        <span id="currentTimeDisplay">0:00</span>
                        <span id="durationDisplay">0:00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Controls (hidden on desktop) -->
        <div class="host-mobile-controls" id="mobileControls">
            <div class="hmc-now" id="mobileNowPlaying">
                <div class="hmc-next">Next</div>
                <div class="hmc-title" id="mobileNowTitle">No track</div>
                <div class="hmc-singer" id="mobileNowSinger"></div>
            </div>
            <div class="hmc-bar">
                <button class="host-ctrl-btn" onclick="retryVideo()" title="Replay">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35A7.96 7.96 0 0012 4C7.58 4 4.01 7.58 4.01 12S7.58 20 12 20c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                </button>
                <button class="host-ctrl-btn primary" onclick="togglePlayPause()" id="mobilePlayPauseBtn" title="Play/Pause">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" id="mobilePlayIcon"><path d="M8 5v14l11-7z"/></svg>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" id="mobilePauseIcon" style="display:none"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </button>
                <button class="host-ctrl-btn" onclick="skipTrack()" title="Skip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                </button>
                <button class="host-action-btn hmc-btn" onclick="toggleMobileQr()" id="mobileQrBtn">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </button>
                <button class="host-action-btn hmc-btn" onclick="toggleFullscreen()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                </button>
            </div>
        </div>
        <div class="host-mobile-qr" id="mobileQrSection" style="display:none">
            <div id="qrcode-mobile"></div>
            <div class="sb-room-code" style="margin-top:var(--space-sm)"><?= sanitize($code) ?></div>
        </div>

        <!-- Right Sidebar -->
        <div class="host-sidebar">

            <!-- QR Code (hidden on mobile) -->
            <div class="sb-section sb-qr">
                <h3>Scan to Join</h3>
                <div id="qrcode"></div>
                <div class="sb-room-code"><?= sanitize($code) ?></div>
            </div>

            <!-- Search + Actions -->
            <div class="sb-section sb-search">
                <div class="host-search" style="position:relative">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="hostSearchInput" placeholder="Search songs..." oninput="hostSearch()" onkeydown="if(event.key==='Escape')closeHostSearch()" autocomplete="off">
                    <span class="host-search-clear" id="hostSearchClear" onclick="clearHostSearch()">✕</span>
                    <div class="host-search-results" id="hostSearchResults"></div>
                </div>
                <button class="host-action-btn" onclick="clearQueue()" aria-label="Clear Queue">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </div>

            <!-- Queue -->
            <div class="sb-queue-header">
                <span class="count" id="queueCount">0</span>
            </div>
            <div class="sb-queue-list" id="queueList">
                <div class="sb-queue-empty" id="queueEmpty">
                    <div class="icon">🎶</div>
                    <p>No songs yet</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        const ROOM_CODE = '<?= sanitize($code) ?>';
        const HOST_TOKEN = '<?= sanitize($token) ?>';
        const ROOM_ID = <?= $room_id ?>;
        const YT_KEY = '<?= sanitize($yt_key) ?>';
        let player = null;
        let currentTrack = null;
        let pollInterval = null;
        let pendingVideoId = null;
        let playerReady = false;
        let seekBarInterval = null;
        let isSeeking = false;

        // ── YouTube IFrame API ──────────────────────────
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(tag);

        window.onYouTubeIframeAPIReady = function() {
            player = new YT.Player('youtubePlayer', {
                height: '100%',
                width: '100%',
                playerVars: {
                    autoplay: 1,
                    controls: 0,
                    modestbranding: 1,
                    rel: 0,
                    enablejsapi: 1,
                    origin: window.location.origin
                },
                events: {
                    onReady: function() {
                        playerReady = true;
                        if (pendingVideoId) {
                            doPlayVideo(pendingVideoId);
                            pendingVideoId = null;
                        }
                    },
                    onStateChange: onPlayerStateChange,
                    onError: onPlayerError
                }
            });
        };

        function onPlayerStateChange(event) {
            if (event.data === YT.PlayerState.ENDED) {
                stopSeekBarUpdater();
                markCompleted();
            } else if (event.data === YT.PlayerState.PLAYING) {
                startSeekBarUpdater();
                document.getElementById('playIcon').style.display = 'none';
                document.getElementById('pauseIcon').style.display = '';
            } else if (event.data === YT.PlayerState.PAUSED) {
                stopSeekBarUpdater();
            }
        }

        function onPlayerError(event) {
            if (event.data === 101 || event.data === 150) {
                showEmbedError();
            }
        }

        function showEmbedError() {
            if (!currentTrack) return;
            document.getElementById('videoPlaceholder').style.display = 'none';
            document.getElementById('youtubePlayer').style.display = 'none';
            document.getElementById('playOverlay').style.display = 'none';
            document.getElementById('seekBar').style.display = 'none';
            stopSeekBarUpdater();
            document.getElementById('hostControls').style.display = 'flex';
            var mc = document.getElementById('mobileControls');
            if (mc) { mc.classList.add('show'); if (window.innerWidth <= 768) document.getElementById('hostNowPlaying').style.display = 'none'; }
            document.getElementById('embedError').style.display = 'flex';
            document.getElementById('watchOnYtBtn').href = 'https://youtube.com/watch?v=' + encodeURIComponent(currentTrack.youtube_id);
        }

        function hideEmbedError() {
            document.getElementById('embedError').style.display = 'none';
        }

        function skipAndNext() {
            hideEmbedError();
            skipTrack();
        }

        function formatTime(s) {
            if (!s || isNaN(s)) return '0:00';
            var m = Math.floor(s / 60);
            var sec = Math.floor(s % 60);
            return m + ':' + (sec < 10 ? '0' : '') + sec;
        }

        function startSeekBarUpdater() {
            stopSeekBarUpdater();
            seekBarInterval = setInterval(function() {
                if (!player || !player.getCurrentTime || isSeeking) return;
                try {
                    var ct = player.getCurrentTime();
                    var dur = player.getDuration();
                    document.getElementById('currentTimeDisplay').textContent = formatTime(ct);
                    document.getElementById('durationDisplay').textContent = formatTime(dur);
                    var pct = dur > 0 ? (ct / dur) * 100 : 0;
                    document.getElementById('seekBarFill').style.width = pct + '%';
                    document.getElementById('seekBarThumb').style.left = pct + '%';
                } catch(e) {}
            }, 250);
        }

        function stopSeekBarUpdater() {
            if (seekBarInterval) {
                clearInterval(seekBarInterval);
                seekBarInterval = null;
            }
        }

        function doPlayVideo(youtubeId) {
            document.getElementById('videoPlaceholder').style.display = 'none';
            hideEmbedError();
            var ytDiv = document.getElementById('youtubePlayer');
            ytDiv.style.display = 'block';
            document.getElementById('hostControls').style.display = 'flex';
            var mc = document.getElementById('mobileControls');
            if (mc) { mc.classList.add('show'); if (window.innerWidth <= 768) document.getElementById('hostNowPlaying').style.display = 'none'; }
            document.getElementById('playOverlay').style.display = 'none';
            document.getElementById('seekBar').style.display = 'flex';
            document.getElementById('seekBar').classList.remove('show');
            document.getElementById('seekBarFill').style.width = '0%';
            showSeekBarTemporarily();
            document.getElementById('seekBarThumb').style.left = '0%';
            document.getElementById('currentTimeDisplay').textContent = '0:00';

            if (player && playerReady && typeof player.loadVideoById === 'function') {
                player.loadVideoById(youtubeId);
            } else {
                ytDiv.innerHTML = '<iframe src="https://www.youtube.com/embed/' + youtubeId + '?autoplay=1&rel=0&modestbranding=1&controls=0&origin=' + encodeURIComponent(window.location.origin) + '" style="width:100%;height:100%;border:none" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
            }
        }

        function playVideo(youtubeId) {
            if (!player || !playerReady) {
                pendingVideoId = youtubeId;
                setTimeout(function() {
                    if (pendingVideoId) {
                        doPlayVideo(pendingVideoId);
                        pendingVideoId = null;
                    }
                }, 1000);
                return;
            }
            doPlayVideo(youtubeId);
        }

        function startPlayback() {
            document.getElementById('playOverlay').style.display = 'none';
            if (pendingVideoId) {
                doPlayVideo(pendingVideoId);
                pendingVideoId = null;
            } else if (player && playerReady) {
                player.playVideo();
            }
        }

        function togglePlayPause() {
            if (!player) return;
            if (player.getPlayerState() === YT.PlayerState.PLAYING) {
                player.pauseVideo();
                document.getElementById('playIcon').style.display = '';
                document.getElementById('pauseIcon').style.display = 'none';
                var mp = document.getElementById('mobilePlayIcon');
                var mp2 = document.getElementById('mobilePauseIcon');
                if (mp) mp.style.display = '';
                if (mp2) mp2.style.display = 'none';
            } else {
                player.playVideo();
                document.getElementById('playIcon').style.display = 'none';
                document.getElementById('pauseIcon').style.display = '';
                var mp = document.getElementById('mobilePlayIcon');
                var mp2 = document.getElementById('mobilePauseIcon');
                if (mp) mp.style.display = 'none';
                if (mp2) mp2.style.display = '';
            }
        }

        function retryVideo() {
            if (currentTrack && currentTrack.youtube_id) {
                doPlayVideo(currentTrack.youtube_id);
            }
        }

        async function resetPlayer() {
            if (player && typeof player.stopVideo === 'function') {
                player.stopVideo();
            }
            document.getElementById('videoPlaceholder').style.display = 'flex';
            document.getElementById('youtubePlayer').style.display = 'none';
            document.getElementById('hostControls').style.display = 'none';
            var mc = document.getElementById('mobileControls');
            if (mc) { mc.classList.remove('show'); if (window.innerWidth <= 768) document.getElementById('hostNowPlaying').style.display = ''; }
            document.getElementById('playOverlay').style.display = 'none';
            hideEmbedError();
            document.getElementById('seekBar').style.display = 'none';
            stopSeekBarUpdater();
            currentTrack = null;
        }

        async function skipTrack() {
            if (!currentTrack) return;
            await fetch('api/update_playback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'skip', track_id: currentTrack.id, room_id: ROOM_ID, token: HOST_TOKEN })
            });
            currentTrack = null;
            fetchQueue();
        }

        async function markCompleted() {
            if (!currentTrack) return;
            var completedId = currentTrack.id;
            currentTrack = null;
            await fetch('api/update_playback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'complete', track_id: completedId, room_id: ROOM_ID, token: HOST_TOKEN })
            });
            fetchQueue();
        }

        async function clearQueue() {
            showConfirmModal('🗑️', 'Clear Queue', 'Remove all pending songs from the queue?', async function() {
                await fetch('api/update_playback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'clear', room_id: ROOM_ID, token: HOST_TOKEN })
                });
                fetchQueue();
            });
        }

        async function closeRoom() {
            showConfirmModal('⚠️', 'End Session?', 'All queued songs will be lost. This cannot be undone.', async function() {
                await fetch('api/update_playback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'close', room_id: ROOM_ID, token: HOST_TOKEN })
                });
                window.location.href = '<?= BASE_URL ?>';
            });
        }

        async function fetchQueue() {
            try {
                const res = await fetch('api/get_queue.php?room_id=' + ROOM_ID + '&token=' + encodeURIComponent(HOST_TOKEN));
                const data = await res.json();

                if (!data.success) return;

                const list = document.getElementById('queueList');
                const empty = document.getElementById('queueEmpty');
                const count = document.getElementById('queueCount');

                const tracks = data.tracks || [];
                const playing = tracks.find(t => t.status === 'playing');
                const pending = tracks.filter(t => t.status === 'pending');
                count.textContent = pending.length + ' Song' + (pending.length !== 1 ? 's' : '');

                // Update next song header
                const hnpTitle = document.getElementById('hnpTitle');
                const hnpBy = document.getElementById('hnpBy');
                const mobileTitle = document.getElementById('mobileNowTitle');
                const mobileSinger = document.getElementById('mobileNowSinger');
                if (pending.length > 0) {
                    hnpTitle.textContent = 'Next : ' + pending[0].video_title;
                    hnpBy.textContent = 'SINGER : ' + pending[0].added_by;
                    if (mobileTitle) mobileTitle.textContent = pending[0].video_title;
                    if (mobileSinger) mobileSinger.textContent = 'SINGER : ' + pending[0].added_by;
                } else if (playing) {
                    hnpTitle.textContent = 'Now : ' + playing.video_title;
                    hnpBy.textContent = 'SINGER : ' + playing.added_by;
                    if (mobileTitle) mobileTitle.textContent = playing.video_title;
                    if (mobileSinger) mobileSinger.textContent = 'SINGER : ' + playing.added_by;
                } else {
                    hnpTitle.textContent = 'No track in queue';
                    hnpBy.textContent = '';
                    if (mobileTitle) mobileTitle.textContent = 'No track';
                    if (mobileSinger) mobileSinger.textContent = '';
                }

                if (tracks.length === 0) {
                    empty.style.display = '';
                    list.innerHTML = '';
                    list.appendChild(empty);
                    resetPlayer();
                    return;
                }

                empty.style.display = 'none';

                let html = '';

                if (playing) {
                    html += '<div class="sb-section-label" style="padding:var(--space-xs) var(--space-md);font-size:0.6rem;text-transform:uppercase;letter-spacing:0.15em;font-weight:600;color:var(--gold)">Playing Now</div>';
                    html += `
                        <div class="queue-item playing" data-id="${playing.id}" data-title="${escapeAttr(playing.video_title)}" data-status="playing">
                            <div class="queue-item-number" style="color:var(--gold)">▶</div>
                            <div class="queue-item-info">
                                <div class="queue-item-title">${escapeHtml(playing.video_title)}</div>
                                <div class="queue-item-by">Added by ${escapeHtml(playing.added_by)}</div>
                            </div>
                        </div>
                    `;
                }
                if (pending.length > 0) {
                    html += '<div class="sb-section-label" style="padding:var(--space-xs) var(--space-md);font-size:0.6rem;text-transform:uppercase;letter-spacing:0.15em;font-weight:600;color:rgba(255,255,255,0.35)">Up Next</div>';
                    pending.forEach((track, i) => {
                        html += `
                            <div class="queue-item" data-id="${track.id}" data-title="${escapeAttr(track.video_title)}" data-status="pending" draggable="true">
                                <span class="drag-handle" draggable="false"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg></span>
                                <div class="queue-item-number">${i + 1}</div>
                                <div class="queue-item-info">
                                    <div class="queue-item-title">${escapeHtml(track.video_title)}</div>
                                    <div class="queue-item-by">Added by ${escapeHtml(track.added_by)}</div>
                                </div>
                                <div class="queue-item-play"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>
                                <div class="queue-item-remove" data-id="${track.id}" data-title="${escapeHtml(track.video_title)}" title="Remove">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </div>
                            </div>
                        `;
                    });
                }
                list.innerHTML = html;
                list.appendChild(empty);
                initDragDrop();

                if (playing) {
                    if (!currentTrack || currentTrack.id !== playing.id) {
                        currentTrack = playing;
                        playVideo(playing.youtube_id);
                    }
                } else {
                    var firstPending = tracks.find(function(t) { return t.status === 'pending'; });
                    if (firstPending && (!currentTrack || currentTrack.id !== firstPending.id)) {
                        startTrack(firstPending.id);
                    } else {
                        currentTrack = null;
                    }
                }

            } catch (err) {
                console.error('Queue fetch error:', err);
            }
        }

        async function startTrack(trackId) {
            await fetch('api/update_playback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'play', track_id: trackId, room_id: ROOM_ID, token: HOST_TOKEN })
            });

            var queueRes = await fetch('api/get_queue.php?room_id=' + ROOM_ID + '&token=' + encodeURIComponent(HOST_TOKEN));
            var queueData = await queueRes.json();
            if (queueData.success) {
                var track = queueData.tracks.find(function(t) { return t.status === 'playing'; });
                if (track) {
                    currentTrack = track;
                    playVideo(track.youtube_id);
                }
            }
            fetchQueue();
        }

        function escapeAttr(text) {
            if (!text) return '';
            return text.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
        }

        // Event delegation for queue items
        document.getElementById('queueList').addEventListener('click', function(e) {
            var item = e.target.closest('.queue-item');
            if (!item) return;
            var status = item.getAttribute('data-status');
            if (status !== 'pending') return;

            var trackId = parseInt(item.getAttribute('data-id'));
            var title = item.getAttribute('data-title');
            showConfirmModal('▶', 'Play "' + title + '"?', 'This track will start playing now.', function() {
                startTrack(trackId);
            }, 'btn-primary');
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showToast(message, type) {
            var existing = document.querySelector('.toast-notification');
            if (existing) existing.remove();
            var toast = document.createElement('div');
            toast.className = 'toast-notification toast-' + (type || 'info');
            toast.textContent = message;
            toast.style.cssText = 'position:fixed;top:80px;left:50%;transform:translateX(-50%);padding:12px 24px;border-radius:8px;z-index:99999;font-size:0.9rem;font-weight:500;animation:fadeIn 0.3s;max-width:90vw;text-align:center;pointer-events:none;';
            if (type === 'success') {
                toast.style.background = 'rgba(212,175,55,0.95)';
                toast.style.color = '#0B0C10';
            } else if (type === 'error') {
                toast.style.background = 'rgba(220,50,50,0.95)';
                toast.style.color = '#fff';
            } else {
                toast.style.background = 'rgba(31,40,51,0.95)';
                toast.style.color = '#F8F9FA';
                toast.style.border = '1px solid rgba(212,175,55,0.3)';
            }
            document.body.appendChild(toast);
            setTimeout(function() { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; }, 2500);
            setTimeout(function() { toast.remove(); }, 3000);
        }

        // ── Initialisation ───────────────────────────────
        (function populateKvGrid() {
            var ids = ['fJ9rUzIMcZQ','kJQP7kiw5Fk','JGwWNGJdvx8','RgKAFK5djSk','lp-EO5I60KA','60ItHLz5WEA','oRdxUFDoQe0','CevxZvSJLk8','YQHsXMglC9A','hT_nvWreIhg','v2AC41dglnM','pRpeEdMmmQ0','dQw4w9WgXcQ','q6EoEwdQm0g','9bZkp7q19f0','OPf0YbXqDm0','kXYiU_JCYtU','LjhCEhWiKXk','M11SvDtPBhA','iik25zYfAE4','JGwWNGJdvx8','RgKAFK5djSk','YQHsXMglC9A','hT_nvWreIhg'];
            var grid = document.getElementById('kvGrid');
            ids.forEach(function(id) {
                var img = document.createElement('img');
                img.src = 'https://img.youtube.com/vi/' + id + '/mqdefault.jpg';
                img.alt = '';
                grid.appendChild(img);
            });
        })();

        // Generate QR code immediately (always visible in sidebar)
        (function() {
            var joinUrl = '<?= get_site_url() ?>' + '<?= BASE_URL ?>guest.php?code=<?= urlencode($code) ?>';
            new QRCode(document.getElementById('qrcode'), {
                text: joinUrl,
                width: 120,
                height: 120,
                colorDark: '#0B0C10',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
            try {
                var mobileQr = document.getElementById('qrcode-mobile');
                if (mobileQr) {
                    new QRCode(mobileQr, {
                        text: joinUrl,
                        width: 160,
                        height: 160,
                        colorDark: '#0B0C10',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            } catch(e) {}
        })();

        function toggleMobileQr() {
            var el = document.getElementById('mobileQrSection');
            var btn = document.getElementById('mobileQrBtn');
            if (el) {
                var show = el.style.display !== 'flex';
                el.style.display = show ? 'flex' : 'none';
                btn.classList.toggle('active', show);
            }
        }

        fetchQueue();
        pollInterval = setInterval(fetchQueue, 5000);

        // Guest count polling
        async function fetchGuestCount() {
            try {
                var res = await fetch('api/get_guest_count.php?room_id=' + ROOM_ID + '&token=' + encodeURIComponent(HOST_TOKEN));
                var data = await res.json();
                if (data.success) {
                    var c = data.count;
                    document.getElementById('guestCount').textContent = c + ' guest' + (c !== 1 ? 's' : '');
                }
            } catch(e) {}
        }
        fetchGuestCount();
        setInterval(fetchGuestCount, 15000);

        fetchRoomLock();
        fetchCheerReactions();
        setInterval(fetchCheerReactions, 8000);
        initAudioRelayReceiver();
        pollGuestPlaybackCmd();
        setInterval(pollGuestPlaybackCmd, 5000);

        // ── Fullscreen ──────────────────────────────────
        function toggleFullscreen() {
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                var el = document.documentElement;
                if (el.requestFullscreen) {
                    el.requestFullscreen();
                } else if (el.webkitRequestFullscreen) {
                    el.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        }

        function updateFullscreenBtn() {
            var isFs = !!(document.fullscreenElement || document.webkitFullscreenElement);
            document.getElementById('fullscreenEnterIcon').style.display = isFs ? 'none' : '';
            document.getElementById('fullscreenExitIcon').style.display = isFs ? '' : 'none';
            document.getElementById('fullscreenBtn').title = isFs ? 'Exit Full Screen' : 'Full Screen';
        }

        document.addEventListener('fullscreenchange', updateFullscreenBtn);
        document.addEventListener('webkitfullscreenchange', updateFullscreenBtn);

        // ── Seek Bar ─────────────────────────────────────
        document.getElementById('seekBarTrack').addEventListener('click', function(e) {
            if (!player || !player.seekTo) return;
            var rect = this.getBoundingClientRect();
            var pct = (e.clientX - rect.left) / rect.width;
            isSeeking = true;
            player.seekTo(pct * player.getDuration(), true);
            document.getElementById('seekBarFill').style.width = (pct * 100) + '%';
            document.getElementById('seekBarThumb').style.left = (pct * 100) + '%';
            setTimeout(function() { isSeeking = false; }, 100);
        });

        let seekBarHideTimer = null;
        var seekBarEl = document.getElementById('seekBar');

        function showSeekBarTemporarily() {
            if (seekBarEl.style.display === 'none') return;
            seekBarEl.classList.add('show');
            clearTimeout(seekBarHideTimer);
            seekBarHideTimer = setTimeout(function() {
                seekBarEl.classList.remove('show');
            }, 3000);
        }

        document.addEventListener('mousemove', function(e) {
            if (seekBarEl.style.display === 'none') return;
            var hv = document.querySelector('.host-video-full');
            if (!hv) return;
            var rect = hv.getBoundingClientRect();
            if (e.clientX >= rect.left && e.clientX <= rect.right &&
                e.clientY >= rect.top && e.clientY <= rect.bottom) {
                showSeekBarTemporarily();
            }
        });

        seekBarEl.addEventListener('mouseenter', function() {
            clearTimeout(seekBarHideTimer);
        });
        seekBarEl.addEventListener('mouseleave', function() {
            seekBarHideTimer = setTimeout(function() {
                seekBarEl.classList.remove('show');
            }, 3000);
        });

        // ── Host Search (Sidebar) ────────────────────────
        var hostSearchTimer = null;

        function hostSearch() {
            var input = document.getElementById('hostSearchInput');
            var clear = document.getElementById('hostSearchClear');
            var results = document.getElementById('hostSearchResults');
            var q = input.value.trim();

            clear.style.display = q ? 'flex' : 'none';

            if (q.length < 2) {
                results.classList.remove('open');
                results.innerHTML = '';
                return;
            }

            clearTimeout(hostSearchTimer);
            hostSearchTimer = setTimeout(async function() {
                results.innerHTML = '<div class="host-search-loading">Searching...</div>';
                results.classList.add('open');
                try {
                    var res = await fetch('api/search_songs.php?q=' + encodeURIComponent(q));
                    var data = await res.json();
                    if (data.success && data.results.length > 0) {
                        results.innerHTML = data.results.map(function(r) {
                            return '<div class="host-search-item" onclick="hostAddSong(\'' + r.id.replace(/'/g, "\\'") + '\', \'' + r.title.replace(/'/g, "\\'").replace(/"/g, '&quot;') + '\')">' +
                                '<img src="' + r.thumbnail + '" alt="" loading="lazy">' +
                                '<div class="host-search-item-info">' +
                                    '<div class="host-search-item-title">' + escHtml(r.title) + '</div>' +
                                    '<div class="host-search-item-channel">' + escHtml(r.channel) + '</div>' +
                                '</div>' +
                                '<button class="host-search-item-add" onclick="event.stopPropagation();hostAddSong(\'' + r.id.replace(/'/g, "\\'") + '\', \'' + r.title.replace(/'/g, "\\'").replace(/"/g, '&quot;') + '\')" title="Add to queue">' +
                                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                                '</button>' +
                            '</div>';
                        }).join('');
                    } else {
                        results.innerHTML = '<div class="host-search-empty">No results found</div>';
                    }
                } catch(e) {
                    results.innerHTML = '<div class="host-search-empty">Search failed</div>';
                }
            }, 400);
        }

        function clearHostSearch() {
            var input = document.getElementById('hostSearchInput');
            var clear = document.getElementById('hostSearchClear');
            var results = document.getElementById('hostSearchResults');
            if (input) input.value = '';
            if (clear) clear.style.display = 'none';
            if (results) results.classList.remove('open');
        }

        function closeHostSearch() {
            var input = document.getElementById('hostSearchInput');
            var results = document.getElementById('hostSearchResults');
            if (results) results.classList.remove('open');
            if (input) input.blur();
            clearHostSearch();
        }

        async function hostAddSong(youtubeId, title) {
            try {
                var res = await fetch('api/add_to_queue.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        room_id: ROOM_ID,
                        youtube_id: youtubeId,
                        video_title: title,
                        added_by: 'Host'
                    })
                });
                var data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    closeHostSearch();
                } else {
                    showToast(data.error || 'Failed to add song', 'error');
                }
            } catch(e) { showToast('Connection error', 'error'); }
        }

        function escHtml(s) {
            var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' };
            return String(s).replace(/[&<>"]/g, function(m) { return map[m]; });
        }

        // Close sidebar search results on click outside
        document.addEventListener('click', function(e) {
            var sbSearch = document.querySelector('.sb-search');
            if (sbSearch && !sbSearch.contains(e.target)) {
                var results = document.getElementById('hostSearchResults');
                if (results) results.classList.remove('open');
            }
        });

        // Remove song from queue (with confirmation)
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.queue-item-remove');
            if (!btn) return;
            e.stopPropagation();
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
                        fetchQueue();
                    } else {
                        showToast(data.error || 'Failed to remove', 'error');
                    }
                } catch(e) { showToast('Connection error', 'error'); }
            });
        }

        // ── Room Locker ──────────────────────────────────
        async function toggleRoomLock() {
            try {
                var res = await fetch('api/toggle_lock.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: ROOM_ID, token: HOST_TOKEN })
                });
                var data = await res.json();
                if (data.success) {
                    updateLockBtn(data.locked);
                    showToast(data.message, 'success');
                }
            } catch(e) {}
        }

        function updateLockBtn(locked) {
            var btn = document.getElementById('hostLockBtn');
            var label = document.getElementById('lockLabel');
            var icon = document.getElementById('lockIcon');
            if (locked) {
                btn.classList.add('danger');
                label.textContent = 'Locked';
                icon.innerHTML = '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/><circle cx="12" cy="16" r="1"/>';
            } else {
                btn.classList.remove('danger');
                label.textContent = 'Lock';
                icon.innerHTML = '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>';
            }
        }

        async function fetchRoomLock() {
            try {
                var res = await fetch('api/room_status.php?room_id=' + ROOM_ID);
                var data = await res.json();
                if (data.success) updateLockBtn(data.locked);
            } catch(e) {}
        }

        // ── Cheer & Reaction Receiver ─────────────────────
        var hostLastCheerId = 0;
        var hostLastReactionId = 0;

        async function fetchCheerReactions() {
            try {
                var [cheerRes, reactRes] = await Promise.all([
                    fetch('api/get_cheers.php?room_id=' + ROOM_ID + '&since=' + hostLastCheerId),
                    fetch('api/get_reactions.php?room_id=' + ROOM_ID + '&since=' + hostLastReactionId)
                ]);
                var cheerData = await cheerRes.json();
                var reactData = await reactRes.json();

                if (cheerData.success && cheerData.cheers) {
                    cheerData.cheers.forEach(function(c) {
                        hostLastCheerId = Math.max(hostLastCheerId, parseInt(c.id));
                        showCheerToast(c);
                    });
                }
                if (reactData.success && reactData.reactions) {
                    reactData.reactions.forEach(function(r) {
                        hostLastReactionId = Math.max(hostLastReactionId, parseInt(r.id));
                        showReactionToast(r);
                    });
                }
            } catch(e) {}
        }

        function showCheerToast(c) {
            var nick = c.from_nick || 'Someone';
            var icon = '';
            switch (c.type) {
                case 'applause': icon = '👏'; break;
                case 'cheer': icon = '🎉'; break;
                case 'airhorn': icon = '📯'; break;
                case 'boo': icon = '👎'; break;
                case 'laugh': icon = '😂'; break;
                case 'drums': icon = '🥁'; break;
                default: icon = '🎵';
            }
            showToast(icon + ' ' + nick + ' sent ' + c.type + '!', 'info');
        }

        function showReactionToast(r) {
            var emoji = '';
            switch (r.type) {
                case 'fire': emoji = '🔥'; break;
                case 'crown': emoji = '👑'; break;
                case 'heart': emoji = '❤️'; break;
                case 'clap': emoji = '👏'; break;
                case 'rocket': emoji = '🚀'; break;
                case 'fireworks': emoji = '🎆'; break;
                case '100': emoji = '💯'; break;
                case 'poop': emoji = '💩'; break;
                default: emoji = '✨';
            }
            // Floating reaction animation
            var el = document.createElement('div');
            el.textContent = emoji;
            el.style.cssText = 'position:fixed;z-index:99998;font-size:2.5rem;pointer-events:none;animation:reaction-float 2s ease-out forwards;bottom:40%;left:' + (20 + Math.random() * 60) + '%;';
            document.body.appendChild(el);
            setTimeout(function() { el.remove(); }, 2000);
        }

        // ── Guest Playback Control (play/pause from guest) ─
        async function pollGuestPlaybackCmd() {
            try {
                var res = await fetch('api/guest_control.php?room_id=' + ROOM_ID);
                var data = await res.json();
                if (data.success && data.action) {
                    if (data.action === 'pause' && player && player.getPlayerState() === YT.PlayerState.PLAYING) {
                        togglePlayPause();
                    } else if (data.action === 'play' && player && player.getPlayerState() !== YT.PlayerState.PLAYING) {
                        togglePlayPause();
                    }
                }
            } catch(e) {}
        }

        // ── Audio Relay Receiver (Phone Mic) ─────────────
        var hostAudioCtx = null;
        var hostAudioRelayTimer = null;
        var hostAudioBuffer = [];
        var hostAudioGain = null;

        function initAudioRelayReceiver() {
            try {
                hostAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                hostAudioGain = hostAudioCtx.createGain();
                hostAudioGain.gain.value = 0.6;
                hostAudioGain.connect(hostAudioCtx.destination);
            } catch(e) { return; }
            hostAudioRelayTimer = setInterval(fetchAudioChunks, 1000);
        }

        async function fetchAudioChunks() {
            if (!hostAudioCtx) return;
            try {
                var res = await fetch('api/audio_relay.php?action=receive&room_id=' + ROOM_ID);
                var data = await res.json();
                if (data.success && data.chunks && data.chunks.length > 0) {
                    data.chunks.forEach(function(chunk) {
                        playAudioChunk(chunk.data);
                    });
                }
            } catch(e) {}
        }

        function playAudioChunk(base64) {
            if (!hostAudioCtx) return;
            var binary = atob(base64);
            var len = binary.length;
            var buf = new ArrayBuffer(len);
            var view = new Uint8Array(buf);
            for (var i = 0; i < len; i++) view[i] = binary.charCodeAt(i);
            try {
                hostAudioCtx.decodeAudioData(buf, function(audioBuf) {
                    var src = hostAudioCtx.createBufferSource();
                    src.buffer = audioBuf;
                    src.connect(hostAudioGain);
                    src.start();
                }, function() {});
            } catch(e) {}
        }

        // ── Reaction Floating Animation ──────────────────
        var styleSheet = document.createElement('style');
        styleSheet.textContent = `
            @keyframes reaction-float {
                0% { transform: translateY(0) scale(0.5); opacity: 1; }
                50% { transform: translateY(-80px) scale(1.2); opacity: 1; }
                100% { transform: translateY(-180px) scale(0.8); opacity: 0; }
            }
        `;
        document.head.appendChild(styleSheet);

        // ── Drag & Drop Reorder ──────────────────────────
        var dragSrcId = null;

        function initDragDrop() {
            document.querySelectorAll('.queue-item[draggable="true"]').forEach(function(el) {
                el.removeEventListener('dragstart', onDragStart);
                el.removeEventListener('dragend', onDragEnd);
                el.removeEventListener('dragenter', onDragEnter);
                el.removeEventListener('dragover', onDragOver);
                el.removeEventListener('dragleave', onDragLeave);
                el.removeEventListener('drop', onDrop);
                el.addEventListener('dragstart', onDragStart);
                el.addEventListener('dragend', onDragEnd);
                el.addEventListener('dragenter', onDragEnter);
                el.addEventListener('dragover', onDragOver);
                el.addEventListener('dragleave', onDragLeave);
                el.addEventListener('drop', onDrop);
            });
        }

        function onDragStart(e) {
            dragSrcId = parseInt(this.dataset.id);
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
        }

        function onDragEnd(e) {
            this.classList.remove('dragging');
            document.querySelectorAll('.queue-item').forEach(function(el) {
                el.classList.remove('drag-over-top', 'drag-over-bottom');
            });
        }

        function onDragEnter(e) {
            e.preventDefault();
            var status = this.dataset.status;
            if (status !== 'pending') return;
            var rect = this.getBoundingClientRect();
            var mid = rect.top + rect.height / 2;
            if (e.clientY < mid) {
                this.classList.add('drag-over-top');
                this.classList.remove('drag-over-bottom');
            } else {
                this.classList.add('drag-over-bottom');
                this.classList.remove('drag-over-top');
            }
        }

        function onDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }

        function onDragLeave(e) {
            this.classList.remove('drag-over-top', 'drag-over-bottom');
        }

        function onDrop(e) {
            e.preventDefault();
            this.classList.remove('drag-over-top', 'drag-over-bottom');
            var dropStatus = this.dataset.status;
            if (dropStatus !== 'pending') return;
            var dropId = parseInt(this.dataset.id);
            if (dropId === dragSrcId) return;

            var items = document.querySelectorAll('.queue-item[data-status="pending"]');
            var ids = [];
            var draggedInserted = false;
            var rect = this.getBoundingClientRect();
            var mid = rect.top + rect.height / 2;
            var insertBefore = e.clientY < mid;

            items.forEach(function(el) {
                var id = parseInt(el.dataset.id);
                if (id === dragSrcId) return;
                if (id === dropId) {
                    if (insertBefore) {
                        ids.push(dragSrcId);
                        ids.push(id);
                    } else {
                        ids.push(id);
                        ids.push(dragSrcId);
                    }
                    draggedInserted = true;
                } else {
                    ids.push(id);
                }
            });

            if (!draggedInserted) ids.push(dragSrcId);

            fetch('api/update_playback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reorder', room_id: ROOM_ID, token: HOST_TOKEN, ordered_ids: ids })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    fetchQueue();
                }
            }).catch(function() {});
        }

    </script>

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
        function showConfirmModal(icon, title, message, onConfirm, btnClass) {
            document.getElementById('confirmModalIcon').textContent = icon;
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalMessage').textContent = message;
            document.getElementById('confirmModal').classList.add('active');

            var btn = document.getElementById('confirmModalAction');
            var newBtn = btn.cloneNode(true);
            newBtn.className = 'btn ' + (btnClass || 'btn-danger');
            newBtn.textContent = 'Confirm';
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
</body>
</html>
