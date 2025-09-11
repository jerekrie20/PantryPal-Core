<?php
declare(strict_types=1);

use Controllers\HomeController;
use Helpers\Router;

define('APP_ROOT', dirname(__DIR__));
const APP_PATH = APP_ROOT . '/app';
const VIEW_PATH = APP_PATH . '/Views';

// Environment and error reporting
require_once APP_PATH . '/Config/environment.php';

// Security headers
require_once APP_PATH . '/Config/headers.php';

// Session setup
require_once APP_PATH . '/Config/sessions.php';

require_once APP_PATH . '/Helpers/support.php';


spl_autoload_register(function ($class) {
    $relative = str_replace('\\', '/', ltrim($class, '\\')) . '.php';
    $file = APP_PATH . '/' . $relative;
    if (str_starts_with(realpath($file) ?: '', realpath(APP_PATH) . DIRECTORY_SEPARATOR) && is_readable($file)) {
        require_once $file;
    }
});

// DB (optional until Module 2)
$maybeDb = APP_PATH . '/Database/connection.php';
if (is_readable($maybeDb)) require_once $maybeDb;

// Router
$router = new Router($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

// ROUTES
require_once APP_PATH . '/routes/web.php';

$router->run();
