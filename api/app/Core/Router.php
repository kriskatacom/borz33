<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;

class Router
{
    protected array $routes = [];

    /**
     * @param null|callable(string, int, mixed): void $errorHandler
     */
    public function __construct(
        private readonly mixed $errorHandler = null
    ) {
    }

    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function patch(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    public function delete(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, array $handler, array $middlewares): void
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function resolve(?string $path = null): void
    {
        $path = $path ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if (strpos($path, '/public') === 0) {
            $path = substr($path, 7);
        }

        $path = rtrim($path, '/') ?: '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePath => $routeData) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\*\}/', '(.+)', $routePath);
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
                $pattern = '#^' . $pattern . '$#';

                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches);

                    $handler = $routeData['handler'];
                    $middlewares = $routeData['middlewares'];

                    try {
                        foreach ($middlewares as $middlewareClass) {
                            if (class_exists($middlewareClass)) {
                                $middleware = new $middlewareClass();
                                $middleware->handle();
                            }
                        }

                        $controllerClass = $handler[0];
                        $methodName = $handler[1];

                        if (class_exists($controllerClass)) {
                            $controller = new $controllerClass();
                            $params = array_map('urldecode', $matches);

                            if (method_exists($controller, $methodName)) {
                                if (method_exists($controller, 'callAction')) {
                                    $controller->callAction($methodName, $params);
                                } else {
                                    call_user_func_array([$controller, $methodName], $params);
                                }
                                return;
                            }
                        }
                    } catch (ValidationException $exception) {
                        $this->error($exception->getMessage(), 422, $exception->errors());
                    } catch (AuthException $exception) {
                        if ($exception->retryAfter() !== null) {
                            header('Retry-After: ' . $exception->retryAfter());
                        }

                        $this->error($exception->getMessage(), $exception->status());
                    }
                }
            }
        }

        $this->error('Not found', 404, ['path' => $path]);
    }

    private function error(string $message, int $status = 400, mixed $errors = null): never
    {
        if (is_callable($this->errorHandler)) {
            ($this->errorHandler)($message, $status, $errors);
            exit;
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
