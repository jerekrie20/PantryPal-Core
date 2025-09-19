<?php
namespace Helpers;

class Router {
    private string $method;
    private string $path;
    private array $routes = ['GET'=>[], 'POST'=>[]];
    private array $middlewareGroupStack = []; // Added to manage nested groups
    private $fallback = null;

    public function __construct(string $method, string $requestUri) {
        $this->method = strtoupper($method);
        $this->path   = $this->detectPath($requestUri);
    }

    public function get(string $pattern, $handler): void { $this->map('GET', $pattern, $handler); }
    public function post(string $pattern, $handler): void { $this->map('POST', $pattern, $handler); }
    public function fallback(callable $handler): void { $this->fallback = $handler; }

    /**
     * NEW: Groups routes together under a common set of options, like middleware.
     *
     * @param array $options An array of options, e.g., ['middleware' => [AuthMiddleware::class]]
     * @param callable $callback A function that defines the routes within the group.
     */
    public function group(array $options, callable $callback): void {
        // Push the middleware from this group onto a stack. This allows for nested groups.
        $this->middlewareGroupStack[] = $options['middleware'] ?? [];

        // Execute the callback, which will register the routes inside the group.
        call_user_func($callback, $this);

        // Once the group is finished, pop its middleware off the stack.
        array_pop($this->middlewareGroupStack);
    }

    public function run(): void {
        $routes = $this->routes[$this->method] ?? [];
        foreach ($routes as [$regex, $keys, $handler, $middlewares]) {
            if (preg_match($regex, $this->path, $m)) {
                // --- NEW: Run Middleware for the matched route ---
                foreach ($middlewares as $middleware) {
                    // Instantiate the middleware class and call its handle method.
                    (new $middleware())->handle();
                }
                // --- End of Middleware Logic ---

                $params = [];
                foreach ($keys as $i => $key) $params[$key] = $m[$i+1] ?? null;

                if (is_array($handler) && is_string($handler[0])) {
                    $fqcn = ltrim($handler[0], '\\');
                    if (strpos($fqcn, 'Controllers\\') !== 0) {
                        http_response_code(500);
                        echo 'Invalid handler';
                        return;
                    }
                    $controller = new $fqcn();
                    echo call_user_func_array([$controller, $handler[1]], $params);
                    return;
                }
                echo call_user_func_array($handler, $params);
                return;
            }
        }
        if ($this->fallback) { echo call_user_func($this->fallback); return; }
        http_response_code(404); echo "Not Found";
    }

    private function map(string $method, string $pattern, $handler): void {
        [$regex, $keys] = $this->compile($pattern);
        // Get all middleware from the current stack and flatten into a single array.
        $currentMiddlewares = array_merge(...$this->middlewareGroupStack);
        $this->routes[$method][] = [$regex, $keys, $handler, $currentMiddlewares];
    }

    private function compile(string $pattern): array {
        $keys = [];

        // Replace tokens like {id} and {id:int}
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::(int))?\}/',
            function ($m) use (&$keys) {
                $keys[] = $m[1];
                return ($m[2] ?? null) === 'int' ? '([0-9]+)' : '([^/]+)';
            },
            $pattern
        );

        // Ensure pattern starts with a single leading slash
        if ($regex === '' || $regex === null) $regex = '/';
        if ($regex[0] !== '/') $regex = '/' . $regex;

        // Keep the slash; only trim a trailing slash (not the root '/')
        if ($regex !== '/' ) $regex = rtrim($regex, '/');

        // Final regex matches full path (including leading slash)
        return ['#^' . $regex . '$#', $keys];
    }

    private function detectPath(string $requestUri): string {
        // Always parse the path piece only
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // Strip the directory part of the script name if any (e.g., /public)
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }

        // Normalize
        $path = '/' . ltrim($path, '/');
        if ($path !== '/') $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }
}

