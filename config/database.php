<?php
/**
 * Database Configuration & Connection
 * Auto-detects: XAMPP local vs online hosting (InfinityFree, GoDaddy, cPanel, etc.)
 */

// ============================================================
// ONLINE HOSTING CREDENTIALS
// Get these from your hosting control panel → MySQL Databases
// ============================================================
$ONLINE_DB_HOST = 'sql103.infinityfree.com';  // Your actual MySQL Host from control panel
$ONLINE_DB_NAME = 'if0_42110526_ktv_lounge';  // Your actual Database Name
$ONLINE_DB_USER = 'if0_42110526';             // Your actual Database Username
$ONLINE_DB_PASS = 'YsGhNtiU31';              // Your actual Database Password

// Auto-detect environment
$isLocal = (
    // Check if MySQL is running locally (XAMPP, Laragon, etc.)
    @file_exists('C:/xampp/mysql/bin/mysql.exe') ||           // XAMPP Windows
    @file_exists('/Applications/XAMPP/xamppfiles/bin/mysql') || // XAMPP Mac
    @file_exists('C:/laragon/bin/mysql/mysql.exe') ||          // Laragon
    (php_uname('s') === 'Windows NT' && @getenv('COMPUTERNAME')) || // Windows dev
    (isset($_SERVER['HTTP_HOST']) && (
        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
        strpos($_SERVER['HTTP_HOST'], '.local') !== false ||
        strpos($_SERVER['HTTP_HOST'], '.test') !== false ||
        strpos($_SERVER['HTTP_HOST'], '.dev') !== false
    ))
);

if ($isLocal) {
    // XAMPP / Local development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ktv_lounge');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
} else {
    // Online hosting (InfinityFree, GoDaddy, cPanel, Hostinger, etc.)
    define('DB_HOST', $ONLINE_DB_HOST);
    define('DB_NAME', $ONLINE_DB_NAME);
    define('DB_USER', $ONLINE_DB_USER);
    define('DB_PASS', $ONLINE_DB_PASS);
    define('DB_PORT', '3306');
}
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $errors = [];
        
        // Build configs: if host is remote, try it first; if local, try variations
        $configs = [];
        if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
            // Remote host (InfinityFree, etc.) — only try the remote host
            $configs[] = [DB_HOST, DB_PORT];
            $configs[] = [DB_HOST, '3306'];
        } else {
            // Local host — try socket and TCP
            $configs[] = ['localhost', DB_PORT];
            $configs[] = ['localhost', '3306'];
            $configs[] = ['127.0.0.1', DB_PORT];
            $configs[] = ['127.0.0.1', '3306'];
        }
        
        $this->pdo = null;
        
        foreach ($configs as $cfg) {
            try {
                $host = $cfg[0];
                $port = $cfg[1];
                $dsn = "mysql:host=$host;port=$port;charset=" . DB_CHARSET;
                
                // Connect without database to create it if needed
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                
                // Create database if not exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `" . DB_NAME . "`");
                
                // Full connection
                $dsn = "mysql:host=$host;port=$port;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 5,
                ]);
                try { $this->pdo->exec("SET time_zone = '+08:00'"); } catch (Exception $e) {}
                
                break; // Success, stop trying
                
            } catch (PDOException $e) {
                $errors[] = "$host:$port - " . $e->getMessage();
                continue;
            }
        }
        
        if ($this->pdo === null) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed. Tried: ' . implode(' | ', $errors)]));
        }
        
        // Auto-create tables if they don't exist
        $this->createTables();
    }

    private function createTables() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `rooms` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_code` VARCHAR(8) NOT NULL UNIQUE,
            `host_session_token` VARCHAR(64) NOT NULL,
            `status` ENUM('active','closed') DEFAULT 'active',
            `last_activity` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_room_code` (`room_code`),
            INDEX `idx_status` (`status`),
            INDEX `idx_last_activity` (`last_activity`)
        ) ENGINE=InnoDB");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `songs_queue` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `video_title` VARCHAR(500) NOT NULL,
            `youtube_id` VARCHAR(20) NOT NULL,
            `added_by` VARCHAR(100) NOT NULL DEFAULT 'Guest',
            `sort_order` INT NOT NULL DEFAULT 0,
            `status` ENUM('pending','playing','completed','skipped') DEFAULT 'pending',
            `started_at` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_room_status` (`room_id`, `status`),
            INDEX `idx_sort_order` (`room_id`, `sort_order`)
        ) ENGINE=InnoDB");

        // Auto-migrate: add started_at if missing
        $cols = $this->pdo->query("SHOW COLUMNS FROM `songs_queue` LIKE 'started_at'")->fetch();
        if (!$cols) {
            $this->pdo->exec("ALTER TABLE `songs_queue` ADD COLUMN `started_at` DATETIME DEFAULT NULL AFTER `status`");
        }

        // Auto-migrate: add last_activity if missing
        $cols = $this->pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'last_activity'")->fetch();
        if (!$cols) {
            $this->pdo->exec("ALTER TABLE `rooms` ADD COLUMN `last_activity` DATETIME DEFAULT NULL AFTER `status`, ADD INDEX `idx_last_activity` (`last_activity`)");
        }

        // Auto-migrate: remove FK cascade from songs_queue so rooms can be deleted without deleting queue
        try {
            $fks = $this->pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'songs_queue' AND REFERENCED_TABLE_NAME = 'rooms'")->fetchAll();
            foreach ($fks as $fk) {
                $this->pdo->exec("ALTER TABLE `songs_queue` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
            }
        } catch (Exception $e) {
            // Ignore if table doesn't exist yet
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `site_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `meta_key` VARCHAR(100) NOT NULL UNIQUE,
            `meta_value` TEXT,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_meta_key` (`meta_key`)
        ) ENGINE=InnoDB");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `administrators` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` ENUM('superadmin','admin','editor') DEFAULT 'admin',
            `last_login` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_username` (`username`)
        ) ENGINE=InnoDB");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `guest_heartbeats` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `guest_id` VARCHAR(64) NOT NULL,
            `nickname` VARCHAR(100) NOT NULL DEFAULT 'Guest',
            `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_room_guest` (`room_id`, `guest_id`),
            INDEX `idx_room_lastseen` (`room_id`, `last_seen`)
        ) ENGINE=InnoDB");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `playlist_profiles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_name` (`name`)
        ) ENGINE=InnoDB");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `playlist_songs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `profile_id` INT NOT NULL,
            `video_title` VARCHAR(500) NOT NULL,
            `youtube_id` VARCHAR(20) NOT NULL,
            `added_by` VARCHAR(100) NOT NULL DEFAULT 'Admin',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`profile_id`) REFERENCES `playlist_profiles`(`id`) ON DELETE CASCADE,
            INDEX `idx_profile_id` (`profile_id`),
            INDEX `idx_youtube_id` (`youtube_id`)
        ) ENGINE=InnoDB");

        // Auto-migrate: add locked column to rooms
        $cols = $this->pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'locked'")->fetch();
        if (!$cols) {
            $this->pdo->exec("ALTER TABLE `rooms` ADD COLUMN `locked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");
        }

        // Auto-migrate: add echo settings columns to rooms
        $cols = $this->pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'echo_delay'")->fetch();
        if (!$cols) {
            $this->pdo->exec("ALTER TABLE `rooms`
                ADD COLUMN `echo_delay` DECIMAL(5,2) NOT NULL DEFAULT 0.35 AFTER `locked`,
                ADD COLUMN `echo_feedback` DECIMAL(5,2) NOT NULL DEFAULT 0.35 AFTER `echo_delay`,
                ADD COLUMN `echo_mix` DECIMAL(5,2) NOT NULL DEFAULT 0.35 AFTER `echo_feedback`");
        }

        // Auto-migrate: add playback_cmd column to rooms
        $cols = $this->pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'playback_cmd'")->fetch();
        if (!$cols) {
            $this->pdo->exec("ALTER TABLE `rooms` ADD COLUMN `playback_cmd` VARCHAR(20) DEFAULT NULL AFTER `echo_mix`");
        }

        // Auto-migrate: add score column to songs_queue if missing
        $cols = $this->pdo->query("SHOW COLUMNS FROM `songs_queue` LIKE 'score'")->fetch();
        if (!$cols) {
            $this->pdo->exec("ALTER TABLE `songs_queue` ADD COLUMN `score` INT DEFAULT NULL AFTER `started_at`");
        }

        // Auto-migrate: add video_id column to songs_queue if missing
        $cols = $this->pdo->query("SHOW COLUMNS FROM `songs_queue` LIKE 'video_id'")->fetch();
        if (!$cols) {
            $this->pdo->exec("ALTER TABLE `songs_queue` ADD COLUMN `video_id` VARCHAR(20) DEFAULT NULL AFTER `id`");
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `song_scores` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `track_id` INT NOT NULL,
            `room_id` INT NOT NULL,
            `score` INT NOT NULL,
            `scored_by` VARCHAR(100) NOT NULL DEFAULT 'Guest',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_track_id` (`track_id`),
            INDEX `idx_room_id` (`room_id`)
        ) ENGINE=InnoDB");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `cheers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `type` VARCHAR(50) NOT NULL,
            `from_nick` VARCHAR(100) DEFAULT '',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_room_created` (`room_id`, `created_at`)
        ) ENGINE=InnoDB");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `audio_relay` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `chunk_data` LONGBLOB NOT NULL,
            `chunk_seq` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_room_seq` (`room_id`, `chunk_seq`)
        ) ENGINE=InnoDB");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `reactions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_id` INT NOT NULL,
            `type` VARCHAR(50) NOT NULL,
            `from_nick` VARCHAR(100) DEFAULT '',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_room_created` (`room_id`, `created_at`)
        ) ENGINE=InnoDB");

        // Insert default settings
        $settings = [
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
        
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO `site_settings` (`meta_key`, `meta_value`) VALUES (?, ?)");
        foreach ($settings as $s) {
            $stmt->execute($s);
        }

        // Insert default admin
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $this->pdo->exec("INSERT IGNORE INTO `administrators` (`username`, `password_hash`, `role`) VALUES ('admin', '$hash', 'superadmin')");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function __clone() {}
}

function db() {
    return Database::getInstance()->getConnection();
}
