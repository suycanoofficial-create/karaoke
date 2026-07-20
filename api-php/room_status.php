<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');
$room_code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';
$room_id = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
if ($room_code) {
    $stmt = db()->prepare("SELECT id, status, locked, echo_delay, echo_feedback, echo_mix FROM rooms WHERE room_code = ?");
    $stmt->execute([$room_code]);
} elseif ($room_id) {
    $stmt = db()->prepare("SELECT id, status, locked, echo_delay, echo_feedback, echo_mix FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
} else {
    json_error('Missing room code or id');
}
$room = $stmt->fetch();
if (!$room) json_error('Room not found');
json_success([
    'room_id' => $room['id'],
    'status' => $room['status'],
    'locked' => (bool) $room['locked'],
    'echo' => [
        'delay' => (float) $room['echo_delay'],
        'feedback' => (float) $room['echo_feedback'],
        'mix' => (float) $room['echo_mix']
    ],
    'scoring_enabled' => get_setting('scoring_enabled', '1') === '1'
]);
