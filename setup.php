<?php
/**
 * KTV LOUNGE - Initial Setup Script
 * 
 * INSTRUCTIONS:
 * 1. Create the database by importing sql/schema.sql into MySQL
 * 2. Update config/database.php with your MySQL credentials
 * 3. Run this script ONCE in your browser: http://localhost/karaoke/setup.php
 * 4. After setup, DELETE this file for security
 * 
 * Default login after setup:
 *   Username: admin
 *   Password: admin123
 */

require_once __DIR__ . '/config/app.php';

$default_password = 'admin123';
$hash = password_hash($default_password, PASSWORD_DEFAULT);

try {
    // Update the default admin password with proper hash
    $stmt = db()->prepare("UPDATE administrators SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);
    
    if ($stmt->rowCount() === 0) {
        // Admin doesn't exist, create it
        $stmt = db()->prepare("INSERT INTO administrators (username, password_hash, role) VALUES ('admin', ?, 'superadmin') ON DUPLICATE KEY UPDATE password_hash = ?");
        $stmt->execute([$hash, $hash]);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Complete — KTV LOUNGE</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-card glass-card animate-slide" style="text-align:center">
            <div style="font-size:3rem;margin-bottom:var(--space-lg)">✅</div>
            <h1 class="heading-lg mb-3 text-gold">Setup Complete</h1>
            <p class="text-muted mb-4">Your KTV Lounge is ready to use.</p>
            
            <div class="glass-card-sm" style="background:rgba(212,175,55,0.05);margin-bottom:var(--space-xl)">
                <p class="text-small text-muted mb-2">Default Admin Credentials</p>
                <p style="font-family:monospace;font-size:1.1rem;color:var(--gold)">
                    Username: <strong>admin</strong><br>
                    Password: <strong>admin123</strong>
                </p>
            </div>

            <div style="display:flex;gap:var(--space-md);justify-content:center">
                <a href="<?= BASE_URL ?>" class="btn btn-primary">Go to Homepage</a>
                <a href="admin/" class="btn btn-secondary">Admin Dashboard</a>
            </div>

            <p class="text-muted text-small mt-4" style="margin-top:var(--space-2xl)">
                ⚠️ For security, delete <code>setup.php</code> after completing setup.
            </p>
        </div>
    </div>
</body>
</html>
<?php
} catch (Exception $e) {
    echo "<h1>Setup Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure the database exists and config/database.php has correct credentials.</p>";
}
