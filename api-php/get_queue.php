<?php
/**
 * API: Get Queue
 * GET /api/get_queue.php?room_id=X&token=Y
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

$room_id = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$room_id) {
    json_error('Missing room_id');
}

// If token provided, verify it's the host
if ($token) {
    $stmt = db()->prepare("SELECT id FROM rooms WHERE id = ? AND host_session_token = ?");
    $stmt->execute([$room_id, $token]);
    if (!$stmt->fetch()) {
        json_error('Unauthorized', 401);
    }
}

$show_completed = isset($_GET['completed']) && $_GET['completed'] === '1';

$status_filter = $show_completed
    ? "status IN ('completed')"
    : "status IN ('pending', 'playing')";

// Get queue
$stmt = db()->prepare("
    SELECT id, video_title, youtube_id, added_by, sort_order, status, started_at, score, created_at 
    FROM songs_queue 
    WHERE room_id = ? AND $status_filter
    ORDER BY 
        CASE status 
            WHEN 'playing' THEN 0 
            WHEN 'pending' THEN 1 
            WHEN 'completed' THEN 2 
        END,
        sort_order ASC, 
        created_at ASC
");
$stmt->execute([$room_id]);
$tracks = $stmt->fetchAll();

// Add elapsed seconds for playing tracks (server-side calculation)
$tz = new DateTimeZone('Asia/Manila');
foreach ($tracks as &$track) {
    if ($track['status'] === 'playing' && $track['started_at']) {
        $started = new DateTime($track['started_at'], $tz);
        $now = new DateTime('now', $tz);
        $track['elapsed'] = max(0, $now->getTimestamp() - $started->getTimestamp());
    } else {
        $track['elapsed'] = 0;
    }
}
unset($track);

json_success([
    'tracks' => $tracks,
    'server_time' => time(),
    'count' => count($tracks)
]);
