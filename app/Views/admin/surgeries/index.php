<?php
use App\Enums\SurgeryStage;
?>
<div class="page-head">
  <h2>Cirugías</h2>
  <div class="actions">
    <?php if (can('quote.edit')): ?><a class="btn btn-primary" href="<?= url('/admin/cirugias/nueva') ?>"><?= icon('plus') ?> Nueva cotización</a><?php endif; ?>
  </div>
</div>

<section class="card">
  <form class="filters" method="get" action="<?= url('/admin/cirugias') ?>">
    <div class="field field-search">
      <label for="f-q">Buscar</label>
      <input id="f-q" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Paciente, DNI, institución, médico, cliente o código">
    </div>
    <div class="field">
      <label for="f-stage">Estadio</label>
      <select id="f-stage" name="stage">
        <option value="">Todos</option>
        <?php foreach (SurgeryStage::options() as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= selected($filters['stage'], $value) ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="f-status">Estado</label>
      <select id="f-status" name="status">
        <option value="activa"<?= selected($filters['status'], 'activa') ?>>Activas</option>
        <option value="suspendida"<?= selected($filters['status'], 'suspendida') ?>>Suspendidas</option>
        <option value="todas"<?= selected($filters['status'], 'todas') ?>>Todas</option>
      </select>
    </div>
    <?php if ($sellers): ?>
    <div class="field">
      <label for="f-seller">Vendedor</label>
      <select id="f-seller" name="seller">
        <option value="">Todos</option>
        <?php foreach ($sellers as $u): ?><option value="<?= (int) $u['id'] ?>"<?= selected($filters['seller'], $u['id']) ?>><?= e($u['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="field">
      <label for="f-from">Desde</label>
      <input id="f-from" type="date" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="field">
      <label for="f-to">Hasta</label>
      <input id="f-to" type="date" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <button class="btn btn-dark" type="submit"><?= icon('search') ?> Filtrar</button>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><?= icon('activity') ?>No hay cirugías con esos filtros.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Código</th>
          <th>Fecha</th>
          <th>Paciente</th>
          <th>Institución / médico</th>
          <th>Cliente</th>
          <?php if (can('surgeries.view_all')): ?><th>Vendedor</th><?php endif; ?>
          <th>Estadio</th>
          <?php if (can('boxes.states.view')): ?><th>Caja</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $s): ?>
        <tr>
          <td class="nowrap"><a class="row-link mono" href="<?= url('/admin/cirugias/' . $s['id']) ?>"><?= e(surgery_code($s['id'])) ?></a></td>
          <td class="nowrap"><?= $s['scheduled_at'] ? e(fdatetime($s['scheduled_at'])) : '<span class="muted">A definir</span>' ?></td>
          <td><?= e(person_name($s, 'patient_')) ?><span class="cell-sub"><?= e($s['procedure_name'] ?? '') ?></span></td>
          <td><?= e($s['institution_name']) ?><span class="cell-sub">Dr/a. <?= e(person_name($s, 'doctor_')) ?></span></td>
          <td><?= e($s['client_name']) ?></td>
          <?php if (can('surgeries.view_all')): ?><td><?= e($s['seller_name'] ?? '—') ?></td><?php endif; ?>
          <td><?= stage_badge($s['stage'], $s['status']) ?></td>
          <?php if (can('boxes.states.view')): ?><td class="nowrap"><?= $s['box_code'] ? '<span class="mono">' . e($s['box_code']) . '</span> ' . box_badge($s['box_status']) : '<span class="muted">—</span>' ?></td><?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= App\Core\View::partial('partials/pagination', ['total' => $total, 'perPage' => $perPage]) ?>
  <?php endif; ?>
</section>
