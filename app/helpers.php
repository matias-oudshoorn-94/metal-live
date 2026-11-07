<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Session;

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

/** Escapa texto para HTML. Usar SIEMPRE al imprimir datos en las vistas. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Ruta base cuando la app corre dentro de un subdirectorio. */
function base_path_url(): string
{
    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        $base = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    }
    return $base;
}

function url(string $path = '/', array $query = []): string
{
    $url = base_path_url() . '/' . ltrim($path, '/');
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    return $url;
}

/** URL absoluta (para correos). */
function absolute_url(string $path = '/'): string
{
    return rtrim((string) env('APP_URL', 'http://localhost:8000'), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function csp_nonce(): string
{
    return App\Core\Response::nonce();
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::get('_old_input', []);
    return $old[$key] ?? $default;
}

function error_for(string $key): ?string
{
    $errors = Session::get('_errors', []);
    return $errors[$key] ?? null;
}

function auth(): ?array
{
    return Auth::user();
}

function can(string ...$permissions): bool
{
    return Auth::canAny(...$permissions);
}

function money(mixed $amount): string
{
    if ($amount === null || $amount === '') {
        return '—';
    }
    return '$ ' . number_format((float) $amount, 2, ',', '.');
}

function fdate(?string $value, string $format = 'd/m/Y'): string
{
    if (!$value) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value))->format($format);
    } catch (Throwable) {
        return '—';
    }
}

function fdatetime(?string $value): string
{
    return fdate($value, 'd/m/Y H:i');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(mixed $value): string
{
    return $value ? ' checked' : '';
}

function surgery_code(int|string $id): string
{
    return 'CX-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

function person_name(?array $row, string $prefix = ''): string
{
    if (!$row) {
        return '—';
    }
    $last = $row[$prefix . 'last_name'] ?? '';
    $first = $row[$prefix . 'first_name'] ?? '';
    $name = trim($last . ', ' . $first, ', ');
    return $name !== '' ? $name : '—';
}

function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $size = (float) $bytes;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return number_format($size, $i ? 1 : 0, ',', '.') . ' ' . $units[$i];
}

function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . e($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . e($name) . '"></use></svg>';
}

function stage_badge(string $stage, string $status = 'activa'): string
{
    if ($status === 'suspendida') {
        return '<span class="badge badge-danger">Suspendida</span>';
    }
    $case = App\Enums\SurgeryStage::tryFrom($stage);
    if (!$case) {
        return '<span class="badge">' . e($stage) . '</span>';
    }
    return '<span class="badge badge-stage stage-' . e($case->value) . '">' . $case->step() . '. ' . e($case->label()) . '</span>';
}

function box_badge(?string $status): string
{
    $case = App\Enums\BoxStatus::tryFrom((string) $status);
    return $case ? '<span class="badge badge-' . $case->tone() . '">' . e($case->label()) . '</span>' : '—';
}

function yes_no(mixed $value): string
{
    return $value ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-muted">No</span>';
}

function field_error(string $name): string
{
    $msg = error_for($name);
    return $msg ? '<p class="field-error" role="alert">' . e($msg) . '</p>' : '';
}

function invalid(string $name): string
{
    return error_for($name) ? ' aria-invalid="true"' : '';
}

/** Valor para un <input type="datetime-local">. */
function dt_local(?string $value): string
{
    return $value ? str_replace(' ', 'T', substr($value, 0, 16)) : '';
}

/** Fecha larga en español sin depender de la extensión intl: "jueves 6 de noviembre de 2026". */
function long_date(?string $value = null, bool $withYear = true): string
{
    $ts = $value ? strtotime($value) : time();
    $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $out = $days[(int) date('w', $ts)] . ' ' . date('j', $ts) . ' de ' . $months[(int) date('n', $ts) - 1];
    return $withYear ? $out . ' de ' . date('Y', $ts) : $out;
}
