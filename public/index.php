<?php

declare(strict_types=1);

// Con el servidor embebido de PHP, los archivos estáticos se sirven directo.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

Response::securityHeaders();
header('Content-Type: text/html; charset=utf-8');

$request = new Request();

try {
    Session::start();
    $router = new Router();
    (require BASE_PATH . '/config/routes.php')($router);
    $router->dispatch($request);
} catch (HttpException $e) {
    http_response_code($e->status);
    $admin = str_starts_with($request->path, '/admin') && App\Core\Auth::check();
    echo View::render('errors/error', ['status' => $e->status, 'message' => $e->getMessage(), 'title' => 'Error ' . $e->status], $admin ? 'admin' : 'site');
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    $message = env('APP_DEBUG', false) ? $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine() : 'Ocurrió un error inesperado. Ya quedó registrado.';
    echo View::render('errors/error', ['status' => 500, 'message' => $message, 'title' => 'Error'], 'site');
}
