<?php
use App\Services\CustomFields;
$val = fn (string $k, mixed $d = '') => old($k, $field[$k] ?? $d);
?>
<div class="page-head"><h2><?= e($title) ?></h2></div>
<form class="card" method="post" action="<?= url($field ? '/admin/campos/' . $field['id'] : '/admin/campos') ?>">
  <?= csrf_field() ?>
  <div class="card-body form-grid">
    <?php if ($field): ?>
      <input type="hidden" name="entity" value="<?= e($field['entity']) ?>">
    <?php else: ?>
      <div class="field">
        <label for="entity">Sección</label>
        <select id="entity" name="entity"><?php foreach (CustomFields::ENTITIES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select>
      </div>
    <?php endif; ?>
    <div class="field"><label for="label" class="req">Etiqueta</label><input id="label" name="label" value="<?= e($val('label')) ?>" required maxlength="120"<?= invalid('label') ?>><?= field_error('label') ?></div>
    <div class="field">
      <label for="type" class="req">Tipo</label>
      <select id="type" name="type"><?php foreach (CustomFields::TYPES as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($val('type', 'text'), $k) ?>><?= e($l) ?></option><?php endforeach; ?></select>
    </div>
    <div class="field"><label for="sort_order">Orden</label><input id="sort_order" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>"></div>
    <div class="field span-all" data-show-when="type=select">
      <label for="options" class="req">Opciones (una por línea)</label>
      <textarea id="options" name="options" rows="4"<?= invalid('options') ?>><?= e($val('options')) ?></textarea>
      <?= field_error('options') ?>
    </div>
    <div class="field"><span class="label">Validación</span><label class="check"><input type="checkbox" name="required" value="1"<?= checked($val('required', 0)) ?>> Obligatorio</label></div>
    <div class="field"><span class="label">Estado</span><label class="check"><input type="checkbox" name="active" value="1"<?= checked($val('active', 1)) ?>> Activo</label></div>
  </div>
  <div class="card-foot form-actions">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-ghost" href="<?= url('/admin/campos') ?>">Cancelar</a>
  </div>
</form>
