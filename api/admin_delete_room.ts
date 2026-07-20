import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  if (!requireAdmin(req)) return jsonError(res, 'Unauthorized', 401);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomIds: number[] = input.room_ids || [];
  if (!Array.isArray(roomIds) || roomIds.length === 0) return jsonError(res, 'No room IDs provided');

  const placeholders = roomIds.map(() => '?').join(',');
  db().prepare(`DELETE FROM songs_queue WHERE room_id IN (${placeholders})`).run(...roomIds);
  db().prepare(`DELETE FROM rooms WHERE id IN (${placeholders})`).run(...roomIds);
  db().prepare(`DELETE FROM guest_heartbeats WHERE room_id IN (${placeholders})`).run(...roomIds);
  db().prepare(`DELETE FROM audio_relay WHERE room_id IN (${placeholders})`).run(...roomIds);

  jsonSuccess(res, { message: `${roomIds.length} room(s) deleted` });
}
