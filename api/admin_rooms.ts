import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (!requireAdmin(req)) return jsonError(res, 'Unauthorized', 401);

  const filter = (req.query.filter as string) || 'all';
  const page = Math.max(1, parseInt(req.query.page as string) || 1);
  const perPage = Math.max(5, Math.min(50, parseInt(req.query.per_page as string) || 10));
  const offset = (page - 1) * perPage;

  let where = '';
  if (filter === 'active') where = "WHERE r.status = 'active'";
  else if (filter === 'closed') where = "WHERE r.status = 'closed'";

  const totalRows = (db().prepare(`SELECT COUNT(*) as cnt FROM rooms r ${where}`).get() as any).cnt;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

  const rooms = db().prepare(`
    SELECT r.*,
      (SELECT COUNT(*) FROM songs_queue sq WHERE sq.room_id = r.id) as song_count,
      (SELECT COUNT(*) FROM songs_queue sq WHERE sq.room_id = r.id AND sq.status = 'pending') as pending_count
    FROM rooms r ${where}
    ORDER BY r.created_at DESC LIMIT ? OFFSET ?
  `).all(perPage, offset) as any[];

  jsonSuccess(res, { rooms, total_rows: totalRows, total_pages: totalPages, page, per_page: perPage });
}
