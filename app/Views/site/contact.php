<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <p class="eyebrow">Contacto</p>
      <h1>¡Comunicate con nosotros!</h1>
      <p>Dejanos tu consulta y te contactaremos cuanto antes.</p>
    </div>
    <?= App\Core\View::partial('partials/hero_art', ['class' => 'page-hero-art']) ?>
  </div>
</section>

<section class="section">
  <div class="container contact-grid">
    <aside class="contact-card">
      <h2>Ubicación</h2>
      <p><a href="<?= e(site_map_url()) ?>" target="_blank" rel="noopener"><?= e(site_info('address')) ?></a></p>
      <ul>
        <li><?= icon('phone') ?><span>Atención comercial<br><a href="tel:<?= e(tel_href(site_info('phone'))) ?>"><?= e(site_info('phone')) ?></a></span></li>
        <li><?= icon('clock') ?><span>Urgencias<br><a href="tel:<?= e(tel_href(site_info('urgent_phone'))) ?>"><?= e(site_info('urgent_phone')) ?></a></span></li>
        <li><?= icon('whatsapp') ?><span>WhatsApp<br><a href="<?= e(site_whatsapp_url('¡Hola! Buenos días. Tengo una consulta')) ?>" target="_blank" rel="noopener"><?= e(site_info('whatsapp_label')) ?></a></span></li>
        <li><?= icon('mail') ?><span>Correo<br><a href="mailto:<?= e(site_info('email')) ?>"><?= e(site_info('email')) ?></a></span></li>
      </ul>
      <h2 class="contact-card-sub">Redes</h2>
      <p>¡Sumate a nuestra comunidad y viví la evolución de la traumatología desde cerca!</p>
      <ul class="social-links">
        <?php foreach (site_social() as [$icon, $label, $href]): ?>
          <li><a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>" title="<?= e($label) ?>"><?= icon($icon) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <form class="form-card" method="post" action="<?= url('/contacto') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><label>No completar <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="form-grid">
        <div class="field">
          <label for="c-name" class="req">Nombre</label>
          <input id="c-name" name="name" value="<?= e(old('name')) ?>" required maxlength="120" autocomplete="name"<?= invalid('name') ?>>
          <?= field_error('name') ?>
        </div>
        <div class="field">
          <label for="c-email" class="req">Correo electrónico</label>
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
        <div class="field span-all">
          <label class="check"><input type="checkbox" name="privacy" value="1" required<?= checked(old('privacy')) ?><?= invalid('privacy') ?>> Acepto que mis datos serán utilizados con el objetivo de brindarme servicios, productos y asistencia, conforme a la política de privacidad.</label>
          <?= field_error('privacy') ?>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary btn-lg" type="submit">Enviar consulta</button>
      </div>
    </form>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Ubicación</p>
      <h2>Dónde estamos</h2>
      <p><?= e(site_info('address')) ?> · <a href="<?= e(site_map_url()) ?>" target="_blank" rel="noopener">Cómo llegar ↗</a></p>
    </div>
    <div class="map-embed reveal">
      <iframe src="<?= e(site_map_embed_url()) ?>" title="Mapa: <?= e(site_info('address')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </div>
  </div>
</section>
