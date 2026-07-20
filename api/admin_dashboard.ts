import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (!requireAdmin(req)) return jsonError(res, 'Unauthorized', 401);

  const activeRooms = (db().prepare("SELECT COUNT(*) as cnt FROM rooms WHERE status = 'active'").get() as any).cnt;
  const totalRooms = (db().prepare("SELECT COUNT(*) as cnt FROM rooms").get() as any).cnt;
  const pendingSongs = (db().prepare("SELECT COUNT(*) as cnt FROM songs_queue WHERE status = 'pending'").get() as any).cnt;
  const totalSongs = (db().prepare("SELECT COUNT(*) as cnt FROM songs_queue").get() as any).cnt;
  const todayRooms = (db().prepare("SELECT COUNT(*) as cnt FROM rooms WHERE date(created_at) = date('now')").get() as any).cnt;

  const recentRooms = db().prepare(`
    SELECT r.*, (SELECT COUNT(*) FROM songs_queue sq WHERE sq.room_id = r.id) as song_count
    FROM rooms r ORDER BY r.created_at DESC LIMIT 20
  `).all() as any[];

  jsonSuccess(res, {
    stats: { active_rooms: activeRooms, total_rooms: totalRooms, pending_songs: pendingSongs, total_songs: totalSongs, today_rooms: todayRooms },
    recent_rooms: recentRooms,
  });
}
