<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title ?? 'Ingresar') ?> · Metal Live</title>
  <link rel="icon" href="<?= asset('img/favicon.png') ?>">
  <link rel="preload" href="<?= url('assets/fonts/plus-jakarta-sans.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="auth-page">
<?= App\Core\View::partial('partials/icons') ?>
<?= $content ?>
</body>
</html>
