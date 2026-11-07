<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class Surgery
{
    public const ATTACHMENT_PERMS = [
        'receta' => 'quote.view',
        'oc' => 'quote.view',
        'informe' => 'cx.view',
        'factura' => 'billing.view',
        'proveedor' => 'billing.view',
    ];

    public const PAYMENT_TYPES = [
        'transferencia' => 'Transferencia',
        'cheque' => 'Cheque',
        'echeq' => 'E-cheq',
        'deposito' => 'Depósito',
        'efectivo' => 'Efectivo',
        'otro' => 'Otro',
    ];

    public static function select(): string
    {
        return 'SELECT s.*,
                p.last_name AS patient_last_name, p.first_name AS patient_first_name, p.document AS patient_document,
                i.name AS institution_name, i.address AS institution_address, i.city AS institution_city,
                d.last_name AS doctor_last_name, d.first_name AS doctor_first_name,
                c.name AS client_name, c.type AS client_type,
                sel.name AS seller_name, drv.name AS driver_name,
                ins.last_name AS instrumentist_last_name, ins.first_name AS instrumentist_first_name,
                b.code AS box_code, b.name AS box_name, b.status AS box_status
           FROM surgeries s
           JOIN patients p ON p.id = s.patient_id
           JOIN institutions i ON i.id = s.institution_id
           JOIN doctors d ON d.id = s.doctor_id
           JOIN clients c ON c.id = s.client_id
           LEFT JOIN users sel ON sel.id = s.seller_id
           LEFT JOIN users drv ON drv.id = s.driver_id
           LEFT JOIN instrumentists ins ON ins.id = s.instrumentist_id
           LEFT JOIN boxes b ON b.id = s.box_id';
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(self::select() . ' WHERE s.id = ?', [$id]);
    }

    /**
     * Condición SQL que limita qué cirugías ve el usuario actual según sus permisos.
     * @return array{0:string, 1:array}
     */
    public static function scope(): array
    {
        if (Auth::can('surgeries.view_all')) {
            return ['1 = 1', []];
        }
        $uid = Auth::id();
        $parts = [];
        $params = [];
        if (Auth::can('surgeries.view_own')) {
            $parts[] = 's.seller_id = ?';
            $params[] = $uid;
        }
        if (Auth::can('deliveries.view_own')) {
            $parts[] = 's.driver_id = ?';
            $params[] = $uid;
        }
        if (Auth::can('collection.view')) {
            $parts[] = "s.stage IN ('cobranza', 'finalizada')";
        }
        return $parts ? ['(' . implode(' OR ', $parts) . ')', $params] : ['1 = 0', []];
    }

    public static function canAccess(array $s): bool
    {
        $uid = Auth::id();
        return Auth::can('surgeries.view_all')
            || (Auth::can('surgeries.view_own') && (int) $s['seller_id'] === $uid)
            || (Auth::can('deliveries.view_own') && (int) $s['driver_id'] === $uid)
            || (Auth::can('collection.view') && in_array($s['stage'], ['cobranza', 'finalizada'], true));
    }

    /** ¿Ve el resumen clínico/comercial? (Cobranzas y choferes no). */
    public static function canSeeSummary(array $s): bool
    {
        return Auth::canAny('surgery.summary.view', 'quote.view')
            && (Auth::can('surgeries.view_all') || (int) $s['seller_id'] === Auth::id());
    }

    public static function quoteItems(int $id): array
    {
        return Database::fetchAll(
            'SELECT qi.*, m.code AS material_code FROM quote_items qi
               LEFT JOIN materials m ON m.id = qi.material_id
              WHERE qi.surgery_id = ? ORDER BY qi.sort_order, qi.id',
            [$id]
        );
    }

    public static function quoteTotal(int $id): float
    {
        return (float) Database::value('SELECT COALESCE(SUM(quantity * unit_price), 0) FROM quote_items WHERE surgery_id = ?', [$id]);
    }

    public static function consumption(int $id): array
    {
        return Database::fetchAll(
            'SELECT ci.*, m.code AS material_code, m.name AS material_name FROM consumption_items ci
               JOIN materials m ON m.id = ci.material_id
              WHERE ci.surgery_id = ? ORDER BY ci.id',
            [$id]
        );
    }

    public static function attachments(int $id): array
    {
        $rows = Database::fetchAll('SELECT * FROM attachments WHERE surgery_id = ? ORDER BY id', [$id]);
        $out = [];
        foreach ($rows as $row) {
            $out[$row['kind']][] = $row;
        }
        return $out;
    }

    public static function supplierInvoices(int $id): array
    {
        return Database::fetchAll(
            'SELECT si.*, sp.name AS supplier_name FROM supplier_invoices si
               JOIN suppliers sp ON sp.id = si.supplier_id
              WHERE si.surgery_id = ? ORDER BY si.id',
            [$id]
        );
    }

    public static function events(int $id): array
    {
        return Database::fetchAll(
            'SELECT e.*, u.name AS user_name FROM surgery_events e
               LEFT JOIN users u ON u.id = e.user_id
              WHERE e.surgery_id = ? ORDER BY e.id DESC',
            [$id]
        );
    }

    public static function log(int $id, string $event, ?string $detail = null): void
    {
        Database::insert('surgery_events', [
            'surgery_id' => $id,
            'user_id' => Auth::id(),
            'event' => $event,
            'detail' => $detail,
            'created_at' => now(),
        ]);
    }

    public static function customData(array $s): array
    {
        $data = json_decode((string) ($s['custom_data'] ?? ''), true);
        return is_array($data) ? $data : [];
    }

    /** Usuarios que pueden ser elegidos como vendedor / chofer. */
    public static function usersWith(string $permission): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.name, u.commission_pct FROM users u
               JOIN role_permissions rp ON rp.role_id = u.role_id
              WHERE rp.permission = ? AND u.active = 1 ORDER BY u.name',
            [$permission]
        );
    }
}
