<?php use App\Enums\BoxStatus; use App\Enums\SurgeryStage; ?>
<div class="page-head">
  <div>
    <h2><span class="mono"><?= e($box['code']) ?></span> · <?= e($box['name']) ?></h2>
    <p class="subtitle"><?= box_badge($box['status']) ?><?= (int) $box['active'] ? '' : ' <span class="badge badge-muted">Dada de baja</span>' ?></p>
  </div>
  <div class="actions">
    <?php if (can('stock.manage')): ?>
      <?php if (in_array($box['status'], [BoxStatus::Usada->value, BoxStatus::ParaEsterilizar->value], true)): ?>
        <form method="post" action="<?= url('/admin/cajas/' . $box['id'] . '/reponer') ?>"><?= csrf_field() ?><button class="btn" type="submit"><?= icon('layers') ?> Reponer desde stock</button></form>
        <form method="post" action="<?= url('/admin/cajas/' . $box['id'] . '/disponible') ?>" data-confirm="¿Marcar la caja como disponible?"><?= csrf_field() ?><button class="btn btn-success" type="submit"><?= icon('check') ?> <?= $box['status'] === BoxStatus::ParaEsterilizar->value ? 'Esterilizada: disponible' : 'Revisada: disponible' ?></button></form>
      <?php endif; ?>
      <a class="btn" href="<?= url('/admin/cajas/' . $box['id'] . '/editar') ?>"><?= icon('edit') ?> Editar</a>
      <?php if ($box['status'] === BoxStatus::Disponible->value && (int) $box['active']): ?>
        <form method="post" action="<?= url('/admin/cajas/' . $box['id'] . '/eliminar') ?>" data-confirm="¿Dar de baja esta caja?"><?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('trash') ?></button></form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php if ($box['description']): ?><p><?= nl2br(e($box['description'])) ?></p><?php endif; ?>

<div class="layout-aside">
  <div>
    <section class="card">
      <div class="card-head"><h3><?= icon('layers') ?> Contenido</h3></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Material</th><th class="text-right">Esperado</th><th class="text-right">Actual</th><th class="text-right">Stock suelto</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): $short = (int) $it['current_qty'] < (int) $it['expected_qty']; ?>
            <tr>
              <td><?= e($it['name']) ?><span class="cell-sub mono"><?= e($it['code']) ?></span></td>
              <td class="text-right"><?= (int) $it['expected_qty'] ?></td>
              <td class="text-right"><strong><?= (int) $it['current_qty'] ?></strong><?= $short ? ' <span class="badge badge-warning">Falta ' . ((int) $it['expected_qty'] - (int) $it['current_qty']) . '</span>' : '' ?></td>
              <td class="text-right"><?= (int) $it['stock_qty'] ?></td>
              <td class="actions-cell">
                <?php if (can('stock.manage')): ?>
                  <form method="post" action="<?= url('/admin/cajas/' . $box['id'] . '/items/' . $it['id'] . '/eliminar') ?>" data-confirm="¿Quitar este material de la caja?"><?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Quitar"><?= icon('trash') ?></button></form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$items): ?><tr><td colspan="5" class="muted">La caja no tiene contenido cargado.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if (can('stock.manage')): ?>
      <form class="card-body inline-form" method="post" action="<?= url('/admin/cajas/' . $box['id'] . '/items') ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="bi-mat">Material</label>
          <select id="bi-mat" name="material_id" required data-combobox data-combobox-code data-placeholder="Buscar material por código o nombre">
            <option value="">Seleccionar…</option>
            <?php foreach ($materials as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['code'] . ' · ' . $m['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label for="bi-exp">Esperado</label><input id="bi-exp" type="number" min="0" name="expected_qty" value="1" required></div>
        <div class="field"><label for="bi-cur">Actual</label><input id="bi-cur" type="number" min="0" name="current_qty" value="1" required></div>
        <button class="btn" type="submit"><?= icon('plus') ?> Agregar / actualizar</button>
      </form>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-head"><h3><?= icon('activity') ?> Historial de cirugías</h3></div>
      <div class="table-wrap">
        <table class="table table-compact">
          <thead><tr><th>Cirugía</th><th>Fecha</th><th>Institución</th><th>Estadio</th><th>Regresó</th></tr></thead>
          <tbody>
          <?php foreach ($surgeries as $sg): ?>
            <tr>
              <td><a class="mono" href="<?= url('/admin/cirugias/' . $sg['id']) ?>"><?= e(surgery_code($sg['id'])) ?></a></td>
              <td><?= e(fdatetime($sg['scheduled_at'])) ?></td>
              <td><?= e($sg['institution_name']) ?></td>
              <td><?= stage_badge($sg['stage'], $sg['status']) ?></td>
              <td><?= $sg['box_final_status'] ? box_badge($sg['box_final_status']) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$surgeries): ?><tr><td colspan="5" class="muted">La caja todavía no fue usada.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <aside>
    <section class="card">
      <div class="card-head"><h3><?= icon('list') ?> Estadios</h3></div>
      <div class="card-body">
        <ul class="event-list">
          <?php foreach ($events as $ev): ?>
            <li>
              <strong><?= box_badge($ev['to_status']) ?></strong>
              <?php if ($ev['note']): ?><span><?= e($ev['note']) ?></span><?php endif; ?>
              <?php if ($ev['surgery_id']): ?> <a href="<?= url('/admin/cirugias/' . $ev['surgery_id']) ?>"><?= e(surgery_code($ev['surgery_id'])) ?></a><?php endif; ?>
              <div class="meta"><?= e(fdatetime($ev['created_at'])) ?><?= $ev['user_name'] ? ' · ' . e($ev['user_name']) : '' ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
  </aside>
</div>
