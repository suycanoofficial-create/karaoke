import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const action = (req.query.action as string) || '';
  const roomId = parseInt(req.query.room_id as string) || 0;

  if (req.method === 'POST' && action === 'send') {
    const input = req.body;
    if (!input || !roomId) return jsonError(res, 'Invalid request');

    const chunk: string = input.chunk || '';
    const seq: number = parseInt(input.seq) || 0;
    if (!chunk) return jsonError(res, 'No audio data');

    const data = Buffer.from(chunk, 'base64');

    db().prepare(
      "DELETE FROM audio_relay WHERE room_id = ? AND created_at < datetime('now', '-5 seconds')"
    ).run(roomId);

    db().prepare(
      "INSERT INTO audio_relay (room_id, chunk_data, chunk_seq) VALUES (?, ?, ?)"
    ).run(roomId, data, seq);

    return jsonSuccess(res, { received: seq });
  }

  if (action === 'receive') {
    if (!roomId) return jsonError(res, 'Missing room_id');

    const chunks = db().prepare(
      "SELECT id, chunk_data, chunk_seq FROM audio_relay WHERE room_id = ? ORDER BY chunk_seq ASC LIMIT 50"
    ).all(roomId) as any[];

    const result = chunks.map((row: any) => ({
      id: row.id,
      seq: row.chunk_seq,
      data: Buffer.from(row.chunk_data).toString('base64'),
    }));

    if (result.length > 0) {
      const ids = result.map((r: any) => r.id);
      const placeholders = ids.map(() => '?').join(',');
      db().prepare(`DELETE FROM audio_relay WHERE id IN (${placeholders})`).run(...ids);
    }

    return jsonSuccess(res, { chunks: result });
  }

  if (action === 'stop') {
    db().prepare("DELETE FROM audio_relay WHERE room_id = ?").run(roomId);
    return jsonSuccess(res, { message: 'Stream stopped' });
  }

  jsonError(res, 'Invalid action');
}
