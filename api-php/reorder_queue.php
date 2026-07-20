<?php
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$room_id = isset($input['room_id']) ? (int) $input['room_id'] : 0;
$ordered_ids = $input['ordered_ids'] ?? [];

if (!$room_id) {
    json_error('Missing room_id');
}
if (!is_array($ordered_ids) || empty($ordered_ids)) {
    json_error('No track IDs provided');
}

// Verify room exists and is active
$stmt = db()->prepare("SELECT id FROM rooms WHERE id = ? AND status = 'active'");
$stmt->execute([$room_id]);
if (!$stmt->fetch()) {
    json_error('Room not found', 404);
}

$ordered_ids = array_map('intval', $ordered_ids);
$stmt = db()->prepare("UPDATE songs_queue SET sort_order = ? WHERE id = ? AND room_id = ?");
foreach ($ordered_ids as $index => $tid) {
    if ($tid > 0) {
        $stmt->execute([$index, $tid, $room_id]);
    }
}

// Update room activity
$stmt = db()->prepare("UPDATE rooms SET last_activity = NOW() WHERE id = ?");
$stmt->execute([$room_id]);

json_success(['message' => 'Queue reordered']);
