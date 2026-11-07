<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Campos adicionales configurables (la cotización puede ampliarse sin tocar código). */
final class CustomFields
{
    public const TYPES = [
        'text' => 'Texto corto',
        'textarea' => 'Texto largo',
        'number' => 'Número',
        'date' => 'Fecha',
        'select' => 'Lista de opciones',
        'checkbox' => 'Sí / No',
    ];

    public const ENTITIES = [
        'surgery_quote' => 'Cotización',
    ];

    public static function for(string $entity, bool $onlyActive = true): array
    {
        return Database::fetchAll(
            'SELECT * FROM custom_fields WHERE entity = ?' . ($onlyActive ? ' AND active = 1' : '') . ' ORDER BY sort_order, id',
            [$entity]
        );
    }

    public static function options(array $field): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", (string) $field['options']))));
    }

    /**
     * Valida los valores enviados en $_POST['custom'].
     * @return array{0:array, 1:array} [valores, errores]
     */
    public static function collect(string $entity, array $input): array
    {
        $values = [];
        $errors = [];
        foreach (self::for($entity) as $field) {
            $key = $field['field_key'];
            $raw = $input[$key] ?? null;
            $value = is_string($raw) ? trim($raw) : $raw;

            if ($field['type'] === 'checkbox') {
                $values[$key] = $value ? 1 : 0;
                continue;
            }
            if ($value === null || $value === '') {
                if ((int) $field['required']) {
                    $errors["custom.{$key}"] = "{$field['label']} es obligatorio.";
                }
                $values[$key] = null;
                continue;
            }
            $value = mb_substr((string) $value, 0, 2000);
            $error = match ($field['type']) {
                'number' => is_numeric(str_replace(',', '.', $value)) ? null : 'debe ser numérico',
                'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? null : 'no es una fecha válida',
                'select' => in_array($value, self::options($field), true) ? null : 'tiene un valor no permitido',
                default => null,
            };
            if ($error) {
                $errors["custom.{$key}"] = "{$field['label']} {$error}.";
            }
            $values[$key] = $value;
        }
        return [$values, $errors];
    }
}
