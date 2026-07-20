import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { verifyHostToken } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const roomId = parseInt(req.query.room_id as string) || 0;
  const token = (req.query.token as string) || '';

  if (!roomId) return jsonError(res, 'Missing room_id');
  if (!verifyHostToken(roomId, token)) return jsonError(res, 'Unauthorized', 401);

  db().prepare(
    "DELETE FROM guest_heartbeats WHERE last_seen < datetime('now', '-10 seconds') AND room_id = ?"
  ).run(roomId);

  const count = (db().prepare(
    "SELECT COUNT(*) as cnt FROM guest_heartbeats WHERE room_id = ?"
  ).get(roomId) as any).cnt;

  jsonSuccess(res, { count });
}
