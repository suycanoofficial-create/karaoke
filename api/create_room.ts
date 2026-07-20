import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { generateRoomCode, generateToken } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  try {
    let roomCode = generateRoomCode(6);
    const hostToken = generateToken(32);

    let retries = 0;
    while (db().prepare("SELECT id FROM rooms WHERE room_code = ?").get(roomCode) && retries < 10) {
      roomCode = generateRoomCode(6);
      retries++;
    }

    db().prepare(
      "INSERT INTO rooms (room_code, host_session_token, status, last_activity) VALUES (?, ?, 'active', datetime('now'))"
    ).run(roomCode, hostToken);

    const roomId = (db().prepare("SELECT id FROM rooms WHERE room_code = ?").get(roomCode) as any).id;

    jsonSuccess(res, {
      room_id: roomId,
      room_code: roomCode,
      token: hostToken,
    });
  } catch (e) {
    jsonError(res, 'Failed to create room', 500);
  }
}
