<?php

declare(strict_types=1);

namespace App\Enums;

/** Disponible → asignada → en preparación → en reparto → entregada → usada / para esterilizar. */
enum BoxStatus: string
{
    case Disponible = 'disponible';
    case Asignada = 'asignada';
    case EnPreparacion = 'en_preparacion';
    case EnReparto = 'en_reparto';
    case Entregada = 'entregada';
    case Usada = 'usada';
    case ParaEsterilizar = 'para_esterilizar';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Asignada => 'Asignada a cirugía',
            self::EnPreparacion => 'En preparación',
            self::EnReparto => 'En reparto',
            self::Entregada => 'Entregada',
            self::Usada => 'Usada',
            self::ParaEsterilizar => 'Para esterilizar',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Disponible => 'success',
            self::Asignada, self::EnPreparacion => 'info',
            self::EnReparto, self::Entregada => 'warning',
            self::Usada, self::ParaEsterilizar => 'muted',
        };
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }
        return $out;
    }
}
