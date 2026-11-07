<?php

declare(strict_types=1);

/*
 * Worker del motor de avisos. Envía los avisos pendientes y reintenta los fallidos.
 * Programar por cron cuando NOTIFY_QUEUE=true, por ejemplo cada minuto:
 *   * * * * * php /ruta/al/proyecto/bin/notify.php >> storage/logs/notify.log 2>&1
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

$result = App\Services\Notifications\Notifier::processQueue(100);
echo date('c') . " procesados={$result['procesados']} enviados={$result['enviados']}\n";

// Limpieza de intentos de login viejos.
App\Core\Database::query('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', strtotime('-1 day'))]);
