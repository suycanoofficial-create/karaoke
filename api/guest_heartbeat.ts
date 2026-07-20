import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomCode = (input.room_code || '').toUpperCase();
  const guestId = input.guest_id || '';
  const nickname = input.nickname || 'Guest';

  if (!roomCode || !guestId) return jsonError(res, 'Missing required fields');

  const room = db().prepare("SELECT id FROM rooms WHERE room_code = ?").get(roomCode) as any;
  if (!room) return jsonError(res, 'Room not found', 404);

  db().prepare(`
    INSERT INTO guest_heartbeats (room_id, guest_id, nickname, last_seen)
    VALUES (?, ?, ?, datetime('now'))
    ON CONFLICT(room_id, guest_id) DO UPDATE SET
      nickname = excluded.nickname,
      last_seen = excluded.last_seen
  `).run(room.id, guestId, nickname);

  jsonSuccess(res, { message: 'Heartbeat received' });
}
