<?php
// Loads config, starts the session, and registers the class autoloader.

define('APP_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));

$GLOBALS['config'] = require APP_PATH . '/config/config.php';
date_default_timezone_set(config('app.timezone'));

if (config('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

spl_autoload_register(function (string $class) {
    foreach (['core', 'controllers', 'models'] as $dir) {
        $file = APP_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require APP_PATH . '/core/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_name('onetap_session');
    session_start();
}

/** Read a setting with dot notation, e.g. config('db.name'). */
function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}
