<?php
$areas = site_job_areas();
?>
<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <p class="eyebrow">Trabajá con nosotros</p>
      <h1>Sumate al equipo</h1>
      <p>Completá tus datos y contanos en qué área te gustaría trabajar.</p>
    </div>
    <?= App\Core\View::partial('partials/hero_art', ['class' => 'page-hero-art']) ?>
  </div>
</section>

<section class="section">
  <div class="container narrow">
    <form class="form-card" method="post" action="<?= url('/sumate-al-equipo') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><label>No completar <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="form-grid">
        <div class="field">
          <label for="j-first" class="req">Nombre</label>
          <input id="j-first" name="first_name" value="<?= e(old('first_name')) ?>" required maxlength="80" autocomplete="given-name"<?= invalid('first_name') ?>>
          <?= field_error('first_name') ?>
        </div>
        <div class="field">
          <label for="j-last" class="req">Apellido</label>
          <input id="j-last" name="last_name" value="<?= e(old('last_name')) ?>" required maxlength="80" autocomplete="family-name"<?= invalid('last_name') ?>>
          <?= field_error('last_name') ?>
        </div>
        <div class="field">
          <label for="j-email" class="req">Correo electrónico</label>
          <input id="j-email" type="email" name="email" value="<?= e(old('email')) ?>" required maxlength="190" autocomplete="email"<?= invalid('email') ?>>
          <?= field_error('email') ?>
        </div>
        <div class="field">
          <label for="j-phone">Teléfono</label>
          <input id="j-phone" type="tel" name="phone" value="<?= e(old('phone')) ?>" maxlength="40" autocomplete="tel">
        </div>
        <div class="field span-all">
          <label for="j-area" class="req">Aspirante a</label>
          <select id="j-area" name="area" required<?= invalid('area') ?>>
            <option value="">Seleccionar</option>
            <?php foreach ($areas as $k => $label): ?><option value="<?= e($k) ?>"<?= selected(old('area'), $k) ?>><?= e($label) ?></option><?php endforeach; ?>
          </select>
          <?= field_error('area') ?>
        </div>
        <div class="field span-all">
          <label for="j-intro" class="req">Presentación</label>
          <textarea id="j-intro" name="message" rows="5" required minlength="10" maxlength="3000"<?= invalid('message') ?>><?= e(old('message')) ?></textarea>
          <?= field_error('message') ?>
        </div>
        <div class="field span-all">
          <label for="j-cv">Curriculum Vitae (link de Google Drive, LinkedIn, etc.)</label>
          <input id="j-cv" type="url" name="cv_url" value="<?= e(old('cv_url')) ?>" maxlength="500" placeholder="https://"<?= invalid('cv_url') ?>>
          <?= field_error('cv_url') ?>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary btn-lg" type="submit">Enviar solicitud</button>
      </div>
    </form>
  </div>
</section>
