import { VercelRequest, VercelResponse } from '@vercel/node';
import { getAllSettings, updateSetting } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (!requireAdmin(req)) return jsonError(res, 'Unauthorized', 401);

  if (req.method === 'GET') {
    const settings = getAllSettings();
    return jsonSuccess(res, { settings });
  }

  if (req.method === 'POST') {
    const input = req.body;
    if (!input) return jsonError(res, 'Invalid request body');

    try {
      for (const [key, value] of Object.entries(input)) {
        if (typeof value === 'string' || typeof value === 'boolean' || typeof value === 'number') {
          updateSetting(key, String(value));
        }
      }
      jsonSuccess(res, { message: 'Settings updated' });
    } catch (e: any) {
      jsonError(res, 'Failed to update settings: ' + e.message, 500);
    }
    return;
  }

  jsonError(res, 'Method not allowed', 405);
}
