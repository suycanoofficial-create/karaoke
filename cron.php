<?php
/**
 * Cron Job Entry Point
 * 
 * Setup instructions (run every minute):
 *   * * * * * php /path/to/cron.php
 *   * * * * * curl --silent https://yourdomain.com/cron.php
 * 
 * This script triggers the cleanup logic in api/cleanup.php
 * and exits cleanly. It's self-throttled to run once per minute.
 */
require_once __DIR__ . '/config/app.php';

$_GET['internal'] = 'scheduled';
$_SERVER['REQUEST_METHOD'] = 'GET';

require __DIR__ . '/api/cleanup.php';
