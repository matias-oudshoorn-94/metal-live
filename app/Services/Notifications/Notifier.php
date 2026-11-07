<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Core\Database;

/**
 * Motor único de avisos. Encola un aviso por destinatario y canal, y lo envía.
 * Si NOTIFY_QUEUE=true, el envío lo hace el worker (bin/notify.php) por cron.
 */
final class Notifier
{
    private const MAX_ATTEMPTS = 5;

    /** @return Channel[] */
    public static function channels(): array
    {
        $enabled = array_map('trim', explode(',', (string) env('NOTIFY_CHANNELS', 'email,whatsapp')));
        $all = [new EmailChannel(), new WhatsAppChannel()];
        return array_values(array_filter($all, fn (Channel $c) => $c->enabled() && in_array($c->name(), $enabled, true)));
    }

    /**
     * @param array<int, array> $users  filas de la tabla users
     */
    public static function send(string $event, array $users, Message $message, ?int $surgeryId = null): void
    {
        $seen = [];
        $ids = [];
        foreach ($users as $user) {
            if (!$user || !(int) ($user['active'] ?? 1) || isset($seen[$user['id']])) {
                continue;
            }
            $seen[$user['id']] = true;
            foreach (self::channels() as $channel) {
                $address = $channel->addressFor($user);
                if ($address === null) {
                    continue;
                }
                $ids[] = Database::insert('notifications', [
                    'event' => $event,
                    'channel' => $channel->name(),
                    'user_id' => (int) $user['id'],
                    'surgery_id' => $surgeryId,
                    'recipient' => $address,
                    'subject' => $message->subject,
                    'body' => $channel->render($message),
                    'status' => 'pendiente',
                    'created_at' => now(),
                ]);
            }
        }

        if (!env('NOTIFY_QUEUE', false)) {
            foreach ($ids as $id) {
                self::deliver($id);
            }
        }
    }

    /** Usuarios activos cuyo rol tiene el permiso indicado. */
    public static function usersWithPermission(string $permission): array
    {
        return Database::fetchAll(
            'SELECT u.* FROM users u JOIN role_permissions rp ON rp.role_id = u.role_id
              WHERE rp.permission = ? AND u.active = 1',
            [$permission]
        );
    }

    public static function user(?int $id): ?array
    {
        return $id ? Database::fetch('SELECT * FROM users WHERE id = ? AND active = 1', [$id]) : null;
    }

    public static function deliver(int $id): bool
    {
        $row = Database::fetch('SELECT * FROM notifications WHERE id = ?', [$id]);
        if (!$row || $row['status'] === 'enviado') {
            return true;
        }
        $channel = array_values(array_filter(self::channels(), fn (Channel $c) => $c->name() === $row['channel']))[0] ?? null;

        try {
            if (!$channel) {
                throw new \RuntimeException("Canal {$row['channel']} deshabilitado.");
            }
            $channel->send($row['recipient'], $row['subject'], $row['body']);
            Database::update('notifications', ['status' => 'enviado', 'sent_at' => now(), 'attempts' => $row['attempts'] + 1, 'last_error' => null], $id);
            return true;
        } catch (\Throwable $e) {
            $attempts = (int) $row['attempts'] + 1;
            Database::update('notifications', [
                'status' => $attempts >= self::MAX_ATTEMPTS ? 'fallido' : 'reintentar',
                'attempts' => $attempts,
                'last_error' => mb_substr($e->getMessage(), 0, 1000),
            ], $id);
            error_log("[notifier] #{$id}: " . $e->getMessage());
            return false;
        }
    }

    /** Procesa la cola (lo usa el worker). */
    public static function processQueue(int $limit = 50): array
    {
        $rows = Database::fetchAll(
            "SELECT id FROM notifications WHERE status IN ('pendiente', 'reintentar') ORDER BY id LIMIT " . max(1, $limit)
        );
        $ok = 0;
        foreach ($rows as $row) {
            $ok += self::deliver((int) $row['id']) ? 1 : 0;
        }
        return ['procesados' => count($rows), 'enviados' => $ok];
    }
}
