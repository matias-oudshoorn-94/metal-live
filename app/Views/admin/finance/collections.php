<?php use App\Models\Surgery; ?>
<div class="page-head">
  <div>
    <h2>Cobranzas</h2>
    <p class="subtitle">Pendiente de cobro: <strong><?= money($pending) ?></strong></p>
  </div>
  <nav class="chips actions" aria-label="Filtros">
    <a class="chip<?= $filter !== 'cobradas' ? ' is-active' : '' ?>" href="<?= url('/admin/cobranzas') ?>">Pendientes</a>
    <a class="chip<?= $filter === 'cobradas' ? ' is-active' : '' ?>" href="<?= url('/admin/cobranzas', ['filtro' => 'cobradas']) ?>">Cobradas</a>
  </nav>
</div>

<section class="card">
  <div class="filters"><div class="field field-search"><label for="tf">Buscar</label><input id="tf" type="search" data-table-filter="#collections" placeholder="Cliente, factura…"></div></div>
  <?php if (!$rows): ?>
    <div class="empty"><?= icon('wallet') ?>No hay cobranzas en esta vista.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table" id="collections">
      <thead><tr><th>Código</th><th>Cliente</th><th>Condición</th><th>Factura</th><th>Fecha factura</th><th class="text-right">Monto</th><?php if ($filter === 'cobradas'): ?><th>Pago</th><th>Tipo</th><th>Cheque</th><?php endif; ?><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="mono"><?= e(surgery_code($r['id'])) ?></td>
          <td><?= e($r['client_name']) ?></td>
          <td><?= e($r['payment_terms'] ?: '—') ?></td>
          <td><?= e($r['invoice_number'] ?: '—') ?></td>
          <td><?= e(fdate($r['invoice_date'])) ?></td>
          <td class="text-right nowrap"><?= money($r['invoice_amount']) ?></td>
          <?php if ($filter === 'cobradas'): ?>
            <td><?= e(fdate($r['payment_date'])) ?></td>
            <td><?= e(Surgery::PAYMENT_TYPES[$r['payment_type']] ?? '—') ?></td>
            <td><?= $r['cheque_number'] ? e($r['cheque_number']) . '<span class="cell-sub">' . e(fdate($r['cheque_date'])) . '</span>' : '—' ?></td>
          <?php endif; ?>
          <td class="actions-cell"><a class="btn btn-sm<?= $filter === 'cobradas' ? ' btn-ghost' : ' btn-primary' ?>" href="<?= url('/admin/cirugias/' . $r['id']) ?>"><?= $filter === 'cobradas' ? 'Ver' : 'Registrar cobro' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
