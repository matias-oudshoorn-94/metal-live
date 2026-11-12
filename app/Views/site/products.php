<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <p class="eyebrow">Productos</p>
      <h1>Nuestros productos</h1>
      <p>Implantes de alta calidad para acompañar tu experiencia en cada cirugía. Comercializamos nuestros productos a todas las coberturas médicas del país.</p>
    </div>
    <?= App\Core\View::partial('partials/hero_art', ['class' => 'page-hero-art']) ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="product-grid product-grid-lg">
      <?php foreach (site_products() as [$icon, $name, $art]): ?>
        <article class="product-chip reveal">
          <?= implant($art, 'chip-implant chip-implant-' . $art) ?>
          <span class="feature-icon"><?= icon($icon) ?></span>
          <strong><?= e($name) ?></strong>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="cta-band reveal">
      <div>
        <h2>¿Cómo obtengo un presupuesto?</h2>
        <p>Enviá una fotografía de tu receta médica y tu número de contacto por WhatsApp o por correo electrónico, y un asesor comercial se comunicará a la brevedad.</p>
      </div>
      <div class="cta-actions">
        <a class="btn btn-light btn-lg" href="<?= e(site_whatsapp_url('¡Hola! Quiero solicitar un presupuesto')) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a>
        <a class="btn btn-outline-light btn-lg" href="mailto:<?= e(site_info('email')) ?>">Enviar por correo</a>
      </div>
    </div>
  </div>
</section>
