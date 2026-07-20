<?php
/**
 * Application Helpers & Utilities
 */

session_start();

require_once __DIR__ . '/database.php';

// ── Security Helpers ──────────────────────────────────────────

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function generate_room_code($length = 6) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_token(32);
    }
    return $_SESSION['csrf_token'];
}

// ── Settings Helpers ──────────────────────────────────────────

function get_setting($key, $default = '') {
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    try {
        $stmt = db()->prepare("SELECT meta_value FROM site_settings WHERE meta_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['meta_value'] : $default;
    } catch (Exception $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function get_all_settings() {
    try {
        $stmt = db()->query("SELECT meta_key, meta_value FROM site_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['meta_key']] = $row['meta_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

function update_setting($key, $value) {
    $stmt = db()->prepare("INSERT INTO site_settings (meta_key, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// ── JSON Response Helpers ─────────────────────────────────────

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message, $status = 400) {
    json_response(['success' => false, 'error' => $message], $status);
}

function json_success($data = []) {
    json_response(array_merge(['success' => true], $data));
}

// ── Admin Auth Helpers ────────────────────────────────────────

function admin_login($username, $password) {
    $stmt = db()->prepare("SELECT * FROM administrators WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_username'] = $admin['username'];
        $stmt = db()->prepare("UPDATE administrators SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
        return true;
    }
    return false;
}

function require_admin() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

function is_admin() {
    return !empty($_SESSION['admin_id']);
}

function admin_logout() {
    session_destroy();
    header('Location: ' . BASE_URL);
    exit;
}

function get_site_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function get_lan_ip() {
    // On web hosting, use SERVER_ADDR or HTTP_HOST
    if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
        return $_SERVER['SERVER_ADDR'];
    }
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = explode(':', $_SERVER['HTTP_HOST'])[0];
        if ($host !== 'localhost' && $host !== '127.0.0.1') {
            return $host;
        }
    }

    // Windows: parse ipconfig
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $output = [];
        @exec('ipconfig', $output);
        $candidates = [];
        foreach ($output as $line) {
            if (preg_match('/IPv4 Address[\s\.]+:\s*([\d.]+)/', $line, $m)) {
                $ip = trim($m[1]);
                if ($ip !== '127.0.0.1') {
                    $candidates[] = $ip;
                }
            }
        }
        foreach ($candidates as $ip) {
            if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0 || strpos($ip, '172.16.') === 0) {
                return $ip;
            }
        }
        if (!empty($candidates)) return $candidates[0];
    }

    // Linux: parse hostname -I
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $output = [];
        @exec('hostname -I', $output);
        if (!empty($output)) {
            $ips = preg_split('/\s+/', trim(implode(' ', $output)));
            foreach ($ips as $ip) {
                if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0 || strpos($ip, '172.16.') === 0) {
                    return $ip;
                }
            }
            if (!empty($ips)) return $ips[0];
        }
    }

    return '127.0.0.1';
}
