<?php
use App\Services\AuditFeed;

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$dayLabel = fn (string $d): string => match ($d) {
    $today => 'Hoy',
    $yesterday => 'Ayer',
    default => ucfirst(long_date($d, substr($d, 0, 4) !== date('Y'))),
};
$days = [];
foreach ($items as $item) {
    $days[substr((string) $item['created_at'], 0, 10)][] = $item;
}
$filtered = array_filter($filters, fn ($v) => $v !== '');
?>
<div class="page-head">
  <div>
    <h2>Auditoría</h2>
    <p class="subtitle">Quién hizo qué y cuándo en el panel: cirugías, stock, maestros, usuarios y accesos.</p>
  </div>
</div>

<section class="card">
  <form class="filters" method="get" action="<?= url('/admin/auditoria') ?>">
    <div class="field">
      <label for="f-user">Usuario</label>
      <select id="f-user" name="usuario">
        <option value="">Todos</option>
        <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"<?= selected($filters['user'], $u['id']) ?>><?= e($u['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="f-type">Tipo</label>
      <select id="f-type" name="tipo">
        <option value="">Todos</option>
        <?php foreach (AuditFeed::TYPES as $value => $label): ?><option value="<?= e($value) ?>"<?= selected($filters['type'], $value) ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="f-from">Desde</label>
      <input id="f-from" type="date" name="desde" value="<?= e($filters['from']) ?>">
    </div>
    <div class="field">
      <label for="f-to">Hasta</label>
      <input id="f-to" type="date" name="hasta" value="<?= e($filters['to']) ?>">
    </div>
    <div class="filters-buttons">
      <button class="btn btn-dark" type="submit"><?= icon('search') ?> Filtrar</button>
      <?php if ($filtered): ?><a class="btn btn-ghost" href="<?= url('/admin/auditoria') ?>">Limpiar</a><?php endif; ?>
    </div>
  </form>

  <?php if (!$items): ?>
    <div class="empty"><?= icon('list') ?><?= $filtered ? 'No hay actividad con esos filtros.' : 'Todavía no hay actividad registrada.' ?></div>
  <?php endif; ?>

  <?php foreach ($days as $day => $dayItems): ?>
    <div class="audit-day">
      <h3 class="audit-day-title"><?= e($dayLabel($day)) ?> <span><?= count($dayItems) ?></span></h3>
      <ol class="audit-list">
        <?php foreach ($dayItems as $it): ?>
          <li class="audit-item tone-<?= e($it['tone']) ?>">
            <time class="audit-time" datetime="<?= e(str_replace(' ', 'T', (string) $it['created_at'])) ?>"><?= e(substr((string) $it['created_at'], 11, 5)) ?></time>
            <span class="audit-icon"><?= icon($it['icon']) ?></span>
            <div class="audit-body">
              <p class="audit-text">
                <span class="audit-action"><?= e($it['title']) ?></span>
                <?php if ($it['target']): ?>
                  <?php if ($it['target']['url']): ?><a href="<?= url($it['target']['url']) ?>"><?= e($it['target']['label']) ?></a><?php else: ?><span class="audit-target"><?= e($it['target']['label']) ?></span><?php endif; ?>
                <?php endif; ?>
              </p>
              <?php foreach ($it['details'] as $d): $d = (string) $d; ?><p class="audit-detail"><?= e(mb_strlen($d) > 400 ? mb_substr($d, 0, 400) . '…' : $d) ?></p><?php endforeach; ?>
              <p class="audit-meta">por <strong><?= e($it['user']) ?></strong><?php if ($it['ip']): ?> <span class="audit-ip" title="Dirección IP">· IP <?= e($it['ip']) ?></span><?php endif; ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  <?php endforeach; ?>

  <?= App\Core\View::partial('partials/pagination', ['total' => $total, 'perPage' => $perPage]) ?>
</section>
