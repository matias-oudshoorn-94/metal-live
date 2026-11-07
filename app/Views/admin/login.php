<main class="auth-card">
  <img class="logo" src="<?= asset('img/logo-dark.webp') ?>" alt="Metal Live" width="190" height="74">
  <h1>Sistema de gestión</h1>
  <p class="muted">Ingresá con tu usuario para continuar.</p>
  <?= App\Core\View::partial('partials/flash') ?>
  <form method="post" action="<?= url('/admin/login') ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">Correo</label>
      <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="username" autofocus>
    </div>
    <div class="field">
      <label for="password">Contraseña</label>
      <input id="password" type="password" name="password" required autocomplete="current-password">
    </div>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Ingresar</button>
  </form>
  <p class="auth-foot"><a href="<?= url('/') ?>">← Volver al sitio</a></p>
</main>
