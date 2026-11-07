<div class="page-head"><h2>Mi perfil</h2></div>
<form class="card" method="post" action="<?= url('/admin/perfil') ?>" autocomplete="off">
  <?= csrf_field() ?>
  <div class="card-body form-grid">
    <div class="field"><label for="name" class="req">Nombre</label><input id="name" name="name" value="<?= e(old('name', $user['name'])) ?>" required maxlength="120"></div>
    <div class="field"><span class="label">Correo</span><p><?= e($user['email']) ?></p></div>
    <div class="field"><span class="label">Rol</span><p><?= e($user['role_name']) ?></p></div>
    <div class="field"><label for="phone">Teléfono / WhatsApp</label><input id="phone" type="tel" name="phone" value="<?= e(old('phone', $user['phone'])) ?>" maxlength="40"></div>
    <div class="field">
      <span class="label">Recibir avisos</span>
      <label class="check"><input type="checkbox" name="notify_email" value="1"<?= checked($user['notify_email']) ?>> Por correo</label>
      <label class="check"><input type="checkbox" name="notify_whatsapp" value="1"<?= checked($user['notify_whatsapp']) ?>> Por WhatsApp</label>
    </div>
  </div>
  <div class="card-body">
    <p class="section-title">Cambiar contraseña</p>
    <div class="form-grid">
      <div class="field"><label for="current_password">Contraseña actual</label><input id="current_password" type="password" name="current_password" autocomplete="current-password"<?= invalid('current_password') ?>><?= field_error('current_password') ?></div>
      <div class="field"><label for="password">Nueva contraseña</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="10"<?= invalid('password') ?>><?= field_error('password') ?></div>
      <div class="field"><label for="password_confirmation">Repetir nueva contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
    </div>
  </div>
  <div class="card-foot form-actions"><button class="btn btn-primary" type="submit">Guardar</button></div>
</form>
