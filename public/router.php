<?php
// Built-in PHP server router for testing: php -S localhost:8000 -t public public/router.php
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}
require __DIR__ . '/index.php';
