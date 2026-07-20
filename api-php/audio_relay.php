<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');
$action = isset($_GET['action']) ? $_GET['action'] : '';
$room_id = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !$room_id) json_error('Invalid request');
    $chunk = isset($input['chunk']) ? $input['chunk'] : '';
    $seq = isset($input['seq']) ? (int) $input['seq'] : 0;
    if (!$chunk) json_error('No audio data');
    $data = base64_decode($chunk);
    if ($data === false) json_error('Invalid base64');
    // Clean old chunks for this room
    $stmt = db()->prepare("DELETE FROM audio_relay WHERE room_id = ? AND created_at < (NOW() - INTERVAL 5 SECOND)");
    $stmt->execute([$room_id]);
    $stmt = db()->prepare("INSERT INTO audio_relay (room_id, chunk_data, chunk_seq) VALUES (?, ?, ?)");
    $stmt->execute([$room_id, $data, $seq]);
    json_success(['received' => $seq]);
} elseif ($action === 'receive') {
    if (!$room_id) json_error('Missing room_id');
    $stmt = db()->prepare("SELECT id, chunk_data, chunk_seq FROM audio_relay WHERE room_id = ? ORDER BY chunk_seq ASC LIMIT 50");
    $stmt->execute([$room_id]);
    $chunks = [];
    while ($row = $stmt->fetch()) {
        $chunks[] = [
            'id' => $row['id'],
            'seq' => (int) $row['chunk_seq'],
            'data' => base64_encode($row['chunk_data'])
        ];
    }
    // Delete fetched chunks
    if (!empty($chunks)) {
        $ids = array_column($chunks, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("DELETE FROM audio_relay WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }
    json_success(['chunks' => $chunks]);
} elseif ($action === 'stop') {
    $stmt = db()->prepare("DELETE FROM audio_relay WHERE room_id = ?");
    $stmt->execute([$room_id]);
    json_success(['message' => 'Stream stopped']);
} else {
    json_error('Invalid action');
}
