<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

final class MessageController extends Controller
{
    public function index(): void
    {
        $rows = Database::fetchAll('SELECT * FROM contact_messages ORDER BY read_at IS NOT NULL, id DESC LIMIT 200');
        $this->render('admin/messages/index', ['title' => 'Mensajes de contacto', 'rows' => $rows]);
    }

    public function markRead(string $id): void
    {
        Database::query('UPDATE contact_messages SET read_at = ? WHERE id = ? AND read_at IS NULL', [now(), (int) $id]);
        $this->back(success: 'Mensaje marcado como leído.');
    }
}
