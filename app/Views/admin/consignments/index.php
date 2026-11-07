<?php use App\Models\Catalog; ?>
<div class="page-head">
  <h2>Stock en consignación</h2>
  <p class="subtitle">Material de la empresa en poder de clientes: envíos, devoluciones y consumos pendientes de facturar.</p>
</div>

<section class="kpis">
  <div class="kpi"><div class="kpi-label">Clientes con consignación</div><div class="kpi-value"><?= (int) $totals['clients'] ?></div></div>
  <div class="kpi"><div class="kpi-label">Unidades en clientes</div><div class="kpi-value"><?= (int) $totals['units'] ?></div></div>
  <div class="kpi"><div class="kpi-label">Valor consignado</div><div class="kpi-value"><?= money($totals['value']) ?></div></div>
  <div class="kpi"><div class="kpi-label">Consumos sin facturar</div><div class="kpi-value"><?= money($totals['pending_amount']) ?></div></div>
</section>

<section class="card">
  <div class="filters filters-split">
    <form class="filters-inline" method="get">
      <div class="field field-search"><label for="q">Buscar cliente</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre del cliente"></div>
      <button class="btn btn-dark" type="submit"><?= icon('search') ?> Filtrar</button>
    </form>
    <?php if (can('consignment.manage')): ?>
    <form class="filters-inline" method="get" action="<?= url('/admin/consignaciones/abrir') ?>">
      <div class="field">
        <label for="client_id">Consignar a otro cliente</label>
        <select id="client_id" name="client_id" required>
          <option value="">Elegir cliente…</option>
          <?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary" type="submit"><?= icon('arrow-right') ?> Abrir</button>
    </form>
    <?php endif; ?>
  </div>

  <?php if (!$rows): ?>
    <div class="empty"><?= icon('briefcase') ?>No hay material en consignación<?= $q !== '' ? ' para esa búsqueda' : '' ?>.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Cliente</th><th>Tipo</th><th class="text-right">Materiales</th><th class="text-right">Unidades</th><th class="text-right">Valor</th><th class="text-right">Sin facturar</th><th>Último movimiento</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a class="row-link" href="<?= url('/admin/consignaciones/' . $r['id']) ?>"><?= e($r['name']) ?></a></td>
          <td><?= e(Catalog::CLIENT_TYPES[$r['type']] ?? $r['type']) ?></td>
          <td class="text-right"><?= (int) $r['items'] ?></td>
          <td class="text-right"><strong><?= (int) $r['units'] ?></strong></td>
          <td class="text-right nowrap"><?= money($r['value']) ?></td>
          <td class="text-right nowrap"><?= (int) $r['pending'] ? '<span class="badge badge-warning">' . (int) $r['pending'] . '</span> ' . money($r['pending_amount']) : '<span class="muted">—</span>' ?></td>
          <td class="nowrap"><?= e($r['last_at'] ? fdatetime($r['last_at']) : '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
