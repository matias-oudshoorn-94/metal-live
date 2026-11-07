<div class="page-head">
  <h2>Usuarios</h2>
  <div class="actions"><a class="btn btn-primary" href="<?= url('/admin/usuarios/nuevo') ?>"><?= icon('plus') ?> Nuevo usuario</a></div>
</div>
<nav class="chips" aria-label="Filtrar por rol">
  <a class="chip<?= !$role ? ' is-active' : '' ?>" href="<?= url('/admin/usuarios') ?>">Todos</a>
  <?php foreach ($roles as $r): ?><a class="chip<?= $role === (int) $r['id'] ? ' is-active' : '' ?>" href="<?= url('/admin/usuarios', ['role' => $r['id']]) ?>"><?= e($r['name']) ?></a><?php endforeach; ?>
</nav>
<br>
<section class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Avisos</th><th>Último ingreso</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $u): ?>
        <tr>
          <td><a class="row-link" href="<?= url('/admin/usuarios/' . $u['id'] . '/editar') ?>"><?= e($u['name']) ?></a><?= (float) $u['commission_pct'] > 0 ? '<span class="cell-sub">Comisión ' . e($u['commission_pct']) . ' %</span>' : '' ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($u['role_name']) ?></td>
          <td><?= (int) $u['notify_email'] ? '<span class="badge">Correo</span> ' : '' ?><?= (int) $u['notify_whatsapp'] ? '<span class="badge badge-success">WhatsApp</span>' : '' ?></td>
          <td><?= e(fdatetime($u['last_login_at'])) ?></td>
          <td><?= (int) $u['active'] ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-muted">Inactivo</span>' ?></td>
          <td class="actions-cell"><a class="btn btn-ghost btn-sm" href="<?= url('/admin/usuarios/' . $u['id'] . '/editar') ?>"><?= icon('edit') ?> Editar</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
