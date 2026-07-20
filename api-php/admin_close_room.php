<?php
/**
 * API: Admin Close Room
 * POST /api/admin_close_room.php
 * Body: { room_id }
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$room_id = isset($input['room_id']) ? (int) $input['room_id'] : 0;

if (!$room_id) {
    json_error('Missing room_id');
}

try {
    $stmt = db()->prepare("UPDATE rooms SET status = 'closed', last_activity = NOW() WHERE id = ?");
    $stmt->execute([$room_id]);
    
    $stmt = db()->prepare("UPDATE songs_queue SET status = 'skipped' WHERE room_id = ? AND status IN ('pending', 'playing')");
    $stmt->execute([$room_id]);
    
    json_success(['message' => 'Room closed']);
} catch (Exception $e) {
    json_error('Failed to close room', 500);
}
