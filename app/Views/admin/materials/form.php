<?php
$val = fn (string $k, mixed $d = '') => old($k, $m[$k] ?? $d);
$action = $m ? url('/admin/materiales/' . $m['id']) : url('/admin/materiales');
?>
<div class="page-head"><h2><?= e($title) ?></h2></div>
<form class="card" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="card-body form-grid">
    <div class="field"><label for="code" class="req">Código</label><input id="code" name="code" value="<?= e($val('code')) ?>" required maxlength="60"<?= invalid('code') ?>><?= field_error('code') ?></div>
    <div class="field span-2"><label for="name" class="req">Nombre</label><input id="name" name="name" value="<?= e($val('name')) ?>" required maxlength="190"<?= invalid('name') ?>><?= field_error('name') ?></div>
    <div class="field">
      <label for="category_id">Categoría</label>
      <select id="category_id" name="category_id"<?= invalid('category_id') ?>>
        <option value="">Sin categoría</option>
        <?php foreach ($categories as $id => $name): ?><option value="<?= (int) $id ?>"<?= selected($val('category_id'), $id) ?>><?= e($name) ?></option><?php endforeach; ?>
      </select>
      <?= field_error('category_id') ?>
      <a class="hint" href="<?= url('/admin/maestros/categorias-materiales') ?>" target="_blank" rel="noopener">Administrar categorías ↗</a>
    </div>
    <div class="field"><label for="unit" class="req">Unidad</label><input id="unit" name="unit" value="<?= e($val('unit', 'u')) ?>" required maxlength="20"></div>
    <div class="field"><label for="price">Precio de lista ($)</label><input id="price" type="number" step="0.01" min="0" name="price" value="<?= e($val('price', '0')) ?>"<?= invalid('price') ?>><?= field_error('price') ?></div>
    <?php if (!$m): ?>
      <div class="field"><label for="stock_qty">Stock inicial</label><input id="stock_qty" type="number" min="0" name="stock_qty" value="<?= e(old('stock_qty', '0')) ?>"></div>
    <?php endif; ?>
    <div class="field"><label for="min_stock">Stock mínimo</label><input id="min_stock" type="number" min="0" name="min_stock" value="<?= e($val('min_stock', '0')) ?>"></div>
    <div class="field"><span class="label">Estado</span><label class="check"><input type="checkbox" name="active" value="1"<?= checked($val('active', 1)) ?>> Activo</label></div>
    <div class="field span-all"><label for="notes">Notas</label><textarea id="notes" name="notes" rows="3" maxlength="2000"><?= e($val('notes')) ?></textarea></div>
  </div>
  <div class="card-foot form-actions">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-ghost" href="<?= url($m ? '/admin/materiales/' . $m['id'] : '/admin/materiales') ?>">Cancelar</a>
  </div>
</form>
