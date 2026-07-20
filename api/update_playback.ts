import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { verifyHostToken } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const action = (input.action || '').trim();
  const roomId = parseInt(input.room_id) || 0;
  const token = (input.token || '').trim();
  const trackId = parseInt(input.track_id) || 0;

  if (!action || !roomId || !token) return jsonError(res, 'Missing required fields');

  if (!verifyHostToken(roomId, token)) return jsonError(res, 'Unauthorized', 401);

  try {
    db().prepare("UPDATE rooms SET last_activity = datetime('now') WHERE id = ?").run(roomId);

    switch (action) {
      case 'play': {
        const time = new Date().toISOString().replace('T', ' ').slice(0, 19);
        db().prepare("UPDATE songs_queue SET status = 'playing', started_at = ? WHERE id = ? AND room_id = ?").run(time, trackId, roomId);
        return jsonSuccess(res, { message: 'Track playing' });
      }

      case 'skip':
      case 'complete': {
        const newStatus = action === 'skip' ? 'skipped' : 'completed';
        db().prepare("UPDATE songs_queue SET status = ? WHERE id = ? AND room_id = ?").run(newStatus, trackId, roomId);

        const next = db().prepare(
          "SELECT id FROM songs_queue WHERE room_id = ? AND status = 'pending' ORDER BY sort_order ASC, created_at ASC LIMIT 1"
        ).get(roomId) as any;

        if (next) {
          const time = new Date().toISOString().replace('T', ' ').slice(0, 19);
          db().prepare("UPDATE songs_queue SET status = 'playing', started_at = ? WHERE id = ?").run(time, next.id);
          jsonSuccess(res, { message: `Track ${action}d`, next_id: next.id });
        } else {
          jsonSuccess(res, { message: `Track ${action}d`, next_id: null });
        }
        break;
      }

      case 'clear':
        db().prepare("UPDATE songs_queue SET status = 'skipped' WHERE room_id = ? AND status = 'pending'").run(roomId);
        jsonSuccess(res, { message: 'Queue cleared' });
        break;

      case 'close':
        db().prepare("UPDATE rooms SET status = 'closed' WHERE id = ?").run(roomId);
        db().prepare("UPDATE songs_queue SET status = 'skipped' WHERE room_id = ? AND status IN ('pending', 'playing')").run(roomId);
        jsonSuccess(res, { message: 'Room closed' });
        break;

      case 'reorder': {
        const orderedIds: number[] = input.ordered_ids || [];
        if (!Array.isArray(orderedIds)) return jsonError(res, 'No track IDs provided');
        const stmt = db().prepare("UPDATE songs_queue SET sort_order = ? WHERE id = ? AND room_id = ?");
        for (let i = 0; i < orderedIds.length; i++) {
          if (orderedIds[i] > 0) stmt.run(i, orderedIds[i], roomId);
        }
        jsonSuccess(res, { message: 'Queue reordered' });
        break;
      }

      default:
        jsonError(res, 'Invalid action');
    }
  } catch (e) {
    jsonError(res, 'Operation failed', 500);
  }
}
