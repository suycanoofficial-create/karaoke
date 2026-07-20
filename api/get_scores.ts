import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const trackId = parseInt(req.query.track_id as string) || 0;
  if (!trackId) return jsonError(res, 'Missing track_id');

  const avg = (db().prepare(
    "SELECT AVG(score) as avg_score FROM song_scores WHERE track_id = ?"
  ).get(trackId) as any).avg_score;

  const scores = db().prepare(
    "SELECT id, score, scored_by, created_at FROM song_scores WHERE track_id = ? ORDER BY created_at DESC"
  ).all(trackId) as any[];

  jsonSuccess(res, {
    average: avg ? Math.round(avg) : 0,
    count: scores.length,
    scores,
  });
}
