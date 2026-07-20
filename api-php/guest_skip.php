<?php
/**
 * API: Guest Skip Song
 * POST /api/guest_skip.php
 * Body: { room_code }
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_error('Invalid request body');
}

$code = isset($input['room_code']) ? strtoupper(trim($input['room_code'])) : '';

if (!$code) {
    json_error('Missing room code');
}

// Find room
$stmt = db()->prepare("SELECT id FROM rooms WHERE room_code = ? AND status = 'active'");
$stmt->execute([$code]);
$room = $stmt->fetch();

if (!$room) {
    json_error('Room not found');
}

$room_id = (int) $room['id'];

try {
    // Find currently playing track
    $stmt = db()->prepare("SELECT id, added_by FROM songs_queue WHERE room_id = ? AND status = 'playing' ORDER BY started_at DESC LIMIT 1");
    $stmt->execute([$room_id]);
    $current = $stmt->fetch();

    if (!$current) {
        json_error('No track is currently playing');
    }

    // Skip the current track
    $stmt = db()->prepare("UPDATE songs_queue SET status = 'skipped' WHERE id = ? AND room_id = ?");
    $stmt->execute([$current['id'], $room_id]);

    // Auto-play next pending
    $stmt = db()->prepare("
        SELECT id FROM songs_queue 
        WHERE room_id = ? AND status = 'pending' 
        ORDER BY sort_order ASC, created_at ASC 
        LIMIT 1
    ");
    $stmt->execute([$room_id]);
    $next = $stmt->fetch();

    if ($next) {
        $time = date('Y-m-d H:i:s');
        $stmt = db()->prepare("UPDATE songs_queue SET status = 'playing', started_at = ? WHERE id = ?");
        $stmt->execute([$time, $next['id']]);
        json_success(['message' => 'Track skipped', 'next_id' => (int) $next['id']]);
    } else {
        json_success(['message' => 'Track skipped', 'next_id' => null]);
    }
} catch (Exception $e) {
    json_error('Server error');
}
