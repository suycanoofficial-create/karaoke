import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const roomCode = (req.query.code as string) || '';

  if (!roomCode) return jsonError(res, 'Missing room code');

  const room = db().prepare(
    "SELECT id, room_code, status, locked, playback_cmd, echo_delay, echo_feedback, echo_mix FROM rooms WHERE room_code = ?"
  ).get(roomCode) as any;

  if (!room) return jsonError(res, 'Room not found', 404);

  jsonSuccess(res, { room });
}
