<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Guarda archivos subidos FUERA de la carpeta pública, con nombre aleatorio,
 * validando el tipo real del contenido (no la extensión que manda el navegador).
 */
final class Upload
{
    public const ALLOWED = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function maxBytes(): int
    {
        return (int) env('UPLOAD_MAX_MB', 10) * 1024 * 1024;
    }

    /** @return array{original_name:string, stored_path:string, mime:string, size:int} */
    public static function store(array $file, array $allowed = self::ALLOWED): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new \DomainException('No se pudo subir el archivo (código ' . (int) ($file['error'] ?? -1) . ').');
        }
        if ($file['size'] > self::maxBytes()) {
            throw new \DomainException('El archivo supera el máximo de ' . env('UPLOAD_MAX_MB', 10) . ' MB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset($allowed[$mime])) {
            throw new \DomainException('Tipo de archivo no permitido. Formatos aceptados: PDF, JPG, PNG o WEBP.');
        }

        $dir = date('Y/m');
        $absDir = BASE_PATH . '/storage/uploads/' . $dir;
        if (!is_dir($absDir) && !mkdir($absDir, 0750, true) && !is_dir($absDir)) {
            throw new \RuntimeException('No se pudo crear el directorio de archivos.');
        }
        $name = bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $absDir . '/' . $name)) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }
        chmod($absDir . '/' . $name, 0640);

        $original = preg_replace('/[^\pL\pN ._\-()]/u', '_', basename((string) $file['name'])) ?: 'archivo';

        return [
            'original_name' => mb_substr($original, 0, 180),
            'stored_path' => $dir . '/' . $name,
            'mime' => $mime,
            'size' => (int) $file['size'],
        ];
    }

    public static function absolutePath(string $storedPath): string
    {
        $base = realpath(BASE_PATH . '/storage/uploads');
        $path = realpath($base . '/' . $storedPath);
        if ($path === false || !str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            throw new HttpException(404);
        }
        return $path;
    }
}
