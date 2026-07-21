<?php
namespace GM_HMS\Core;

/**
 * Professional Router Engine
 */
class Router {
    private $routes = [];

    /**
     * Add a route
     * @param string $method HTTP Method (GET, POST, etc.)
     * @param string $pattern Regex pattern for the URI
     * @param string|callable $controller Controller class name or callback
     * @param string|null $action Method name in the controller (null for callbacks)
     */
    public function add($method, $pattern, $controller, $action) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'controller' => $controller,
            'action' => $action
        ];
    }

    /**
     * Dispatch the request
     */
    public function dispatch($requestUri, $requestMethod) {
        // Strip query string
        $uri = parse_url($requestUri, PHP_URL_PATH);
        
        // Remove trailing slash
        $uri = rtrim($uri, '/');
        if (empty($uri)) $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod || $route['method'] === 'ANY') {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    // Remove the full match from matches
                    array_shift($matches);
                    
                    return [
                        'controller' => $route['controller'],
                        'action' => $route['action'],
                        'params' => $matches
                    ];
                }
            }
        }

        return null;
    }
}
