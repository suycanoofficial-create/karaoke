import { VercelResponse } from '@vercel/node';

export function jsonResponse(res: VercelResponse, data: unknown, status = 200): void {
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  res.status(status).json(data);
}

export function jsonSuccess(res: VercelResponse, data: Record<string, unknown> = {}): void {
  jsonResponse(res, { success: true, ...data });
}

export function jsonError(res: VercelResponse, message: string, status = 400): void {
  jsonResponse(res, { success: false, error: message }, status);
}
