<?php

declare(strict_types=1);

/*
 * Reemplazos mínimos para cuando el servidor no tiene la extensión mbstring.
 * En producción se recomienda instalar php-mbstring; estas funciones solo se definen si faltan.
 */

if (!function_exists('mb_internal_encoding')) {
    function mb_internal_encoding(?string $encoding = null): string|bool
    {
        return $encoding === null ? 'UTF-8' : true;
    }
}

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        return (int) preg_match_all('/./us', $string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return implode('', array_slice($chars, $start, $length));
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string
    {
        return strtr(strtolower($string), ['Á' => 'á', 'É' => 'é', 'Í' => 'í', 'Ó' => 'ó', 'Ú' => 'ú', 'Ñ' => 'ñ', 'Ü' => 'ü']);
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, ?string $encoding = null): string
    {
        return strtr(strtoupper($string), ['á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ú' => 'Ú', 'ñ' => 'Ñ', 'ü' => 'Ü']);
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst(string $string, ?string $encoding = null): string
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }
}

if (!function_exists('mb_encode_mimeheader')) {
    function mb_encode_mimeheader(string $string, ?string $charset = null, ?string $transfer = null, string $newline = "\r\n", int $indent = 0): string
    {
        return preg_match('/[^\x20-\x7E]/', $string) ? '=?UTF-8?B?' . base64_encode($string) . '?=' : $string;
    }
}
