<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$room_id = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
if (!$room_id) json_error('Missing room_id');
if ($method === 'GET') {
    $stmt = db()->prepare("SELECT echo_delay, echo_feedback, echo_mix FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $row = $stmt->fetch();
    if (!$row) json_error('Room not found');
    json_success([
        'delay' => (float) $row['echo_delay'],
        'feedback' => (float) $row['echo_feedback'],
        'mix' => (float) $row['echo_mix']
    ]);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) json_error('Invalid request');
    // Verify room exists
    $stmt = db()->prepare("SELECT id FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    if (!$stmt->fetch()) json_error('Room not found');
    $delay = isset($input['delay']) ? min(1.0, max(0.05, (float) $input['delay'])) : null;
    $feedback = isset($input['feedback']) ? min(0.8, max(0.0, (float) $input['feedback'])) : null;
    $mix = isset($input['mix']) ? min(1.0, max(0.0, (float) $input['mix'])) : null;
    $fields = [];
    $params = [];
    if ($delay !== null) { $fields[] = 'echo_delay = ?'; $params[] = $delay; }
    if ($feedback !== null) { $fields[] = 'echo_feedback = ?'; $params[] = $feedback; }
    if ($mix !== null) { $fields[] = 'echo_mix = ?'; $params[] = $mix; }
    if (empty($fields)) json_error('No settings to update');
    $params[] = $room_id;
    $stmt = db()->prepare("UPDATE rooms SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);
    json_success(['message' => 'Echo settings saved']);
} else {
    json_error('Method not allowed', 405);
}
