<div class="page-head">
  <div>
    <h2>Agenda</h2>
    <p class="subtitle">Cirugías activas de los próximos 30 días. Abajo, el calendario completo del mes.</p>
  </div>
  <div class="actions">
    <a class="btn" href="#calendario"><?= icon('calendar') ?> Ver calendario</a>
    <a class="btn" href="<?= url('/admin/cirugias') ?>"><?= icon('list') ?> Ver listado</a>
  </div>
</div>

<?php if (!$days): ?>
  <section class="card"><div class="empty"><?= icon('calendar') ?>No hay cirugías programadas.</div></section>
<?php endif; ?>

<?php foreach ($days as $day => $rows): ?>
  <section class="agenda-day">
    <h3><?= e(long_date($day, false)) ?><?= $day === date('Y-m-d') ? ' · hoy' : '' ?></h3>
    <?php foreach ($rows as $s): ?>
      <a class="agenda-item" href="<?= url('/admin/cirugias/' . $s['id']) ?>">
        <time datetime="<?= e($s['scheduled_at']) ?>"><?= e(fdate($s['scheduled_at'], 'H:i')) ?></time>
        <div>
          <strong><?= e($s['procedure_name'] ?: surgery_code($s['id'])) ?></strong>
          <span class="cell-sub"><?= e($s['institution_name']) ?> · Dr/a. <?= e(person_name($s, 'doctor_')) ?> · <?= e(person_name($s, 'patient_')) ?></span>
        </div>
        <div class="button-row">
          <?= stage_badge($s['stage'], $s['status']) ?>
          <?php if ($s['assigned_at'] && !$s['cx_confirmed_at']): ?><span class="badge badge-warning">Cx sin confirmar</span><?php endif; ?>
          <?php if ($s['box_code']): ?><span class="badge"><?= e($s['box_code']) ?></span><?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </section>
<?php endforeach; ?>

<?php
$months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
[$calYear, $calMonth] = array_map('intval', explode('-', $calendar['month']));
$today = date('Y-m-d');
?>
<section class="card calendar" id="calendario" aria-labelledby="calendar-title">
  <div class="card-head">
    <h3 id="calendar-title"><?= icon('calendar') ?> Calendario · <span class="calendar-month"><?= e(ucfirst($months[$calMonth - 1])) ?> de <?= $calYear ?></span></h3>
    <div class="actions">
      <a class="btn btn-sm" href="<?= url('/admin/agenda', ['mes' => $calendar['prev']]) ?>#calendario" aria-label="Mes anterior">‹ <?= e(ucfirst($months[(int) substr($calendar['prev'], 5) - 1])) ?></a>
      <?php if ($calendar['month'] !== date('Y-m')): ?><a class="btn btn-sm" href="<?= url('/admin/agenda') ?>#calendario">Hoy</a><?php endif; ?>
      <a class="btn btn-sm" href="<?= url('/admin/agenda', ['mes' => $calendar['next']]) ?>#calendario" aria-label="Mes siguiente"><?= e(ucfirst($months[(int) substr($calendar['next'], 5) - 1])) ?> ›</a>
    </div>
  </div>
  <div class="calendar-grid" role="table" aria-label="Cirugías de <?= e($months[$calMonth - 1]) ?> de <?= $calYear ?>">
    <div class="calendar-weekdays" role="row">
      <?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $wd): ?><span role="columnheader"><?= $wd ?></span><?php endforeach; ?>
    </div>
    <?php foreach ($calendar['weeks'] as $week): ?>
      <div class="calendar-week" role="row">
        <?php foreach ($week as $d):
            $classes = 'calendar-day' . ($d['in_month'] ? '' : ' is-outside') . ($d['date'] === $today ? ' is-today' : '') . ($d['events'] ? '' : ' is-empty'); ?>
          <div class="<?= $classes ?>" role="cell">
            <span class="calendar-date"><span class="calendar-date-long"><?= e(ucfirst(long_date($d['date'], false))) ?></span><span class="calendar-date-short"><?= (int) substr($d['date'], 8) ?></span></span>
            <?php foreach ($d['events'] as $s):
                $suspended = $s['status'] === 'suspendida'; ?>
              <a class="calendar-event stage-<?= e($s['stage']) ?><?= $suspended ? ' is-suspended' : '' ?>" href="<?= url('/admin/cirugias/' . $s['id']) ?>"
                 title="<?= e(fdate($s['scheduled_at'], 'H:i') . ' · ' . ($s['procedure_name'] ?: surgery_code($s['id'])) . ' · ' . $s['institution_name'] . ' · ' . person_name($s, 'patient_') . ($suspended ? ' (suspendida)' : '')) ?>">
                <time datetime="<?= e($s['scheduled_at']) ?>"><?= e(fdate($s['scheduled_at'], 'H:i')) ?></time>
                <span><?= e($s['procedure_name'] ?: surgery_code($s['id'])) ?></span>
                <small><?= e($s['institution_name']) ?></small>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$calendar['total']): ?><p class="calendar-none muted">No hay cirugías este mes.</p><?php endif; ?>
</section>
