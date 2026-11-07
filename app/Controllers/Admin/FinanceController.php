<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Surgery;

final class FinanceController extends Controller
{
    public function billing(): void
    {
        $filter = (string) $this->request->query('filtro', 'pendientes');
        $where = match ($filter) {
            'honorarios' => "s.stage IN ('cobranza', 'finalizada') AND s.fee_liquidated = 0 AND s.fee_amount > 0",
            'proveedores' => 'EXISTS (SELECT 1 FROM supplier_invoices si WHERE si.surgery_id = s.id AND si.paid = 0)',
            'emitidas' => "s.stage IN ('cobranza', 'finalizada')",
            default => "s.stage = 'facturacion' AND s.status = 'activa'",
        };
        $rows = Database::fetchAll(
            'SELECT x.*, (SELECT COALESCE(SUM(quantity * unit_price), 0) FROM quote_items q WHERE q.surgery_id = x.id) AS quote_total,
                    (SELECT COALESCE(SUM(amount), 0) FROM supplier_invoices si WHERE si.surgery_id = x.id AND si.paid = 0) AS supplier_due
               FROM (' . Surgery::select() . " WHERE {$where}) x ORDER BY x.updated_at DESC LIMIT 200"
        );
        $this->render('admin/finance/billing', ['title' => 'Facturación', 'rows' => $rows, 'filter' => $filter]);
    }

    public function collections(): void
    {
        $filter = (string) $this->request->query('filtro', 'pendientes');
        $where = $filter === 'cobradas' ? "s.stage = 'finalizada'" : "s.stage = 'cobranza'";
        // Cobranzas no ve datos de Cx: solo cliente, factura y cobro.
        $rows = Database::fetchAll(
            "SELECT s.id, s.stage, s.invoice_number, s.invoice_date, s.invoice_amount, s.payment_date, s.payment_type,
                    s.payment_amount, s.cheque_number, s.cheque_date, c.name AS client_name, c.payment_terms
               FROM surgeries s JOIN clients c ON c.id = s.client_id
              WHERE {$where} ORDER BY s.invoice_date, s.id LIMIT 300"
        );
        $pending = (float) Database::value("SELECT COALESCE(SUM(invoice_amount), 0) FROM surgeries WHERE stage = 'cobranza'");
        $this->render('admin/finance/collections', ['title' => 'Cobranzas', 'rows' => $rows, 'filter' => $filter, 'pending' => $pending]);
    }
}
