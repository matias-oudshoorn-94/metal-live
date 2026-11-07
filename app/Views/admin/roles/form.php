<?php use App\Services\Permissions; ?>
<div class="page-head">
  <h2><?= e($title) ?></h2>
  <?php if ($role && !(int) App\Core\Database::value('SELECT COUNT(*) FROM users WHERE role_id = ?', [$role['id']])): ?>
  <div class="actions">
    <form method="post" action="<?= url('/admin/roles/' . $role['id'] . '/eliminar') ?>" data-confirm="¿Eliminar este rol?"><?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('trash') ?> Eliminar</button></form>
  </div>
  <?php endif; ?>
</div>
<form method="post" action="<?= url($role ? '/admin/roles/' . $role['id'] : '/admin/roles') ?>">
  <?= csrf_field() ?>
  <section class="card">
    <div class="card-body form-grid">
      <div class="field"><label for="name" class="req">Nombre</label><input id="name" name="name" value="<?= e(old('name', $role['name'] ?? '')) ?>" required maxlength="100"<?= invalid('name') ?>><?= field_error('name') ?></div>
      <div class="field span-2"><label for="description">Descripción</label><input id="description" name="description" value="<?= e(old('description', $role['description'] ?? '')) ?>" maxlength="255"></div>
    </div>
  </section>
  <section class="card">
    <div class="card-head"><h3><?= icon('shield') ?> Permisos</h3></div>
    <div class="card-body">
      <?php foreach (Permissions::GROUPS as $group => $perms): ?>
        <fieldset class="perm-group">
          <legend><?= e($group) ?></legend>
          <div class="perm-list">
            <?php foreach ($perms as $key => $label): ?>
              <label class="check"><input type="checkbox" name="permissions[]" value="<?= e($key) ?>"<?= checked(isset($granted[$key])) ?>><span><?= e($label) ?><small><?= e($key) ?></small></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      <?php endforeach; ?>
    </div>
    <div class="card-foot form-actions">
      <button class="btn btn-primary" type="submit">Guardar accesos</button>
      <a class="btn btn-ghost" href="<?= url('/admin/roles') ?>">Cancelar</a>
    </div>
  </section>
</form>
