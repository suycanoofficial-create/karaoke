import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const roomCode = (input.room_code || '').toUpperCase();
  const videoTitle = input.video_title || '';
  const youtubeId = input.youtube_id || '';
  const addedBy = input.added_by || 'Guest';

  if (!roomCode || !youtubeId) return jsonError(res, 'Missing required fields');

  const room = db().prepare(
    "SELECT id, locked, status FROM rooms WHERE room_code = ?"
  ).get(roomCode) as any;

  if (!room) return jsonError(res, 'Room not found', 404);
  if (room.status !== 'active') return jsonError(res, 'Room is closed');
  if (room.locked) return jsonError(res, 'Room is locked');

  const maxQueue = parseInt(process.env.MAX_QUEUE_PER_ROOM || '50');
  const count = (db().prepare(
    "SELECT COUNT(*) as cnt FROM songs_queue WHERE room_id = ? AND status IN ('pending','playing')"
  ).get(room.id) as any).cnt;

  if (count >= maxQueue) return jsonError(res, 'Queue is full');

  const maxSort = db().prepare(
    "SELECT COALESCE(MAX(sort_order), -1) + 1 as next FROM songs_queue WHERE room_id = ?"
  ).get(room.id) as any;

  db().prepare(
    "INSERT INTO songs_queue (room_id, video_title, youtube_id, added_by, sort_order, status) VALUES (?, ?, ?, ?, ?, 'pending')"
  ).run(room.id, videoTitle, youtubeId, addedBy, maxSort.next);

  db().prepare(
    "UPDATE rooms SET last_activity = datetime('now') WHERE id = ?"
  ).run(room.id);

  jsonSuccess(res, { message: 'Song added to queue' });
}
