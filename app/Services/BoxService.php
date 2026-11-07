<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Enums\BoxStatus;

/** Estadios de cajas y movimientos de stock de material. */
final class BoxService
{
    public static function setStatus(int $boxId, BoxStatus $to, ?int $surgeryId = null, ?string $note = null): void
    {
        $box = Database::fetch('SELECT * FROM boxes WHERE id = ?', [$boxId]);
        if (!$box) {
            throw new \DomainException('La caja no existe.');
        }
        $current = in_array($to, [BoxStatus::Disponible, BoxStatus::Usada, BoxStatus::ParaEsterilizar], true) ? null : $surgeryId;
        Database::update('boxes', ['status' => $to->value, 'current_surgery_id' => $current, 'updated_at' => now()], $boxId);
        Database::insert('box_events', [
            'box_id' => $boxId,
            'surgery_id' => $surgeryId,
            'from_status' => $box['status'],
            'to_status' => $to->value,
            'user_id' => Auth::id(),
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    /** Cajas que pueden asignarse (disponibles, más la actualmente asignada). */
    public static function assignable(?int $includeId = null): array
    {
        return Database::fetchAll(
            "SELECT id, code, name, status FROM boxes WHERE active = 1 AND (status = 'disponible' OR id = ?) ORDER BY code",
            [$includeId ?? 0]
        );
    }

    /**
     * Movimiento sobre el stock suelto (cantidad con signo: + ingresa, − egresa).
     */
    public static function moveStock(int $materialId, int $qty, string $type, ?string $reason = null, ?int $boxId = null, ?int $surgeryId = null, bool $affectsLoose = true): void
    {
        if ($affectsLoose) {
            $current = (int) Database::value('SELECT stock_qty FROM materials WHERE id = ?', [$materialId]);
            if ($current + $qty < 0) {
                $name = Database::value('SELECT name FROM materials WHERE id = ?', [$materialId]);
                throw new \DomainException("Stock insuficiente de «{$name}» (disponible: {$current}).");
            }
            Database::query('UPDATE materials SET stock_qty = stock_qty + ?, updated_at = ? WHERE id = ?', [$qty, now(), $materialId]);
        }
        Database::insert('stock_movements', [
            'material_id' => $materialId,
            'box_id' => $boxId,
            'surgery_id' => $surgeryId,
            'type' => $type,
            'quantity' => $qty,
            'reason' => $reason,
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    /** Ajusta el contenido de una caja (cantidad con signo). */
    public static function moveBoxItem(int $boxId, int $materialId, int $qty): void
    {
        $item = Database::fetch('SELECT * FROM box_items WHERE box_id = ? AND material_id = ?', [$boxId, $materialId]);
        if (!$item) {
            $name = Database::value('SELECT name FROM materials WHERE id = ?', [$materialId]);
            throw new \DomainException("«{$name}» no forma parte de la caja asignada. Cargalo como consumo de stock suelto.");
        }
        $new = max(0, (int) $item['current_qty'] + $qty);
        Database::update('box_items', ['current_qty' => $new], (int) $item['id']);
    }

    /** Repone el contenido de una caja desde el stock suelto hasta la cantidad esperada. */
    public static function refill(int $boxId): int
    {
        $moved = 0;
        foreach (Database::fetchAll('SELECT * FROM box_items WHERE box_id = ? AND current_qty < expected_qty', [$boxId]) as $item) {
            $need = (int) $item['expected_qty'] - (int) $item['current_qty'];
            $available = (int) Database::value('SELECT stock_qty FROM materials WHERE id = ?', [$item['material_id']]);
            $qty = min($need, $available);
            if ($qty <= 0) {
                continue;
            }
            self::moveStock((int) $item['material_id'], -$qty, 'reposicion_caja', 'Reposición de caja', $boxId);
            Database::update('box_items', ['current_qty' => (int) $item['current_qty'] + $qty], (int) $item['id']);
            $moved += $qty;
        }
        return $moved;
    }
}
