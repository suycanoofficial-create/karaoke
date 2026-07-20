import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomCode = (input.room_code || '').toUpperCase();
  if (!roomCode) return jsonError(res, 'Missing room_code');

  const room = db().prepare(
    "SELECT id, status, host_session_token FROM rooms WHERE room_code = ?"
  ).get(roomCode) as any;

  if (!room) return jsonError(res, 'Room not found', 404);
  if (room.status === 'active') return jsonError(res, 'Room is already active');

  db().prepare("UPDATE rooms SET status = 'active', last_activity = datetime('now') WHERE id = ?").run(room.id);

  jsonSuccess(res, {
    message: 'Room recovered! Redirecting...',
    redirect: `/host?code=${roomCode}&token=${room.host_session_token}`,
  });
}
