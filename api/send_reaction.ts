import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomId = parseInt(input.room_id) || 0;
  const type = (input.type || '').trim();
  const fromNick = input.from_nick || '';

  if (!roomId || !type) return jsonError(res, 'Missing required fields');

  db().prepare(
    "INSERT INTO reactions (room_id, type, from_nick) VALUES (?, ?, ?)"
  ).run(roomId, type, fromNick);

  db().prepare(
    "DELETE FROM reactions WHERE room_id = ? AND created_at < datetime('now', '-30 seconds')"
  ).run(roomId);

  jsonSuccess(res, { message: 'Reaction sent' });
}
