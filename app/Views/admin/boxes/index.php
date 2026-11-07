<?php use App\Enums\BoxStatus; ?>
<div class="page-head">
  <h2>Cajas</h2>
  <div class="actions">
    <?php if (can('stock.manage')): ?><a class="btn btn-primary" href="<?= url('/admin/cajas/nueva') ?>"><?= icon('plus') ?> Nueva caja</a><?php endif; ?>
  </div>
  <p class="subtitle">Disponible → asignada → en preparación → en reparto → entregada → usada / para esterilizar.</p>
</div>

<nav class="chips" aria-label="Filtrar por estadio">
  <a class="chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= url('/admin/cajas') ?>">Todas<span class="count"><?= array_sum($counts) ?></span></a>
  <?php foreach (BoxStatus::cases() as $st): ?>
    <a class="chip<?= $status === $st->value ? ' is-active' : '' ?>" href="<?= url('/admin/cajas', ['status' => $st->value]) ?>"><?= e($st->label()) ?><span class="count"><?= (int) ($counts[$st->value] ?? 0) ?></span></a>
  <?php endforeach; ?>
</nav>
<br>

<section class="card">
  <?php if (!$rows): ?>
    <div class="empty"><?= icon('box') ?>No hay cajas en este estadio.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Código</th><th>Caja</th><th>Estadio</th><th>Cirugía actual</th><th class="text-right">Ítems</th><th class="text-right">Cirugías</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $b): ?>
        <tr>
          <td><a class="row-link mono" href="<?= url('/admin/cajas/' . $b['id']) ?>"><?= e($b['code']) ?></a></td>
          <td><?= e($b['name']) ?></td>
          <td><?= box_badge($b['status']) ?></td>
          <td><?= $b['current_surgery_id'] ? '<a href="' . url('/admin/cirugias/' . $b['current_surgery_id']) . '">' . e(surgery_code($b['current_surgery_id'])) . '</a><span class="cell-sub">' . e($b['institution_name'] ?? '') . ' · ' . e(fdatetime($b['scheduled_at'])) . '</span>' : '<span class="muted">—</span>' ?></td>
          <td class="text-right"><?= (int) $b['items'] ?><?= (int) $b['incomplete'] ? ' <span class="badge badge-warning">' . (int) $b['incomplete'] . ' incompletos</span>' : '' ?></td>
          <td class="text-right"><?= (int) $b['surgeries'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
