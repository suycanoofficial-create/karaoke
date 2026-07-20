<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) json_error('Invalid request body');
$track_id = isset($input['track_id']) ? (int) $input['track_id'] : 0;
$score = isset($input['score']) ? (int) $input['score'] : 0;
$room_id = isset($input['room_id']) ? (int) $input['room_id'] : 0;
$scored_by = isset($input['scored_by']) ? trim($input['scored_by']) : 'Guest';
if (!$track_id || !$room_id || $score < 1 || $score > 100) {
    json_error('Invalid parameters. Score must be 1-100.');
}
$stmt = db()->prepare("SELECT id, room_id FROM songs_queue WHERE id = ? AND room_id = ? AND status = 'completed'");
$stmt->execute([$track_id, $room_id]);
if (!$stmt->fetch()) json_error('Track not found or not completed');
$stmt = db()->prepare("INSERT INTO song_scores (track_id, room_id, score, scored_by) VALUES (?, ?, ?, ?)");
$stmt->execute([$track_id, $room_id, $score, $scored_by]);
// Update average score on songs_queue
$stmt = db()->prepare("SELECT AVG(score) as avg_score FROM song_scores WHERE track_id = ?");
$stmt->execute([$track_id]);
$avg = round($stmt->fetch()['avg_score']);
$stmt = db()->prepare("UPDATE songs_queue SET score = ? WHERE id = ?");
$stmt->execute([$avg, $track_id]);
json_success(['track_id' => $track_id, 'score' => $score, 'average' => $avg]);
