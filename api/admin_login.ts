import { VercelRequest, VercelResponse } from '@vercel/node';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { adminLogin, createAdminToken } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') return jsonError(res, 'Method not allowed', 405);

  const input = req.body;
  if (!input) return jsonError(res, 'Invalid request body');

  const username = (input.username || '').trim();
  const password = input.password || '';

  if (!username || !password) return jsonError(res, 'Username and password required');

  const session = adminLogin(username, password);
  if (!session) return jsonError(res, 'Invalid credentials', 401);

  const token = createAdminToken({
    id: session.admin_id,
    username: session.admin_username,
    role: session.admin_role,
  });

  jsonSuccess(res, {
    token,
    user: {
      username: session.admin_username,
      role: session.admin_role,
    },
  });
}
