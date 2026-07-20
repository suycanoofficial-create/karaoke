import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { verifyHostToken } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomId = parseInt(input.room_id) || 0;
  const token = (input.token || '').trim();

  if (!roomId || !token) return jsonError(res, 'Missing required fields');
  if (!verifyHostToken(roomId, token)) return jsonError(res, 'Unauthorized', 401);

  const room = db().prepare("SELECT locked FROM rooms WHERE id = ?").get(roomId) as any;
  const newLocked = room.locked ? 0 : 1;
  db().prepare("UPDATE rooms SET locked = ? WHERE id = ?").run(newLocked, roomId);

  jsonSuccess(res, { locked: !!newLocked, message: newLocked ? 'Room locked' : 'Room unlocked' });
}
