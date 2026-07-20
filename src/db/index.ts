import { createClient, Client } from '@libsql/client';

let _db: Client | null = null;

export function db(): Client {
  if (!_db) {
    const url = process.env.TURSO_DATABASE_URL;
    const authToken = process.env.TURSO_AUTH_TOKEN;

    if (!url) {
      throw new Error('TURSO_DATABASE_URL environment variable is required');
    }

    _db = createClient({
      url,
      authToken,
    });
  }
  return _db;
}

export function getSetting(key: string, defaultValue: string = ''): string {
  try {
    const row = db().prepare(
      "SELECT meta_value FROM site_settings WHERE meta_key = ?"
    ).get(key) as { meta_value: string } | undefined;
    return row?.meta_value ?? defaultValue;
  } catch {
    return defaultValue;
  }
}

export function getAllSettings(): Record<string, string> {
  try {
    const rows = db().prepare(
      "SELECT meta_key, meta_value FROM site_settings"
    ).all() as { meta_key: string; meta_value: string }[];
    const settings: Record<string, string> = {};
    for (const row of rows) {
      settings[row.meta_key] = row.meta_value;
    }
    return settings;
  } catch {
    return {};
  }
}

export function updateSetting(key: string, value: string): void {
  db().prepare(
    "INSERT INTO site_settings (meta_key, meta_value) VALUES (?, ?) ON CONFLICT(meta_key) DO UPDATE SET meta_value = excluded.meta_value"
  ).run(key, value);
}
