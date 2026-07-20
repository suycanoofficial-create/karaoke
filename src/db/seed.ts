import { db } from './index';

const DEFAULT_SETTINGS: [string, string][] = [
  ['site_name', 'KTV LOUNGE'],
  ['site_tagline', 'Elevate Your Night'],
  ['youtube_api_key', ''],
  ['max_queue_per_room', '50'],
  ['meta_title', 'KTV LOUNGE - Premium Synchronized Karaoke Experience'],
  ['meta_description', 'An elite, app-free synchronized karaoke lounge. Elevate your night with seamless karaoke, tailored for the connoisseur.'],
  ['og_title', 'KTV LOUNGE - Premium Karaoke'],
  ['og_description', 'Seamless synchronized karaoke for the modern lounge experience.'],
  ['og_image', ''],
  ['schema_markup', ''],
  ['brand_primary', '#D4AF37'],
  ['brand_accent', '#C5A059'],
  ['show_now_playing', '1'],
  ['room_cleanup_enabled', '1'],
  ['room_cleanup_time', '1 day'],
  ['queue_cleanup_enabled', '1'],
  ['queue_cleanup_time', '7 days'],
  ['last_cleanup_run', ''],
  ['scoring_enabled', '1'],
];

export async function seedDefaults(): Promise<void> {
  const stmt = db().prepare(
    "INSERT OR IGNORE INTO site_settings (meta_key, meta_value) VALUES (?, ?)"
  );
  for (const [key, value] of DEFAULT_SETTINGS) {
    stmt.run(key, value);
  }

  const adminPasswordHash = await hashPassword(process.env.ADMIN_PASSWORD || 'admin123');
  db().prepare(
    "INSERT OR IGNORE INTO administrators (username, password_hash, role) VALUES (?, ?, 'superadmin')"
  ).run(process.env.ADMIN_USERNAME || 'admin', adminPasswordHash);
}

async function hashPassword(password: string): Promise<string> {
  const { createHash } = await import('node:crypto');
  const { randomBytes, pbkdf2Sync } = await import('node:crypto');
  const salt = randomBytes(16).toString('hex');
  const hash = pbkdf2Sync(password, salt, 1000, 64, 'sha512').toString('hex');
  return `${salt}:${hash}`;
}

export function verifyPassword(password: string, stored: string): boolean {
  const { pbkdf2Sync, timingSafeEqual } = require('node:crypto');
  const [salt, hash] = stored.split(':');
  const verify = pbkdf2Sync(password, salt, 1000, 64, 'sha512').toString('hex');
  return timingSafeEqual(Buffer.from(hash), Buffer.from(verify));
}
