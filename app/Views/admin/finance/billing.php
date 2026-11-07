<?php
$tabs = ['pendientes' => 'Por facturar', 'emitidas' => 'Emitidas', 'honorarios' => 'Honorarios sin liquidar', 'proveedores' => 'Proveedores impagos'];
?>
<div class="page-head">
  <h2>Facturación</h2>
  <nav class="chips actions" aria-label="Filtros">
    <?php foreach ($tabs as $k => $label): ?>
      <a class="chip<?= $filter === $k ? ' is-active' : '' ?>" href="<?= url('/admin/facturacion', ['filtro' => $k]) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<section class="card">
  <?php if (!$rows): ?>
    <div class="empty"><?= icon('file') ?>No hay cirugías en esta vista.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Código</th><th>Cliente</th><th>Paciente / institución</th><th>Vendedor</th><th class="text-right">Cotizado</th><th class="text-right">Honorario</th><th>Factura</th><th>Hon. liquidado</th><th class="text-right">Prov. impago</th><th>Estadio</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $s): ?>
        <tr>
          <td><a class="row-link mono" href="<?= url('/admin/cirugias/' . $s['id']) ?>"><?= e(surgery_code($s['id'])) ?></a></td>
          <td><?= e($s['client_name']) ?></td>
          <td><?= e(person_name($s, 'patient_')) ?><span class="cell-sub"><?= e($s['institution_name']) ?></span></td>
          <td><?= e($s['seller_name'] ?? '—') ?></td>
          <td class="text-right nowrap"><?= money($s['quote_total']) ?></td>
          <td class="text-right nowrap"><?= money($s['fee_amount']) ?></td>
          <td class="nowrap"><?= $s['invoice_number'] ? e($s['invoice_number']) . '<span class="cell-sub">' . money($s['invoice_amount']) . '</span>' : '<span class="muted">—</span>' ?></td>
          <td><?= yes_no($s['fee_liquidated']) ?></td>
          <td class="text-right nowrap"><?= (float) $s['supplier_due'] > 0 ? money($s['supplier_due']) : '<span class="muted">—</span>' ?></td>
          <td><?= stage_badge($s['stage'], $s['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
