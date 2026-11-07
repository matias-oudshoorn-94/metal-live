<?php
use App\Enums\SurgeryStage;
?>
<div class="page-head">
  <div>
    <h2>Hola, <?= e(explode(' ', auth()['name'])[0]) ?></h2>
    <p class="subtitle"><?= e(mb_ucfirst(long_date())) ?></p>
  </div>
  <div class="actions">
    <?php if (can('quote.edit')): ?><a class="btn btn-primary" href="<?= url('/admin/cirugias/nueva') ?>"><?= icon('plus') ?> Nueva cotización</a><?php endif; ?>
  </div>
</div>

<?php if (can('surgeries.view_all', 'surgeries.view_own')): ?>
<section class="kpis" aria-label="Cirugías por estadio">
  <?php foreach ($stages as $stage): if ($stage === SurgeryStage::Finalizada) continue; ?>
    <a class="kpi" href="<?= url('/admin/cirugias', ['stage' => $stage->value]) ?>">
      <div class="kpi-label"><?= $stage->step() ?>. <?= e($stage->label()) ?></div>
      <div class="kpi-value"><?= (int) ($byStage[$stage->value] ?? 0) ?></div>
      <div class="kpi-bar stage-<?= e($stage->value) ?>"></div>
    </a>
  <?php endforeach; ?>
  <a class="kpi" href="<?= url('/admin/cirugias', ['status' => 'suspendida']) ?>">
    <div class="kpi-label">Suspendidas</div>
    <div class="kpi-value"><?= $suspended ?></div>
    <div class="kpi-bar stage-finalizada"></div>
  </a>
</section>
<?php endif; ?>

<div class="layout-aside">
  <div>
    <?php if (can('surgeries.view_all', 'surgeries.view_own')): ?>
    <section class="card">
      <div class="card-head">
        <h3><?= icon('calendar') ?> Próximas cirugías</h3>
        <div class="actions"><a class="btn btn-ghost btn-sm" href="<?= url(can('surgeries.view_all') ? '/admin/agenda' : '/admin/cirugias') ?>">Ver todo</a></div>
      </div>
      <?php if (!$upcoming): ?>
        <div class="empty"><?= icon('calendar') ?>No hay cirugías programadas para los próximos 7 días.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Fecha</th><th>Cirugía</th><th>Institución</th><th>Estadio</th><?php if (can('boxes.states.view')): ?><th>Caja</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($upcoming as $s): ?>
              <tr>
                <td class="nowrap"><strong><?= e(fdate($s['scheduled_at'], 'd/m')) ?></strong> <span class="muted"><?= e(fdate($s['scheduled_at'], 'H:i')) ?></span></td>
                <td><a class="row-link" href="<?= url('/admin/cirugias/' . $s['id']) ?>"><?= e(surgery_code($s['id'])) ?></a><span class="cell-sub"><?= e($s['procedure_name'] ?: person_name($s, 'patient_')) ?></span></td>
                <td><?= e($s['institution_name']) ?></td>
                <td><?= stage_badge($s['stage'], $s['status']) ?><?php if ($s['stage'] === 'asignacion' && $s['assigned_at'] && !$s['cx_confirmed_at']): ?> <span class="badge badge-warning">Cx sin confirmar</span><?php endif; ?></td>
                <?php if (can('boxes.states.view')): ?><td><?= $s['box_code'] ? e($s['box_code']) . ' ' . box_badge($s['box_status']) : '<span class="muted">—</span>' ?></td><?php endif; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($boxes !== null): ?>
    <section class="card">
      <div class="card-head"><h3><?= icon('box') ?> Estadios de cajas</h3><div class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('/admin/cajas') ?>">Ver cajas</a></div></div>
      <div class="card-body box-states">
        <?php foreach ($boxStatuses as $st): ?>
          <a class="box-state" href="<?= url('/admin/cajas', ['status' => $st->value]) ?>"><?= box_badge($st->value) ?><strong><?= (int) ($boxes[$st->value] ?? 0) ?></strong></a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <aside>
    <?php if ($tasks): ?>
    <section class="card">
      <div class="card-head"><h3><?= icon('check') ?> Pendientes</h3></div>
      <ul class="task-list">
        <?php foreach ($tasks as [$label, $count, $link]): ?>
          <li><a href="<?= url($link) ?>"><span><?= e($label) ?></span><span class="task-count<?= $count ? '' : ' zero' ?>"><?= $count ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <?php if ($lowStock): ?>
    <section class="card">
      <div class="card-head"><h3><?= icon('alert') ?> Stock bajo</h3><div class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('/admin/materiales', ['bajo' => 1]) ?>">Ver</a></div></div>
      <ul class="task-list">
        <?php foreach ($lowStock as $m): ?>
          <li><a href="<?= url('/admin/materiales/' . $m['id']) ?>"><span><?= e($m['name']) ?><span class="cell-sub mono"><?= e($m['code']) ?></span></span><span class="task-count"><?= (int) $m['stock_qty'] ?> / <?= (int) $m['min_stock'] ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>
  </aside>
</div>
