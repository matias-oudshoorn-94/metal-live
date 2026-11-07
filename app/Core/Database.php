<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Acceso a datos con PDO y sentencias preparadas reales.
 * Soporta SQLite (por defecto, sin instalación) y MySQL/MariaDB.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        if (self::driver() === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', '127.0.0.1'),
                env('DB_PORT', '3306'),
                env('DB_DATABASE', 'metal_live')
            );
            self::$pdo = new PDO($dsn, (string) env('DB_USERNAME', 'root'), (string) env('DB_PASSWORD', ''), $options);
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        } else {
            $path = (string) env('DB_DATABASE', 'storage/database.sqlite');
            if (!str_starts_with($path, '/')) {
                $path = BASE_PATH . '/' . $path;
            }
            self::$pdo = new PDO('sqlite:' . $path, null, null, $options);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
        }

        return self::$pdo;
    }

    public static function driver(): string
    {
        return env('DB_CONNECTION', 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $param = is_int($key) ? $key + 1 : ':' . ltrim($key, ':');
            $stmt->bindValue($param, $value, self::type($value));
        }
        $stmt->execute();
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $table, array $data): int
    {
        self::assertIdentifier($table, ...array_keys($data));
        $cols = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        self::query("INSERT INTO {$table} ({$cols}) VALUES ({$marks})", array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, int $id): void
    {
        if (!$data) {
            return;
        }
        self::assertIdentifier($table, ...array_keys($data));
        $set = implode(', ', array_map(static fn (string $c): string => "{$c} = ?", array_keys($data)));
        self::query("UPDATE {$table} SET {$set} WHERE id = ?", [...array_values($data), $id]);
    }

    public static function delete(string $table, int $id): void
    {
        self::assertIdentifier($table);
        self::query("DELETE FROM {$table} WHERE id = ?", [$id]);
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Los nombres de tablas/columnas nunca vienen del usuario, pero se validan igual. */
    public static function assertIdentifier(string ...$names): void
    {
        foreach ($names as $name) {
            if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
                throw new \InvalidArgumentException("Identificador SQL inválido: {$name}");
            }
        }
    }

    private static function type(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }
}
