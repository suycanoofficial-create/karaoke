<?php
// Proxy script to test get_queue.php
$_GET = [];
parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_GET);
require __DIR__ . '/api/get_queue.php';
