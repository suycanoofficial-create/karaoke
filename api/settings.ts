import { VercelRequest, VercelResponse } from '@vercel/node';
import { getSetting, getAllSettings } from '../src/db/index';
import { jsonSuccess } from '../src/lib/response';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const key = req.query.key as string;

  if (key) {
    const value = getSetting(key);
    return jsonSuccess(res, { key, value });
  }

  const settings = getAllSettings();
  jsonSuccess(res, { settings });
}
