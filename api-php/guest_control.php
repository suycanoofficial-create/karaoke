<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$room_id = 0;
if ($method === 'GET' && isset($_GET['room_id'])) {
    $room_id = (int) $_GET['room_id'];
}
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['room_id'])) $room_id = (int) $input['room_id'];
    if (!$room_id && isset($_GET['room_id'])) $room_id = (int) $_GET['room_id'];
    if (!$room_id) json_error('Missing room_id');
    if (!isset($input['action'])) json_error('Invalid request');
    $action = trim($input['action']);
    if (!in_array($action, ['play', 'pause'])) json_error('Invalid action');
    $stmt = db()->prepare("UPDATE rooms SET playback_cmd = ? WHERE id = ?");
    $stmt->execute([$action, $room_id]);
    json_success(['action' => $action]);
} elseif ($method === 'GET') {
    if (!$room_id) json_error('Missing room_id');
    $stmt = db()->prepare("SELECT playback_cmd FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $row = $stmt->fetch();
    if (!$row) json_error('Room not found');
    $cmd = $row['playback_cmd'];
    // Clear the command after reading
    if ($cmd) {
        $stmt = db()->prepare("UPDATE rooms SET playback_cmd = NULL WHERE id = ?");
        $stmt->execute([$room_id]);
    }
    json_success(['action' => $cmd]);
} else {
    json_error('Method not allowed', 405);
}
