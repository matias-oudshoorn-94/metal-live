<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

final class AuditController extends Controller
{
    public function index(): void
    {
        $rows = Database::fetchAll(
            'SELECT a.*, u.name AS user_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 300'
        );
        $this->render('admin/audit/index', ['title' => 'Auditoría', 'rows' => $rows]);
    }
}
