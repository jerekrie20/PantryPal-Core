<?php

namespace Helpers;

class ErrorHandler
{
    public static function register()
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    public static function handleException(\Throwable $exception)
    {
        error_log('Uncaught Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());
        error_log($exception->getTraceAsString());

        if (php_sapi_name() === 'cli') {
            echo "Error: " . $exception->getMessage() . PHP_EOL;
            exit(1);
        }

        $code = 500;
        if (method_exists($exception, 'getCode') && $exception->getCode() >= 400 && $exception->getCode() < 600) {
            $code = $exception->getCode();
        }

        self::renderError($code, $exception);
    }

    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleFatalError()
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    private static function renderError($code, \Throwable $exception = null)
    {
        if (!headers_sent()) {
            http_response_code($code);
        }

        $env = getenv('APP_ENV') ?: 'development';
        $debug = getenv('APP_DEBUG') === 'true';

        $data = [
            'title' => 'Error ' . $code,
            'code' => $code,
            'message' => ($env === 'production' && !$debug) ? 'An unexpected error occurred.' : $exception->getMessage(),
            'exception' => ($env !== 'production' || $debug) ? $exception : null
        ];

        try {
            $viewName = "Pages/{$code}";
            // Fallback to 500 if specific code view doesn't exist
            if (!is_file(VIEW_PATH . "/{$viewName}.php")) {
                $viewName = "Pages/500";
            }
            echo View::render($viewName, $data);
        } catch (\Throwable $e) {
            // Last resort if view rendering fails
            echo "<h1>Error {$code}</h1>";
            echo "<p>An unexpected error occurred and the error page could not be displayed.</p>";
            if ($env !== 'production' || $debug) {
                echo "<pre>" . $exception . "</pre>";
            }
        }
        exit;
    }
}
