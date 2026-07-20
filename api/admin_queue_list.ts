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
  if (filter === 'pending') where = "WHERE sq.status = 'pending'";
  else if (filter === 'playing') where = "WHERE sq.status = 'playing'";
  else if (filter === 'completed') where = "WHERE sq.status = 'completed'";
  else if (filter === 'skipped') where = "WHERE sq.status = 'skipped'";

  const totalRows = (db().prepare(`
    SELECT COUNT(*) as cnt FROM songs_queue sq JOIN rooms r ON sq.room_id = r.id ${where}
  `).get() as any).cnt;

  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

  const queue = db().prepare(`
    SELECT sq.*, r.room_code
    FROM songs_queue sq
    JOIN rooms r ON sq.room_id = r.id
    ${where}
    ORDER BY sq.created_at DESC
    LIMIT ? OFFSET ?
  `).all(perPage, offset) as any[];

  const activeRooms = db().prepare(
    "SELECT id, room_code FROM rooms WHERE status = 'active' ORDER BY room_code"
  ).all() as any[];

  jsonSuccess(res, {
    queue,
    total_rows: totalRows,
    total_pages: totalPages,
    page,
    per_page: perPage,
    active_rooms: activeRooms,
  });
}
