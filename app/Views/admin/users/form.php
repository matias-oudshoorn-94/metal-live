<?php $val = fn (string $k, mixed $d = '') => old($k, $user[$k] ?? $d); ?>
<div class="page-head">
  <h2><?= e($title) ?></h2>
  <?php if ($user && (int) $user['id'] !== (int) auth()['id']): ?>
  <div class="actions">
    <form method="post" action="<?= url('/admin/usuarios/' . $user['id'] . '/eliminar') ?>" data-confirm="¿Eliminar este usuario? Si tiene historial, se desactivará.">
      <?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('trash') ?> Eliminar</button>
    </form>
  </div>
  <?php endif; ?>
</div>
<form class="card" method="post" action="<?= url($user ? '/admin/usuarios/' . $user['id'] : '/admin/usuarios') ?>" autocomplete="off">
  <?= csrf_field() ?>
  <div class="card-body form-grid">
    <div class="field"><label for="name" class="req">Nombre</label><input id="name" name="name" value="<?= e($val('name')) ?>" required maxlength="120"<?= invalid('name') ?>><?= field_error('name') ?></div>
    <div class="field"><label for="email" class="req">Correo</label><input id="email" type="email" name="email" value="<?= e($val('email')) ?>" required maxlength="190"<?= invalid('email') ?>><?= field_error('email') ?></div>
    <div class="field"><label for="phone">Teléfono / WhatsApp</label><input id="phone" type="tel" name="phone" value="<?= e($val('phone')) ?>" maxlength="40" placeholder="+54 9 11 …"></div>
    <div class="field">
      <label for="role_id" class="req">Rol</label>
      <select id="role_id" name="role_id" required<?= invalid('role_id') ?>>
        <option value="">Seleccionar…</option>
        <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"<?= selected($val('role_id'), $r['id']) ?>><?= e($r['name']) ?></option><?php endforeach; ?>
      </select>
      <?= field_error('role_id') ?>
    </div>
    <div class="field"><label for="commission_pct">Comisión por defecto (%)</label><input id="commission_pct" type="number" step="0.01" min="0" name="commission_pct" value="<?= e($val('commission_pct', '0')) ?>"><p class="hint">Para vendedores.</p></div>
    <div class="field">
      <span class="label">Avisos</span>
      <label class="check"><input type="checkbox" name="notify_email" value="1"<?= checked($val('notify_email', 1)) ?>> Por correo</label>
      <label class="check"><input type="checkbox" name="notify_whatsapp" value="1"<?= checked($val('notify_whatsapp', 0)) ?>> Por WhatsApp</label>
    </div>
    <div class="field"><span class="label">Estado</span><label class="check"><input type="checkbox" name="active" value="1"<?= checked($val('active', 1)) ?>> Activo</label></div>
    <div class="field">
      <label for="password"<?= $user ? '' : ' class="req"' ?>><?= $user ? 'Nueva contraseña' : 'Contraseña' ?></label>
      <input id="password" type="password" name="password" autocomplete="new-password" minlength="10"<?= $user ? '' : ' required' ?><?= invalid('password') ?>>
      <?= field_error('password') ?>
      <p class="hint"><?= $user ? 'Dejar vacío para no cambiarla. ' : '' ?>Mínimo 10 caracteres, con letras y números.</p>
    </div>
    <div class="field"><label for="password_confirmation">Repetir contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
  </div>
  <div class="card-foot form-actions">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-ghost" href="<?= url('/admin/usuarios') ?>">Cancelar</a>
  </div>
</form>
