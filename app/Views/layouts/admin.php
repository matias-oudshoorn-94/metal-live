<?php
$user = auth();
$path = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$path = substr($path, strlen(base_path_url())) ?: '/';
$unread = can('messages.view') ? (int) App\Core\Database::value('SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL') : 0;

$nav = [
    'Operación' => [
        ['Inicio', '/admin', 'home', ['dashboard.view'], true],
        ['Cirugías', '/admin/cirugias', 'activity', ['surgeries.view_all', 'surgeries.view_own']],
        ['Agenda', '/admin/agenda', 'calendar', ['surgeries.view_all']],
        [can('logistics.manage') ? 'Entregas' : 'Mis entregas', '/admin/entregas', 'truck', ['deliveries.view_own', 'logistics.manage']],
        ['Facturación', '/admin/facturacion', 'file', ['billing.view']],
        ['Cobranzas', '/admin/cobranzas', 'wallet', ['collection.view']],
    ],
    'Stock' => [
        ['Cajas', '/admin/cajas', 'box', ['boxes.states.view', 'stock.view', 'stock.manage']],
        ['Materiales', '/admin/materiales', 'layers', ['stock.view', 'stock.manage']],
        ['Categorías', '/admin/maestros/categorias-materiales', 'list', ['stock.manage']],
        ['Consignaciones', '/admin/consignaciones', 'briefcase', ['consignment.view', 'consignment.manage']],
    ],
    'Maestros' => [
        ['Pacientes', '/admin/maestros/pacientes', 'user', ['catalogs.manage']],
        ['Médicos', '/admin/maestros/medicos', 'stethoscope', ['catalogs.manage']],
        ['Instituciones', '/admin/maestros/instituciones', 'building', ['catalogs.manage']],
        ['Clientes', '/admin/maestros/clientes', 'briefcase', ['catalogs.manage']],
        ['Obras sociales', '/admin/maestros/obras-sociales', 'shield', ['catalogs.manage']],
        ['Instrumentadoras', '/admin/maestros/instrumentadoras', 'scissors', ['catalogs.manage']],
        ['Proveedores', '/admin/maestros/proveedores', 'truck', ['catalogs.manage']],
    ],
    'Configuración' => [
        ['Usuarios', '/admin/usuarios', 'users', ['users.manage']],
        ['Roles y accesos', '/admin/roles', 'shield', ['roles.manage']],
        ['Campos de cotización', '/admin/campos', 'sliders', ['custom_fields.manage']],
        ['Avisos', '/admin/avisos', 'bell', ['notifications.view']],
        ['Mensajes web', '/admin/mensajes', 'mail', ['messages.view']],
        ['Auditoría', '/admin/auditoria', 'list', ['audit.view']],
    ],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title ?? 'Panel') ?> · Metal Live</title>
  <link rel="icon" href="<?= asset('img/favicon.png') ?>">
  <link rel="preload" href="<?= url('assets/fonts/plus-jakarta-sans.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
  <script src="<?= asset('js/admin.js') ?>" type="module" nonce="<?= e(csp_nonce()) ?>"></script>
</head>
<body class="admin">
<?= App\Core\View::partial('partials/icons') ?>
<a class="skip-link" href="#main">Saltar al contenido</a>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= url('/admin') ?>">
      <img src="<?= asset('img/logo.webp') ?>" alt="Metal Live" width="150" height="58">
    </a>
    <nav class="sidebar-nav" aria-label="Principal">
      <?php foreach ($nav as $group => $links):
          $visible = array_filter($links, fn ($l) => can(...$l[3]));
          if (!$visible) continue; ?>
        <p class="nav-group"><?= e($group) ?></p>
        <ul>
          <?php foreach ($visible as $l):
              $active = !empty($l[4]) ? $path === $l[1] : str_starts_with($path, $l[1]); ?>
            <li>
              <a href="<?= url($l[1]) ?>" class="nav-link<?= $active ? ' is-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                <?= icon($l[2]) ?><span><?= e($l[0]) ?></span>
                <?php if ($l[1] === '/admin/mensajes' && $unread): ?><span class="nav-count"><?= $unread ?></span><?php endif; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= url('/') ?>" target="_blank" rel="noopener">Ver sitio público ↗</a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-close-sidebar></div>

  <div class="main-col">
    <header class="topbar">
      <button class="icon-btn menu-btn" type="button" data-toggle-sidebar aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menú"><?= icon('menu') ?></button>
      <h1 class="topbar-title"><?= e($title ?? '') ?></h1>
      <div class="topbar-user">
        <a href="<?= url('/admin/perfil') ?>" class="user-chip" title="Mi perfil">
          <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'] ?? '?', 0, 1))) ?></span>
          <span class="user-meta"><strong><?= e($user['name'] ?? '') ?></strong><small><?= e($user['role_name'] ?? '') ?></small></span>
        </a>
        <form method="post" action="<?= url('/admin/logout') ?>">
          <?= csrf_field() ?>
          <button class="icon-btn" type="submit" title="Cerrar sesión" aria-label="Cerrar sesión"><?= icon('logout') ?></button>
        </form>
      </div>
    </header>
    <main id="main" class="content">
      <?= App\Core\View::partial('partials/flash') ?>
      <?= $content ?>
    </main>
  </div>
</div>
</body>
</html>
