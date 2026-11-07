<?php use App\Services\CustomFields; ?>
<div class="page-head">
  <div>
    <h2>Campos de la cotización</h2>
    <p class="subtitle">Ampliá la cotización con campos nuevos sin tocar el código. Se muestran en el formulario y en la ficha de cada cirugía.</p>
  </div>
  <div class="actions"><a class="btn btn-primary" href="<?= url('/admin/campos/nuevo') ?>"><?= icon('plus') ?> Nuevo campo</a></div>
</div>
<section class="card">
  <?php if (!$rows): ?>
    <div class="empty"><?= icon('sliders') ?>Todavía no hay campos adicionales.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Orden</th><th>Etiqueta</th><th>Clave</th><th>Tipo</th><th>Obligatorio</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $f): ?>
        <tr>
          <td><?= (int) $f['sort_order'] ?></td>
          <td><strong><?= e($f['label']) ?></strong><?= $f['type'] === 'select' ? '<span class="cell-sub">' . e(implode(' · ', CustomFields::options($f))) . '</span>' : '' ?></td>
          <td class="mono"><?= e($f['field_key']) ?></td>
          <td><?= e(CustomFields::TYPES[$f['type']] ?? $f['type']) ?></td>
          <td><?= yes_no($f['required']) ?></td>
          <td><?= (int) $f['active'] ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-muted">Inactivo</span>' ?></td>
          <td class="actions-cell">
            <a class="btn btn-ghost btn-sm" href="<?= url('/admin/campos/' . $f['id'] . '/editar') ?>"><?= icon('edit') ?> Editar</a>
            <?php if ((int) $f['active']): ?>
              <form method="post" action="<?= url('/admin/campos/' . $f['id'] . '/eliminar') ?>" data-confirm="¿Desactivar este campo? Los datos ya cargados se conservan."><?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Desactivar"><?= icon('trash') ?></button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
