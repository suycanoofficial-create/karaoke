<?php
/**
 * Admin - Queue Management API
 * Actions: delete, bulk_delete, move_to_room
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'POST required']));
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {

    // Delete single queue item
    case 'delete':
        $id = intval($input['id'] ?? 0);
        if (!$id) { http_response_code(400); die(json_encode(['error' => 'Invalid ID'])); }

        // Get room_id before deleting
        $stmt = db()->prepare("SELECT room_id FROM songs_queue WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $room_id = $row ? $row['room_id'] : 0;

        $stmt = db()->prepare("DELETE FROM songs_queue WHERE id = ?");
        $stmt->execute([$id]);

        if ($room_id) {
            $stmt = db()->prepare("UPDATE rooms SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$room_id]);
        }

        echo json_encode(['success' => true, 'message' => 'Song deleted']);
        break;

    // Bulk delete queue items
    case 'bulk_delete':
        $ids = $input['ids'] ?? [];
        $ids = array_map('intval', $ids);
        if (empty($ids)) { http_response_code(400); die(json_encode(['error' => 'No IDs provided'])); }

        // Get affected room IDs before deleting
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("SELECT DISTINCT room_id FROM songs_queue WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $roomIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = db()->prepare("DELETE FROM songs_queue WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        foreach ($roomIds as $rid) {
            $stmt = db()->prepare("UPDATE rooms SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$rid]);
        }

        echo json_encode(['success' => true, 'message' => count($ids) . ' song(s) deleted']);
        break;

    // Move queue items to another active room
    case 'move_to_room':
        $ids = $input['ids'] ?? [];
        $target_room_id = intval($input['target_room_id'] ?? 0);
        $ids = array_map('intval', $ids);

        if (empty($ids)) { http_response_code(400); die(json_encode(['error' => 'No songs selected'])); }
        if (!$target_room_id) { http_response_code(400); die(json_encode(['error' => 'No target room selected'])); }

        // Verify target room exists and is active
        $stmt = db()->prepare("SELECT id FROM rooms WHERE id = ? AND status = 'active'");
        $stmt->execute([$target_room_id]);
        if (!$stmt->fetch()) { http_response_code(400); die(json_encode(['error' => 'Target room not found or not active'])); }

        // Get current max sort_order in target room
        $stmt = db()->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM songs_queue WHERE room_id = ?");
        $stmt->execute([$target_room_id]);
        $max_order = $stmt->fetch()['max_order'];

        // Move each song
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("SELECT id, video_title FROM songs_queue WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $songs = $stmt->fetchAll();

        $moved = 0;
        foreach ($songs as $i => $song) {
            $max_order++;
            $stmt = db()->prepare("UPDATE songs_queue SET room_id = ?, sort_order = ?, status = 'pending' WHERE id = ?");
            $stmt->execute([$target_room_id, $max_order, $song['id']]);
            $moved++;
        }

        // Update target room last_activity
        $stmt = db()->prepare("UPDATE rooms SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$target_room_id]);

        echo json_encode(['success' => true, 'message' => $moved . ' song(s) moved to room #' . $target_room_id]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
