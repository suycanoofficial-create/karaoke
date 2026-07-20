import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  if (!requireAdmin(req)) return jsonError(res, 'Unauthorized', 401);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomId = parseInt(input.room_id) || 0;
  if (!roomId) return jsonError(res, 'Missing room_id');

  db().prepare("UPDATE rooms SET status = 'closed' WHERE id = ?").run(roomId);
  db().prepare("UPDATE songs_queue SET status = 'skipped' WHERE room_id = ? AND status IN ('pending', 'playing')").run(roomId);

  jsonSuccess(res, { message: 'Room closed' });
}
