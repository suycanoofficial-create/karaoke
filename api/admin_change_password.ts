import { VercelRequest, VercelResponse } from '@vercel/node';
import { db } from '../src/db/index';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin, validateAdminToken } from '../src/lib/auth';
import { verifyPassword } from '../src/db/seed';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const session = requireAdmin(req);
  if (!session) return jsonError(res, 'Unauthorized', 401);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const currentPassword = input.current_password || '';
  const newPassword = input.new_password || '';

  if (!currentPassword || !newPassword) return jsonError(res, 'Current and new password required');
  if (newPassword.length < 6) return jsonError(res, 'New password must be at least 6 characters');

  const admin = db().prepare(
    "SELECT password_hash FROM administrators WHERE id = ?"
  ).get(session.admin_id) as any;

  if (!admin || !verifyPassword(currentPassword, admin.password_hash)) {
    return jsonError(res, 'Current password is incorrect', 401);
  }

  const { randomBytes, pbkdf2Sync } = require('node:crypto');
  const salt = randomBytes(16).toString('hex');
  const hash = pbkdf2Sync(newPassword, salt, 1000, 64, 'sha512').toString('hex');

  db().prepare("UPDATE administrators SET password_hash = ? WHERE id = ?").run(`${salt}:${hash}`, session.admin_id);

  jsonSuccess(res, { message: 'Password changed successfully' });
}
