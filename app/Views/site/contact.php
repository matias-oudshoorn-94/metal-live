<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Contacto</p>
    <h1>Hablemos de tu próxima cirugía</h1>
    <p>Dejanos tus datos y la indicación médica, y un asesor comercial se comunicará con vos.</p>
  </div>
</section>

<section class="section">
  <div class="container contact-grid">
    <aside class="contact-card">
      <h2>Datos de contacto</h2>
      <p>Atención comercial de lunes a viernes de 8 a 18 h. Urgencias quirúrgicas las 24 horas.</p>
      <ul>
        <li><?= icon('phone') ?><span><?= e(env('SITE_PHONE', '')) ?></span></li>
        <li><?= icon('mail') ?><a href="mailto:<?= e(env('SITE_EMAIL', '')) ?>"><?= e(env('SITE_EMAIL', '')) ?></a></li>
        <li><?= icon('pin') ?><span><?= e(env('SITE_ADDRESS', '')) ?></span></li>
      </ul>
    </aside>

    <form class="form-card" method="post" action="<?= url('/contacto') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><label>No completar <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="form-grid">
        <div class="field">
          <label for="c-name" class="req">Nombre y apellido</label>
          <input id="c-name" name="name" value="<?= e(old('name')) ?>" required maxlength="120" autocomplete="name"<?= invalid('name') ?>>
          <?= field_error('name') ?>
        </div>
        <div class="field">
          <label for="c-email" class="req">Correo</label>
          <input id="c-email" type="email" name="email" value="<?= e(old('email')) ?>" required maxlength="190" autocomplete="email"<?= invalid('email') ?>>
          <?= field_error('email') ?>
        </div>
        <div class="field">
          <label for="c-phone">Teléfono</label>
          <input id="c-phone" type="tel" name="phone" value="<?= e(old('phone')) ?>" maxlength="40" autocomplete="tel">
        </div>
        <div class="field">
          <label for="c-company">Institución / consultorio</label>
          <input id="c-company" name="company" value="<?= e(old('company')) ?>" maxlength="160" autocomplete="organization">
        </div>
        <div class="field span-all">
          <label for="c-message" class="req">Mensaje</label>
          <textarea id="c-message" name="message" rows="6" required minlength="10" maxlength="3000"<?= invalid('message') ?>><?= e(old('message')) ?></textarea>
          <?= field_error('message') ?>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary btn-lg" type="submit">Enviar consulta</button>
        <small class="muted">Usamos tus datos solo para responder esta consulta.</small>
      </div>
    </form>
  </div>
</section>
