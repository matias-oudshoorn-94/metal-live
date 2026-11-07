<div class="page-head">
  <div>
    <h2>Agenda</h2>
    <p class="subtitle">Cirugías activas de los próximos 30 días.</p>
  </div>
  <div class="actions"><a class="btn" href="<?= url('/admin/cirugias') ?>"><?= icon('list') ?> Ver listado</a></div>
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
