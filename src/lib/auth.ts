import { db } from '../db/index';
import { verifyPassword } from '../db/seed';
import { VercelRequest } from '@vercel/node';

export function generateToken(length = 32): string {
  const { randomBytes } = require('node:crypto');
  return randomBytes(length).toString('hex');
}

export function generateRoomCode(length = 6): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let code = '';
  for (let i = 0; i < length; i++) {
    code += chars[Math.floor(Math.random() * chars.length)];
  }
  return code;
}

export function verifyHostToken(roomId: number, token: string): boolean {
  const row = db().prepare(
    "SELECT id FROM rooms WHERE id = ? AND host_session_token = ?"
  ).get(roomId, token);
  return !!row;
}

export interface AdminSession {
  admin_id: number;
  admin_role: string;
  admin_username: string;
}

export function getAdminSession(req: VercelRequest): AdminSession | null {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return null;
  }
  const token = authHeader.slice(7);
  return validateAdminToken(token);
}

export function createAdminToken(admin: { id: number; username: string; role: string }): string {
  const crypto = require('node:crypto');
  const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT' })).toString('base64url');
  const payload = Buffer.from(JSON.stringify({
    sub: admin.id,
    username: admin.username,
    role: admin.role,
    iat: Math.floor(Date.now() / 1000),
    exp: Math.floor(Date.now() / 1000) + 86400, // 24h
  })).toString('base64url');
  const secret = process.env.JWT_SECRET || 'ktv-lounge-secret-change-me';
  const signature = crypto.createHmac('sha256', secret).update(`${header}.${payload}`).digest('base64url');
  return `${header}.${payload}.${signature}`;
}

export function validateAdminToken(token: string): AdminSession | null {
  try {
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    const secret = process.env.JWT_SECRET || 'ktv-lounge-secret-change-me';
    const crypto = require('node:crypto');
    const expectedSig = crypto.createHmac('sha256', secret).update(`${parts[0]}.${parts[1]}`).digest('base64url');
    if (!crypto.timingSafeEqual(Buffer.from(expectedSig), Buffer.from(parts[2]))) {
      return null;
    }
    const payload = JSON.parse(Buffer.from(parts[1], 'base64url').toString());
    if (payload.exp < Math.floor(Date.now() / 1000)) return null;
    return {
      admin_id: payload.sub,
      admin_role: payload.role,
      admin_username: payload.username,
    };
  } catch {
    return null;
  }
}

export function requireAdmin(req: VercelRequest): AdminSession | null {
  return getAdminSession(req);
}

export function adminLogin(username: string, password: string): AdminSession | null {
  const row = db().prepare(
    "SELECT id, username, password_hash, role FROM administrators WHERE username = ?"
  ).get(username) as { id: number; username: string; password_hash: string; role: string } | undefined;

  if (!row) return null;
  if (!verifyPassword(password, row.password_hash)) return null;

  db().prepare(
    "UPDATE administrators SET last_login = datetime('now') WHERE id = ?"
  ).run(row.id);

  return {
    admin_id: row.id,
    admin_role: row.role,
    admin_username: row.username,
  };
}
