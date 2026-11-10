<?php
$page ??= '';
$links = ['productos' => 'Productos', 'nosotros' => 'Nosotros', 'preguntas-frecuentes' => 'Preguntas frecuentes', 'sumate-al-equipo' => 'Sumate al equipo', 'contacto' => 'Contacto'];
$social = site_social();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Metal Live') ?> · Metal Live</title>
  <meta name="description" content="Metal Live ®, empresa argentina líder en la producción y distribución de implantes traumatológicos. Innovación en implantes, compromiso en salud.">
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
      <p>Innovación en Implantes, Compromiso en Salud.</p>
      <p>Seguinos en nuestras redes para descubrir las últimas novedades en implantes traumatológicos.</p>
      <ul class="social-links" aria-label="Redes sociales">
        <?php foreach ($social as [$icon, $label, $href]): ?>
          <li><a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>" title="<?= e($label) ?>"><?= icon($icon) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h2>Navegación</h2>
      <ul>
        <?php foreach ($links as $slug => $label): ?><li><a href="<?= url('/' . $slug) ?>"><?= e($label) ?></a></li><?php endforeach; ?>
        <li><a href="<?= url('/admin') ?>">Acceso al sistema</a></li>
      </ul>
    </div>
    <div>
      <h2>Contacto</h2>
      <ul class="contact-list">
        <li><?= icon('phone') ?><span>Atención comercial: <a href="tel:<?= e(tel_href(site_info('phone'))) ?>"><?= e(site_info('phone')) ?></a></span></li>
        <li><?= icon('clock') ?><span>Urgencias: <a href="tel:<?= e(tel_href(site_info('urgent_phone'))) ?>"><?= e(site_info('urgent_phone')) ?></a></span></li>
        <li><?= icon('mail') ?><a href="mailto:<?= e(site_info('email')) ?>"><?= e(site_info('email')) ?></a></li>
        <li><?= icon('pin') ?><a href="<?= e(site_map_url()) ?>" target="_blank" rel="noopener"><?= e(site_info('address')) ?></a></li>
      </ul>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© Powered by Metal Live® SRL <?= date('Y') ?></span>
  </div>
</footer>
<?php if (site_info('whatsapp')): ?>
  <a class="wa-float" href="<?= e(site_whatsapp_url('¡Hola! Buenos días. Tengo una consulta')) ?>" target="_blank" rel="noopener" aria-label="Escribinos por WhatsApp"><?= icon('whatsapp') ?></a>
<?php endif; ?>
</body>
</html>
