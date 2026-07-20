<?php
/**
 * API: Remove from Queue
 * POST /api/remove_from_queue.php
 * Body: { room_id, track_id }
 * Anyone in the room can remove pending songs.
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$room_id = intval($input['room_id'] ?? 0);
$track_id = intval($input['track_id'] ?? 0);

if (!$room_id || !$track_id) {
    json_error('Missing required fields');
}

$stmt = db()->prepare("SELECT id, room_id, status FROM songs_queue WHERE id = ?");
$stmt->execute([$track_id]);
$track = $stmt->fetch();

if (!$track) {
    json_error('Song not found in queue');
}

if ($track['room_id'] != $room_id) {
    json_error('Song not in this room');
}

if (!in_array($track['status'], ['pending', 'playing'])) {
    json_error('Cannot remove ' . $track['status'] . ' songs');
}

$stmt = db()->prepare("DELETE FROM songs_queue WHERE id = ?");
$stmt->execute([$track_id]);

// Update room last_activity
$stmt = db()->prepare("UPDATE rooms SET last_activity = NOW() WHERE id = ?");
$stmt->execute([$room_id]);

json_success(['message' => 'Song removed from queue']);
