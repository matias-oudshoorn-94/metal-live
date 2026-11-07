<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:array, middleware:array}> */
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $path, array $handler, string ...$middleware): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, string ...$middleware): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $routes): void
    {
        [$prevPrefix, $prevMw] = [$this->groupPrefix, $this->groupMiddleware];
        $this->groupPrefix .= $prefix;
        $this->groupMiddleware = [...$this->groupMiddleware, ...$middleware];
        $routes($this);
        [$this->groupPrefix, $this->groupMiddleware] = [$prevPrefix, $prevMw];
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $full = '/' . trim($this->groupPrefix . $path, '/');
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[0-9]+|[A-Za-z0-9_\-]+)', $full) . '$#';
        $this->routes[] = [
            'method' => $method,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => [...$this->groupMiddleware, ...$middleware],
        ];
    }

    public function dispatch(Request $request): void
    {
        $allowed = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $m)) {
                continue;
            }
            $allowed = true;
            if ($route['method'] !== $request->method) {
                continue;
            }

            if ($request->method === 'POST') {
                Csrf::verify();
            }
            foreach ($route['middleware'] as $mw) {
                $this->runMiddleware($mw);
            }

            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $route['handler'];
            (new $class($request))->{$action}(...array_values($params));
            return;
        }

        throw new HttpException($allowed ? 405 : 404);
    }

    private function runMiddleware(string $mw): void
    {
        [$name, $arg] = array_pad(explode(':', $mw, 2), 2, null);
        match ($name) {
            'auth' => Auth::check() || Response::redirect('/admin/login'),
            'guest' => !Auth::check() || Response::redirect('/admin'),
            'can' => Auth::canAny(...explode('|', (string) $arg)) || throw new HttpException(403),
            default => throw new \LogicException("Middleware desconocido: {$name}"),
        };
    }
}
