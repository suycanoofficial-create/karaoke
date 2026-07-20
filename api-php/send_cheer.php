<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) json_error('Invalid request body');
$room_id = isset($input['room_id']) ? (int) $input['room_id'] : 0;
$type = isset($input['type']) ? trim($input['type']) : '';
$from_nick = isset($input['from_nick']) ? trim($input['from_nick']) : '';
$allowed = ['applause','cheer','airhorn','boo','laugh','fire','crown','heart','drums'];
if (!$room_id || !$type) json_error('Missing parameters');
if (!in_array($type, $allowed)) json_error('Invalid cheer type');
$stmt = db()->prepare("INSERT INTO cheers (room_id, type, from_nick) VALUES (?, ?, ?)");
$stmt->execute([$room_id, $type, $from_nick]);
// Clean old cheers
$stmt = db()->prepare("DELETE FROM cheers WHERE room_id = ? AND created_at < (NOW() - INTERVAL 1 MINUTE)");
$stmt->execute([$room_id]);
json_success(['message' => 'Cheer sent']);
