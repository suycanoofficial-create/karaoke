<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');
$room_id = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
$since = isset($_GET['since']) ? (int) $_GET['since'] : 0;
if (!$room_id) json_error('Missing room_id');
if ($since) {
    $stmt = db()->prepare("SELECT id, type, from_nick, UNIX_TIMESTAMP(created_at) as ts FROM cheers WHERE room_id = ? AND id > ? ORDER BY id ASC");
    $stmt->execute([$room_id, $since]);
} else {
    $stmt = db()->prepare("SELECT id, type, from_nick, UNIX_TIMESTAMP(created_at) as ts FROM cheers WHERE room_id = ? ORDER BY id ASC");
    $stmt->execute([$room_id]);
}
json_success(['cheers' => $stmt->fetchAll()]);
