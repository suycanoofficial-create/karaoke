import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const roomId = parseInt(req.query.room_id as string) || 0;
  const since = parseInt(req.query.since as string) || 0;

  if (!roomId) return jsonError(res, 'Missing room_id');

  let reactions: any[];
  if (since > 0) {
    reactions = db().prepare(
      "SELECT id, type, from_nick, CAST(strftime('%s', created_at) AS INTEGER) as timestamp FROM reactions WHERE room_id = ? AND CAST(strftime('%s', created_at) AS INTEGER) > ? ORDER BY created_at ASC"
    ).all(roomId, since) as any[];
  } else {
    reactions = db().prepare(
      "SELECT id, type, from_nick, CAST(strftime('%s', created_at) AS INTEGER) as timestamp FROM reactions WHERE room_id = ? ORDER BY created_at ASC"
    ).all(roomId) as any[];
  }

  jsonSuccess(res, { reactions });
}
