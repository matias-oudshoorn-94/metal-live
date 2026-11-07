<div class="page-head">
  <h2><?= e($def['title']) ?></h2>
  <div class="actions"><a class="btn btn-primary" href="<?= url('/admin/maestros/' . $def['slug'] . '/nuevo') ?>"><?= icon('plus') ?> Nuevo <?= e($def['singular']) ?></a></div>
</div>

<section class="card">
  <form class="filters" method="get">
    <div class="field field-search"><label for="q">Buscar</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar <?= e($def['title']) ?>…"></div>
    <button class="btn btn-dark" type="submit"><?= icon('search') ?> Buscar</button>
  </form>
  <?php if (!$rows): ?>
    <div class="empty"><?= icon($def['icon']) ?>No hay registros<?= $q !== '' ? ' para esa búsqueda' : '' ?>.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><?php foreach ($def['list'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <?php $first = true; foreach ($def['list'] as $col => $_):
              $field = $def['fields'][$col] ?? [];
              $v = $row[$col] ?? null;
              if (isset($lookups[$col])) { $v = $lookups[$col][$v] ?? null; }
              if (($field['type'] ?? '') === 'checkbox') { echo '<td>' . yes_no($v) . '</td>'; continue; } ?>
            <td><?php if ($first): ?><a class="row-link" href="<?= url('/admin/maestros/' . $def['slug'] . '/' . $row['id'] . '/editar') ?>"><?= e($v ?? '—') ?></a><?php else: ?><?= e($v ?? '—') ?><?php endif; ?></td>
          <?php $first = false; endforeach; ?>
          <td class="actions-cell">
            <a class="btn btn-ghost btn-sm" href="<?= url('/admin/maestros/' . $def['slug'] . '/' . $row['id'] . '/editar') ?>"><?= icon('edit') ?> Editar</a>
            <form method="post" action="<?= url('/admin/maestros/' . $def['slug'] . '/' . $row['id'] . '/eliminar') ?>" data-confirm="¿Eliminar este registro?">
              <?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Eliminar"><?= icon('trash') ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= App\Core\View::partial('partials/pagination', ['total' => $total, 'perPage' => $perPage]) ?>
  <?php endif; ?>
</section>
