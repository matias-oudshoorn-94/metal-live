<?php use App\Services\Permissions; ?>
<div class="page-head">
  <div>
    <h2>Roles y accesos</h2>
    <p class="subtitle">Los permisos de cada rol se pueden editar. Esta matriz muestra la configuración actual.</p>
  </div>
  <div class="actions"><a class="btn btn-primary" href="<?= url('/admin/roles/nuevo') ?>"><?= icon('plus') ?> Nuevo rol</a></div>
</div>

<div class="grid grid-3">
  <?php foreach ($roles as $r): ?>
    <section class="card">
      <div class="card-head">
        <h3><?= icon('shield') ?> <?= e($r['name']) ?></h3>
        <div class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('/admin/roles/' . $r['id'] . '/editar') ?>"><?= icon('edit') ?> Editar accesos</a></div>
      </div>
      <div class="card-body">
        <p class="muted"><?= e($r['description'] ?: 'Sin descripción.') ?></p>
        <span class="badge"><?= (int) $r['users'] ?> usuarios</span>
        <span class="badge badge-info"><?= (int) $r['perms'] ?> permisos</span>
      </div>
    </section>
  <?php endforeach; ?>
</div>

<section class="card">
  <div class="card-head"><h3><?= icon('list') ?> Matriz de permisos</h3></div>
  <div class="table-wrap">
    <table class="table table-compact matrix">
      <thead><tr><th>Permiso</th><?php foreach ($roles as $r): ?><th><?= e($r['name']) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach (Permissions::GROUPS as $group => $perms): ?>
        <tr class="group-row"><td colspan="<?= count($roles) + 1 ?>"><?= e($group) ?></td></tr>
        <?php foreach ($perms as $key => $label): ?>
          <tr>
            <td><?= e($label) ?></td>
            <?php foreach ($roles as $r): $has = isset($matrix[$r['id']][$key]); ?>
              <td class="<?= $has ? 'yes' : 'no' ?>"><?= icon($has ? 'check' : 'x') ?><span class="visually-hidden"><?= $has ? 'Sí' : 'No' ?></span></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
