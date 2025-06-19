<?php
// index.php
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/system');

// Load Composer autoloader (add this FIRST)
require_once BASE_PATH . '/vendor/autoload.php';

// Load environment variables
require_once APP_PATH . '/core/load-env.php';

session_start();
require_once BASE_PATH . '/system/core/Router.php';
require_once BASE_PATH . '/system/core/Controller.php';
require_once BASE_PATH . '/system/core/Database.php';
// Load routes
require_once BASE_PATH . '/router/routes.php';

$router->dispatch();