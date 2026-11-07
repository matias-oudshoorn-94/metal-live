<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Enums\BoxStatus;
use App\Enums\SurgeryStage;
use App\Models\Surgery;

final class DashboardController extends Controller
{
    public function index(): void
    {
        if (!Auth::can('dashboard.view')) {
            $this->redirect(Auth::can('deliveries.view_own') ? '/admin/entregas' : '/admin/perfil');
        }

        [$where, $params] = Surgery::scope();
        $byStage = array_column(Database::fetchAll(
            "SELECT s.stage, COUNT(*) AS total FROM surgeries s WHERE s.status = 'activa' AND {$where} GROUP BY s.stage",
            $params
        ), 'total', 'stage');
        $suspended = (int) Database::value("SELECT COUNT(*) FROM surgeries s WHERE s.status = 'suspendida' AND {$where}", $params);

        $upcoming = Database::fetchAll(
            Surgery::select() . " WHERE s.status = 'activa' AND s.scheduled_at >= ? AND s.scheduled_at < ? AND {$where} ORDER BY s.scheduled_at LIMIT 8",
            [date('Y-m-d 00:00:00'), date('Y-m-d 00:00:00', strtotime('+8 days')), ...$params]
        );

        $tasks = [];
        if (Auth::can('cx.confirm')) {
            $tasks[] = ['Confirmar fecha y horario (Cx)', (int) Database::value("SELECT COUNT(*) FROM surgeries WHERE status = 'activa' AND stage = 'asignacion' AND assigned_at IS NOT NULL AND cx_confirmed_at IS NULL"), '/admin/cirugias?stage=asignacion'];
        }
        if (Auth::can('logistics.manage')) {
            $tasks[] = ['Asignar material y logística', (int) Database::value("SELECT COUNT(*) FROM surgeries WHERE status = 'activa' AND stage = 'asignacion' AND assigned_at IS NULL"), '/admin/cirugias?stage=asignacion'];
        }
        if (Auth::can('cx.post')) {
            $tasks[] = ['Cargar informe o consumo', (int) Database::value("SELECT COUNT(*) FROM surgeries WHERE status = 'activa' AND stage IN ('cirugia', 'consumo')"), '/admin/cirugias?stage=cirugia'];
        }
        if (Auth::can('billing.edit')) {
            $tasks[] = ['Emitir facturas', (int) Database::value("SELECT COUNT(*) FROM surgeries WHERE status = 'activa' AND stage = 'facturacion'"), '/admin/facturacion'];
            $tasks[] = ['Honorarios sin liquidar', (int) Database::value("SELECT COUNT(*) FROM surgeries WHERE stage IN ('cobranza', 'finalizada') AND fee_liquidated = 0 AND fee_amount > 0"), '/admin/facturacion?filtro=honorarios'];
        }
        if (Auth::canAny('consignment.manage', 'billing.edit') && Auth::canAny('consignment.view', 'consignment.manage')) {
            $tasks[] = ['Consumos en consignación sin facturar', (int) Database::value("SELECT COUNT(DISTINCT client_id) FROM consignment_movements WHERE type = 'consumo' AND billed_at IS NULL"), '/admin/consignaciones'];
        }
        if (Auth::can('collection.edit')) {
            $tasks[] = ['Cobranzas pendientes', (int) Database::value("SELECT COUNT(*) FROM surgeries WHERE status = 'activa' AND stage = 'cobranza'"), '/admin/cobranzas'];
        }
        if (Auth::can('quote.edit')) {
            $own = Auth::can('surgeries.view_all') ? '' : ' AND seller_id = ' . (int) Auth::id();
            $tasks[] = ['Cotizaciones en curso', (int) Database::value("SELECT COUNT(*) FROM surgeries WHERE status = 'activa' AND stage IN ('cotizacion', 'autorizacion'){$own}"), '/admin/cirugias?stage=cotizacion'];
        }

        $boxes = Auth::canAny('boxes.states.view', 'stock.view')
            ? array_column(Database::fetchAll('SELECT status, COUNT(*) AS total FROM boxes WHERE active = 1 GROUP BY status'), 'total', 'status')
            : null;

        $lowStock = Auth::can('stock.view')
            ? Database::fetchAll('SELECT id, code, name, stock_qty, min_stock FROM materials WHERE active = 1 AND stock_qty <= min_stock AND min_stock > 0 ORDER BY stock_qty LIMIT 6')
            : [];

        $this->render('admin/dashboard', [
            'title' => 'Inicio',
            'byStage' => $byStage,
            'suspended' => $suspended,
            'upcoming' => $upcoming,
            'tasks' => $tasks,
            'boxes' => $boxes,
            'lowStock' => $lowStock,
            'stages' => SurgeryStage::cases(),
            'boxStatuses' => BoxStatus::cases(),
        ]);
    }
}
