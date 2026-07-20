import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

const HUMAN_TIME_MAP: Record<string, string> = {
  '10 seconds': '10 seconds',
  '10 minutes': '10 minutes',
  '1 day': '1 day',
  '2 days': '2 days',
  '7 days': '7 days',
  '15 days': '15 days',
  '30 days': '30 days',
};

export default function handler(req: VercelRequest, res: VercelResponse) {
  const isInternal = req.query.internal === '1';
  if (!isInternal && !requireAdmin(req)) {
    return jsonError(res, 'Unauthorized', 401);
  }

  try {
    const lastRun = (db().prepare(
      "SELECT meta_value FROM site_settings WHERE meta_key = 'last_cleanup_run'"
    ).get() as any)?.meta_value;

    if (lastRun) {
      const lastRunTime = new Date(lastRun).getTime();
      if (Date.now() - lastRunTime < 60000) {
        return jsonSuccess(res, { message: 'Throttled - last run was less than 1 minute ago' });
      }
    }

    const roomCleanupEnabled = (db().prepare(
      "SELECT meta_value FROM site_settings WHERE meta_key = 'room_cleanup_enabled'"
    ).get() as any)?.meta_value;

    if (roomCleanupEnabled === '1') {
      const roomTime = (db().prepare(
        "SELECT meta_value FROM site_settings WHERE meta_key = 'room_cleanup_time'"
      ).get() as any)?.meta_value || '1 day';

      const timeStr = HUMAN_TIME_MAP[roomTime] || '1 day';
      db().prepare(`
        UPDATE rooms SET status = 'closed'
        WHERE status = 'active'
          AND last_activity IS NOT NULL
          AND last_activity < datetime('now', '-${timeStr}')
          AND (SELECT COUNT(*) FROM songs_queue WHERE songs_queue.room_id = rooms.id AND status IN ('pending', 'playing')) = 0
      `).run();

      db().prepare(`
        UPDATE rooms SET status = 'closed'
        WHERE status = 'active'
          AND last_activity IS NULL
          AND created_at < datetime('now', '-${timeStr}')
          AND (SELECT COUNT(*) FROM songs_queue WHERE songs_queue.room_id = rooms.id AND status IN ('pending', 'playing')) = 0
      `).run();
    }

    const queueCleanupEnabled = (db().prepare(
      "SELECT meta_value FROM site_settings WHERE meta_key = 'queue_cleanup_enabled'"
    ).get() as any)?.meta_value;

    if (queueCleanupEnabled === '1') {
      const queueTime = (db().prepare(
        "SELECT meta_value FROM site_settings WHERE meta_key = 'queue_cleanup_time'"
      ).get() as any)?.meta_value || '7 days';

      const timeStr = HUMAN_TIME_MAP[queueTime] || '7 days';
      db().prepare(`
        DELETE FROM songs_queue
        WHERE status IN ('completed', 'skipped')
          AND created_at < datetime('now', '-${timeStr}')
      `).run();
    }

    db().prepare(
      "INSERT INTO site_settings (meta_key, meta_value) VALUES ('last_cleanup_run', datetime('now')) ON CONFLICT(meta_key) DO UPDATE SET meta_value = excluded.meta_value"
    ).run();

    jsonSuccess(res, { message: 'Cleanup completed' });
  } catch (e) {
    jsonError(res, 'Cleanup failed', 500);
  }
}
