<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Models\Surgery;
use App\Services\SurgeryWorkflow;

final class DeliveryController extends Controller
{
    /** "Mis entregas" del chofer (o todas, para logística). */
    public function index(): void
    {
        $all = Auth::can('logistics.manage');
        $where = $all ? '1 = 1' : 's.driver_id = ?';
        $params = $all ? [] : [Auth::id()];
        $rows = Database::fetchAll(
            Surgery::select() . " WHERE {$where} AND s.status = 'activa' AND s.preparing_at IS NOT NULL
              AND (s.delivered_at IS NULL OR s.delivered_at >= ?) ORDER BY s.delivered_at IS NOT NULL, s.scheduled_at",
            [...$params, date('Y-m-d H:i:s', strtotime('-7 days'))]
        );
        $this->render('admin/deliveries/index', ['title' => $all ? 'Entregas' : 'Mis entregas', 'rows' => $rows]);
    }

    /** Página pública a la que llega el chofer desde el correo. GET no modifica nada. */
    public function tokenForm(string $token): void
    {
        [$s, $row] = $this->resolve($token);
        $this->render('site/delivery', [
            'title' => 'Confirmar entrega',
            's' => $s,
            'token' => $token,
            'used' => (bool) $row['used_at'] || $s['stage'] !== 'entrega',
        ], 'site');
    }

    public function tokenConfirm(string $token): void
    {
        [$s, $row] = $this->resolve($token);
        if (!$row['used_at'] && $s['stage'] === 'entrega' && $s['status'] === 'activa') {
            try {
                SurgeryWorkflow::confirmDelivery($s, 'email');
            } catch (\DomainException $e) {
                $this->back($e->getMessage());
            }
        }
        $this->redirect('/entrega/' . $token, 'Entrega confirmada. ¡Gracias!');
    }

    private function resolve(string $token): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new HttpException(404);
        }
        $row = Database::fetch('SELECT * FROM delivery_tokens WHERE token_hash = ?', [hash('sha256', $token)]);
        if (!$row || strtotime($row['expires_at']) < time()) {
            throw new HttpException(404, 'El enlace no es válido o venció. Confirmá la entrega desde el panel.');
        }
        $s = Surgery::find((int) $row['surgery_id']) ?? throw new HttpException(404);
        return [$s, $row];
    }
}
