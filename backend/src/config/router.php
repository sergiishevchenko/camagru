<?php

class Router {
    private $routes = [];
    private $middlewares = [];

    public function get($path, $handler, $middleware = null) {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post($path, $handler, $middleware = null) {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function delete($path, $handler, $middleware = null) {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute($method, $path, $handler, $middleware) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);

                if ($route['middleware']) {
                    $middlewareClass = $route['middleware'];
                    if (class_exists($middlewareClass)) {
                        $middleware = new $middlewareClass();
                        if (!$middleware->handle()) {
                            return;
                        }
                    }
                }

                $handler = $route['handler'];
                if (is_array($handler) && is_string($handler[0])) {
                    $controller = new $handler[0]();
                    $method = $handler[1];
                    call_user_func_array([$controller, $method], $matches);
                } else {
                    call_user_func_array($handler, $matches);
                }
                return;
            }
        }

        http_response_code(404);
        echo "404 - Page not found";
    }

    private function convertToRegex($path) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
