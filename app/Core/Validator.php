<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validación declarativa: ['email' => 'required|email|max:190'].
 * Devuelve [datos_limpios, errores].
 */
final class Validator
{
    public static function make(array $input, array $rules, array $labels = []): array
    {
        $data = [];
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $rulesList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $input[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $nullable = in_array('nullable', $rulesList, true) || !in_array('required', $rulesList, true);
            $empty = $value === null || $value === '' || $value === [];

            if (in_array('bool', $rulesList, true)) {
                $data[$field] = $value ? 1 : 0;
                continue;
            }

            if ($empty) {
                if (in_array('required', $rulesList, true)) {
                    $errors[$field] = "{$label} es obligatorio.";
                }
                $data[$field] = null;
                continue;
            }

            foreach ($rulesList as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $error = match ($name) {
                    'required', 'nullable' => null,
                    'string' => is_string($value) ? null : "{$label} no es válido.",
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "{$label} no es un correo válido.",
                    'max' => mb_strlen((string) $value) <= (int) $param ? null : "{$label} admite hasta {$param} caracteres.",
                    'min' => mb_strlen((string) $value) >= (int) $param ? null : "{$label} requiere al menos {$param} caracteres.",
                    'int' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "{$label} debe ser un número entero.",
                    'numeric' => is_numeric(str_replace(',', '.', (string) $value)) ? null : "{$label} debe ser numérico.",
                    'min_value' => (float) str_replace(',', '.', (string) $value) >= (float) $param ? null : "{$label} debe ser mayor o igual a {$param}.",
                    'date' => self::isDate((string) $value, 'Y-m-d') ? null : "{$label} no es una fecha válida.",
                    'datetime' => self::isDate((string) $value, 'Y-m-d\TH:i') || self::isDate((string) $value, 'Y-m-d H:i:s') ? null : "{$label} no es una fecha y hora válida.",
                    'time' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value) ? null : "{$label} no es un horario válido.",
                    'in' => in_array((string) $value, explode(',', (string) $param), true) ? null : "{$label} tiene un valor no permitido.",
                    'exists' => self::exists((string) $param, $value) ? null : "{$label} no existe.",
                    'regex' => preg_match((string) $param, (string) $value) ? null : "{$label} tiene un formato inválido.",
                    'password' => self::strongPassword((string) $value) ? null : "{$label} debe tener al menos 10 caracteres, con letras y números.",
                    default => throw new \LogicException("Regla desconocida: {$name}"),
                };
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }

            if (!isset($errors[$field])) {
                if (in_array('numeric', $rulesList, true)) {
                    $value = (string) (float) str_replace(',', '.', (string) $value);
                } elseif (in_array('int', $rulesList, true) || in_array('exists', array_map(fn ($r) => explode(':', $r)[0], $rulesList), true)) {
                    $value = (int) $value;
                } elseif (in_array('datetime', $rulesList, true)) {
                    $value = date('Y-m-d H:i:s', strtotime((string) $value));
                }
            }
            $data[$field] = $value;
        }

        return [$data, $errors];
    }

    private static function isDate(string $value, string $format): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!' . $format, $value);
        return $d !== false && $d->format($format) === $value;
    }

    private static function exists(string $table, mixed $value): bool
    {
        Database::assertIdentifier($table);
        return filter_var($value, FILTER_VALIDATE_INT) !== false
            && (bool) Database::value("SELECT 1 FROM {$table} WHERE id = ?", [(int) $value]);
    }

    private static function strongPassword(string $value): bool
    {
        return mb_strlen($value) >= 10 && preg_match('/[A-Za-z]/', $value) && preg_match('/\d/', $value);
    }
}
