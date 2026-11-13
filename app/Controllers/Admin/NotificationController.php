<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Notifications\Notifier;

final class NotificationController extends Controller
{
    private const PER_PAGE = 100;

    /** Estados: etiqueta y color del badge. */
    public const STATUSES = [
        'pendiente' => ['Pendiente', 'info'],
        'reintentar' => ['Por reintentar', 'warning'],
        'enviado' => ['Enviado', 'success'],
        'fallido' => ['Fallido', 'danger'],
    ];

    /** Eventos: nombre claro, ícono y cuándo/a quién se envía. */
    public const EVENTS = [
        'quote.updated' => ['Cotización modificada', 'file', 'Al vendedor, cuando otra persona edita su cotización.'],
        'surgery.authorized' => ['Cirugía autorizada', 'check', 'A logística, para que asigne material, caja, chofer y fecha.'],
        'surgery.assigned' => ['Material y fecha asignados', 'calendar', 'Al equipo de Cx, para que confirme la fecha y el horario.'],
        'box.preparing' => ['Material en preparación', 'box', 'Al chofer asignado, para que sepa que se está armando la caja.'],
        'box.ready' => ['Listo para reparto', 'truck', 'Al chofer asignado, con el enlace para confirmar la entrega.'],
        'delivery.confirmed' => ['Entrega confirmada', 'check', 'Al vendedor, cuando el material llegó a la institución.'],
        'surgery.suspended' => ['Cirugía suspendida', 'pause', 'Al equipo de Cx, al vendedor y al chofer.'],
        'contact.received' => ['Mensaje desde el sitio web', 'mail', 'A quienes atienden los mensajes del formulario de contacto.'],
    ];

    public const CHANNELS = ['email' => ['Correo', 'mail'], 'whatsapp' => ['WhatsApp', 'whatsapp']];

    public function index(): void
    {
        $status = (string) $this->request->query('status', '');
        $event = (string) $this->request->query('evento', '');
        $conds = [];
        $params = [];
        if (isset(self::STATUSES[$status])) {
            $conds[] = 'n.status = ?';
            $params[] = $status;
        }
        if ($event !== '' && preg_match('/^[a-z_.]+$/', $event)) {
            $conds[] = 'n.event = ?';
            $params[] = $event;
        }
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

        $total = (int) Database::value("SELECT COUNT(*) FROM notifications n {$where}", $params);
        $rows = Database::fetchAll(
            "SELECT n.id, n.event, n.channel, n.recipient, n.subject, n.body, n.status, n.attempts, n.last_error, n.created_at, n.sent_at, n.surgery_id, u.name AS user_name
               FROM notifications n LEFT JOIN users u ON u.id = n.user_id {$where}
              ORDER BY n.id DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (($this->page() - 1) * self::PER_PAGE),
            $params
        );

        // Un mismo aviso enviado a varias personas a la vez se muestra como un solo elemento.
        $groups = [];
        foreach ($rows as $n) {
            $key = $n['event'] . '|' . $n['surgery_id'] . '|' . $n['subject'] . '|' . substr((string) $n['created_at'], 0, 16);
            $groups[$key] ??= ['first' => $n, 'recipients' => []];
            $groups[$key]['recipients'][] = $n;
        }

        $counts = [];
        foreach (Database::fetchAll('SELECT status, COUNT(*) AS c FROM notifications GROUP BY status') as $c) {
            $counts[$c['status']] = (int) $c['c'];
        }
        $events = array_column(Database::fetchAll('SELECT DISTINCT event FROM notifications ORDER BY event'), 'event');

        $this->render('admin/notifications/index', [
            'title' => 'Avisos',
            'groups' => array_values($groups),
            'status' => $status,
            'event' => $event,
            'events' => array_unique([...array_keys(self::EVENTS), ...$events]),
            'counts' => $counts,
            'total' => $total,
            'perPage' => self::PER_PAGE,
            'channels' => array_map(fn ($c) => $c->name(), Notifier::channels()),
        ]);
    }

    public function retry(string $id): void
    {
        Database::query("UPDATE notifications SET status = 'reintentar', attempts = 0 WHERE id = ? AND status <> 'enviado'", [(int) $id]);
        $ok = Notifier::deliver((int) $id);
        $ok ? $this->back(success: 'Aviso enviado.') : $this->back('El aviso volvió a fallar. Revisá el error.');
    }
}
