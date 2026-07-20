<?php
/**
 * Site Configuration Bootstrap
 */

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

// Load settings into constants
define('SITE_NAME', get_setting('site_name', 'KTV LOUNGE'));
define('SITE_TAGLINE', get_setting('site_tagline', 'Elevate Your Night'));
define('YOUTUBE_API_KEY', get_setting('youtube_api_key', ''));
define('MAX_QUEUE_PER_ROOM', (int) get_setting('max_queue_per_room', 50));

define('ASSETS_VERSION', '1.1.0');

// Auto-detect BASE_URL
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
if (basename($scriptDir) === 'karaoke' || strpos($scriptDir, '/karaoke') !== false || strpos($scriptDir, '\\karaoke') !== false) {
    // Subfolder install (XAMPP local)
    define('BASE_URL', '/karaoke/');
} else {
    // Root install (online hosting)
    define('BASE_URL', '/');
}
