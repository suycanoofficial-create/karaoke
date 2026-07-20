<?php
/**
 * API: Get Guest Count
 * GET /api/get_guest_count.php?room_id=X&token=Y
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

$room_id = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$room_id) {
    json_error('Missing room_id');
}

// Verify host token
$stmt = db()->prepare("SELECT id FROM rooms WHERE id = ? AND host_session_token = ?");
$stmt->execute([$room_id, $token]);
if (!$stmt->fetch()) {
    json_error('Unauthorized', 401);
}

// Clean stale heartbeats (older than 15 seconds)
db()->prepare("DELETE FROM guest_heartbeats WHERE room_id = ? AND last_seen < DATE_SUB(NOW(), INTERVAL 15 SECOND)")->execute([$room_id]);

// Count active guests
$stmt = db()->prepare("SELECT COUNT(*) as cnt FROM guest_heartbeats WHERE room_id = ? AND last_seen >= DATE_SUB(NOW(), INTERVAL 15 SECOND)");
$stmt->execute([$room_id]);
$count = $stmt->fetch()['cnt'];

json_success(['count' => (int) $count]);
