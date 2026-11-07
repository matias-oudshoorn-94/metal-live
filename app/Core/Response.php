<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= base64_encode(random_bytes(16));
    }

    /** Cabeceras de seguridad aplicadas a todas las respuestas HTML. */
    public static function securityHeaders(): void
    {
        $nonce = self::nonce();
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; "
            . "style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; "
            . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        if (Request::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        header_remove('X-Powered-By');
    }

    public static function redirect(string $to, int $status = 302): never
    {
        if (!preg_match('#^https?://#', $to)) {
            $to = url($to);
        }
        header('Location: ' . $to, true, $status);
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // Solo redirige al referer si es del mismo host (evita open redirect).
        if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === parse_url('//' . $host, PHP_URL_HOST)) {
            header('Location: ' . $ref, true, 302);
            exit;
        }
        self::redirect($fallback);
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
