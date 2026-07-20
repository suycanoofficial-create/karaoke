<?php
/**
 * API: Add to Queue
 * POST /api/add_to_queue.php
 * Body: { room_id, youtube_id, video_title, added_by }
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

$room_id = isset($input['room_id']) ? (int) $input['room_id'] : 0;
$youtube_id = isset($input['youtube_id']) ? trim($input['youtube_id']) : '';
$video_title = isset($input['video_title']) ? trim($input['video_title']) : '';
$added_by = isset($input['added_by']) ? trim($input['added_by']) : 'Guest';

if (!$room_id || !$youtube_id || !$video_title) {
    json_error('Missing required fields: room_id, youtube_id, video_title');
}

// Verify room is active and not locked
$stmt = db()->prepare("SELECT id, status, locked FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room || $room['status'] !== 'active') {
    json_error('Room is not active');
}

if ($room['locked']) {
    json_error('Queue is locked by host');
}

// Get next sort order
$stmt = db()->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM songs_queue WHERE room_id = ?");
$stmt->execute([$room_id]);
$next = $stmt->fetch();

// Insert song
$stmt = db()->prepare("
    INSERT INTO songs_queue (room_id, video_title, youtube_id, added_by, sort_order, status) 
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$stmt->execute([$room_id, $video_title, $youtube_id, $added_by, $next['next_order']]);

$track_id = db()->lastInsertId();

// Update room last_activity
$stmt = db()->prepare("UPDATE rooms SET last_activity = NOW() WHERE id = ?");
$stmt->execute([$room_id]);

json_success([
    'track_id' => (int) $track_id,
    'message' => 'Song added to queue'
]);
