<?php
/**
 * API: Recover Room
 * POST /api/recover_room.php
 * Body: { room_code }
 * Reactivates a closed room by room code alone.
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$room_code = isset($input['room_code']) ? strtoupper(trim($input['room_code'])) : '';

if (!$room_code) {
    json_error('Missing room_code');
}

$stmt = db()->prepare("SELECT id, status, host_session_token FROM rooms WHERE room_code = ?");
$stmt->execute([$room_code]);
$room = $stmt->fetch();

if (!$room) {
    json_error('Room not found');
}

if ($room['status'] === 'active') {
    json_success(['message' => 'Room is already active', 'redirect' => 'host.php?code=' . urlencode($room_code) . '&token=' . urlencode($room['host_session_token'])]);
}

$stmt = db()->prepare("UPDATE rooms SET status = 'active', last_activity = NOW() WHERE id = ?");
$stmt->execute([$room['id']]);

json_success(['message' => 'Room recovered', 'redirect' => 'host.php?code=' . urlencode($room_code) . '&token=' . urlencode($room['host_session_token'])]);
