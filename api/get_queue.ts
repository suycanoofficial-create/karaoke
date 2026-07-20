import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { verifyHostToken } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const roomId = parseInt(req.query.room_id as string) || 0;
  const token = (req.query.token as string) || '';

  if (!roomId) return jsonError(res, 'Missing room_id');

  if (token && !verifyHostToken(roomId, token)) {
    return jsonError(res, 'Unauthorized', 401);
  }

  const showCompleted = req.query.completed === '1';
  const statusFilter = showCompleted
    ? "status IN ('completed')"
    : "status IN ('pending', 'playing')";

  const tracks = db().prepare(`
    SELECT id, video_title, youtube_id, added_by, sort_order, status, started_at, score, created_at
    FROM songs_queue
    WHERE room_id = ? AND ${statusFilter}
    ORDER BY
      CASE status
        WHEN 'playing' THEN 0
        WHEN 'pending' THEN 1
        WHEN 'completed' THEN 2
      END,
      sort_order ASC,
      created_at ASC
  `).all(roomId) as any[];

  const now = Math.floor(Date.now() / 1000);
  for (const track of tracks) {
    if (track.status === 'playing' && track.started_at) {
      const started = Math.floor(new Date(track.started_at).getTime() / 1000);
      track.elapsed = Math.max(0, now - started);
    } else {
      track.elapsed = 0;
    }
  }

  jsonSuccess(res, { tracks, server_time: now, count: tracks.length });
}
