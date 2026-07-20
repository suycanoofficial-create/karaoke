import { VercelRequest, VercelResponse } from '@vercel/node';
import { jsonSuccess, jsonError } from '../src/lib/response';
import { requireAdmin } from '../src/lib/auth';

export default function handler(req: VercelRequest, res: VercelResponse) {
  const session = requireAdmin(req);
  if (!session) return jsonError(res, 'Unauthorized', 401);

  jsonSuccess(res, {
    user: {
      id: session.admin_id,
      username: session.admin_username,
      role: session.admin_role,
    },
  });
}
