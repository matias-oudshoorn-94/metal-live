<?php
use App\Models\Catalog;
use App\Services\ConsignmentService;

$byMaterial = array_column($balance, 'quantity', 'material_id');
$canManage = can('consignment.manage');
$canBill = can('consignment.manage', 'billing.edit');
$pendingTotal = 0.0;
foreach ($pending as $p) {
    $pendingTotal += -(int) $p['quantity'] * (float) $p['unit_price'];
}
$typeShort = ['envio' => 'Envío', 'devolucion' => 'Devolución', 'consumo' => 'Consumo', 'ajuste' => 'Ajuste'];
$typeTone = ['envio' => 'badge-info', 'devolucion' => 'badge-muted', 'consumo' => 'badge-warning', 'ajuste' => 'badge-muted'];
?>
<div class="page-head">
  <div>
    <h2><?= e($client['name']) ?></h2>
    <p class="subtitle"><?= e(Catalog::CLIENT_TYPES[$client['type']] ?? $client['type']) ?><?= $client['cuit'] ? ' · CUIT ' . e($client['cuit']) : '' ?><?= $client['contact'] ? ' · ' . e($client['contact']) : '' ?></p>
  </div>
  <div class="actions"><a class="btn" href="<?= url('/admin/consignaciones') ?>">Volver</a></div>
</div>

<section class="kpis">
  <div class="kpi"><div class="kpi-label">Materiales en consignación</div><div class="kpi-value"><?= count($balance) ?></div></div>
  <div class="kpi"><div class="kpi-label">Unidades</div><div class="kpi-value"><?= array_sum(array_column($balance, 'quantity')) ?></div></div>
  <div class="kpi"><div class="kpi-label">Valor consignado</div><div class="kpi-value"><?= money(array_sum(array_map(static fn ($b) => $b['quantity'] * $b['price'], $balance))) ?></div></div>
  <div class="kpi"><div class="kpi-label">Consumos sin facturar</div><div class="kpi-value"><?= money($pendingTotal) ?></div></div>
</section>

<div class="layout-aside">
  <div class="stack">
    <section class="card">
      <div class="card-head"><h3><?= icon('layers') ?> Saldo en el cliente</h3></div>
      <div class="table-wrap">
        <table class="table table-compact">
          <thead><tr><th>Código</th><th>Material</th><th class="text-right">Cantidad</th><th class="text-right">Precio</th><th class="text-right">Valor</th></tr></thead>
          <tbody>
          <?php foreach ($balance as $b): ?>
            <tr>
              <td class="mono nowrap"><?= e($b['code']) ?></td>
              <td><a href="<?= url('/admin/materiales/' . $b['material_id']) ?>"><?= e($b['name']) ?></a></td>
              <td class="text-right"><strong><?= (int) $b['quantity'] ?></strong> <?= e($b['unit']) ?></td>
              <td class="text-right nowrap"><?= money($b['price']) ?></td>
              <td class="text-right nowrap"><?= money($b['quantity'] * $b['price']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$balance): ?><tr><td colspan="5" class="muted">Este cliente no tiene material en consignación.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <?php if ($pending): ?>
    <section class="card">
      <div class="card-head"><h3><?= icon('dollar') ?> Consumos pendientes de facturar</h3></div>
      <form method="post" action="<?= url('/admin/consignaciones/' . $client['id'] . '/facturar') ?>">
        <?= csrf_field() ?>
        <div class="table-wrap">
          <table class="table table-compact">
            <thead><tr><?php if ($canBill): ?><th class="col-act"></th><?php endif; ?><th>Fecha</th><th>Material</th><th>Referencia</th><th class="text-right">Cant.</th><th class="text-right">Importe</th></tr></thead>
            <tbody>
            <?php foreach ($pending as $p): ?>
              <tr>
                <?php if ($canBill): ?><td><input type="checkbox" name="movements[]" value="<?= (int) $p['id'] ?>" checked aria-label="Incluir en la factura"></td><?php endif; ?>
                <td class="nowrap"><?= e(fdate($p['created_at'])) ?></td>
                <td class="col-material"><?= e($p['material_name']) ?><span class="cell-sub mono"><?= e($p['code']) ?></span></td>
                <td><?= e($p['reference'] ?? '') ?></td>
                <td class="text-right"><?= -(int) $p['quantity'] ?></td>
                <td class="text-right nowrap"><?= money(-(int) $p['quantity'] * (float) $p['unit_price']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($canBill): ?>
        <div class="card-body">
          <div class="form-grid">
            <div class="field">
              <label for="invoice_number" class="req">Número de factura</label>
              <input id="invoice_number" name="invoice_number" maxlength="60" required placeholder="Ej.: A 0003-00001234" value="<?= e(old('invoice_number')) ?>"<?= invalid('invoice_number') ?>>
              <?= field_error('invoice_number') ?>
            </div>
          </div>
          <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Marcar seleccionados como facturados</button></div>
        </div>
        <?php endif; ?>
      </form>
    </section>
    <?php endif; ?>

    <section class="card">
      <div class="card-head">
        <h3><?= icon('list') ?> Movimientos</h3>
        <nav class="chips" aria-label="Filtrar movimientos">
          <a class="chip<?= $type === '' ? ' is-active' : '' ?>" href="<?= url('/admin/consignaciones/' . $client['id']) ?>">Todos</a>
          <?php foreach (ConsignmentService::TYPES as $key => $label): ?>
            <a class="chip<?= $type === $key ? ' is-active' : '' ?>" href="<?= url('/admin/consignaciones/' . $client['id'], ['tipo' => $key]) ?>"><?= e($typeShort[$key]) ?></a>
          <?php endforeach; ?>
        </nav>
      </div>
      <div class="table-wrap">
        <table class="table table-compact">
          <thead><tr><th>Fecha</th><th>Tipo</th><th>Material</th><th class="text-right">Cant.</th><th>Referencia</th><th>Factura</th></tr></thead>
          <tbody>
          <?php foreach ($movements as $mv): ?>
            <tr>
              <td class="nowrap"><?= e(fdatetime($mv['created_at'])) ?><span class="cell-sub"><?= e($mv['user_name'] ?? '—') ?></span></td>
              <td><span class="badge <?= $typeTone[$mv['type']] ?? 'badge-muted' ?>" title="<?= e(ConsignmentService::TYPES[$mv['type']] ?? '') ?>"><?= e($typeShort[$mv['type']] ?? $mv['type']) ?></span></td>
              <td class="col-material"><?= e($mv['material_name']) ?><span class="cell-sub mono"><?= e($mv['code']) ?></span></td>
              <td class="text-right"><strong><?= (int) $mv['quantity'] > 0 ? '+' : '' ?><?= (int) $mv['quantity'] ?></strong></td>
              <td><?= e($mv['reference'] ?? '') ?><?= $mv['notes'] ? '<span class="cell-sub">' . e($mv['notes']) . '</span>' : '' ?></td>
              <td class="nowrap"><?= $mv['type'] !== 'consumo' ? '<span class="muted">—</span>' : ($mv['billed_at'] ? e($mv['invoice_number']) : '<span class="badge badge-warning">Pendiente</span>') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$movements): ?><tr><td colspan="6" class="muted">Sin movimientos.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <aside>
    <?php if ($canManage): ?>
    <section class="card">
      <div class="card-head"><h3><?= icon('plus') ?> Registrar movimiento</h3></div>
      <form method="post" action="<?= url('/admin/consignaciones/' . $client['id'] . '/movimiento') ?>">
        <?= csrf_field() ?>
        <div class="card-body">
          <div class="form-grid">
            <div class="field span-all">
              <label for="cm-type" class="req">Tipo</label>
              <select id="cm-type" name="type">
                <?php foreach (ConsignmentService::TYPES as $key => $label): ?><option value="<?= $key ?>"<?= selected(old('type'), $key) ?>><?= e($label) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="field span-all" data-show-when="type=ajuste">
              <label for="cm-dir">Sentido del ajuste</label>
              <select id="cm-dir" name="direction"><option value="menos">Restar del saldo del cliente</option><option value="mas">Sumar al saldo del cliente</option></select>
            </div>
            <div class="field span-all">
              <label for="cm-ref">Remito / referencia</label>
              <input id="cm-ref" name="reference" maxlength="120" value="<?= e(old('reference')) ?>" placeholder="Ej.: Remito R 0001-00000456 o paciente">
            </div>
            <div class="field span-all">
              <label for="cm-notes">Observaciones</label>
              <input id="cm-notes" name="notes" maxlength="255" value="<?= e(old('notes')) ?>">
            </div>
          </div>
          <p class="hint">Envío: descuenta del depósito. Devolución: vuelve al depósito. Consumo: el cliente lo usó y queda para facturar (la referencia es obligatoria).</p>
        </div>
        <div class="table-wrap">
          <table class="table rows-table">
            <thead><tr><th>Material</th><th class="col-qty">Cant.</th><th class="col-act"></th></tr></thead>
            <tbody data-rows="consignment">
              <tr>
                <td><?php $index = 0; include __DIR__ . '/_material_select.php'; ?></td>
                <td class="col-qty"><input type="number" min="1" max="100000" name="items[0][quantity]" value="1" aria-label="Cantidad"></td>
                <td class="col-act"></td>
              </tr>
            </tbody>
          </table>
        </div>
        <template data-row-template="consignment">
          <tr>
            <td><?php $index = '__INDEX__'; include __DIR__ . '/_material_select.php'; ?></td>
            <td class="col-qty"><input type="number" min="1" max="100000" name="items[__INDEX__][quantity]" value="1" aria-label="Cantidad"></td>
            <td class="col-act"><button class="icon-btn" type="button" data-remove-row aria-label="Quitar"><?= icon('trash') ?></button></td>
          </tr>
        </template>
        <div class="card-body">
          <button class="btn btn-sm" type="button" data-add-row="consignment"><?= icon('plus') ?> Agregar material</button>
          <div class="form-actions"><button class="btn btn-primary" type="submit">Registrar</button></div>
        </div>
      </form>
    </section>
    <?php endif; ?>
  </aside>
</div>
