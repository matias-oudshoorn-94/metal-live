<?php
use App\Services\Permissions;

$groupIcons = [
    'General' => 'home',
    'Cirugías: alcance' => 'activity',
    'Cotización y autorización' => 'file',
    'Cirugías y logística' => 'calendar',
    'Choferes' => 'truck',
    'Facturación' => 'dollar',
    'Cobranzas' => 'wallet',
    'Stock' => 'box',
    'Administración' => 'sliders',
];
$oldPerms = old('permissions', null);
$isOn = fn (string $key): bool => is_array($oldPerms) ? in_array($key, $oldPerms, true) : isset($granted[$key]);
$total = count(Permissions::all());
$users = $role ? (int) App\Core\Database::value('SELECT COUNT(*) FROM users WHERE role_id = ?', [$role['id']]) : 0;
?>
<div class="page-head">
  <div>
    <h2><?= e($title) ?></h2>
    <p class="subtitle">
      <?= $role ? ($users === 1 ? 'Este rol lo tiene 1 usuario' : 'Este rol lo tienen ' . $users . ' usuarios') . '. ' : '' ?>Activá lo que el rol puede ver y hacer en el panel.
    </p>
  </div>
  <?php if ($role && !$users): ?>
  <div class="actions">
    <form method="post" action="<?= url('/admin/roles/' . $role['id'] . '/eliminar') ?>" data-confirm="¿Eliminar este rol?"><?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('trash') ?> Eliminar rol</button></form>
  </div>
  <?php endif; ?>
</div>

<form method="post" action="<?= url($role ? '/admin/roles/' . $role['id'] : '/admin/roles') ?>" class="form-compact" data-perm-form>
  <?= csrf_field() ?>
  <section class="card">
    <div class="card-head"><h3><?= icon('shield') ?> Datos del rol</h3></div>
    <div class="card-body form-grid role-data">
      <div class="field"><label for="name" class="req">Nombre</label><input id="name" name="name" value="<?= e(old('name', $role['name'] ?? '')) ?>" required maxlength="100"<?= invalid('name') ?>><?= field_error('name') ?></div>
      <div class="field"><label for="description">Descripción</label><input id="description" name="description" value="<?= e(old('description', $role['description'] ?? '')) ?>" maxlength="255" placeholder="Qué hace este rol, en una línea"></div>
    </div>
  </section>

  <div class="perm-head">
    <h3>Permisos</h3>
    <p class="muted">Cada interruptor habilita una acción o una sección del panel.</p>
  </div>

  <div class="perm-groups">
    <?php foreach (Permissions::GROUPS as $group => $perms):
        $on = count(array_filter(array_keys($perms), $isOn)); ?>
      <fieldset class="perm-panel" data-perm-group>
        <legend class="visually-hidden"><?= e($group) ?></legend>
        <div class="perm-panel-head">
          <span class="perm-panel-icon"><?= icon($groupIcons[$group] ?? 'shield') ?></span>
          <strong><?= e($group) ?></strong>
          <span class="perm-count" data-perm-count><?= $on ?> de <?= count($perms) ?></span>
          <button class="btn btn-ghost btn-sm" type="button" data-perm-toggle><?= $on === count($perms) ? 'Quitar todos' : 'Marcar todos' ?></button>
        </div>
        <ul class="perm-rows">
          <?php foreach ($perms as $key => $label): ?>
            <li>
              <label class="perm-row" title="<?= e($key) ?>">
                <span><?= e($label) ?></span>
                <input class="switch" type="checkbox" name="permissions[]" value="<?= e($key) ?>"<?= checked($isOn($key)) ?>>
              </label>
            </li>
          <?php endforeach; ?>
        </ul>
      </fieldset>
    <?php endforeach; ?>
  </div>

  <div class="form-actions form-actions-sticky">
    <a class="btn btn-ghost" href="<?= url('/admin/roles') ?>">Cancelar</a>
    <span class="spacer"></span>
    <span class="perm-total"><strong data-perm-total><?= count(array_filter(array_keys(Permissions::all()), $isOn)) ?></strong> de <?= $total ?> permisos activos</span>
    <button class="btn btn-primary" type="submit"><?= icon('check') ?> Guardar accesos</button>
  </div>
</form>
