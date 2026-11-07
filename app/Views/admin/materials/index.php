<div class="page-head">
  <h2>Materiales y stock</h2>
  <div class="actions">
    <?php if (can('stock.manage')): ?><a class="btn btn-primary" href="<?= url('/admin/materiales/nuevo') ?>"><?= icon('plus') ?> Nuevo material</a><?php endif; ?>
  </div>
  <p class="subtitle">Stock suelto (en depósito), unidades dentro de las cajas y en consignación en clientes.</p>
</div>

<section class="card">
  <form class="filters" method="get">
    <div class="field field-search"><label for="q">Buscar</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Código o nombre"></div>
    <div class="field">
      <label for="cat">Categoría</label>
      <select id="cat" name="category">
        <option value="">Todas</option>
        <?php foreach ($categories as $c): ?><option<?= selected($category, $c) ?>><?= e($c) ?></option><?php endforeach; ?>
      </select>
    </div>
    <label class="check"><input type="checkbox" name="bajo" value="1"<?= checked(($_GET['bajo'] ?? '') === '1') ?>> Solo stock bajo</label>
    <button class="btn btn-dark" type="submit"><?= icon('search') ?> Filtrar</button>
  </form>
  <?php if (!$rows): ?>
    <div class="empty"><?= icon('layers') ?>No hay materiales.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Código</th><th>Material</th><th>Categoría</th><th class="text-right">Precio</th><th class="text-right">Stock suelto</th><th class="text-right">En cajas</th><th class="text-right">En consignación</th><th>Estado</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $m): $low = (int) $m['min_stock'] > 0 && (int) $m['stock_qty'] <= (int) $m['min_stock']; ?>
        <tr>
          <td class="mono"><?= e($m['code']) ?></td>
          <td><a class="row-link" href="<?= url('/admin/materiales/' . $m['id']) ?>"><?= e($m['name']) ?></a></td>
          <td><?= e($m['category'] ?: '—') ?></td>
          <td class="text-right nowrap"><?= money($m['price']) ?></td>
          <td class="text-right"><strong><?= (int) $m['stock_qty'] ?></strong> <?= $low ? '<span class="badge badge-danger">Bajo</span>' : '' ?><span class="cell-sub">mín. <?= (int) $m['min_stock'] ?></span></td>
          <td class="text-right"><?= (int) $m['in_boxes'] ?></td>
          <td class="text-right"><?= (int) $m['in_consignment'] ?: '<span class="muted">—</span>' ?></td>
          <td><?= (int) $m['active'] ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-muted">Inactivo</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
