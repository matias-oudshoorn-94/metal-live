<?php
use App\Controllers\Admin\NotificationController as N;

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$dayLabel = fn (string $d): string => match ($d) {
    $today => 'Hoy',
    $yesterday => 'Ayer',
    default => ucfirst(long_date($d, substr($d, 0, 4) !== date('Y'))),
};
$days = [];
foreach ($groups as $g) {
    $days[substr((string) $g['first']['created_at'], 0, 10)][] = $g;
}
$channelNames = array_map(fn ($c) => N::CHANNELS[$c][0] ?? $c, $channels);
$isLog = env('MAIL_DRIVER', 'log') === 'log';
$filterUrl = fn (array $q): string => url('/admin/avisos', array_filter($q + ['status' => $status, 'evento' => $event], fn ($v) => $v !== ''));
$plainBody = function (string $html): string {
    $text = html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/div|\/tr|\/h\d)[^>]*>/i', "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace("/\n\s*\n+/", "\n\n", preg_replace('/[ \t]+/', ' ', $text)));
};
?>
<div class="page-head">
  <div>
    <h2>Avisos</h2>
    <p class="subtitle">Correos y mensajes que el sistema envía solo cuando avanza una cirugía o llega una consulta.</p>
  </div>
</div>

<div class="notify-status">
  <span><?= icon('bell') ?> Canales activos: <strong><?= $channelNames ? e(implode(' y ', $channelNames)) : 'ninguno' ?></strong></span>
  <?php if ($isLog): ?>
    <span class="badge badge-warning"><?= icon('alert') ?> Modo de prueba: los correos no salen, se guardan en storage/mail</span>
  <?php endif; ?>
</div>

<section class="card">
  <div class="filters filters-split">
    <nav class="chips" aria-label="Filtrar por estado">
      <a class="chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $filterUrl(['status' => '']) ?>">Todos <span class="count"><?= array_sum($counts) ?></span></a>
      <?php foreach (N::STATUSES as $k => [$label]): ?>
        <a class="chip<?= $status === $k ? ' is-active' : '' ?>" href="<?= $filterUrl(['status' => $k]) ?>"><?= e($label) ?> <span class="count"><?= $counts[$k] ?? 0 ?></span></a>
      <?php endforeach; ?>
    </nav>
    <form method="get" action="<?= url('/admin/avisos') ?>" class="notify-event-filter">
      <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <label for="f-event" class="visually-hidden">Tipo de aviso</label>
      <select id="f-event" name="evento" data-autosubmit>
        <option value="">Todos los tipos de aviso</option>
        <?php foreach ($events as $ev): ?><option value="<?= e($ev) ?>"<?= selected($event, $ev) ?>><?= e(N::EVENTS[$ev][0] ?? $ev) ?></option><?php endforeach; ?>
      </select>
      <noscript><button class="btn btn-sm" type="submit">Filtrar</button></noscript>
    </form>
  </div>

  <?php if (!$groups): ?>
    <div class="empty"><?= icon('bell') ?><?= $status !== '' || $event !== '' ? 'No hay avisos con esos filtros.' : 'Todavía no se envió ningún aviso.' ?></div>
  <?php endif; ?>

  <?php foreach ($days as $day => $dayGroups): ?>
    <div class="audit-day">
      <h3 class="audit-day-title"><?= e($dayLabel($day)) ?> <span><?= count($dayGroups) ?></span></h3>
      <ol class="audit-list">
        <?php foreach ($dayGroups as $g):
            $n = $g['first'];
            [$evLabel, $evIcon, $evWhy] = N::EVENTS[$n['event']] ?? [$n['event'], 'bell', ''];
            $failed = array_filter($g['recipients'], fn ($r) => $r['status'] !== 'enviado'); ?>
          <li class="audit-item notify-item<?= $failed ? ' has-problem' : '' ?>">
            <time class="audit-time" datetime="<?= e(str_replace(' ', 'T', (string) $n['created_at'])) ?>"><?= e(substr((string) $n['created_at'], 11, 5)) ?></time>
            <span class="audit-icon"><?= icon($evIcon) ?></span>
            <div class="audit-body">
              <p class="audit-text">
                <span class="audit-action"><?= e($evLabel) ?></span>
                <?php if ($n['surgery_id']): ?><a href="<?= url('/admin/cirugias/' . $n['surgery_id']) ?>"><?= e(surgery_code($n['surgery_id'])) ?></a><?php endif; ?>
              </p>
              <p class="audit-detail">«<?= e($n['subject']) ?>»</p>
              <?php if ($evWhy): ?><p class="audit-meta"><?= e($evWhy) ?></p><?php endif; ?>

              <ul class="notify-recipients">
                <?php foreach ($g['recipients'] as $r):
                    [$stLabel, $stTone] = N::STATUSES[$r['status']] ?? [ucfirst($r['status']), 'muted'];
                    [$chLabel, $chIcon] = N::CHANNELS[$r['channel']] ?? [$r['channel'], 'bell']; ?>
                  <li class="notify-recipient is-<?= e($stTone) ?>">
                    <span class="notify-dot" aria-hidden="true"></span>
                    <span class="notify-who" title="<?= e($r['recipient']) ?>"><strong><?= e($r['user_name'] ?? $r['recipient']) ?></strong> <span><?= icon($chIcon) ?> <?= e($chLabel) ?></span></span>
                    <span class="badge badge-<?= e($stTone) ?>" title="<?= $r['sent_at'] ? 'Enviado el ' . e(fdatetime($r['sent_at'])) : '' ?><?= (int) $r['attempts'] > 1 ? ' · ' . (int) $r['attempts'] . ' intentos' : '' ?>"><?= e($stLabel) ?></span>
                    <?php if ($r['status'] !== 'enviado'): ?>
                      <form method="post" action="<?= url('/admin/avisos/' . $r['id'] . '/reintentar') ?>"><?= csrf_field() ?><button class="btn btn-outline btn-sm" type="submit">Reintentar</button></form>
                    <?php endif; ?>
                    <?php if ($r['last_error'] && $r['status'] !== 'enviado'): ?><p class="notify-error"><?= icon('alert') ?> <?= e($r['last_error']) ?> <span>(<?= (int) $r['attempts'] ?> <?= (int) $r['attempts'] === 1 ? 'intento' : 'intentos' ?>)</span></p><?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>

              <details class="notify-body">
                <summary>Ver mensaje</summary>
                <div><?= nl2br(e($plainBody((string) $n['body']))) ?></div>
              </details>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  <?php endforeach; ?>

  <?= App\Core\View::partial('partials/pagination', ['total' => $total, 'perPage' => $perPage]) ?>
</section>
