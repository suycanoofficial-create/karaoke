<?php
/**
 * Admin - SEO & Meta Tags Management
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'schema_markup'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_setting($field, trim($_POST[$field]));
        }
    }
    $success = 'SEO settings updated successfully.';
}

$settings = get_all_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO & Meta — Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= ASSETS_VERSION ?>">
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
                <a href="../admin/settings.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </a>
                <a href="../admin/seo.php" class="admin-nav-item active">
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
                <h1 class="heading-lg">SEO & Meta Tags</h1>
                <p class="text-muted mt-1">Control how your site appears in search engines and social media</p>
            </div>

            <?php if ($success): ?>
            <div style="padding:var(--space-md);background:rgba(39,174,96,0.1);border:1px solid rgba(39,174,96,0.3);border-radius:var(--radius-md);margin-bottom:var(--space-lg);color:var(--success)"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="glass-card" style="padding:var(--space-md)">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md)">
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-input" value="<?= sanitize($settings['meta_title'] ?? '') ?>" maxlength="70">
                            <p class="text-muted text-small mt-1">50-60 chars recommended</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">OG Title</label>
                            <input type="text" name="og_title" class="form-input" value="<?= sanitize($settings['og_title'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-input" rows="2" maxlength="160"><?= sanitize($settings['meta_description'] ?? '') ?></textarea>
                            <p class="text-muted text-small mt-1">150-160 chars recommended</p>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">OG Description</label>
                            <textarea name="og_description" class="form-input" rows="2"><?= sanitize($settings['og_description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">OG Image URL</label>
                            <input type="url" name="og_image" class="form-input" value="<?= sanitize($settings['og_image'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                            <p class="text-muted text-small mt-1">1200x630px recommended</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">JSON-LD Schema</label>
                            <textarea name="schema_markup" class="form-input" rows="4" style="font-family:monospace;font-size:0.85rem" placeholder='{"@context":"https://schema.org","@type":"WebApplication","name":"..."}'><?= sanitize($settings['schema_markup'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div style="margin-top:var(--space-md);padding-top:var(--space-md);border-top:1px solid rgba(255,255,255,0.06)">
                        <div style="padding:var(--space-md);background:var(--pure-white);border-radius:var(--radius-md);color:#333">
                            <div style="font-size:1.1rem;color:#1a0dab;font-weight:500;margin-bottom:4px" id="previewTitle"><?= sanitize($settings['meta_title'] ?? 'Page Title') ?></div>
                            <div style="font-size:0.85rem;color:#006620;margin-bottom:4px">https://yoursite.com</div>
                            <div style="font-size:0.85rem;color:#545454" id="previewDesc"><?= sanitize($settings['meta_description'] ?? 'Page description') ?></div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:var(--space-md)">Save SEO Settings</button>
            </form>
        </main>
    </div>

    <script>
        document.querySelectorAll('input[name="meta_title"]').forEach(el => {
            el.addEventListener('input', function() {
                document.getElementById('previewTitle').textContent = this.value || 'Page Title';
            });
        });
        document.querySelectorAll('textarea[name="meta_description"]').forEach(el => {
            el.addEventListener('input', function() {
                document.getElementById('previewDesc').textContent = this.value || 'Page description';
            });
        });
    </script>
</body>
</html>
