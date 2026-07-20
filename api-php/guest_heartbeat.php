<?php
/**
 * API: Guest Heartbeat
 * POST /api/guest_heartbeat.php
 * Body: { room_id, guest_id, nickname }
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$room_id = isset($input['room_id']) ? (int) $input['room_id'] : 0;
$guest_id = isset($input['guest_id']) ? trim($input['guest_id']) : '';
$nickname = isset($input['nickname']) ? trim($input['nickname']) : 'Guest';

if (!$room_id || !$guest_id) {
    json_error('Missing room_id or guest_id');
}

$nickname = sanitize($nickname);

$stmt = db()->prepare("INSERT INTO guest_heartbeats (room_id, guest_id, nickname, last_seen) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE nickname = VALUES(nickname), last_seen = NOW()");
$stmt->execute([$room_id, $guest_id, $nickname]);

json_success(['message' => 'heartbeat ok']);
