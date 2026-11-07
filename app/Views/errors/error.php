<section class="error-page">
  <div class="container narrow">
    <p class="error-code"><?= (int) $status ?></p>
    <h1><?= $status === 404 ? 'Página no encontrada' : ($status === 403 ? 'Acceso restringido' : 'Algo salió mal') ?></h1>
    <p class="lead"><?= e($message) ?></p>
    <p><a class="btn btn-primary" href="<?= url(str_starts_with($_SERVER['REQUEST_URI'] ?? '', base_path_url() . '/admin') ? '/admin' : '/') ?>">Volver al inicio</a></p>
  </div>
</section>
