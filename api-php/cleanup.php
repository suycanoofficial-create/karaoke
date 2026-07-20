<?php
/**
 * API: Auto Cleanup
 * Called from admin pages to auto-close idle rooms and purge old queue entries.
 * GET /api/cleanup.php
 * This is a self-throttling endpoint.
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow internal/admin-triggered calls
$isAdminCall = !empty($_SESSION['admin_id']);
$isInternal = isset($_GET['internal']) && $_GET['internal'] === 'scheduled';
if (!$isAdminCall && !$isInternal) {
    json_error('Unauthorized', 401);
}

// Throttle: use the min of configured intervals (min 10s)
function time_to_seconds($label) {
    $label = strtolower(trim($label));
    $map = [
        '10 seconds' => 10,
        '10 minutes' => 600,
        '1 hour'     => 3600,
        '2 hours'    => 7200,
        '1 day'      => 86400,
        '2 days'     => 172800,
        '7 days'     => 604800,
        '15 days'    => 1296000,
        '30 days'    => 2592000,
    ];
    return $map[$label] ?? 60;
}
$throttle = min(time_to_seconds(get_setting('room_cleanup_time', '1 day')), time_to_seconds(get_setting('queue_cleanup_time', '7 days')));
$throttle = max($throttle, 1);

$lastRun = get_setting('last_cleanup_run', '');
if ($lastRun) {
    $nextRun = strtotime($lastRun) + $throttle;
    if (time() < $nextRun) {
        json_success(['message' => 'Throttled', 'next_run' => date('Y-m-d H:i:s', $nextRun)]);
    }
}

update_setting('last_cleanup_run', date('Y-m-d H:i:s'));

$logs = [];

// ── Helper: convert human time to SQL INTERVAL ──
function time_to_sql_interval($label) {
    $label = strtolower(trim($label));
    $map = [
        '10 seconds' => '10 SECOND',
        '10 minutes' => '10 MINUTE',
        '1 hour'     => '1 HOUR',
        '2 hours'    => '2 HOUR',
        '1 day'      => '1 DAY',
        '2 days'     => '2 DAY',
        '7 days'     => '7 DAY',
        '15 days'    => '15 DAY',
        '30 days'    => '30 DAY',
    ];
    return $map[$label] ?? null;
}

// ── 1. Room Cleanup ────────────────────────────────────────────
if (get_setting('room_cleanup_enabled', '1') === '1') {
    $roomTime = get_setting('room_cleanup_time', '1 hour');
    $interval = time_to_sql_interval($roomTime);
    if ($interval) {
        $stmt = db()->prepare("
            DELETE FROM rooms
            WHERE (status = 'active' OR status = 'closed')
              AND (
                SELECT COUNT(*) FROM songs_queue
                WHERE songs_queue.room_id = rooms.id
                  AND songs_queue.status IN ('pending', 'playing')
              ) = 0
              AND COALESCE(last_activity, created_at) < DATE_SUB(NOW(), INTERVAL $interval)
        ");
        $stmt->execute();
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            $logs[] = "Deleted $affected idle room(s)";
        }
    }
}

// ── 2. Queue Cleanup ───────────────────────────────────────────
if (get_setting('queue_cleanup_enabled', '1') === '1') {
    $queueTime = get_setting('queue_cleanup_time', '7 days');
    $interval = time_to_sql_interval($queueTime);
    if ($interval) {
        $stmt = db()->prepare("
            DELETE FROM songs_queue
            WHERE created_at < DATE_SUB(NOW(), INTERVAL $interval)
        ");
        $stmt->execute();
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            $logs[] = "Purged $affected old queue song(s)";
        }
    }
}

if (empty($logs)) {
    $logs[] = 'Nothing to clean up';
}

json_success(['message' => implode('; ', $logs)]);
