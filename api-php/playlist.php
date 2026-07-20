<?php
/**
 * Playlist API (Profile-based)
 * Actions: create_profile, get_profiles, add_to_profile, recover, bulk_recover, delete, bulk_delete
 * Read actions (get_profiles, get_songs) and recover are accessible by guests.
 * Write/admin actions require admin auth.
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'POST required']));
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {

    // Create a new playlist profile
    case 'create_profile':
        require_admin();
        $name = trim($input['name'] ?? '');
        if (empty($name)) { http_response_code(400); die(json_encode(['error' => 'Name is required'])); }
        if (mb_strlen($name) > 100) { http_response_code(400); die(json_encode(['error' => 'Name too long'])); }

        $stmt = db()->prepare("SELECT id FROM playlist_profiles WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) { http_response_code(400); die(json_encode(['error' => 'Profile already exists'])); }

        $stmt = db()->prepare("INSERT INTO playlist_profiles (name) VALUES (?)");
        $stmt->execute([$name]);
        $id = db()->lastInsertId();

        echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'message' => 'Profile created']);
        break;

    // Get all profiles with song counts
    case 'get_profiles':
        $stmt = db()->query("
            SELECT pp.*, COUNT(ps.id) as song_count
            FROM playlist_profiles pp
            LEFT JOIN playlist_songs ps ON ps.profile_id = pp.id
            GROUP BY pp.id
            ORDER BY pp.name ASC
        ");
        $profiles = $stmt->fetchAll();
        echo json_encode(['success' => true, 'profiles' => $profiles]);
        break;

    // Add a song from queue to a playlist profile
    case 'add_to_profile':
        require_admin();
        $id = intval($input['id'] ?? 0);
        $profile_id = intval($input['profile_id'] ?? 0);
        if (!$id) { http_response_code(400); die(json_encode(['error' => 'Invalid song ID'])); }
        if (!$profile_id) { http_response_code(400); die(json_encode(['error' => 'No profile selected'])); }

        $stmt = db()->prepare("SELECT id FROM playlist_profiles WHERE id = ?");
        $stmt->execute([$profile_id]);
        if (!$stmt->fetch()) { http_response_code(404); die(json_encode(['error' => 'Profile not found'])); }

        $stmt = db()->prepare("SELECT video_title, youtube_id, added_by FROM songs_queue WHERE id = ?");
        $stmt->execute([$id]);
        $song = $stmt->fetch();
        if (!$song) { http_response_code(404); die(json_encode(['error' => 'Song not found in queue'])); }

        $stmt = db()->prepare("INSERT INTO playlist_songs (profile_id, video_title, youtube_id, added_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$profile_id, $song['video_title'], $song['youtube_id'], $song['added_by']]);

        echo json_encode(['success' => true, 'message' => 'Added to playlist']);
        break;

    // Get songs for a specific profile
    case 'get_songs':
        $profile_id = intval($input['profile_id'] ?? 0);
        if (!$profile_id) { http_response_code(400); die(json_encode(['error' => 'No profile selected'])); }

        $stmt = db()->prepare("SELECT * FROM playlist_songs WHERE profile_id = ? ORDER BY created_at DESC");
        $stmt->execute([$profile_id]);
        $songs = $stmt->fetchAll();

        echo json_encode(['success' => true, 'songs' => $songs]);
        break;

    // Recover a song from playlist to an active room (does NOT delete from playlist)
    case 'recover':
        $id = intval($input['id'] ?? 0);
        $target_room_id = intval($input['target_room_id'] ?? 0);
        if (!$id) { http_response_code(400); die(json_encode(['error' => 'Invalid ID'])); }
        if (!$target_room_id) { http_response_code(400); die(json_encode(['error' => 'No target room selected'])); }

        $stmt = db()->prepare("SELECT id FROM rooms WHERE id = ? AND status = 'active'");
        $stmt->execute([$target_room_id]);
        if (!$stmt->fetch()) { http_response_code(400); die(json_encode(['error' => 'Target room not found or not active'])); }

        $stmt = db()->prepare("SELECT video_title, youtube_id, added_by FROM playlist_songs WHERE id = ?");
        $stmt->execute([$id]);
        $song = $stmt->fetch();
        if (!$song) { http_response_code(404); die(json_encode(['error' => 'Song not found in playlist'])); }

        // Skip if already in queue (pending or playing)
        $stmt = db()->prepare("SELECT id FROM songs_queue WHERE room_id = ? AND youtube_id = ? AND status IN ('pending', 'playing')");
        $stmt->execute([$target_room_id, $song['youtube_id']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => true, 'skipped' => true, 'message' => 'Already in queue']);
            break;
        }

        $stmt = db()->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM songs_queue WHERE room_id = ?");
        $stmt->execute([$target_room_id]);
        $max_order = $stmt->fetch()['max_order'];

        $stmt = db()->prepare("INSERT INTO songs_queue (room_id, video_title, youtube_id, added_by, sort_order, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$target_room_id, $song['video_title'], $song['youtube_id'], $song['added_by'], $max_order + 1]);

        // Update target room last_activity
        $stmt = db()->prepare("UPDATE rooms SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$target_room_id]);

        echo json_encode(['success' => true, 'message' => 'Song sent to room (kept in playlist)']);
        break;

    // Bulk recover songs to a room
    case 'bulk_recover':
        $ids = array_map('intval', $input['ids'] ?? []);
        $target_room_id = intval($input['target_room_id'] ?? 0);
        if (empty($ids)) { http_response_code(400); die(json_encode(['error' => 'No songs selected'])); }
        if (!$target_room_id) { http_response_code(400); die(json_encode(['error' => 'No target room selected'])); }

        $stmt = db()->prepare("SELECT id FROM rooms WHERE id = ? AND status = 'active'");
        $stmt->execute([$target_room_id]);
        if (!$stmt->fetch()) { http_response_code(400); die(json_encode(['error' => 'Target room not found or not active'])); }

        $stmt = db()->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM songs_queue WHERE room_id = ?");
        $stmt->execute([$target_room_id]);
        $max_order = $stmt->fetch()['max_order'];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("SELECT id, video_title, youtube_id, added_by FROM playlist_songs WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $songs = $stmt->fetchAll();

        // Get existing youtube_ids in queue (pending/playing) for this room
        $existing = db()->prepare("SELECT youtube_id FROM songs_queue WHERE room_id = ? AND status IN ('pending', 'playing')");
        $existing->execute([$target_room_id]);
        $existingIds = $existing->fetchAll(PDO::FETCH_COLUMN);

        $recovered = 0;
        foreach ($songs as $song) {
            if (in_array($song['youtube_id'], $existingIds)) continue;
            $max_order++;
            $stmt = db()->prepare("INSERT INTO songs_queue (room_id, video_title, youtube_id, added_by, sort_order, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$target_room_id, $song['video_title'], $song['youtube_id'], $song['added_by'], $max_order]);
            $recovered++;
        }

        // Update target room last_activity
        $stmt = db()->prepare("UPDATE rooms SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$target_room_id]);

        echo json_encode(['success' => true, 'message' => $recovered . ' song(s) sent to room (kept in playlist)']);
        break;

    // Delete single playlist song
    case 'delete':
        require_admin();
        $id = intval($input['id'] ?? 0);
        if (!$id) { http_response_code(400); die(json_encode(['error' => 'Invalid ID'])); }

        $stmt = db()->prepare("DELETE FROM playlist_songs WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Song removed from playlist']);
        break;

    // Bulk delete playlist songs
    case 'bulk_delete':
        require_admin();
        $ids = array_map('intval', $input['ids'] ?? []);
        if (empty($ids)) { http_response_code(400); die(json_encode(['error' => 'No IDs provided'])); }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("DELETE FROM playlist_songs WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        echo json_encode(['success' => true, 'message' => count($ids) . ' song(s) removed from playlist']);
        break;

    // Delete a profile and all its songs
    case 'delete_profile':
        require_admin();
        $id = intval($input['id'] ?? 0);
        if (!$id) { http_response_code(400); die(json_encode(['error' => 'Invalid ID'])); }

        $stmt = db()->prepare("DELETE FROM playlist_profiles WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Profile deleted']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
