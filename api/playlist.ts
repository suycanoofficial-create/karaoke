import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const action = (input.action || '').trim();
  if (!action) return jsonError(res, 'Missing action');

  try {
    switch (action) {
      case 'get_profiles': {
        const profiles = db().prepare(`
          SELECT pp.*, COALESCE(ps.cnt, 0) as song_count
          FROM playlist_profiles pp
          LEFT JOIN (SELECT profile_id, COUNT(*) as cnt FROM playlist_songs GROUP BY profile_id) ps ON pp.id = ps.profile_id
          ORDER BY pp.name ASC
        `).all() as any[];
        return jsonSuccess(res, { profiles });
      }

      case 'create_profile': {
        const admin = requireAdmin(req);
        if (!admin) return jsonError(res, 'Unauthorized', 401);

        const name = (input.name || '').trim();
        if (!name) return jsonError(res, 'Profile name required');

        db().prepare("INSERT INTO playlist_profiles (name) VALUES (?)").run(name);
        return jsonSuccess(res, { message: 'Profile created' });
      }

      case 'get_songs': {
        const profileId = parseInt(input.profile_id) || 0;
        if (!profileId) return jsonError(res, 'Missing profile_id');

        const songs = db().prepare(
          "SELECT id, video_title, youtube_id, added_by, created_at FROM playlist_songs WHERE profile_id = ? ORDER BY created_at DESC"
        ).all(profileId) as any[];
        return jsonSuccess(res, { songs });
      }

      case 'add_to_profile': {
        const admin = requireAdmin(req);
        if (!admin) return jsonError(res, 'Unauthorized', 401);

        const id = parseInt(input.id) || 0;
        const profileId = parseInt(input.profile_id) || 0;
        if (!id || !profileId) return jsonError(res, 'Missing required fields');

        const track = db().prepare("SELECT video_title, youtube_id FROM songs_queue WHERE id = ?").get(id) as any;
        if (!track) return jsonError(res, 'Track not found', 404);

        const exists = db().prepare(
          "SELECT id FROM playlist_songs WHERE profile_id = ? AND youtube_id = ?"
        ).get(profileId, track.youtube_id);
        if (exists) return jsonError(res, 'Song already in playlist');

        db().prepare(
          "INSERT INTO playlist_songs (profile_id, video_title, youtube_id) VALUES (?, ?, ?)"
        ).run(profileId, track.video_title, track.youtube_id);

        return jsonSuccess(res, { message: 'Song added to profile' });
      }

      case 'delete': {
        const admin = requireAdmin(req);
        if (!admin) return jsonError(res, 'Unauthorized', 401);

        const id = parseInt(input.id) || 0;
        if (!id) return jsonError(res, 'Missing song ID');

        db().prepare("DELETE FROM playlist_songs WHERE id = ?").run(id);
        return jsonSuccess(res, { message: 'Song deleted' });
      }

      case 'bulk_delete': {
        const admin = requireAdmin(req);
        if (!admin) return jsonError(res, 'Unauthorized', 401);

        const ids: number[] = input.ids || [];
        if (ids.length === 0) return jsonError(res, 'No IDs provided');

        const placeholders = ids.map(() => '?').join(',');
        db().prepare(`DELETE FROM playlist_songs WHERE id IN (${placeholders})`).run(...ids);
        return jsonSuccess(res, { message: `${ids.length} song(s) deleted` });
      }

      case 'delete_profile': {
        const admin = requireAdmin(req);
        if (!admin) return jsonError(res, 'Unauthorized', 401);

        const profileId = parseInt(input.profile_id) || 0;
        if (!profileId) return jsonError(res, 'Missing profile_id');

        db().prepare("DELETE FROM playlist_profiles WHERE id = ?").run(profileId);
        return jsonSuccess(res, { message: 'Profile deleted' });
      }

      case 'recover': {
        const admin = requireAdmin(req);
        if (!admin) return jsonError(res, 'Unauthorized', 401);

        const id = parseInt(input.id) || 0;
        const roomCode = (input.room_code || '').toUpperCase();

        if (!id || !roomCode) return jsonError(res, 'Missing required fields');

        const song = db().prepare("SELECT * FROM playlist_songs WHERE id = ?").get(id) as any;
        if (!song) return jsonError(res, 'Song not found', 404);

        const room = db().prepare("SELECT id FROM rooms WHERE room_code = ? AND status = 'active'").get(roomCode);
        if (!room) return jsonError(res, 'Room not active or not found', 404);

        const maxSort = (db().prepare(
          "SELECT COALESCE(MAX(sort_order), -1) + 1 as next FROM songs_queue WHERE room_id = ?"
        ).get((room as any).id) as any).next;

        db().prepare(
          "INSERT INTO songs_queue (room_id, video_title, youtube_id, added_by, sort_order, status) VALUES (?, ?, ?, 'Playlist', ?, 'pending')"
        ).run((room as any).id, song.video_title, song.youtube_id, maxSort);

        return jsonSuccess(res, { message: 'Song added to queue' });
      }

      case 'bulk_recover': {
        const admin = requireAdmin(req);
        if (!admin) return jsonError(res, 'Unauthorized', 401);

        const ids: number[] = input.ids || [];
        const roomCode = (input.room_code || '').toUpperCase();

        if (ids.length === 0 || !roomCode) return jsonError(res, 'Missing required fields');

        const room = db().prepare("SELECT id FROM rooms WHERE room_code = ? AND status = 'active'").get(roomCode);
        if (!room) return jsonError(res, 'Room not active or not found', 404);

        const placeholders = ids.map(() => '?').join(',');
        const songs = db().prepare(
          `SELECT id, video_title, youtube_id FROM playlist_songs WHERE id IN (${placeholders})`
        ).all(...ids) as any[];

        let maxSortVal = (db().prepare(
          "SELECT COALESCE(MAX(sort_order), -1) + 1 as next FROM songs_queue WHERE room_id = ?"
        ).get((room as any).id) as any).next;

        let added = 0;
        const stmt = db().prepare(
          "INSERT INTO songs_queue (room_id, video_title, youtube_id, added_by, sort_order, status) VALUES (?, ?, ?, 'Playlist', ?, 'pending')"
        );
        for (const song of songs) {
          stmt.run((room as any).id, song.video_title, song.youtube_id, maxSortVal++);
          added++;
        }

        return jsonSuccess(res, { message: `${added} song(s) added to queue` });
      }

      default:
        jsonError(res, 'Invalid action');
    }
  } catch (e: any) {
    jsonError(res, e.message || 'Operation failed', 500);
  }
}
