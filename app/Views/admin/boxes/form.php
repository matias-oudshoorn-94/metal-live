<?php $val = fn (string $k) => old($k, $box[$k] ?? ''); ?>
<div class="page-head"><h2><?= e($title) ?></h2></div>
<form class="card" method="post" action="<?= url($box ? '/admin/cajas/' . $box['id'] : '/admin/cajas') ?>">
  <?= csrf_field() ?>
  <div class="card-body form-grid">
    <div class="field"><label for="code" class="req">Código</label><input id="code" name="code" value="<?= e($val('code')) ?>" required maxlength="60" pattern="[A-Za-z0-9\-_.]+" placeholder="CJ-OST-003"<?= invalid('code') ?>><?= field_error('code') ?><p class="hint">Letras, números, guion, punto o guion bajo.</p></div>
    <div class="field span-2"><label for="name" class="req">Nombre</label><input id="name" name="name" value="<?= e($val('name')) ?>" required maxlength="160"<?= invalid('name') ?>><?= field_error('name') ?></div>
    <div class="field span-all"><label for="description">Descripción</label><textarea id="description" name="description" rows="3" maxlength="2000"><?= e($val('description')) ?></textarea></div>
  </div>
  <div class="card-foot form-actions">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-ghost" href="<?= url($box ? '/admin/cajas/' . $box['id'] : '/admin/cajas') ?>">Cancelar</a>
  </div>
</form>
