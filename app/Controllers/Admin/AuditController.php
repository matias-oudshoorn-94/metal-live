<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\AuditFeed;

final class AuditController extends Controller
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $filters = [
            'user' => (string) $this->request->query('usuario', ''),
            'type' => (string) $this->request->query('tipo', ''),
            'from' => (string) $this->request->query('desde', ''),
            'to' => (string) $this->request->query('hasta', ''),
        ];
        [$rows, $total] = AuditFeed::fetch($filters, $this->page(), self::PER_PAGE);

        $this->render('admin/audit/index', [
            'title' => 'Auditoría',
            'items' => AuditFeed::describe($rows),
            'filters' => $filters,
            'users' => AuditFeed::users(),
            'total' => $total,
            'perPage' => self::PER_PAGE,
        ]);
    }
}
