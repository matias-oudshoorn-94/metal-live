<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Stock en consignación: material de la empresa que queda en poder de un cliente.
 *
 * - envío:      sale del stock suelto y suma al saldo del cliente.
 * - devolución: vuelve al stock suelto y resta del saldo del cliente.
 * - consumo:    el cliente informa que lo usó; sale del saldo y queda pendiente de facturar.
 * - ajuste:     corrige el saldo del cliente tras un recuento (no toca el depósito).
 */
final class ConsignmentService
{
    public const TYPES = [
        'envio' => 'Envío a consignación',
        'devolucion' => 'Devolución al depósito',
        'consumo' => 'Consumo informado',
        'ajuste' => 'Ajuste por recuento',
    ];

    /**
     * Registra un movimiento con uno o más materiales (un mismo remito o comprobante).
     *
     * @param list<array{material_id:int, quantity:int}> $items
     * @return int cantidad de renglones registrados
     */
    public static function register(int $clientId, string $type, array $items, ?string $reference = null, ?string $notes = null, bool $adjustUp = false): int
    {
        if (!isset(self::TYPES[$type])) {
            throw new \DomainException('Tipo de movimiento inválido.');
        }
        $client = (string) Database::value('SELECT name FROM clients WHERE id = ?', [$clientId]);
        if ($client === '') {
            throw new \DomainException('El cliente no existe.');
        }

        // Agrupa renglones repetidos del mismo material.
        $qtys = [];
        foreach ($items as $item) {
            $qtys[$item['material_id']] = ($qtys[$item['material_id']] ?? 0) + $item['quantity'];
        }
        if (!$qtys) {
            throw new \DomainException('Agregá al menos un material con cantidad.');
        }

        return Database::transaction(static function () use ($clientId, $client, $type, $qtys, $reference, $notes, $adjustUp): int {
            $detail = trim("Consignación {$client}" . ($reference ? " · {$reference}" : ''));
            foreach ($qtys as $materialId => $qty) {
                $material = Database::fetch('SELECT id, name, price, active FROM materials WHERE id = ?', [$materialId])
                    ?? throw new \DomainException('Material inexistente.');
                $delta = match ($type) {
                    'envio' => $qty,
                    'ajuste' => $adjustUp ? $qty : -$qty,
                    default => -$qty,
                };

                if ($type === 'envio' && !(int) $material['active']) {
                    throw new \DomainException("«{$material['name']}» está inactivo.");
                }
                self::changeBalance($clientId, (int) $materialId, $delta, (string) $material['name']);

                match ($type) {
                    'envio' => BoxService::moveStock((int) $materialId, -$qty, 'consig_envio', $detail),
                    'devolucion' => BoxService::moveStock((int) $materialId, $qty, 'consig_devolucion', $detail),
                    // El consumo no pasa por el depósito: queda registrado para la trazabilidad del material.
                    'consumo' => BoxService::moveStock((int) $materialId, -$qty, 'consig_consumo', $detail, affectsLoose: false),
                    'ajuste' => null,
                };

                Database::insert('consignment_movements', [
                    'client_id' => $clientId,
                    'material_id' => $materialId,
                    'type' => $type,
                    'quantity' => $delta,
                    'unit_price' => $material['price'],
                    'reference' => $reference,
                    'notes' => $notes,
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                ]);
            }
            Audit::log('consignment.' . $type, 'clients', $clientId, $reference);
            return count($qtys);
        });
    }

    /**
     * Marca consumos como facturados.
     *
     * @param list<int> $movementIds
     */
    public static function markBilled(int $clientId, array $movementIds, string $invoiceNumber): int
    {
        $movementIds = array_values(array_unique(array_filter(array_map('intval', $movementIds))));
        if (!$movementIds) {
            throw new \DomainException('Seleccioná al menos un consumo.');
        }
        $marks = implode(',', array_fill(0, count($movementIds), '?'));
        $stmt = Database::query(
            "UPDATE consignment_movements SET invoice_number = ?, billed_at = ?
              WHERE client_id = ? AND type = 'consumo' AND billed_at IS NULL AND id IN ({$marks})",
            [$invoiceNumber, now(), $clientId, ...$movementIds]
        );
        $count = $stmt->rowCount();
        if ($count === 0) {
            throw new \DomainException('Los consumos seleccionados ya estaban facturados.');
        }
        Audit::log('consignment.billed', 'clients', $clientId, "Factura {$invoiceNumber} ({$count})");
        return $count;
    }

    /** Saldo del cliente por material. */
    public static function balance(int $clientId): array
    {
        return Database::fetchAll(
            'SELECT cs.material_id, cs.quantity, m.code, m.name, m.price, m.unit
               FROM consignment_stock cs JOIN materials m ON m.id = cs.material_id
              WHERE cs.client_id = ? AND cs.quantity <> 0 ORDER BY m.name',
            [$clientId]
        );
    }

    private static function changeBalance(int $clientId, int $materialId, int $delta, string $name): void
    {
        $current = Database::value('SELECT quantity FROM consignment_stock WHERE client_id = ? AND material_id = ?', [$clientId, $materialId]);
        $now = (int) ($current ?? 0);
        if ($now + $delta < 0) {
            throw new \DomainException("El cliente tiene solo {$now} u. de «{$name}» en consignación.");
        }
        if ($current === null) {
            Database::insert('consignment_stock', ['client_id' => $clientId, 'material_id' => $materialId, 'quantity' => $delta, 'updated_at' => now()]);
        } else {
            Database::query('UPDATE consignment_stock SET quantity = quantity + ?, updated_at = ? WHERE client_id = ? AND material_id = ?', [$delta, now(), $clientId, $materialId]);
        }
    }
}
