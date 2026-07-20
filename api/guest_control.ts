import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method === 'GET') {
    const roomId = parseInt(req.query.room_id as string) || 0;
    if (!roomId) return jsonError(res, 'Missing room_id');

    const room = db().prepare(
      "SELECT id, playback_cmd FROM rooms WHERE id = ?"
    ).get(roomId) as any;
    if (!room) return jsonError(res, 'Room not found', 404);

    return jsonSuccess(res, { playback_cmd: room.playback_cmd });
  }

  if (req.method === 'POST') {
    const input = req.body;
    if (!input) return jsonError(res, 'Invalid request body');

    const roomCode = (input.room_code || '').toUpperCase();
    const cmd = (input.cmd || '').trim();

    if (!roomCode || !cmd) return jsonError(res, 'Missing required fields');

    const room = db().prepare("SELECT id FROM rooms WHERE room_code = ?").get(roomCode) as any;
    if (!room) return jsonError(res, 'Room not found', 404);

    db().prepare("UPDATE rooms SET playback_cmd = ? WHERE id = ?").run(cmd, room.id);

    return jsonSuccess(res, { message: `Command sent: ${cmd}` });
  }

  jsonError(res, 'Method not allowed', 405);
}
