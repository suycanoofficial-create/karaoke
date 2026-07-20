<?php
/**
 * API: Create Room
 * POST /api/create_room.php
 * Returns room code and host token
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

try {
    $room_code = generate_room_code(6);
    $host_token = generate_token(32);

    // Ensure uniqueness
    $stmt = db()->prepare("SELECT id FROM rooms WHERE room_code = ?");
    $stmt->execute([$room_code]);
    while ($stmt->fetch()) {
        $room_code = generate_room_code(6);
        $stmt = db()->prepare("SELECT id FROM rooms WHERE room_code = ?");
        $stmt->execute([$room_code]);
    }

    $stmt = db()->prepare("INSERT INTO rooms (room_code, host_session_token, status, last_activity) VALUES (?, ?, 'active', NOW())");
    $stmt->execute([$room_code, $host_token]);

    $room_id = db()->lastInsertId();

    json_success([
        'room_id' => (int) $room_id,
        'room_code' => $room_code,
        'token' => $host_token
    ]);
} catch (Exception $e) {
    json_error('Failed to create room', 500);
}
