<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class Audit
{
    public static function log(string $action, ?string $entity = null, ?int $entityId = null, array|string|null $detail = null): void
    {
        Database::insert('audit_log', [
            'user_id' => $_SESSION['_uid'] ?? null,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'detail' => is_array($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : $detail,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'created_at' => now(),
        ]);
    }
}
