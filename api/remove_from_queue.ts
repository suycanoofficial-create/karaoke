import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const trackId = parseInt(input.track_id) || 0;
  const guestId = input.guest_id || '';
  const roomCode = (input.room_code || '').toUpperCase();

  if (!trackId) return jsonError(res, 'Missing track_id');

  const track = db().prepare(
    "SELECT sq.*, r.room_code FROM songs_queue sq JOIN rooms r ON sq.room_id = r.id WHERE sq.id = ?"
  ).get(trackId) as any;

  if (!track) return jsonError(res, 'Track not found', 404);

  if (track.status !== 'pending') return jsonError(res, 'Cannot remove track that is already playing or completed');

  if (guestId && track.added_by !== guestId) return jsonError(res, 'Not authorized to remove this track', 401);

  db().prepare("DELETE FROM songs_queue WHERE id = ?").run(trackId);
  db().prepare("UPDATE rooms SET last_activity = datetime('now') WHERE id = ?").run(track.room_id);

  jsonSuccess(res, { message: 'Track removed' });
}
