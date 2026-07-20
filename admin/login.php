<?php
/**
 * Admin - Login Portal
 */
require_once __DIR__ . '/../config/app.php';

if (is_admin()) {
    header('Location: ' . BASE_URL . 'admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (admin_login($username, $password)) {
        header('Location: ' . BASE_URL . 'admin/');
        exit;
    } else {
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= sanitize(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= ASSETS_VERSION ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎤</text></svg>">
</head>
<body>
    <div class="login-page">
        <div class="login-card glass-card animate-slide">
            <a href="<?= BASE_URL ?>" class="site-logo" style="display:block;text-align:center;margin-bottom:var(--space-2xl)">
                <?= sanitize(SITE_NAME) ?>
            </a>
            
            <h2 class="heading-md text-center mb-4">Admin Access</h2>

            <?php if ($error): ?>
                <div style="padding:var(--space-md);background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);border-radius:var(--radius-md);margin-bottom:var(--space-lg);color:var(--danger);font-size:0.9rem;text-align:center">
                    <?= sanitize($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Enter username" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Enter password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Sign In</button>
            </form>

            <p class="text-center text-muted text-small mt-4">
                <a href="<?= BASE_URL ?>">← Back to <?= sanitize(SITE_NAME) ?></a>
            </p>
        </div>
    </div>
</body>
</html>
