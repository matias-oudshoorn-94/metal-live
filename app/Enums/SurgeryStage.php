<?php

declare(strict_types=1);

namespace App\Enums;

/** Los ocho estadios de una cirugía (más el cierre). El valor indica qué está pendiente. */
enum SurgeryStage: string
{
    case Cotizacion = 'cotizacion';
    case Autorizacion = 'autorizacion';
    case Asignacion = 'asignacion';
    case Entrega = 'entrega';
    case Cirugia = 'cirugia';
    case Consumo = 'consumo';
    case Facturacion = 'facturacion';
    case Cobranza = 'cobranza';
    case Finalizada = 'finalizada';

    public function label(): string
    {
        return match ($this) {
            self::Cotizacion => 'Cotización',
            self::Autorizacion => 'Autorización',
            self::Asignacion => 'Asignación',
            self::Entrega => 'Entrega',
            self::Cirugia => 'Cirugía realizada',
            self::Consumo => 'Consumo',
            self::Facturacion => 'Facturación',
            self::Cobranza => 'Cobranza',
            self::Finalizada => 'Finalizada',
        };
    }

    public function step(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }

    public function pending(): string
    {
        return match ($this) {
            self::Cotizacion => 'Completar la cotización',
            self::Autorizacion => 'Registrar la autorización (OC)',
            self::Asignacion => 'Asignar material, caja, chofer y fecha',
            self::Entrega => 'Confirmar la entrega del material',
            self::Cirugia => 'Cargar el informe de la cirugía',
            self::Consumo => 'Cargar el consumo y faltantes',
            self::Facturacion => 'Emitir la factura',
            self::Cobranza => 'Registrar el cobro',
            self::Finalizada => 'Circuito completo',
        };
    }

    public function isBefore(self $other): bool
    {
        return $this->step() < $other->step();
    }

    /** Estadios del recorrido (sin "Finalizada") para la línea de tiempo. */
    public static function flow(): array
    {
        return array_slice(self::cases(), 0, 8);
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
