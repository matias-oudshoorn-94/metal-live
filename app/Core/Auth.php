<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\Audit;

final class Auth
{
    private const MAX_ATTEMPTS_EMAIL = 5;
    private const MAX_ATTEMPTS_IP = 20;
    private const WINDOW_MINUTES = 15;

    private static ?array $user = null;
    private static ?array $permissions = null;

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = $_SESSION['_uid'] ?? null;
        if (!$id) {
            return null;
        }
        $user = Database::fetch(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.id = ? AND u.active = 1',
            [(int) $id]
        );
        // Si cambió la contraseña o se desactivó el usuario, se invalida la sesión.
        if (!$user || !hash_equals($_SESSION['_pwv'] ?? '', self::passwordVersion($user['password_hash']))) {
            self::logout();
            return null;
        }
        unset($user['password_hash']);
        return self::$user = $user;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }
        $user = self::user();
        if (!$user) {
            return self::$permissions = [];
        }
        $rows = Database::fetchAll('SELECT permission FROM role_permissions WHERE role_id = ?', [(int) $user['role_id']]);
        return self::$permissions = array_fill_keys(array_column($rows, 'permission'), true);
    }

    public static function can(string $permission): bool
    {
        return isset(self::permissions()[$permission]);
    }

    public static function canAny(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Intenta iniciar sesión. Devuelve null si fue exitoso o un mensaje de error.
     */
    public static function attempt(string $email, string $password, string $ip): ?string
    {
        $email = mb_strtolower(trim($email));
        $since = date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);

        $byEmail = (int) Database::value('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > ?', [$email, $since]);
        $byIp = (int) Database::value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [$ip, $since]);
        if ($byEmail >= self::MAX_ATTEMPTS_EMAIL || $byIp >= self::MAX_ATTEMPTS_IP) {
            return 'Demasiados intentos fallidos. Esperá ' . self::WINDOW_MINUTES . ' minutos e intentá de nuevo.';
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);
        // Se verifica contra un hash ficticio si el usuario no existe, para no filtrar
        // por tiempo de respuesta qué correos están registrados.
        $hash = $user['password_hash'] ?? self::hash(bin2hex(random_bytes(8)));
        $valid = password_verify($password, $hash);

        if (!$user || !$valid || !(int) $user['active']) {
            Database::insert('login_attempts', ['ip' => $ip, 'email' => $email, 'attempted_at' => now()]);
            return 'Correo o contraseña incorrectos.';
        }

        if (password_needs_rehash($user['password_hash'], self::algorithm())) {
            $user['password_hash'] = self::hash($password);
            Database::update('users', ['password_hash' => $user['password_hash']], (int) $user['id']);
        }

        Database::query('DELETE FROM login_attempts WHERE email = ?', [$email]);
        Session::regenerate();
        $_SESSION['_uid'] = (int) $user['id'];
        $_SESSION['_pwv'] = self::passwordVersion($user['password_hash']);
        Database::update('users', ['last_login_at' => now()], (int) $user['id']);
        self::$user = null;
        self::$permissions = null;
        Audit::log('login', 'users', (int) $user['id']);
        return null;
    }

    public static function logout(): void
    {
        self::$user = null;
        self::$permissions = null;
        Session::destroy();
    }

    /** Refresca la sesión tras cambiar la contraseña propia. */
    public static function refreshPasswordVersion(string $hash): void
    {
        $_SESSION['_pwv'] = self::passwordVersion($hash);
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algorithm());
    }

    private static function algorithm(): string|int|null
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    private static function passwordVersion(string $hash): string
    {
        return hash_hmac('sha256', $hash, (string) env('APP_KEY', 'metal-live'));
    }
}
