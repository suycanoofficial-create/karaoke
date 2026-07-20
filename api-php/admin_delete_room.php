<?php
/**
 * API: Admin Delete Room(s)
 * POST /api/admin_delete_room.php
 * Body: { room_ids: [1, 2, 3] }
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$room_ids = $input['room_ids'] ?? [];

if (empty($room_ids)) {
    json_error('No rooms selected');
}

$ids = array_map('intval', $room_ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = db()->prepare("DELETE FROM songs_queue WHERE room_id IN ($placeholders)");
$stmt->execute($ids);

$stmt = db()->prepare("DELETE FROM rooms WHERE id IN ($placeholders)");
$stmt->execute($ids);

$count = $stmt->rowCount();

json_success(['message' => "$count room(s) deleted", 'deleted' => $count]);
