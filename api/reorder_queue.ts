import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomCode = (input.room_code || '').toUpperCase();
  const orderedIds = input.ordered_ids;

  if (!roomCode || !Array.isArray(orderedIds)) return jsonError(res, 'Missing required fields');

  const room = db().prepare("SELECT id FROM rooms WHERE room_code = ?").get(roomCode) as any;
  if (!room) return jsonError(res, 'Room not found', 404);

  const stmt = db().prepare("UPDATE songs_queue SET sort_order = ? WHERE id = ? AND room_id = ?");
  for (let i = 0; i < orderedIds.length; i++) {
    if (orderedIds[i] > 0) {
      stmt.run(i, orderedIds[i], room.id);
    }
  }

  db().prepare("UPDATE rooms SET last_activity = datetime('now') WHERE id = ?").run(room.id);
  jsonSuccess(res, { message: 'Queue reordered' });
}
