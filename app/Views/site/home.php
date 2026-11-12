<section class="hero has-implants">
  <div class="implant-layer" aria-hidden="true">
    <?= implant('screw', 'imp-hero-screw') ?>
    <?= implant('plate', 'imp-hero-plate') ?>
    <?= implant('nail', 'imp-hero-nail imp-wide') ?>
  </div>
  <div class="container hero-grid">
    <div>
      <p class="eyebrow">Implantes traumatológicos</p>
      <h1>Innovación en Implantes, <em>Compromiso en Salud.</em></h1>
      <p class="lead">En Metal Live ®, empresa argentina líder en la producción y distribución de implantes traumatológicos, nos impulsa un compromiso inquebrantable: ofrecer soluciones integrales que combinen seguridad, eficacia y vanguardia para profesionales y pacientes.</p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="<?= url('/contacto') ?>">Contactanos <?= icon('arrow-right') ?></a>
        <a class="btn btn-outline-light btn-lg" href="<?= url('/productos') ?>">Nuestros productos</a>
      </div>
      <div class="hero-badges">
        <div><strong>+20 años</strong><span>De experiencia en traumatología</span></div>
        <div><strong>Todo el país</strong><span>Logística propia</span></div>
        <div><strong>Todas</strong><span>Las coberturas médicas</span></div>
      </div>
    </div>
    <?= App\Core\View::partial('partials/hero_art') ?>
  </div>
</section>

<section class="section has-implants">
  <div class="implant-layer" aria-hidden="true">
    <?= implant('hip', 'imp-team-hip') ?>
    <?= implant('pedicle', 'imp-team-pedicle imp-wide') ?>
  </div>
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Nuestro equipo</p>
      <h2>Guiados por la excelencia</h2>
      <p>Nos adaptamos a los desafíos de la medicina actual, respaldados por un equipo multidisciplinario especializado en áreas clave.</p>
    </div>
    <div class="cards cards-3">
      <?php foreach (site_areas() as [$icon, $name, $text]): ?>
        <article class="feature reveal">
          <div class="feature-icon"><?= icon($icon) ?></div>
          <h3><?= e($name) ?></h3>
          <p><?= e($text) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt has-implants">
  <div class="implant-layer" aria-hidden="true">
    <?= implant('knee', 'imp-trust-knee') ?>
    <?= implant('screw', 'imp-trust-screw imp-wide') ?>
    <?= implant('plate', 'imp-trust-plate imp-wide') ?>
  </div>
  <div class="container">
    <div class="section-head center reveal">
      <p class="eyebrow">Por qué elegirnos</p>
      <h2>La confianza que necesitás. La solución que esperás.</h2>
      <p>Implantes de alta calidad para acompañar tu experiencia en cada cirugía.</p>
    </div>
    <div class="cards cards-3">
      <?php foreach (site_values() as [$icon, $name, $text]): ?>
        <article class="feature reveal">
          <div class="feature-icon"><?= icon($icon) ?></div>
          <h3><?= e($name) ?></h3>
          <p><?= e($text) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Productos</p>
      <h2>Nuestros productos</h2>
      <p>Comercializamos nuestros productos a todas las coberturas médicas del país.</p>
    </div>
    <div class="product-grid">
      <?php foreach (site_products() as [$icon, $name, $art]): ?>
        <a class="product-chip reveal" href="<?= url('/productos') ?>">
          <?= implant($art, 'chip-implant chip-implant-' . $art) ?>
          <span class="feature-icon"><?= icon($icon) ?></span>
          <strong><?= e($name) ?></strong>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="cta-band has-implants reveal">
      <div class="implant-layer" aria-hidden="true">
        <?= implant('nail', 'imp-cta-nail') ?>
        <?= implant('anchor', 'imp-cta-anchor imp-wide') ?>
      </div>
      <div>
        <h2>¿Cómo obtengo un presupuesto?</h2>
        <p>Enviá una fotografía de tu receta médica y tu número de contacto por WhatsApp o por correo electrónico, y un asesor comercial se comunicará a la brevedad.</p>
      </div>
      <div class="cta-actions">
        <a class="btn btn-light btn-lg" href="<?= e(site_whatsapp_url('¡Hola! Quiero solicitar un presupuesto')) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a>
        <a class="btn btn-outline-light btn-lg" href="<?= url('/contacto') ?>">Contactanos</a>
      </div>
    </div>
  </div>
</section>
