import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const trackId = parseInt(input.track_id) || 0;
  const score = parseInt(input.score) || 0;

  if (!trackId || score < 0 || score > 100) return jsonError(res, 'Invalid input');

  const existing = db().prepare("SELECT score FROM songs_queue WHERE id = ?").get(trackId) as any;
  if (!existing) return jsonError(res, 'Track not found', 404);

  if (existing.score === null || score > existing.score) {
    db().prepare("UPDATE songs_queue SET score = ? WHERE id = ?").run(score, trackId);
  }

  jsonSuccess(res, { message: 'Score updated', score: Math.max(existing.score || 0, score) });
}
