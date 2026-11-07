<?php
$types = ['ingreso' => 'Ingreso', 'egreso' => 'Egreso', 'ajuste' => 'Ajuste', 'consumo_stock' => 'Consumo (stock)', 'consumo_caja' => 'Consumo (caja)', 'faltante_caja' => 'Faltante (caja)', 'reposicion_caja' => 'Reposición de caja', 'consig_envio' => 'Envío a consignación', 'consig_devolucion' => 'Devolución de consignación', 'consig_consumo' => 'Consumo en consignación'];
?>
<div class="page-head">
  <div>
    <h2><?= e($m['name']) ?></h2>
    <p class="subtitle mono"><?= e($m['code']) ?> · <?= e($m['category'] ?: 'Sin categoría') ?></p>
  </div>
  <div class="actions">
    <?php if (can('stock.manage')): ?>
      <a class="btn" href="<?= url('/admin/materiales/' . $m['id'] . '/editar') ?>"><?= icon('edit') ?> Editar</a>
      <form method="post" action="<?= url('/admin/materiales/' . $m['id'] . '/eliminar') ?>" data-confirm="¿Eliminar este material? Si tiene historial se desactivará.">
        <?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('trash') ?> Eliminar</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<section class="kpis">
  <div class="kpi"><div class="kpi-label">Stock suelto</div><div class="kpi-value"><?= (int) $m['stock_qty'] ?></div></div>
  <div class="kpi"><div class="kpi-label">Stock mínimo</div><div class="kpi-value"><?= (int) $m['min_stock'] ?></div></div>
  <div class="kpi"><div class="kpi-label">En cajas</div><div class="kpi-value"><?= array_sum(array_column($boxes, 'current_qty')) ?></div></div>
  <div class="kpi"><div class="kpi-label">En consignación</div><div class="kpi-value"><?= array_sum(array_column($consigned, 'quantity')) ?></div></div>
  <div class="kpi"><div class="kpi-label">Precio</div><div class="kpi-value"><?= money($m['price']) ?></div></div>
</section>

<div class="layout-aside">
  <section class="card">
    <div class="card-head"><h3><?= icon('list') ?> Movimientos</h3></div>
    <div class="table-wrap">
      <table class="table table-compact">
        <thead><tr><th>Fecha</th><th>Tipo</th><th class="text-right">Cantidad</th><th>Caja</th><th>Motivo</th><th>Usuario</th></tr></thead>
        <tbody>
        <?php foreach ($movements as $mv): ?>
          <tr>
            <td class="nowrap"><?= e(fdatetime($mv['created_at'])) ?></td>
            <td><?= e($types[$mv['type']] ?? $mv['type']) ?></td>
            <td class="text-right"><strong><?= (int) $mv['quantity'] > 0 ? '+' : '' ?><?= (int) $mv['quantity'] ?></strong></td>
            <td class="mono"><?= e($mv['box_code'] ?? '—') ?></td>
            <td><?= e($mv['reason'] ?? '') ?><?= $mv['surgery_id'] ? ' <a href="' . url('/admin/cirugias/' . $mv['surgery_id']) . '">' . e(surgery_code($mv['surgery_id'])) . '</a>' : '' ?></td>
            <td><?= e($mv['user_name'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$movements): ?><tr><td colspan="6" class="muted">Sin movimientos.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <aside>
    <?php if (can('stock.manage')): ?>
    <section class="card">
      <div class="card-head"><h3><?= icon('plus') ?> Registrar movimiento</h3></div>
      <form class="card-body" method="post" action="<?= url('/admin/materiales/' . $m['id'] . '/movimiento') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field">
            <label for="mv-type">Tipo</label>
            <select id="mv-type" name="type">
              <option value="ingreso">Ingreso de stock</option>
              <option value="egreso">Egreso</option>
              <option value="ajuste">Ajuste de inventario</option>
            </select>
          </div>
          <div class="field" data-show-when="type=ajuste">
            <label for="mv-dir">Sentido</label>
            <select id="mv-dir" name="direction"><option value="menos">Restar</option><option value="mas">Sumar</option></select>
          </div>
          <div class="field">
            <label for="mv-qty" class="req">Cantidad</label>
            <input id="mv-qty" type="number" min="1" name="quantity" required>
          </div>
          <div class="field span-all">
            <label for="mv-reason">Motivo / comprobante</label>
            <input id="mv-reason" name="reason" maxlength="255" placeholder="Ej.: Remito 0001-000123 del proveedor">
          </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Registrar</button></div>
      </form>
    </section>
    <?php endif; ?>

    <section class="card">
      <div class="card-head"><h3><?= icon('box') ?> Presente en cajas</h3></div>
      <ul class="task-list">
        <?php foreach ($boxes as $b): ?>
          <li><a href="<?= url('/admin/cajas/' . $b['id']) ?>"><span><span class="mono"><?= e($b['code']) ?></span><span class="cell-sub"><?= e($b['name']) ?></span></span><span class="task-count"><?= (int) $b['current_qty'] ?>/<?= (int) $b['expected_qty'] ?></span></a></li>
        <?php endforeach; ?>
        <?php if (!$boxes): ?><li class="empty">No forma parte de ninguna caja.</li><?php endif; ?>
      </ul>
    </section>

    <?php if (can('consignment.view', 'consignment.manage')): ?>
    <section class="card">
      <div class="card-head"><h3><?= icon('briefcase') ?> En consignación</h3></div>
      <ul class="task-list">
        <?php foreach ($consigned as $c): ?>
          <li><a href="<?= url('/admin/consignaciones/' . $c['id']) ?>"><span><?= e($c['name']) ?></span><span class="task-count"><?= (int) $c['quantity'] ?></span></a></li>
        <?php endforeach; ?>
        <?php if (!$consigned): ?><li class="empty">No hay unidades en clientes.</li><?php endif; ?>
      </ul>
    </section>
    <?php endif; ?>
  </aside>
</div>
