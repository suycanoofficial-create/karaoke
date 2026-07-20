import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  if (!requireAdmin(req)) return jsonError(res, 'Unauthorized', 401);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const action = (input.action || '').trim();
  const ids: number[] = input.ids || [];

  if (!action || !Array.isArray(ids) || ids.length === 0) return jsonError(res, 'Missing required fields');

  const placeholders = ids.map(() => '?').join(',');

  switch (action) {
    case 'delete':
      db().prepare(`DELETE FROM songs_queue WHERE id IN (${placeholders})`).run(...ids);
      jsonSuccess(res, { message: `${ids.length} song(s) deleted` });
      break;

    case 'bulk_delete':
      db().prepare(`DELETE FROM songs_queue WHERE id IN (${placeholders})`).run(...ids);
      jsonSuccess(res, { message: `${ids.length} song(s) deleted` });
      break;

    case 'move_to_room': {
      const targetRoomId = parseInt(input.target_room_id) || 0;
      if (!targetRoomId) return jsonError(res, 'Missing target room');

      const room = db().prepare("SELECT id FROM rooms WHERE id = ? AND status = 'active'").get(targetRoomId);
      if (!room) return jsonError(res, 'Target room not found or not active');

      const maxSort = (db().prepare(
        "SELECT COALESCE(MAX(sort_order), -1) + 1 as next FROM songs_queue WHERE room_id = ?"
      ).get(targetRoomId) as any).next;

      let i = 0;
      const stmt = db().prepare("UPDATE songs_queue SET room_id = ?, sort_order = ? WHERE id = ?");
      for (const id of ids) {
        stmt.run(targetRoomId, maxSort + i, id);
        i++;
      }
      jsonSuccess(res, { message: `${ids.length} song(s) moved` });
      break;
    }

    default:
      jsonError(res, 'Invalid action');
  }
}
