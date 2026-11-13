<?php
use App\Enums\BoxStatus;
use App\Enums\SurgeryStage;
use App\Models\Surgery;

/** @var array $s @var SurgeryStage $stage */
$id = (int) $s['id'];
$suspended = $s['status'] === 'suspendida';
$active = !$suspended;
$base = '/admin/cirugias/' . $id;

// Estado visual de cada tarjeta de estadio.
$state = function (SurgeryStage $step) use ($stage, $suspended): string {
    if ($stage === SurgeryStage::Finalizada || $step->isBefore($stage)) {
        return 'is-done';
    }
    return $step === $stage ? ($suspended ? '' : 'is-current') : 'is-locked';
};
$isCurrent = fn (SurgeryStage $step): bool => $active && $stage === $step;
$reached = fn (SurgeryStage $step): bool => !$stage->isBefore($step);

$files = function (string $kind) use ($attachments): string {
    if (empty($attachments[$kind])) {
        return '<p class="muted">Sin archivos.</p>';
    }
    $out = '<ul class="files">';
    foreach ($attachments[$kind] as $f) {
        $out .= '<li><a href="' . url('/admin/adjuntos/' . $f['id']) . '" target="_blank" rel="noopener">' . icon('paperclip') . e($f['original_name'])
            . '</a><small>' . e(format_bytes((int) $f['size'])) . ' · ' . e(fdatetime($f['created_at'])) . '</small></li>';
    }
    return $out . '</ul>';
};
$canEditQuote = can('quote.edit') && (can('surgeries.view_all') || $isOwner) && $stage->isBefore(SurgeryStage::Cobranza);
?>

<section class="card">
  <div class="hero-card">
    <div>
      <span class="code"><?= e(surgery_code($id)) ?></span>
      <h2>
        <?php if ($showSummary): ?>
          <?= e($s['procedure_name'] ?: 'Cirugía traumatológica') ?>
        <?php else: ?>
          <?= e($s['client_name']) ?>
        <?php endif; ?>
      </h2>
      <div class="button-row">
        <?= stage_badge($s['stage'], $s['status']) ?>
        <?php if ($s['reprogram_count']): ?><span class="badge badge-warning">Reprogramada ×<?= (int) $s['reprogram_count'] ?></span><?php endif; ?>
        <?php if ($active && $stage !== SurgeryStage::Finalizada): ?>
          <span class="pending-note"><?= icon('clock') ?> Pendiente: <?= e($stage->pending()) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="actions">
      <?php if ($canEditQuote): ?>
        <a class="btn" href="<?= url($base . '/cotizacion') ?>"><?= icon('edit') ?> Editar cotización</a>
      <?php endif; ?>
    </div>
  </div>
  <ol class="timeline<?= $suspended ? ' is-suspended' : '' ?>" aria-label="Recorrido de la cirugía">
    <?php foreach (SurgeryStage::flow() as $step):
        $cls = $state($step) === 'is-done' ? 'done' : ($step === $stage ? 'current' : ''); ?>
      <li class="<?= $cls ?>"<?= $step === $stage ? ' aria-current="step"' : '' ?>>
        <span class="dot"><?= $cls === 'done' ? icon('check') : $step->step() ?></span><?= e($step->label()) ?>
      </li>
    <?php endforeach; ?>
  </ol>
</section>

<?php if ($suspended): ?>
  <div class="alert alert-danger"><?= icon('pause') ?><span><strong>Cirugía suspendida</strong> el <?= e(fdatetime($s['suspended_at'])) ?>. Motivo: <?= e($s['suspension_reason']) ?>. Quedó registrada sin consumo y la caja volvió al stock.</span></div>
<?php endif; ?>

<div class="layout-aside">
<div>

<?php /* ============================== 1. COTIZACIÓN ============================== */ ?>
<?php if (can('quote.view')): ?>
<section class="card <?= $state(SurgeryStage::Cotizacion) ?>">
  <div class="card-head">
    <h3><span class="step-num">1</span> Cotización</h3>
    <div class="actions">
      <?php if ($isCurrent(SurgeryStage::Cotizacion) && $canEditQuote): ?>
        <form method="post" action="<?= url($base . '/enviar-autorizacion') ?>">
          <?= csrf_field() ?>
          <button class="btn btn-primary btn-sm" type="submit">Enviar a autorización <?= icon('arrow-right') ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <dl class="dl">
      <div><dt>Paciente</dt><dd><?= e(person_name($s, 'patient_')) ?><?= $s['patient_document'] ? ' · DNI ' . e($s['patient_document']) : '' ?></dd></div>
      <div><dt>Institución</dt><dd><?= e($s['institution_name']) ?></dd></div>
      <div><dt>Médico</dt><dd><?= e(person_name($s, 'doctor_')) ?></dd></div>
      <div><dt>Cliente</dt><dd><?= e($s['client_name']) ?></dd></div>
      <div><dt>Vendedor</dt><dd><?= e($s['seller_name'] ?? '—') ?></dd></div>
      <?php if (can('quote.amounts.view')): ?>
        <div><dt>Honorario</dt><dd><?= money($s['fee_amount']) ?></dd></div>
        <div><dt>Comisión</dt><dd><?= e(rtrim(rtrim(number_format((float) $s['commission_pct'], 2, ',', '.'), '0'), ',')) ?> %</dd></div>
      <?php endif; ?>
      <?php foreach ($customFields as $f): if (!array_key_exists($f['field_key'], $customData)) continue;
          $v = $customData[$f['field_key']]; ?>
        <div><dt><?= e($f['label']) ?></dt><dd><?= $f['type'] === 'checkbox' ? ($v ? 'Sí' : 'No') : e($v ?? '—') ?></dd></div>
      <?php endforeach; ?>
    </dl>

    <p class="section-title">Material cotizado</p>
    <div class="table-wrap">
      <table class="table table-compact">
        <thead><tr><th>Código</th><th>Descripción</th><th class="text-right">Cant.</th><?php if (can('quote.amounts.view')): ?><th class="text-right">Unitario</th><th class="text-right">Subtotal</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td class="mono"><?= e($it['material_code'] ?? '—') ?></td>
            <td><?= e($it['description']) ?></td>
            <td class="text-right"><?= (int) $it['quantity'] ?></td>
            <?php if (can('quote.amounts.view')): ?>
              <td class="text-right nowrap"><?= money($it['unit_price']) ?></td>
              <td class="text-right nowrap"><?= money($it['quantity'] * $it['unit_price']) ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="5" class="muted">Sin ítems cargados.</td></tr><?php endif; ?>
        </tbody>
        <?php if (can('quote.amounts.view') && $items): ?>
          <tfoot><tr><td colspan="4" class="text-right">Total material</td><td class="text-right nowrap"><?= money($quoteTotal) ?></td></tr></tfoot>
        <?php endif; ?>
      </table>
    </div>

    <?php if ($s['quote_notes']): ?><p class="section-title">Observaciones</p><p><?= nl2br(e($s['quote_notes'])) ?></p><?php endif; ?>
    <p class="section-title">Receta del médico</p>
    <?= $files('receta') ?>
  </div>
</section>

<?php /* ============================== 2. AUTORIZACIÓN ============================== */ ?>
<section class="card <?= $state(SurgeryStage::Autorizacion) ?>">
  <div class="card-head"><h3><span class="step-num">2</span> Autorización</h3></div>
  <div class="card-body">
    <?php if ($isCurrent(SurgeryStage::Autorizacion) && $canEditQuote): ?>
      <form method="post" action="<?= url($base . '/autorizacion') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field span-all">
            <span class="label">Tipo de autorización</span>
            <div class="button-row">
              <label class="check"><input type="radio" name="auth_type" value="oc" checked> Orden de compra (obra social / prepaga)</label>
              <label class="check"><input type="radio" name="auth_type" value="particular"> Paciente particular (sin OC)</label>
            </div>
          </div>
          <div class="field" data-show-when="auth_type=oc">
            <label for="oc_number">N.º de orden de compra</label>
            <input id="oc_number" name="oc_number" maxlength="80" placeholder="Opcional">
          </div>
          <div class="field" data-show-when="auth_type=oc">
            <label for="oc_file">Documento de la OC</label>
            <input id="oc_file" name="oc_file" type="file" accept="application/pdf,image/jpeg,image/png,image/webp">
          </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Registrar autorización</button></div>
      </form>
    <?php elseif ($s['authorized_at']): ?>
      <dl class="dl">
        <div><dt>Tipo</dt><dd><?= $s['auth_type'] === 'particular' ? 'Paciente particular' : 'Orden de compra' ?></dd></div>
        <div><dt>N.º de OC</dt><dd><?= e($s['oc_number'] ?: '—') ?></dd></div>
        <div><dt>Autorizada</dt><dd><?= e(fdatetime($s['authorized_at'])) ?></dd></div>
      </dl>
      <?php if (!empty($attachments['oc'])): ?><p class="section-title">Orden de compra</p><?= $files('oc') ?><?php endif; ?>
    <?php else: ?>
      <p class="muted">La orden de compra es opcional: en pacientes particulares no existe.</p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php /* ============================== 3-4. ASIGNACIÓN Y ENTREGA ============================== */ ?>
<?php if (can('logistics.manage', 'cx.view', 'cx.confirm') && $reached(SurgeryStage::Asignacion) || (can('deliveries.view_own') && (int) $s['driver_id'] === (int) auth()['id'])): ?>
<section class="card <?= $state(SurgeryStage::Asignacion) ?>">
  <div class="card-head"><h3><span class="step-num">3</span> Asignación de material y fecha</h3></div>
  <div class="card-body">
    <ul class="checklist">
      <li><?= icon($s['assigned_at'] ? 'check' : 'clock', 'icon ' . ($s['assigned_at'] ? 'ok' : 'no')) ?> Material, caja, instrumentadora y chofer asignados</li>
      <li><?= icon($s['cx_confirmed_at'] ? 'check' : 'clock', 'icon ' . ($s['cx_confirmed_at'] ? 'ok' : 'no')) ?> Fecha y horario confirmados por Cx<?= $s['cx_confirmed_at'] ? ' · ' . e(fdatetime($s['cx_confirmed_at'])) : '' ?></li>
      <li><?= icon($s['preparing_at'] ? 'check' : 'clock', 'icon ' . ($s['preparing_at'] ? 'ok' : 'no')) ?> Material en preparación<?= $s['preparing_at'] ? ' · ' . e(fdatetime($s['preparing_at'])) : '' ?></li>
      <li><?= icon($s['dispatched_at'] ? 'check' : 'clock', 'icon ' . ($s['dispatched_at'] ? 'ok' : 'no')) ?> Listo para reparto<?= $s['dispatched_at'] ? ' · ' . e(fdatetime($s['dispatched_at'])) : '' ?></li>
    </ul>

    <?php if ($s['assigned_at']): ?>
      <dl class="dl">
        <div><dt>Fecha y hora</dt><dd><?= e(fdatetime($s['scheduled_at'])) ?></dd></div>
        <div><dt>Caja</dt><dd><?= $s['box_code'] ? '<span class="mono">' . e($s['box_code']) . '</span> ' . (can('boxes.states.view') ? box_badge($s['box_status']) : '') : '—' ?></dd></div>
        <div><dt>Instrumentadora</dt><dd><?= e(person_name($s, 'instrumentist_')) ?></dd></div>
        <div><dt>Chofer</dt><dd><?= e($s['driver_name'] ?? '—') ?></dd></div>
        <?php if ($s['logistics_notes']): ?><div><dt>Indicaciones</dt><dd><?= nl2br(e($s['logistics_notes'])) ?></dd></div><?php endif; ?>
      </dl>
    <?php endif; ?>

    <?php if ($isCurrent(SurgeryStage::Asignacion) && can('logistics.manage') && !$s['preparing_at']): ?>
      <hr>
      <form method="post" action="<?= url($base . '/asignacion') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field">
            <label for="a-date" class="req">Fecha y hora</label>
            <input id="a-date" type="datetime-local" name="scheduled_at" value="<?= e(dt_local(old('scheduled_at', $s['scheduled_at']))) ?>" required<?= invalid('scheduled_at') ?>>
            <?= field_error('scheduled_at') ?>
          </div>
          <div class="field">
            <label for="a-box" class="req">Código de caja</label>
            <select id="a-box" name="box_id" required<?= invalid('box_id') ?>>
              <option value="">Cajas disponibles…</option>
              <?php foreach ($boxes as $b): ?><option value="<?= (int) $b['id'] ?>"<?= selected(old('box_id', $s['box_id']), $b['id']) ?>><?= e($b['code'] . ' · ' . $b['name']) ?></option><?php endforeach; ?>
            </select>
            <?= field_error('box_id') ?>
          </div>
          <div class="field">
            <label for="a-ins" class="req">Instrumentadora</label>
            <select id="a-ins" name="instrumentist_id" required<?= invalid('instrumentist_id') ?>>
              <option value="">Seleccionar…</option>
              <?php foreach ($instrumentists as $p): ?><option value="<?= (int) $p['id'] ?>"<?= selected(old('instrumentist_id', $s['instrumentist_id']), $p['id']) ?>><?= e(person_name($p)) ?></option><?php endforeach; ?>
            </select>
            <?= field_error('instrumentist_id') ?>
          </div>
          <div class="field">
            <label for="a-drv" class="req">Chofer</label>
            <select id="a-drv" name="driver_id" required<?= invalid('driver_id') ?>>
              <option value="">Seleccionar…</option>
              <?php foreach ($drivers as $u): ?><option value="<?= (int) $u['id'] ?>"<?= selected(old('driver_id', $s['driver_id']), $u['id']) ?>><?= e($u['name']) ?></option><?php endforeach; ?>
            </select>
            <?= field_error('driver_id') ?>
          </div>
          <div class="field span-all">
            <label for="a-notes">Indicaciones para logística</label>
            <textarea id="a-notes" name="logistics_notes" rows="2" maxlength="2000"><?= e(old('logistics_notes', $s['logistics_notes'])) ?></textarea>
          </div>
        </div>
        <div class="form-actions">
          <button class="btn btn-primary" type="submit"><?= $s['assigned_at'] ? 'Guardar cambios' : 'Asignar y avisar a Cx' ?></button>
          <span class="muted">Cambiar la fecha vuelve a pedir la confirmación de Cx.</span>
        </div>
      </form>
    <?php endif; ?>

    <?php if ($active && can('cx.confirm') && $s['assigned_at'] && in_array($s['stage'], ['asignacion', 'entrega'], true)): ?>
      <hr>
      <form class="inline-form" method="post" action="<?= url($base . '/confirmar-cx') ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="cx-date">Fecha y horario confirmados por Cx</label>
          <input id="cx-date" type="datetime-local" name="scheduled_at" value="<?= e(dt_local($s['scheduled_at'])) ?>" required>
        </div>
        <button class="btn btn-success" type="submit"><?= icon('check') ?> <?= $s['cx_confirmed_at'] ? 'Actualizar confirmación' : 'Confirmar fecha y horario' ?></button>
      </form>
    <?php endif; ?>

    <?php if ($isCurrent(SurgeryStage::Asignacion) && can('logistics.manage') && $s['assigned_at']): ?>
      <hr>
      <div class="button-row">
        <?php if (!$s['preparing_at']): ?>
          <form method="post" action="<?= url($base . '/preparacion') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-dark" type="submit"><?= icon('box') ?> Material en preparación · avisar al chofer</button>
          </form>
        <?php else: ?>
          <form method="post" action="<?= url($base . '/reparto') ?>" data-confirm="¿Confirmás que el material está listo para reparto? Se enviará al chofer el enlace de confirmación.">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit"<?= $s['cx_confirmed_at'] ? '' : ' disabled title="Falta la confirmación de Cx"' ?>><?= icon('truck') ?> Listo para reparto · avisar al chofer</button>
          </form>
          <?php if (!$s['cx_confirmed_at']): ?><span class="muted">Falta que Cx confirme fecha y horario.</span><?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="card <?= $state(SurgeryStage::Entrega) ?>">
  <div class="card-head"><h3><span class="step-num">4</span> Entrega</h3></div>
  <div class="card-body">
    <?php if ($s['delivered_at']): ?>
      <p><?= icon('check', 'icon ok') ?> Material entregado el <strong><?= e(fdatetime($s['delivered_at'])) ?></strong>.</p>
    <?php elseif ($isCurrent(SurgeryStage::Entrega)): ?>
      <p>El material está en reparto. El chofer puede confirmar desde el correo o desde acá.</p>
      <?php if (can('logistics.manage') || (can('delivery.confirm') && (int) $s['driver_id'] === (int) auth()['id'])): ?>
        <form method="post" action="<?= url($base . '/entrega') ?>" data-confirm="¿Confirmás que el material fue entregado?">
          <?= csrf_field() ?>
          <button class="btn btn-success" type="submit"><?= icon('check') ?> Confirmar material entregado</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <p class="muted">Pendiente de reparto.</p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php /* ============================== 5-6. CIRUGÍA Y CONSUMO ============================== */ ?>
<?php if (can('cx.view') && $reached(SurgeryStage::Cirugia)): ?>
<section class="card <?= $state(SurgeryStage::Cirugia) ?>">
  <div class="card-head"><h3><span class="step-num">5</span> Cirugía realizada</h3></div>
  <div class="card-body">
    <?php if ($isCurrent(SurgeryStage::Cirugia) && can('cx.post')): ?>
      <form method="post" action="<?= url($base . '/informe') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field">
            <label for="r-date" class="req">Fecha de realización</label>
            <input id="r-date" type="datetime-local" name="performed_at" value="<?= e(dt_local($s['performed_at'] ?? $s['scheduled_at'])) ?>" required>
          </div>
          <div class="field">
            <label for="r-pdf" class="req">PDF con la información de la cirugía</label>
            <input id="r-pdf" type="file" name="report_pdf" accept="application/pdf"<?= empty($attachments['informe']) ? ' required' : '' ?>>
          </div>
          <div class="field span-all">
            <label for="r-obs">Observaciones</label>
            <textarea id="r-obs" name="observations" rows="3" maxlength="5000"><?= e($s['observations']) ?></textarea>
          </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Cargar informe</button></div>
      </form>
    <?php else: ?>
      <dl class="dl">
        <div><dt>Realizada</dt><dd><?= e(fdatetime($s['performed_at'])) ?></dd></div>
        <div><dt>Observaciones</dt><dd><?= $s['observations'] ? nl2br(e($s['observations'])) : '—' ?></dd></div>
      </dl>
      <p class="section-title">Informe</p>
      <?= $files('informe') ?>
    <?php endif; ?>
  </div>
</section>

<?php if ($reached(SurgeryStage::Consumo)): ?>
<section class="card <?= $state(SurgeryStage::Consumo) ?>">
  <div class="card-head"><h3><span class="step-num">6</span> Consumo y faltantes</h3></div>
  <?php if ($isCurrent(SurgeryStage::Consumo) && can('cx.post')): ?>
    <form method="post" action="<?= url($base . '/consumo') ?>" data-confirm="¿Registrar el consumo? Se descontará del stock y la caja cambiará de estado.">
      <?= csrf_field() ?>
      <div class="card-body">
        <p class="muted">Indicá lo que se usó y lo que faltó al regresar la caja. Marcá «Reponer» para completar la caja desde el stock suelto.</p>
      </div>
      <div class="table-wrap">
        <table class="table rows-table">
          <thead><tr><th>Material</th><th class="col-src">Origen</th><th class="col-qty">Usado</th><th class="col-qty">Faltante</th><th>Reponer</th><th class="col-act"></th></tr></thead>
          <tbody data-rows="consumption">
            <?php foreach ($boxItems as $i => $bi): ?>
              <tr>
                <td>
                  <input type="hidden" name="items[<?= $i ?>][material_id]" value="<?= (int) $bi['material_id'] ?>">
                  <input type="hidden" name="items[<?= $i ?>][source]" value="caja">
                  <strong><?= e($bi['name']) ?></strong><span class="cell-sub mono"><?= e($bi['code']) ?> · en caja: <?= (int) $bi['current_qty'] ?>/<?= (int) $bi['expected_qty'] ?></span>
                </td>
                <td class="col-src"><span class="badge badge-info">Caja</span></td>
                <td class="col-qty"><input type="number" min="0" max="<?= (int) $bi['current_qty'] ?>" name="items[<?= $i ?>][used]" value="0" aria-label="Usado"></td>
                <td class="col-qty"><input type="number" min="0" name="items[<?= $i ?>][missing]" value="0" aria-label="Faltante"></td>
                <td><label class="check"><input type="checkbox" name="items[<?= $i ?>][replenish]" value="1" checked> Reponer</label></td>
                <td></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <template data-row-template="consumption">
        <tr>
          <td>
            <select name="items[__INDEX__][material_id]" data-combobox data-combobox-code data-placeholder="Buscar material por código o nombre" aria-label="Material">
              <option value="">Seleccionar material…</option>
              <?php foreach ($materials as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['code'] . ' · ' . $m['name']) ?> (stock <?= (int) $m['stock_qty'] ?>)</option><?php endforeach; ?>
            </select>
          </td>
          <td class="col-src"><select name="items[__INDEX__][source]" aria-label="Origen"><option value="stock">Stock suelto</option><option value="caja">Caja</option></select></td>
          <td class="col-qty"><input type="number" min="0" name="items[__INDEX__][used]" value="1" aria-label="Usado"></td>
          <td class="col-qty"><input type="number" min="0" name="items[__INDEX__][missing]" value="0" aria-label="Faltante"></td>
          <td><label class="check"><input type="checkbox" name="items[__INDEX__][replenish]" value="1"> Reponer</label></td>
          <td class="col-act"><button class="icon-btn" type="button" data-remove-row aria-label="Quitar"><?= icon('trash') ?></button></td>
        </tr>
      </template>
      <div class="card-body">
        <button class="btn btn-sm" type="button" data-add-row="consumption"><?= icon('plus') ?> Agregar material fuera de la caja</button>
        <div class="form-grid">
          <div class="field">
            <span class="label req">Estado de la caja al regresar</span>
            <label class="check"><input type="radio" name="box_final_status" value="<?= BoxStatus::ParaEsterilizar->value ?>" checked> Para esterilizar</label>
            <label class="check"><input type="radio" name="box_final_status" value="<?= BoxStatus::Usada->value ?>"> Usada (requiere revisión)</label>
          </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Registrar consumo</button></div>
      </div>
    </form>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-compact">
        <thead><tr><th>Material</th><th>Origen</th><th class="text-right">Usado</th><th class="text-right">Faltante</th><th>Repuesto</th></tr></thead>
        <tbody>
        <?php foreach ($consumption as $c): ?>
          <tr>
            <td><?= e($c['material_name']) ?><span class="cell-sub mono"><?= e($c['material_code']) ?></span></td>
            <td><?= $c['source'] === 'caja' ? 'Caja' : 'Stock suelto' ?></td>
            <td class="text-right"><?= (int) $c['used_qty'] ?></td>
            <td class="text-right"><?= (int) $c['missing_qty'] ?></td>
            <td><?= $c['source'] === 'caja' ? yes_no($c['replenished']) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$consumption): ?><tr><td colspan="5" class="muted"><?= $suspended ? 'Suspendida: sin consumo.' : 'Sin consumo registrado.' ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($s['box_final_status']): ?><div class="card-body">Caja al regresar: <?= box_badge($s['box_final_status']) ?></div><?php endif; ?>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php endif; ?>

<?php /* ============================== 7. FACTURACIÓN ============================== */ ?>
<?php if (can('billing.view', 'billing.amounts.view', 'fee.status.view') && ($reached(SurgeryStage::Facturacion) || can('fee.status.view'))): ?>
<section class="card <?= $state(SurgeryStage::Facturacion) ?>">
  <div class="card-head"><h3><span class="step-num">7</span> Facturación</h3></div>
  <div class="card-body">
    <?php if (can('billing.edit') && $active && $reached(SurgeryStage::Facturacion)): ?>
      <form method="post" action="<?= url($base . '/facturacion') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field">
            <label for="b-num">N.º de factura</label>
            <input id="b-num" name="invoice_number" value="<?= e(old('invoice_number', $s['invoice_number'])) ?>" maxlength="60"<?= invalid('invoice_number') ?>>
          </div>
          <div class="field">
            <label for="b-date">Fecha de factura</label>
            <input id="b-date" type="date" name="invoice_date" value="<?= e(old('invoice_date', $s['invoice_date'] ?? date('Y-m-d'))) ?>">
          </div>
          <div class="field">
            <label for="b-amount">Monto facturado ($)</label>
            <input id="b-amount" type="number" step="0.01" min="0" name="invoice_amount" value="<?= e(old('invoice_amount', $s['invoice_amount'] ?? number_format($quoteTotal + (float) $s['fee_amount'], 2, '.', ''))) ?>">
          </div>
          <div class="field">
            <label for="b-file">Factura (PDF)</label>
            <input id="b-file" type="file" name="invoice_file" accept="application/pdf,image/jpeg,image/png">
          </div>
          <div class="field span-all button-row">
            <label class="check"><input type="checkbox" name="fee_liquidated" value="1"<?= checked($s['fee_liquidated']) ?>> Honorario liquidado</label>
            <label class="check"><input type="checkbox" name="collected" value="1"<?= checked($s['collected']) ?>> Cobrado</label>
          </div>
        </div>
        <div class="form-actions">
          <button class="btn" type="submit">Guardar</button>
          <?php if ($isCurrent(SurgeryStage::Facturacion)): ?>
            <button class="btn btn-primary" type="submit" name="emit" value="1">Emitir factura y pasar a cobranza</button>
          <?php endif; ?>
        </div>
      </form>
      <?php if (!empty($attachments['factura'])): ?><p class="section-title">Factura</p><?= $files('factura') ?><?php endif; ?>
    <?php else: ?>
      <dl class="dl">
        <?php if (can('billing.view', 'billing.amounts.view')): ?>
          <div><dt>Factura</dt><dd><?= e($s['invoice_number'] ?: '—') ?></dd></div>
          <div><dt>Fecha</dt><dd><?= e(fdate($s['invoice_date'])) ?></dd></div>
          <div><dt>Monto facturado</dt><dd><?= money($s['invoice_amount']) ?></dd></div>
        <?php endif; ?>
        <?php if (can('billing.view', 'fee.status.view')): ?>
          <div><dt>Honorario liquidado</dt><dd><?= yes_no($s['fee_liquidated']) ?></dd></div>
        <?php endif; ?>
        <?php if (can('billing.view')): ?>
          <div><dt>Cobrado</dt><dd><?= yes_no($s['collected']) ?></dd></div>
        <?php endif; ?>
      </dl>
      <?php if (can('billing.view') && !empty($attachments['factura'])): ?><p class="section-title">Factura</p><?= $files('factura') ?><?php endif; ?>
    <?php endif; ?>

    <?php if (can('billing.view') && $reached(SurgeryStage::Facturacion)): ?>
      <p class="section-title">Pago a proveedores</p>
      <div class="table-wrap">
        <table class="table table-compact">
          <thead><tr><th>Proveedor</th><th>Factura</th><th>Fecha</th><th class="text-right">Monto</th><th>Pagada</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($supplierInvoices as $si): ?>
            <tr>
              <td><?= e($si['supplier_name']) ?></td>
              <td><?= e($si['invoice_number']) ?><?php if ($si['attachment_id']): ?> <a href="<?= url('/admin/adjuntos/' . $si['attachment_id']) ?>" target="_blank" rel="noopener" title="Ver archivo"><?= icon('paperclip') ?></a><?php endif; ?></td>
              <td><?= e(fdate($si['invoice_date'])) ?></td>
              <td class="text-right"><?= money($si['amount']) ?></td>
              <td><?= yes_no($si['paid']) ?><?= $si['paid_date'] ? '<span class="cell-sub">' . e(fdate($si['paid_date'])) . '</span>' : '' ?></td>
              <td class="actions-cell">
                <?php if (can('billing.edit')): ?>
                  <form method="post" action="<?= url($base . '/proveedores/' . $si['id'] . '/pagada') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit"><?= $si['paid'] ? 'Desmarcar' : 'Marcar pagada' ?></button></form>
                  <form method="post" action="<?= url($base . '/proveedores/' . $si['id'] . '/eliminar') ?>" data-confirm="¿Eliminar esta factura de proveedor?"><?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Eliminar"><?= icon('trash') ?></button></form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$supplierInvoices): ?><tr><td colspan="6" class="muted">Sin facturas de proveedores.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if (can('billing.edit')): ?>
        <form class="form-grid" method="post" action="<?= url($base . '/proveedores') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="field">
            <label for="si-sup" class="req">Proveedor</label>
            <select id="si-sup" name="supplier_id" required>
              <option value="">Seleccionar…</option>
              <?php foreach ($suppliers as $sp): ?><option value="<?= (int) $sp['id'] ?>"><?= e($sp['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label for="si-num" class="req">N.º de factura</label><input id="si-num" name="invoice_number" required maxlength="60"></div>
          <div class="field"><label for="si-date">Fecha</label><input id="si-date" type="date" name="invoice_date" value="<?= date('Y-m-d') ?>"></div>
          <div class="field"><label for="si-amount" class="req">Monto ($)</label><input id="si-amount" type="number" step="0.01" min="0" name="amount" required></div>
          <div class="field"><label for="si-file">Archivo</label><input id="si-file" type="file" name="supplier_file" accept="application/pdf,image/jpeg,image/png"></div>
          <div class="field"><span class="label">Estado</span><label class="check"><input type="checkbox" name="paid" value="1"> Ya está pagada</label></div>
          <div class="field span-all"><button class="btn" type="submit"><?= icon('plus') ?> Ingresar factura de proveedor</button></div>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php /* ============================== 8. COBRANZA ============================== */ ?>
<?php if (can('collection.view') && $reached(SurgeryStage::Cobranza)): ?>
<section class="card <?= $state(SurgeryStage::Cobranza) ?>">
  <div class="card-head"><h3><span class="step-num">8</span> Cobranza</h3></div>
  <div class="card-body">
    <dl class="dl">
      <div><dt>Cliente</dt><dd><?= e($s['client_name']) ?></dd></div>
      <div><dt>Factura</dt><dd><?= e($s['invoice_number'] ?: '—') ?> · <?= e(fdate($s['invoice_date'])) ?></dd></div>
      <div><dt>Monto facturado</dt><dd><?= money($s['invoice_amount']) ?></dd></div>
    </dl>
    <?php if ($isCurrent(SurgeryStage::Cobranza) && can('collection.edit')): ?>
      <hr>
      <form method="post" action="<?= url($base . '/cobranza') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field">
            <label for="p-date" class="req">Fecha de pago</label>
            <input id="p-date" type="date" name="payment_date" value="<?= e(old('payment_date', date('Y-m-d'))) ?>" required<?= invalid('payment_date') ?>>
            <?= field_error('payment_date') ?>
          </div>
          <div class="field">
            <label for="p-type" class="req">Tipo de pago</label>
            <select id="p-type" name="payment_type" required>
              <?php foreach (Surgery::PAYMENT_TYPES as $k => $label): ?><option value="<?= e($k) ?>"<?= selected(old('payment_type', 'transferencia'), $k) ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="p-amount" class="req">Monto cobrado ($)</label>
            <input id="p-amount" type="number" step="0.01" min="0" name="payment_amount" value="<?= e(old('payment_amount', $s['invoice_amount'])) ?>" required<?= invalid('payment_amount') ?>>
            <?= field_error('payment_amount') ?>
          </div>
        </div>
        <div class="form-grid" data-show-when="payment_type=cheque,echeq">
          <p class="section-title span-all">Datos del cheque</p>
          <div class="field"><label for="ch-bank" class="req">Banco</label><input id="ch-bank" name="cheque_bank" value="<?= e(old('cheque_bank')) ?>" maxlength="120"<?= invalid('cheque_bank') ?>><?= field_error('cheque_bank') ?></div>
          <div class="field"><label for="ch-num" class="req">N.º de cheque</label><input id="ch-num" name="cheque_number" value="<?= e(old('cheque_number')) ?>" maxlength="60"<?= invalid('cheque_number') ?>><?= field_error('cheque_number') ?></div>
          <div class="field"><label for="ch-date" class="req">Fecha de cobro</label><input id="ch-date" type="date" name="cheque_date" value="<?= e(old('cheque_date')) ?>"<?= invalid('cheque_date') ?>><?= field_error('cheque_date') ?></div>
          <div class="field"><label for="ch-holder">Librador</label><input id="ch-holder" name="cheque_holder" value="<?= e(old('cheque_holder')) ?>" maxlength="160"></div>
        </div>
        <div class="form-grid">
          <div class="field span-all"><label for="p-notes">Observaciones</label><textarea id="p-notes" name="collection_notes" rows="2" maxlength="2000"><?= e(old('collection_notes')) ?></textarea></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Registrar cobranza</button></div>
      </form>
    <?php elseif ($s['collected_at']): ?>
      <dl class="dl">
        <div><dt>Fecha de pago</dt><dd><?= e(fdate($s['payment_date'])) ?></dd></div>
        <div><dt>Tipo</dt><dd><?= e(Surgery::PAYMENT_TYPES[$s['payment_type']] ?? $s['payment_type']) ?></dd></div>
        <div><dt>Monto cobrado</dt><dd><?= money($s['payment_amount']) ?></dd></div>
        <?php if ($s['cheque_number']): ?>
          <div><dt>Cheque</dt><dd><?= e($s['cheque_bank']) ?> · N.º <?= e($s['cheque_number']) ?> · <?= e(fdate($s['cheque_date'])) ?><?= $s['cheque_holder'] ? ' · ' . e($s['cheque_holder']) : '' ?></dd></div>
        <?php endif; ?>
        <?php if ($s['collection_notes']): ?><div><dt>Observaciones</dt><dd><?= nl2br(e($s['collection_notes'])) ?></dd></div><?php endif; ?>
      </dl>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

</div>

<aside>
  <?php if ($showSummary): ?>
  <section class="card">
    <div class="card-head"><h3><?= icon('file') ?> Resumen</h3></div>
    <div class="card-body">
      <dl class="dl">
        <div><dt>Fecha</dt><dd><?= $s['scheduled_at'] ? e(fdatetime($s['scheduled_at'])) : 'A definir' ?></dd></div>
        <div><dt>Institución</dt><dd><?= e($s['institution_name']) ?></dd></div>
        <div><dt>Cliente</dt><dd><?= e($s['client_name']) ?></dd></div>
        <div><dt>Médico</dt><dd><?= e(person_name($s, 'doctor_')) ?></dd></div>
        <div><dt>Paciente</dt><dd><?= e(person_name($s, 'patient_')) ?></dd></div>
        <div><dt>Vendedor</dt><dd><?= e($s['seller_name'] ?? '—') ?></dd></div>
      </dl>
      <?php if (!can('quote.view')): ?>
        <p class="section-title">Material aprobado</p>
        <ul class="files">
          <?php foreach ($items as $it): ?><li><?= (int) $it['quantity'] ?> × <?= e($it['description']) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (can('surgery.suspend') && $stage->isBefore(SurgeryStage::Consumo)): ?>
  <section class="card">
    <div class="card-head"><h3><?= icon('pause') ?> Suspensión y reprogramación</h3></div>
    <div class="card-body">
      <?php if ($active): ?>
        <form method="post" action="<?= url($base . '/suspender') ?>" data-confirm="¿Suspender la cirugía? La caja vuelve al stock y se avisa a Cx, vendedor y chofer.">
          <?= csrf_field() ?>
          <div class="field">
            <label for="sus-reason" class="req">Motivo</label>
            <textarea id="sus-reason" name="suspension_reason" rows="2" required maxlength="1000"></textarea>
          </div>
          <div class="form-actions"><button class="btn btn-danger" type="submit">Suspender cirugía</button></div>
        </form>
        <p class="muted"><small>Para cambiar solo la fecha, editala en la asignación.</small></p>
      <?php else: ?>
        <form method="post" action="<?= url($base . '/reprogramar') ?>">
          <?= csrf_field() ?>
          <div class="field">
            <label for="rep-date" class="req">Nueva fecha y hora</label>
            <input id="rep-date" type="datetime-local" name="scheduled_at" required>
          </div>
          <p class="muted"><small>Sale ya cotizada y autorizada; vuelve a asignación de material.</small></p>
          <div class="form-actions"><button class="btn btn-primary" type="submit">Reprogramar</button></div>
        </form>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($events): ?>
  <section class="card">
    <div class="card-head"><h3><?= icon('list') ?> Historial</h3></div>
    <div class="card-body">
      <ul class="event-list">
        <?php foreach ($events as $ev): ?>
          <li>
            <strong><?= e($ev['event']) ?></strong>
            <?php if ($ev['detail']): ?><span><?= e($ev['detail']) ?></span><?php endif; ?>
            <div class="meta"><?= e(fdatetime($ev['created_at'])) ?><?= $ev['user_name'] ? ' · ' . e($ev['user_name']) : '' ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
  <?php endif; ?>
</aside>
</div>
