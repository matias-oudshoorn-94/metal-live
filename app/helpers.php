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
        // Con el .htaccess de la raíz, /admin se reescribe a /public/index.php sin que /public aparezca en la URL:
        // en ese caso la base visible es la carpeta de arriba.
        $uri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if ($base !== '' && $uri !== $base && !str_starts_with($uri, $base . '/') && str_ends_with($base, '/public')) {
            $base = substr($base, 0, -strlen('/public'));
        }
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

// ---------------------------------------------------------------- Sitio institucional

/** Datos de contacto del sitio. Se pueden cambiar desde .env (SITE_*). */
function site_info(string $key): string
{
    $defaults = [
        'phone' => ['SITE_PHONE', '+54 11 5264 2763'],
        'urgent_phone' => ['SITE_URGENT_PHONE', '+54 11 4448 7396'],
        'whatsapp' => ['SITE_WHATSAPP', '5491144487396'],
        'whatsapp_label' => ['SITE_WHATSAPP_LABEL', '+54 11 4448 7396'],
        'email' => ['SITE_EMAIL', 'info@metallive.com.ar'],
        'address' => ['SITE_ADDRESS', 'Av. Amancio Alcorta 3711, CABA, Argentina'],
    ];
    [$env, $default] = $defaults[$key];
    return (string) env($env, $default);
}

function tel_href(string $phone): string
{
    return '+' . ltrim(preg_replace('/\D/', '', $phone), '+');
}

function site_whatsapp_url(string $text = ''): string
{
    $number = preg_replace('/\D/', '', site_info('whatsapp'));
    return 'https://wa.me/' . $number . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

function site_map_url(): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(site_info('address'));
}

/** Mapa de Google para incrustar (no requiere clave de API). */
function site_map_embed_url(): string
{
    return 'https://www.google.com/maps?q=' . rawurlencode(site_info('address')) . '&z=16&output=embed';
}

/** @return list<array{0:string,1:string,2:string}> ícono, nombre y enlace de cada red social */
function site_social(): array
{
    return [
        ['instagram', 'Instagram', 'https://www.instagram.com/metalliveok'],
        ['youtube', 'YouTube', 'https://www.youtube.com/@metalliveok'],
        ['facebook', 'Facebook', 'https://www.facebook.com/share/19f7Wb7BYK/'],
        ['tiktok', 'TikTok', 'https://www.tiktok.com/@metalliveok'],
    ];
}

/** Áreas clave del equipo (inicio). */
function site_areas(): array
{
    return [
        ['stethoscope', 'Medicina y Bioingeniería', 'Innovación constante para desarrollar productos que anticipen las demandas presentes y futuras de la traumatología.'],
        ['award', 'Control de Calidad', 'Rigurosos protocolos que garantizan la máxima calidad y confiabilidad en cada producto.'],
        ['truck', 'Logística Propia', 'Distribución eficiente en todo el territorio nacional, garantizando entregas oportunas y disponibilidad permanente.'],
        ['users', 'Gestión Comercial Personalizada', 'Asesoramiento ágil y especializado, priorizando la satisfacción y fidelidad de nuestros clientes.'],
        ['trend', 'Inteligencia de Mercado e Innovación', 'Investigamos continuamente las necesidades del sector para desarrollar soluciones vanguardistas y anticiparnos a las demandas futuras de la traumatología.'],
        ['shield', 'Cumplimiento Normativo', 'Adhesión estricta a regulaciones nacionales e internacionales, asegurando los más altos estándares del sector.'],
    ];
}

/** Somos Calidad, Confianza y Compromiso. */
function site_values(): array
{
    return [
        ['sparkle', 'Somos Calidad', 'Trabajamos con productos de alta calidad y tecnología avanzada.'],
        ['heart', 'Somos Confianza', 'Fundada por expertos con más de 20 años de experiencia en el rubro de la traumatología.'],
        ['check', 'Somos Compromiso', 'Excelencia en servicio y rapidez en la entrega de productos y servicios. Comprometidos con la satisfacción del cliente.'],
    ];
}

/** Áreas del formulario "Sumate al equipo". */
function site_job_areas(): array
{
    return ['administracion' => 'Administración', 'cirugia' => 'Sector cirugía', 'ventas' => 'Ventas', 'otro' => 'Otro'];
}

/** Líneas de producto: ícono, nombre e ilustración de implante (partials/implants). */
function site_products(): array
{
    return [
        ['spine', 'Columna y Cráneo', 'pedicle'],
        ['joint', 'Cadera y Rodilla', 'hip'],
        ['bone', 'Tobillo y pie', 'screw'],
        ['activity', 'Miembros Superiores', 'plate'],
        ['scope', 'Medicina Artroscópica', 'anchor'],
    ];
}

/** Ilustración decorativa de un implante (ver partials/implants.php). */
function implant(string $name, string $class = ''): string
{
    return '<svg class="implant implant-' . e($name) . ' ' . e($class) . '" aria-hidden="true" focusable="false"><use href="#imp-' . e($name) . '"></use></svg>';
}
