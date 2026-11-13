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
    <section class="card role-card">
      <div class="card-head">
        <h3><?= icon('shield') ?> <?= e($r['name']) ?></h3>
      </div>
      <div class="card-body">
        <p class="muted"><?= e($r['description'] ?: 'Sin descripción.') ?></p>
        <div class="role-stats">
          <span class="badge"><?= icon('users') ?> <?= (int) $r['users'] ?> <?= (int) $r['users'] === 1 ? 'usuario' : 'usuarios' ?></span>
          <span class="badge badge-info"><?= (int) $r['perms'] ?> permisos</span>
        </div>
      </div>
      <div class="card-foot">
        <a class="btn btn-outline btn-sm btn-block" href="<?= url('/admin/roles/' . $r['id'] . '/editar') ?>"><?= icon('edit') ?> Editar accesos</a>
      </div>
    </section>
  <?php endforeach; ?>
</div>

<section class="card">
  <div class="card-head">
    <h3><?= icon('list') ?> Matriz de permisos</h3>
    <p class="matrix-legend">
      <span><span class="matrix-yes"><?= icon('check') ?></span> Tiene el permiso</span>
      <span><span class="matrix-no"></span> No lo tiene</span>
    </p>
  </div>
  <div class="table-wrap">
    <table class="table matrix" data-matrix>
      <thead>
        <tr>
          <th scope="col">Permiso</th>
          <?php foreach ($roles as $r): ?>
            <th scope="col">
              <a href="<?= url('/admin/roles/' . $r['id'] . '/editar') ?>" title="Editar accesos de <?= e($r['name']) ?>">
                <span class="matrix-role"><?= e($r['name']) ?></span>
                <small><?= (int) $r['perms'] ?> permisos</small>
              </a>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <?php foreach (Permissions::GROUPS as $group => $perms): ?>
        <tbody>
          <tr class="group-row"><th scope="rowgroup" colspan="<?= count($roles) + 1 ?>"><span><?= e($group) ?></span></th></tr>
          <?php foreach ($perms as $key => $label): ?>
            <tr>
              <th scope="row"><?= e($label) ?></th>
              <?php foreach ($roles as $r): $has = isset($matrix[$r['id']][$key]); ?>
                <td><?php if ($has): ?><span class="matrix-yes"><?= icon('check') ?></span><span class="visually-hidden">Sí</span><?php else: ?><span class="matrix-no"></span><span class="visually-hidden">No</span><?php endif; ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      <?php endforeach; ?>
    </table>
  </div>
</section>
