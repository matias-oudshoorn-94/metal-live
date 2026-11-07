<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Notifications\Notifier;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $status = (string) $this->request->query('status', '');
        $params = [];
        $where = '';
        if (in_array($status, ['pendiente', 'reintentar', 'enviado', 'fallido'], true)) {
            $where = 'WHERE n.status = ?';
            $params[] = $status;
        }
        $rows = Database::fetchAll(
            "SELECT n.id, n.event, n.channel, n.recipient, n.subject, n.status, n.attempts, n.last_error, n.created_at, n.sent_at, n.surgery_id, u.name AS user_name
               FROM notifications n LEFT JOIN users u ON u.id = n.user_id {$where} ORDER BY n.id DESC LIMIT 200",
            $params
        );
        $channels = array_map(fn ($c) => $c->name(), Notifier::channels());
        $this->render('admin/notifications/index', ['title' => 'Avisos', 'rows' => $rows, 'status' => $status, 'channels' => $channels]);
    }

    public function retry(string $id): void
    {
        Database::query("UPDATE notifications SET status = 'reintentar', attempts = 0 WHERE id = ? AND status <> 'enviado'", [(int) $id]);
        $ok = Notifier::deliver((int) $id);
        $ok ? $this->back(success: 'Aviso enviado.') : $this->back('El aviso volvió a fallar. Revisá el error.');
    }
}
