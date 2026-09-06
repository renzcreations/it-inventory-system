<?php
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/system');

// Load Composer autoloader (add this FIRST)
require_once BASE_PATH . '/vendor/autoload.php';

// Load environment variables
require_once APP_PATH . '/core/load-env.php';

$production = ($_ENV['APP_ENV'] ?? 'production') === 'production';
ini_set('display_errors', $production ? '0' : '1');
ini_set('display_startup_errors', $production ? '0' : '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $production) {
    ini_set('session.cookie_secure', '1');
}
session_start();
$now = time();
$sessionLifetime = max(15, (int) ($_ENV['SESSION_MINUTES'] ?? 120)) * 60;
if (!empty($_SESSION['user_id']) && !empty($_SESSION['last_activity'])
    && ($now - (int) $_SESSION['last_activity']) > $sessionLifetime) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', $now - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    $_SESSION['warning'] = 'Your session expired. Please sign in again.';
}
if (!empty($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = $now;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Load routes
$router = require BASE_PATH . '/router/routes.php';

$router->dispatch();
