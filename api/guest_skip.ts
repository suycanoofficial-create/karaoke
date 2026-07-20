import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomCode = (input.room_code || '').toUpperCase();
  const trackId = parseInt(input.track_id) || 0;
  const guestId = input.guest_id || '';

  if (!roomCode || !trackId) return jsonError(res, 'Missing required fields');

  const track = db().prepare(
    "SELECT sq.*, r.status as room_status FROM songs_queue sq JOIN rooms r ON sq.room_id = r.id WHERE sq.id = ? AND r.room_code = ?"
  ).get(trackId, roomCode) as any;

  if (!track) return jsonError(res, 'Track not found', 404);
  if (track.room_status !== 'active') return jsonError(res, 'Room is not active');
  if (track.status !== 'playing') return jsonError(res, 'Track is not playing');

  db().prepare("UPDATE songs_queue SET status = 'skipped' WHERE id = ?").run(trackId);

  const next = db().prepare(
    "SELECT id FROM songs_queue WHERE room_id = ? AND status = 'pending' ORDER BY sort_order ASC, created_at ASC LIMIT 1"
  ).get(track.room_id) as any;

  if (next) {
    const time = new Date().toISOString().replace('T', ' ').slice(0, 19);
    db().prepare("UPDATE songs_queue SET status = 'playing', started_at = ? WHERE id = ?").run(time, next.id);
    jsonSuccess(res, { message: 'Track skipped', next_id: next.id });
  } else {
    jsonSuccess(res, { message: 'Track skipped', next_id: null });
  }
}
