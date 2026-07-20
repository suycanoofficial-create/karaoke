<?php
/**
 * Admin - Settings Management
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['site_name', 'site_tagline', 'youtube_api_key', 'max_queue_per_room', 'brand_primary', 'brand_accent'];
    
    try {
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_setting($field, trim($_POST[$field]));
            }
        }
        // Handle checkboxes
        update_setting('show_now_playing', isset($_POST['show_now_playing']) ? '1' : '0');
        update_setting('room_cleanup_enabled', isset($_POST['room_cleanup_enabled']) ? '1' : '0');
        update_setting('queue_cleanup_enabled', isset($_POST['queue_cleanup_enabled']) ? '1' : '0');
        // Handle cleanup time selects
        if (isset($_POST['room_cleanup_time'])) {
            update_setting('room_cleanup_time', trim($_POST['room_cleanup_time']));
        }
        if (isset($_POST['queue_cleanup_time'])) {
            update_setting('queue_cleanup_time', trim($_POST['queue_cleanup_time']));
        }
        $success = 'Settings updated successfully.';
    } catch (Exception $e) {
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

$settings = get_all_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= ASSETS_VERSION ?>">
    <script src="../assets/js/app.js?v=<?= ASSETS_VERSION ?>" defer></script>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a href="<?= BASE_URL ?>" class="site-logo" style="display:block;margin-bottom:var(--space-xl)"><?= sanitize(SITE_NAME) ?></a>
            <div class="text-small text-muted text-upper" style="letter-spacing:0.15em;margin-bottom:var(--space-sm)">Administration</div>
            <nav class="admin-nav">
                <a href="../admin/" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="../admin/rooms.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Rooms
                </a>
                <a href="../admin/queue.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Queue
                </a>
                <a href="../admin/playlist.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    Playlist
                </a>
                <a href="../admin/settings.php" class="admin-nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </a>
                <a href="../admin/seo.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    SEO & Meta
                </a>
                <a href="../admin/password.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Security
                </a>
            </nav>
            <div style="margin-top:auto;padding-top:var(--space-2xl);border-top:1px solid rgba(255,255,255,0.05);margin-top:var(--space-3xl)">
                <a href="../admin/logout.php" class="btn btn-ghost btn-sm" style="width:100%">Sign Out</a>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-page-header">
                <h1 class="heading-lg">Settings</h1>
                <p class="text-muted mt-1">Configure your lounge platform</p>
            </div>

            <?php if ($success): ?>
            <div style="padding:var(--space-md);background:rgba(39,174,96,0.1);border:1px solid rgba(39,174,96,0.3);border-radius:var(--radius-md);margin-bottom:var(--space-lg);color:var(--success)"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div style="padding:var(--space-md);background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);border-radius:var(--radius-md);margin-bottom:var(--space-lg);color:var(--danger)"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="glass-card" style="padding:var(--space-md)">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md)">
                        <div class="form-group">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-input" value="<?= sanitize($settings['site_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tagline</label>
                            <input type="text" name="site_tagline" class="form-input" value="<?= sanitize($settings['site_tagline'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">YouTube API Key</label>
                            <input type="text" name="youtube_api_key" class="form-input" value="<?= sanitize($settings['youtube_api_key'] ?? '') ?>" placeholder="AIza...">
                            <p class="text-muted text-small mt-1">Empty = fallback mode</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Max Queue / Room</label>
                            <input type="number" name="max_queue_per_room" class="form-input" value="<?= sanitize($settings['max_queue_per_room'] ?? '50') ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Primary Color</label>
                            <input type="color" name="brand_primary" class="form-input" value="<?= sanitize($settings['brand_primary'] ?? '#D4AF37') ?>" style="height:40px;padding:2px">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Accent Color</label>
                            <input type="color" name="brand_accent" class="form-input" value="<?= sanitize($settings['brand_accent'] ?? '#C5A059') ?>" style="height:40px;padding:2px">
                        </div>
                    </div>
                    <div style="margin-top:var(--space-md);padding-top:var(--space-md);border-top:1px solid rgba(255,255,255,0.06)">
                        <label class="flex gap-sm" style="align-items:center;cursor:pointer">
                            <input type="checkbox" name="show_now_playing" value="1" <?= ($settings['show_now_playing'] ?? '1') === '1' ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--gold)">
                            <span class="text-small">Show "Now Playing" to guests</span>
                        </label>
                    </div>
                </div>

            <!-- Auto Cleanup Settings -->
            <div class="glass-card" style="padding:var(--space-md);margin-top:var(--space-lg)">
                    <h3 class="heading-sm" style="margin-bottom:var(--space-md)">Auto Cleanup</h3>
                    <p class="text-muted text-small" style="margin-bottom:var(--space-lg)">Automatically close idle rooms and purge old queue entries.</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-lg)">
                        <div>
                            <div style="display:flex;align-items:center;gap:var(--space-sm);margin-bottom:var(--space-sm)">
                                <input type="checkbox" name="room_cleanup_enabled" value="1" id="room_cleanup_enabled" <?= ($settings['room_cleanup_enabled'] ?? '1') === '1' ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--gold)">
                                <label for="room_cleanup_enabled" class="text-small" style="cursor:pointer;font-weight:500">Auto-close idle rooms</label>
                            </div>
                            <p class="text-muted text-small" style="margin-bottom:var(--space-sm)">Close room when queue is empty and no activity for:</p>
                            <select name="room_cleanup_time" class="form-input">
                                <option value="10 seconds" <?= ($settings['room_cleanup_time'] ?? '1 day') === '10 seconds' ? 'selected' : '' ?>>10 seconds</option>
                                <option value="10 minutes" <?= ($settings['room_cleanup_time'] ?? '1 day') === '10 minutes' ? 'selected' : '' ?>>10 minutes</option>
                                <option value="1 day"      <?= ($settings['room_cleanup_time'] ?? '1 day') === '1 day' ? 'selected' : '' ?>>1 day</option>
                                <option value="2 days"     <?= ($settings['room_cleanup_time'] ?? '1 day') === '2 days' ? 'selected' : '' ?>>2 days</option>
                                <option value="7 days"     <?= ($settings['room_cleanup_time'] ?? '1 day') === '7 days' ? 'selected' : '' ?>>7 days</option>
                                <option value="15 days"    <?= ($settings['room_cleanup_time'] ?? '1 day') === '15 days' ? 'selected' : '' ?>>15 days</option>
                                <option value="30 days"    <?= ($settings['room_cleanup_time'] ?? '1 day') === '30 days' ? 'selected' : '' ?>>30 days</option>
                            </select>
                            <p class="text-muted text-small mt-1">Queue data is retained when room is closed.</p>
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;gap:var(--space-sm);margin-bottom:var(--space-sm)">
                                <input type="checkbox" name="queue_cleanup_enabled" value="1" id="queue_cleanup_enabled" <?= ($settings['queue_cleanup_enabled'] ?? '1') === '1' ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--gold)">
                                <label for="queue_cleanup_enabled" class="text-small" style="cursor:pointer;font-weight:500">Auto-purge old queue entries</label>
                            </div>
                            <p class="text-muted text-small" style="margin-bottom:var(--space-sm)">Delete queue entries older than:</p>
                            <select name="queue_cleanup_time" class="form-input">
                                <option value="10 seconds" <?= ($settings['queue_cleanup_time'] ?? '7 days') === '10 seconds' ? 'selected' : '' ?>>10 seconds</option>
                                <option value="10 minutes" <?= ($settings['queue_cleanup_time'] ?? '7 days') === '10 minutes' ? 'selected' : '' ?>>10 minutes</option>
                                <option value="1 day"      <?= ($settings['queue_cleanup_time'] ?? '7 days') === '1 day' ? 'selected' : '' ?>>1 day</option>
                                <option value="2 days"     <?= ($settings['queue_cleanup_time'] ?? '7 days') === '2 days' ? 'selected' : '' ?>>2 days</option>
                                <option value="7 days"     <?= ($settings['queue_cleanup_time'] ?? '7 days') === '7 days' ? 'selected' : '' ?>>7 days</option>
                                <option value="15 days"    <?= ($settings['queue_cleanup_time'] ?? '7 days') === '15 days' ? 'selected' : '' ?>>15 days</option>
                                <option value="30 days"    <?= ($settings['queue_cleanup_time'] ?? '7 days') === '30 days' ? 'selected' : '' ?>>30 days</option>
                            </select>
                            <p class="text-muted text-small mt-1">Playlist songs are never affected.</p>
                        </div>
                    </div>
                </div>
            <button type="submit" class="btn btn-primary" style="margin-top:var(--space-lg)">Save Settings</button>
            </form>
        </main>
    </div>

    <div id="toastContainer" class="toast-container"></div>
</body>
</html>
