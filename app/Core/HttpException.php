<?php

declare(strict_types=1);

namespace App\Core;

final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message ?: match ($status) {
            403 => 'No tenés permisos para acceder a esta sección.',
            404 => 'La página que buscás no existe.',
            419 => 'La sesión expiró. Volvé a intentarlo.',
            429 => 'Demasiados intentos. Esperá unos minutos.',
            default => 'Ocurrió un error inesperado.',
        });
    }
}
