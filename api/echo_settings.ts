import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const roomId = parseInt(req.query.room_id as string) || 0;

  if (req.method === 'GET') {
    if (!roomId) return jsonError(res, 'Missing room_id');
    const room = db().prepare(
      "SELECT echo_delay, echo_feedback, echo_mix FROM rooms WHERE id = ?"
    ).get(roomId) as any;
    if (!room) return jsonError(res, 'Room not found', 404);
    return jsonSuccess(res, { settings: room });
  }

  if (req.method === 'POST') {
    const input = req.body;
    if (!input) return jsonError(res, 'Invalid request body');

    const token = (input.token || '').trim();
    const rId = parseInt(input.room_id) || roomId;
    if (!rId) return jsonError(res, 'Missing room_id');

    const hostOk = db().prepare(
      "SELECT id FROM rooms WHERE id = ? AND host_session_token = ?"
    ).get(rId, token);
    if (!hostOk) return jsonError(res, 'Unauthorized', 401);

    const updates: string[] = [];
    const params: any[] = [];
    for (const field of ['echo_delay', 'echo_feedback', 'echo_mix'] as const) {
      if (input[field] !== undefined) {
        updates.push(`${field} = ?`);
        params.push(parseFloat(input[field]));
      }
    }
    if (updates.length === 0) return jsonError(res, 'No settings to update');

    params.push(rId);
    db().prepare(`UPDATE rooms SET ${updates.join(', ')} WHERE id = ?`).run(...params);

    const room = db().prepare("SELECT echo_delay, echo_feedback, echo_mix FROM rooms WHERE id = ?").get(rId) as any;
    return jsonSuccess(res, { settings: room });
  }

  jsonError(res, 'Method not allowed', 405);
}
