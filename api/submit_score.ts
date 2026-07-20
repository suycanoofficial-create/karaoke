import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const trackId = parseInt(input.track_id) || 0;
  const roomId = parseInt(input.room_id) || 0;
  const score = parseInt(input.score) || 0;

  if (!trackId || !roomId || score < 0 || score > 100) return jsonError(res, 'Invalid input');

  const track = db().prepare("SELECT id FROM songs_queue WHERE id = ? AND room_id = ?").get(trackId, roomId);
  if (!track) return jsonError(res, 'Track not found', 404);

  db().prepare(
    "INSERT INTO song_scores (track_id, room_id, score) VALUES (?, ?, ?)"
  ).run(trackId, roomId, score);

  const avg = (db().prepare(
    "SELECT AVG(score) as avg_score FROM song_scores WHERE track_id = ?"
  ).get(trackId) as any).avg_score;

  db().prepare("UPDATE songs_queue SET score = ? WHERE id = ?").run(Math.round(avg), trackId);

  jsonSuccess(res, { message: 'Score submitted', average: Math.round(avg) });
}
