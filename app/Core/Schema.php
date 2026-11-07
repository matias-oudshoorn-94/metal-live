<?php

declare(strict_types=1);

namespace App\Core;

/** Lee database/schema.sql y lo adapta al motor (sqlite o mysql). */
final class Schema
{
    /** @return list<string> sentencias CREATE TABLE / CREATE INDEX en orden */
    public static function statements(string $driver): array
    {
        $sql = file_get_contents(BASE_PATH . '/database/schema.sql');
        $sql = strtr($sql, $driver === 'mysql'
            ? ['{{PK}}' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', '{{ENGINE}}' => ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci']
            : ['{{PK}}' => 'INTEGER PRIMARY KEY AUTOINCREMENT', '{{ENGINE}}' => '']);
        if ($driver === 'mysql') {
            // En MySQL las FK deben tener el mismo tipo que la PK (INT UNSIGNED).
            $sql = preg_replace('/^(\s+\w+_id|\s+(?:role|authorized|cx_confirmed|delivered|created|uploaded)_by|\s+attachment_id) INTEGER/m', '$1 INT UNSIGNED', $sql);
            $sql = preg_replace('/^(\s+(?:current_surgery_id|user_id|entity_id)) INTEGER/m', '$1 INT UNSIGNED', $sql);
        }
        $sql = preg_replace('/^--.*$/m', '', $sql);
        return array_values(array_filter(array_map('trim', explode(';', $sql))));
    }

    /** @return list<string> nombres de tabla en el orden del esquema */
    public static function tables(): array
    {
        preg_match_all('/^CREATE TABLE (\w+)/mi', (string) file_get_contents(BASE_PATH . '/database/schema.sql'), $m);
        return $m[1];
    }
}
