export const SCHEMA_SQL = `
CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_code TEXT NOT NULL UNIQUE,
    host_session_token TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','closed')),
    locked INTEGER NOT NULL DEFAULT 0,
    echo_delay REAL NOT NULL DEFAULT 0.35,
    echo_feedback REAL NOT NULL DEFAULT 0.35,
    echo_mix REAL NOT NULL DEFAULT 0.35,
    playback_cmd TEXT DEFAULT NULL,
    last_activity TEXT DEFAULT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_room_code ON rooms(room_code);
CREATE INDEX IF NOT EXISTS idx_rooms_status ON rooms(status);
CREATE INDEX IF NOT EXISTS idx_rooms_last_activity ON rooms(last_activity);

CREATE TABLE IF NOT EXISTS songs_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    video_id TEXT DEFAULT NULL,
    room_id INTEGER NOT NULL,
    video_title TEXT NOT NULL,
    youtube_id TEXT NOT NULL,
    added_by TEXT NOT NULL DEFAULT 'Guest',
    sort_order INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','playing','completed','skipped')),
    started_at TEXT DEFAULT NULL,
    score INTEGER DEFAULT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_queue_room_status ON songs_queue(room_id, status);
CREATE INDEX IF NOT EXISTS idx_queue_sort_order ON songs_queue(room_id, sort_order);

CREATE TABLE IF NOT EXISTS site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    meta_key TEXT NOT NULL UNIQUE,
    meta_value TEXT DEFAULT NULL,
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_meta_key ON site_settings(meta_key);

CREATE TABLE IF NOT EXISTS administrators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin' CHECK(role IN ('superadmin','admin','editor')),
    last_login TEXT DEFAULT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_admin_username ON administrators(username);

CREATE TABLE IF NOT EXISTS guest_heartbeats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    guest_id TEXT NOT NULL,
    nickname TEXT NOT NULL DEFAULT 'Guest',
    last_seen TEXT DEFAULT (datetime('now')),
    UNIQUE(room_id, guest_id)
);

CREATE INDEX IF NOT EXISTS idx_heartbeat_room_lastseen ON guest_heartbeats(room_id, last_seen);

CREATE TABLE IF NOT EXISTS playlist_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS playlist_songs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL REFERENCES playlist_profiles(id) ON DELETE CASCADE,
    video_title TEXT NOT NULL,
    youtube_id TEXT NOT NULL,
    added_by TEXT NOT NULL DEFAULT 'Admin',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_playlist_profile ON playlist_songs(profile_id);
CREATE INDEX IF NOT EXISTS idx_playlist_youtube ON playlist_songs(youtube_id);

CREATE TABLE IF NOT EXISTS cheers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    from_nick TEXT DEFAULT '',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_cheers_room_created ON cheers(room_id, created_at);

CREATE TABLE IF NOT EXISTS reactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    from_nick TEXT DEFAULT '',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_reactions_room_created ON reactions(room_id, created_at);

CREATE TABLE IF NOT EXISTS audio_relay (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    chunk_data BLOB NOT NULL,
    chunk_seq INTEGER NOT NULL DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_audio_room_seq ON audio_relay(room_id, chunk_seq);

CREATE TABLE IF NOT EXISTS song_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    track_id INTEGER NOT NULL,
    room_id INTEGER NOT NULL,
    score INTEGER NOT NULL,
    scored_by TEXT NOT NULL DEFAULT 'Guest',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_scores_track ON song_scores(track_id);
CREATE INDEX IF NOT EXISTS idx_scores_room ON song_scores(room_id);

-- Trigger for rooms.updated_at
CREATE TRIGGER IF NOT EXISTS trg_rooms_updated
AFTER UPDATE ON rooms
FOR EACH ROW
BEGIN
    UPDATE rooms SET updated_at = datetime('now') WHERE id = OLD.id;
END;

-- Trigger for site_settings.updated_at
CREATE TRIGGER IF NOT EXISTS trg_settings_updated
AFTER UPDATE ON site_settings
FOR EACH ROW
BEGIN
    UPDATE site_settings SET updated_at = datetime('now') WHERE id = OLD.id;
END;
`;
