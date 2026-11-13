<?php
$s = $surgery;
$isNew = $s === null;
$val = fn (string $key, mixed $default = '') => old($key, $s[$key] ?? $default);
$custom = old('custom', $s ? App\Models\Surgery::customData($s) : []);
$oldItems = old('items', null);
$rows = is_array($oldItems) ? array_values($oldItems) : ($items ?: [[]]);
$action = $isNew ? url('/admin/cirugias') : url('/admin/cirugias/' . $s['id'] . '/cotizacion');
$return = $isNew ? '/admin/cirugias/nueva' : '/admin/cirugias/' . $s['id'] . '/cotizacion';
?>
<div class="page-head">
  <div>
    <h2><?= $isNew ? 'Nueva cotización' : 'Cotización ' . e(surgery_code($s['id'])) ?></h2>
    <p class="subtitle">Estadio 1 · Paciente, institución, médico, cliente, material, honorario y vendedor. Todos los campos son editables.</p>
  </div>
</div>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="form-compact">
  <?= csrf_field() ?>

  <section class="card">
    <div class="card-head"><h3><?= icon('user') ?> Datos de la cirugía</h3></div>
    <div class="card-body form-grid">
      <div class="field">
        <div class="field-head">
          <label for="patient_id" class="req">Paciente</label>
          <?php if (can('catalogs.manage')): ?><a class="field-link" href="<?= url('/admin/maestros/pacientes/nuevo', ['return' => $return]) ?>">+ Nuevo paciente</a><?php endif; ?>
        </div>
        <select id="patient_id" name="patient_id" required data-combobox data-placeholder="Buscar por apellido o DNI…"<?= invalid('patient_id') ?>>
          <option value="">Seleccionar…</option>
          <?php foreach ($patients as $p): ?>
            <option value="<?= (int) $p['id'] ?>"<?= selected($val('patient_id'), $p['id']) ?>><?= e(person_name($p)) ?><?= $p['document'] ? ' · DNI ' . e($p['document']) : '' ?></option>
          <?php endforeach; ?>
        </select>
        <?= field_error('patient_id') ?>
      </div>
      <div class="field">
        <div class="field-head">
          <label for="institution_id" class="req">Institución</label>
          <?php if (can('catalogs.manage')): ?><a class="field-link" href="<?= url('/admin/maestros/instituciones/nuevo', ['return' => $return]) ?>">+ Nueva</a><?php endif; ?>
        </div>
        <select id="institution_id" name="institution_id" required<?= invalid('institution_id') ?>>
          <option value="">Seleccionar…</option>
          <?php foreach ($institutions as $i): ?><option value="<?= (int) $i['id'] ?>"<?= selected($val('institution_id'), $i['id']) ?>><?= e($i['name']) ?></option><?php endforeach; ?>
        </select>
        <?= field_error('institution_id') ?>
      </div>
      <div class="field">
        <div class="field-head">
          <label for="doctor_id" class="req">Médico</label>
          <?php if (can('catalogs.manage')): ?><a class="field-link" href="<?= url('/admin/maestros/medicos/nuevo', ['return' => $return]) ?>">+ Nuevo</a><?php endif; ?>
        </div>
        <select id="doctor_id" name="doctor_id" required<?= invalid('doctor_id') ?>>
          <option value="">Seleccionar…</option>
          <?php foreach ($doctors as $d): ?><option value="<?= (int) $d['id'] ?>"<?= selected($val('doctor_id'), $d['id']) ?>><?= e(person_name($d)) ?></option><?php endforeach; ?>
        </select>
        <?= field_error('doctor_id') ?>
      </div>
      <div class="field">
        <label for="client_id" class="req" title="Obra social, prepaga o particular">Cliente</label>
        <select id="client_id" name="client_id" required<?= invalid('client_id') ?>>
          <option value="">Seleccionar…</option>
          <?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($val('client_id'), $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <?= field_error('client_id') ?>
      </div>
      <div class="field">
        <label for="procedure_name">Procedimiento</label>
        <input id="procedure_name" name="procedure_name" value="<?= e($val('procedure_name')) ?>" maxlength="255" placeholder="Ej.: Osteosíntesis de radio distal">
      </div>
      <?php foreach ($customFields as $f):
          $name = 'custom[' . $f['field_key'] . ']';
          $id = 'cf-' . $f['field_key'];
          $value = $custom[$f['field_key']] ?? ''; ?>
        <div class="field<?= $f['type'] === 'textarea' ? ' span-all' : '' ?>">
          <?php if ($f['type'] === 'checkbox'): ?>
            <span class="label"><?= e($f['label']) ?></span>
            <label class="check"><input type="checkbox" name="<?= e($name) ?>" value="1"<?= checked($value) ?>> Sí</label>
          <?php else: ?>
            <label for="<?= e($id) ?>"<?= (int) $f['required'] ? ' class="req"' : '' ?>><?= e($f['label']) ?></label>
            <?php if ($f['type'] === 'select'): ?>
              <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= invalid('custom.' . $f['field_key']) ?>>
                <option value="">Seleccionar…</option>
                <?php foreach (App\Services\CustomFields::options($f) as $opt): ?><option<?= selected($value, $opt) ?>><?= e($opt) ?></option><?php endforeach; ?>
              </select>
            <?php elseif ($f['type'] === 'textarea'): ?>
              <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="3"<?= invalid('custom.' . $f['field_key']) ?>><?= e($value) ?></textarea>
            <?php else: ?>
              <input id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= $f['type'] === 'number' ? 'number" step="any' : ($f['type'] === 'date' ? 'date' : 'text') ?>" value="<?= e($value) ?>"<?= invalid('custom.' . $f['field_key']) ?>>
            <?php endif; ?>
          <?php endif; ?>
          <?= field_error('custom.' . $f['field_key']) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="card">
    <div class="card-head">
      <h3><?= icon('layers') ?> Material cotizado</h3>
      <div class="actions"><button class="btn btn-sm btn-outline" type="button" data-add-row="items"><?= icon('plus') ?> Agregar ítem</button></div>
    </div>
    <?php foreach ((array) App\Core\Session::get('_errors', []) as $k => $msg): if (str_starts_with((string) $k, 'items.')): ?>
      <div class="alert alert-danger"><?= e($msg) ?></div>
    <?php endif; endforeach; ?>
    <div class="table-wrap">
      <table class="table rows-table">
        <thead><tr><th>Material del catálogo</th><th>Descripción</th><th class="col-qty">Cant.</th><th class="col-price">Precio unit.</th><th class="col-act"></th></tr></thead>
        <tbody data-rows="items">
          <?php foreach ($rows as $i => $item): ?>
            <?= App\Core\View::partial('admin/surgeries/_item_row', ['index' => $i, 'item' => $item, 'materials' => $materials]) ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <template data-row-template="items"><?= App\Core\View::partial('admin/surgeries/_item_row', ['index' => '__INDEX__', 'item' => [], 'materials' => $materials]) ?></template>
    <div class="card-body">
      <div class="total-line"><span>Total material cotizado</span><strong data-quote-total>$ 0,00</strong></div>
    </div>
  </section>

  <section class="card">
    <div class="card-head"><h3><?= icon('dollar') ?> Honorario, vendedor y receta</h3></div>
    <div class="card-body form-grid">
      <div class="field">
        <label for="fee_amount">Honorario ($)</label>
        <input id="fee_amount" name="fee_amount" type="number" min="0" step="0.01" value="<?= e($val('fee_amount', '0')) ?>"<?= invalid('fee_amount') ?>>
        <?= field_error('fee_amount') ?>
      </div>
      <?php if ($canPickSeller): ?>
      <div class="field">
        <label for="seller_id">Vendedor</label>
        <select id="seller_id" name="seller_id" data-seller>
          <option value="">Sin vendedor</option>
          <?php foreach ($sellers as $u): ?><option value="<?= (int) $u['id'] ?>" data-commission="<?= e($u['commission_pct']) ?>"<?= selected($val('seller_id'), $u['id']) ?>><?= e($u['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <div class="field"><span class="label">Vendedor</span><p><?= e(auth()['name']) ?></p></div>
      <?php endif; ?>
      <div class="field">
        <label for="commission_pct">Comisión (%)</label>
        <input id="commission_pct" name="commission_pct" type="number" min="0" step="0.01" value="<?= e($val('commission_pct', $isNew ? (auth()['commission_pct'] ?? '') : '')) ?>"<?= invalid('commission_pct') ?>>
        <?= field_error('commission_pct') ?>
      </div>
      <div class="field">
        <label for="prescription">Receta del médico (PDF o imagen)</label>
        <input id="prescription" name="prescription" type="file" accept="application/pdf,image/jpeg,image/png,image/webp">
        <p class="hint">Máximo <?= (int) env('UPLOAD_MAX_MB', 10) ?> MB.<?= $isNew ? '' : ' Si subís otra, se agrega a las existentes.' ?></p>
      </div>
      <div class="field span-all">
        <label for="quote_notes">Observaciones de la cotización</label>
        <textarea id="quote_notes" name="quote_notes" rows="3" maxlength="5000"><?= e($val('quote_notes')) ?></textarea>
      </div>
    </div>
  </section>

  <div class="form-actions form-actions-sticky">
    <a class="btn btn-ghost" href="<?= url($isNew ? '/admin/cirugias' : '/admin/cirugias/' . $s['id']) ?>">Cancelar</a>
    <span class="spacer"></span>
    <button class="btn btn-primary" type="submit"><?= icon('check') ?> Guardar cotización</button>
    <?php if ($isNew): ?>
      <button class="btn btn-dark" type="submit" name="send" value="1">Guardar y enviar a autorización</button>
    <?php endif; ?>
  </div>
</form>
