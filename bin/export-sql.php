<?php

declare(strict_types=1);

/*
 * Genera un archivo .sql para importar la base completa en MySQL / MariaDB
 * (phpMyAdmin, Adminer o `mysql -u usuario -p base < archivo.sql`).
 * Incluye la estructura y los datos de la base configurada en .env.
 *
 *   php bin/export-sql.php                       → database/metal_live.sql
 *   php bin/export-sql.php --output=otro.sql
 *   php bin/export-sql.php --structure-only      solo tablas, sin datos
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Core\Schema;

$options = getopt('', ['output:', 'structure-only']);
$output = $options['output'] ?? BASE_PATH . '/database/metal_live.sql';
$withData = !isset($options['structure-only']);

// Tablas que no tiene sentido llevar a otra instalación.
const SKIP_DATA = ['login_attempts'];

$quote = static function (mixed $value): string {
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    return "'" . strtr((string) $value, ['\\' => '\\\\', "'" => "\\'", "\0" => '\\0', "\n" => '\\n', "\r" => '\\r', "\x1a" => '\\Z']) . "'";
};

$tables = Schema::tables();
$out = fopen($output, 'wb') ?: exit("No se pudo escribir {$output}\n");

fwrite($out, "-- Metal Live · base de datos completa para MySQL 8 / MariaDB 10.6+\n");
fwrite($out, '-- Generado el ' . date('Y-m-d H:i') . ' desde ' . Database::driver() . ($withData ? ' (estructura y datos)' : ' (solo estructura)') . "\n");
fwrite($out, "-- Importar en una base vacía, por ejemplo:\n--   CREATE DATABASE metal_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n--   mysql -u usuario -p metal_live < metal_live.sql\n\n");
fwrite($out, "SET NAMES utf8mb4;\nSET time_zone = '+00:00';\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

foreach (array_reverse($tables) as $table) {
    fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");
}
fwrite($out, "\n");
foreach (Schema::statements('mysql') as $statement) {
    fwrite($out, $statement . ";\n\n");
}

$total = 0;
if ($withData) {
    foreach ($tables as $table) {
        if (in_array($table, SKIP_DATA, true)) {
            continue;
        }
        $rows = Database::fetchAll("SELECT * FROM {$table} ORDER BY 1");
        if (!$rows) {
            continue;
        }
        $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
        fwrite($out, "-- {$table}: " . count($rows) . " registros\n");
        foreach (array_chunk($rows, 100) as $chunk) {
            $values = array_map(static fn (array $row): string => '(' . implode(', ', array_map($quote, $row)) . ')', $chunk);
            fwrite($out, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $values) . ";\n");
        }
        fwrite($out, "\n");
        $total += count($rows);
    }
}

fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($out);

echo 'Exportado: ' . $output . ' (' . count($tables) . " tablas, {$total} registros, " . format_bytes((int) filesize($output)) . ")\n";
