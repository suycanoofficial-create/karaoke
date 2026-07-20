<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');
$track_id = isset($_GET['track_id']) ? (int) $_GET['track_id'] : 0;
$room_id = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
if (!$track_id) json_error('Missing track_id');
$stmt = db()->prepare("SELECT COUNT(*) as count, AVG(score) as avg_score FROM song_scores WHERE track_id = ?");
$stmt->execute([$track_id]);
$stats = $stmt->fetch();
$stmt = db()->prepare("SELECT score, scored_by, created_at FROM song_scores WHERE track_id = ? ORDER BY created_at DESC");
$stmt->execute([$track_id]);
json_success([
    'track_id' => $track_id,
    'count' => (int) $stats['count'],
    'average' => $stats['avg_score'] ? round($stats['avg_score']) : null,
    'scores' => $stmt->fetchAll()
]);
