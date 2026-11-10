<?php
use App\Models\Catalog;

$action = $row ? url('/admin/maestros/' . $def['slug'] . '/' . $row['id']) : url('/admin/maestros/' . $def['slug']);
$return = (string) ($_GET['return'] ?? old('return', ''));
?>
<div class="page-head"><h2><?= e($title) ?></h2></div>
<form class="card" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <?php if ($return !== '' && str_starts_with($return, '/admin/cirugias')): ?><input type="hidden" name="return" value="<?= e($return) ?>"><?php endif; ?>
  <div class="card-body form-grid">
    <?php foreach ($def['fields'] as $name => $f):
        $type = $f['type'] ?? 'text';
        $value = old($name, $row[$name] ?? ($f['default'] ?? ''));
        $req = str_contains($f['rules'], 'required'); ?>
      <div class="field<?= !empty($f['wide']) ? ' span-all' : '' ?>">
        <?php if ($type === 'checkbox'): ?>
          <span class="label"><?= e($f['label']) ?></span>
          <label class="check"><input type="checkbox" name="<?= e($name) ?>" value="1"<?= checked($value) ?>> Sí</label>
        <?php else: ?>
          <label for="f-<?= e($name) ?>"<?= $req ? ' class="req"' : '' ?>><?= e($f['label']) ?></label>
          <?php if ($type === 'select'): ?>
            <select id="f-<?= e($name) ?>" name="<?= e($name) ?>"<?= $req ? ' required' : '' ?><?= invalid($name) ?>>
              <option value="">Seleccionar…</option>
              <?php foreach (Catalog::sourceOptions($f, $value) as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($value, $k) ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
            <?php if (isset($f['manage']) && can(Catalog::permission(Catalog::get($f['manage'])))): ?>
              <a class="hint" href="<?= url('/admin/maestros/' . $f['manage']) ?>" target="_blank" rel="noopener">Administrar <?= e(mb_strtolower(Catalog::get($f['manage'])['title'])) ?> ↗</a>
            <?php endif; ?>
          <?php elseif ($type === 'textarea'): ?>
            <textarea id="f-<?= e($name) ?>" name="<?= e($name) ?>" rows="3"<?= invalid($name) ?>><?= e($value) ?></textarea>
          <?php else: ?>
            <input id="f-<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" value="<?= e($value) ?>"<?= $req ? ' required' : '' ?><?= invalid($name) ?>>
          <?php endif; ?>
        <?php endif; ?>
        <?= field_error($name) ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card-foot form-actions">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-ghost" href="<?= url($return !== '' && str_starts_with($return, '/admin/cirugias') ? $return : '/admin/maestros/' . $def['slug']) ?>">Cancelar</a>
  </div>
</form>
