<?php
/**
 * Admin - Security / Account Management
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$success = '';
$error = '';

// Get current user role
$stmt = db()->prepare("SELECT role FROM administrators WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$current_user = $stmt->fetch();
$is_super = $current_user && $current_user['role'] === 'superadmin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Change own password ──
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $error = 'All fields are required.';
        } elseif (strlen($new) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = db()->prepare("SELECT password_hash FROM administrators WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($current, $admin['password_hash'])) {
                $stmt = db()->prepare("UPDATE administrators SET password_hash = ? WHERE id = ?");
                $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
                $success = 'Password updated.';
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }

    // ── Create account ──
    if ($action === 'create_account' && $is_super) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'editor';

        if (!$username || !$password) {
            $error = 'Username and password are required.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $error = 'Username: 3-50 alphanumeric characters or underscores.';
        } else {
            $stmt = db()->prepare("SELECT id FROM administrators WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists.';
            } else {
                $stmt = db()->prepare("INSERT INTO administrators (username, password_hash, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
                $success = 'Account "' . sanitize($username) . '" created.';
            }
        }
    }

    // ── Delete account ──
    if ($action === 'delete_account' && $is_super) {
        $delete_id = (int) ($_POST['delete_id'] ?? 0);
        if ($delete_id === (int) $_SESSION['admin_id']) {
            $error = 'Cannot delete your own account.';
        } else {
            $stmt = db()->prepare("SELECT role FROM administrators WHERE id = ?");
            $stmt->execute([$delete_id]);
            $target = $stmt->fetch();
            if (!$target) {
                $error = 'Account not found.';
            } else {
                // Don't allow deleting the last superadmin
                if ($target['role'] === 'superadmin') {
                    $stmt = db()->prepare("SELECT COUNT(*) as cnt FROM administrators WHERE role = 'superadmin'");
                    $stmt->execute();
                    $cnt = $stmt->fetch();
                    if ($cnt['cnt'] <= 1) {
                        $error = 'Cannot delete the last superadmin account.';
                    }
                }
                if (!$error) {
                    $stmt = db()->prepare("DELETE FROM administrators WHERE id = ?");
                    $stmt->execute([$delete_id]);
                    $success = 'Account deleted.';
                }
            }
        }
    }
}

$admins = db()->query("SELECT id, username, role, last_login, created_at FROM administrators ORDER BY created_at ASC")->fetchAll();
$roles = ['superadmin', 'admin', 'editor'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security — Admin</title>
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
                <a href="../admin/seo.php" class="admin-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    SEO & Meta
                </a>
                <a href="../admin/password.php" class="admin-nav-item active">
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
                <h1 class="heading-lg">Security</h1>
                <p class="text-muted mt-1">Manage admin passwords and accounts</p>
            </div>

            <?php if ($success): ?>
            <div style="padding:var(--space-sm) var(--space-md);background:rgba(39,174,96,0.1);border:1px solid rgba(39,174,96,0.3);border-radius:var(--radius-md);margin-bottom:var(--space-md);color:var(--success);font-size:0.85rem"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div style="padding:var(--space-sm) var(--space-md);background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);border-radius:var(--radius-md);margin-bottom:var(--space-md);color:var(--danger);font-size:0.85rem"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <div class="glass-card" style="padding:var(--space-md)">
                <!-- Top row: Change Password + Accounts -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-lg)">
                    <!-- Change Password -->
                    <div>
                        <h3 class="heading-sm mb-2 text-gold">Change Password</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group">
                                <input type="password" name="current_password" class="form-input" placeholder="Current password" required autocomplete="current-password">
                            </div>
                            <div class="form-group">
                                <input type="password" name="new_password" class="form-input" placeholder="New password (8+ chars)" required minlength="8" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <input type="password" name="confirm_password" class="form-input" placeholder="Confirm new password" required minlength="8" autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Update</button>
                        </form>
                    </div>

                    <!-- Admin Accounts -->
                    <div>
                        <h3 class="heading-sm mb-2 text-gold">Admin Accounts</h3>
                        <table class="data-table" style="font-size:0.8rem">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <?php if ($is_super): ?><th style="width:40px"></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $a): ?>
                                <tr>
                                    <td style="font-weight:500"><?= sanitize($a['username']) ?></td>
                                    <td><span class="badge <?= $a['role'] === 'superadmin' ? 'badge-active' : 'badge-pending' ?>"><?= $a['role'] ?></span></td>
                                    <td class="text-muted text-small"><?= $a['last_login'] ? date('M j, g:i A', strtotime($a['last_login'])) : 'Never' ?></td>
                                    <?php if ($is_super): ?>
                                    <td>
                                        <?php if ((int)$a['id'] !== (int)$_SESSION['admin_id']): ?>
                                        <form method="POST" onsubmit="return confirm('Delete account &ldquo;<?= sanitize($a['username']) ?>&rdquo;?')" style="display:inline">
                                            <input type="hidden" name="action" value="delete_account">
                                            <input type="hidden" name="delete_id" value="<?= $a['id'] ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm" style="width:28px;height:28px;padding:0;color:var(--danger);font-size:0.75rem" title="Delete">✕</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Account (superadmin only) -->
                <?php if ($is_super): ?>
                <div style="margin-top:var(--space-md);padding-top:var(--space-md);border-top:1px solid rgba(255,255,255,0.06)">
                    <h3 class="heading-sm mb-2 text-gold">Add Account</h3>
                    <form method="POST" style="display:flex;gap:var(--space-sm);align-items:end;flex-wrap:wrap">
                        <input type="hidden" name="action" value="create_account">
                        <div>
                            <input type="text" name="username" class="form-input" placeholder="Username" required pattern="[a-zA-Z0-9_]{3,50}" style="width:160px">
                        </div>
                        <div style="position:relative">
                            <input type="password" name="password" id="newPass" class="form-input" placeholder="Password (8+ chars)" required minlength="8" style="width:180px;padding-right:36px">
                            <span onclick="togglePass()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gold);font-size:0.75rem;font-weight:600;user-select:none" id="passToggle">Show</span>
                        </div>
                        <div>
                            <select name="role" class="form-input" style="width:130px">
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r ?>" <?= $r === 'editor' ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Create</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="toastContainer" class="toast-container"></div>
    <script>
        function togglePass() {
            var input = document.getElementById('newPass');
            var btn = document.getElementById('passToggle');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Hide';
            } else {
                input.type = 'password';
                btn.textContent = 'Show';
            }
        }
    </script>
</body>
</html>
