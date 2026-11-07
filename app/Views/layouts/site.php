<?php
$page ??= '';
$links = ['productos' => 'Productos', 'nosotros' => 'Nosotros', 'contacto' => 'Contacto'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Metal Live') ?> · Metal Live</title>
  <meta name="description" content="Metal Live: distribución de implantes, prótesis e insumos para cirugía traumatológica, con logística quirúrgica, instrumentación y trazabilidad.">
  <meta name="theme-color" content="#0b1b36">
  <link rel="icon" href="<?= asset('img/favicon.png') ?>">
  <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/site.css') ?>">
  <script src="<?= asset('js/site.js') ?>" type="module" nonce="<?= e(csp_nonce()) ?>"></script>
</head>
<body class="site">
<?= App\Core\View::partial('partials/icons') ?>
<a class="skip-link" href="#main">Saltar al contenido</a>
<header class="site-header" data-header>
  <div class="container header-inner">
    <a href="<?= url('/') ?>" class="brand" aria-label="Metal Live, inicio">
      <img src="<?= asset('img/logo.webp') ?>" alt="Metal Live" width="160" height="62">
    </a>
    <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="Menú" data-nav-toggle><?= icon('menu') ?></button>
    <nav id="site-nav" class="site-nav" aria-label="Principal">
      <?php foreach ($links as $slug => $label): ?>
        <a href="<?= url('/' . $slug) ?>"<?= $page === $slug ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <a href="<?= url('/admin') ?>" class="btn btn-light btn-sm">Acceso clientes</a>
    </nav>
  </div>
</header>

<main id="main">
  <?php if (App\Core\Session::get('success') || App\Core\Session::get('error')): ?>
    <div class="container flash-wrap"><?= App\Core\View::partial('partials/flash') ?></div>
  <?php endif; ?>
  <?= $content ?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <img src="<?= asset('img/logo.webp') ?>" alt="Metal Live" width="150" height="58" loading="lazy">
      <p>Implantes, prótesis e insumos para traumatología y ortopedia. Logística quirúrgica con trazabilidad completa.</p>
    </div>
    <div>
      <h2>Navegación</h2>
      <ul>
        <li><a href="<?= url('/productos') ?>">Productos</a></li>
        <li><a href="<?= url('/nosotros') ?>">Nosotros</a></li>
        <li><a href="<?= url('/contacto') ?>">Contacto</a></li>
        <li><a href="<?= url('/admin') ?>">Acceso al sistema</a></li>
      </ul>
    </div>
    <div>
      <h2>Contacto</h2>
      <ul class="contact-list">
        <li><?= icon('phone') ?><?= e(env('SITE_PHONE', '')) ?></li>
        <li><?= icon('mail') ?><a href="mailto:<?= e(env('SITE_EMAIL', '')) ?>"><?= e(env('SITE_EMAIL', '')) ?></a></li>
        <li><?= icon('pin') ?><?= e(env('SITE_ADDRESS', '')) ?></li>
      </ul>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <?= date('Y') ?> Metal Live. Todos los derechos reservados.</span>
    <span>Productos médicos con registro ANMAT.</span>
  </div>
</footer>
<?php if (env('SITE_WHATSAPP')): ?>
  <a class="wa-float" href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) env('SITE_WHATSAPP'))) ?>" target="_blank" rel="noopener" aria-label="Escribinos por WhatsApp"><?= icon('whatsapp') ?></a>
<?php endif; ?>
</body>
</html>
