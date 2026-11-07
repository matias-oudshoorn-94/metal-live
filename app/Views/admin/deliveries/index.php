<div class="page-head">
  <div>
    <h2><?= e($title) ?></h2>
    <p class="subtitle">Material en preparación, en reparto y entregas de los últimos 7 días.</p>
  </div>
</div>

<?php if (!$rows): ?>
  <section class="card"><div class="empty"><?= icon('truck') ?>No tenés entregas asignadas por ahora.</div></section>
<?php endif; ?>

<div class="grid grid-3">
<?php foreach ($rows as $s): ?>
  <section class="card delivery-card">
    <div class="card-head">
      <h3><?= icon('truck') ?> <?= e(surgery_code($s['id'])) ?></h3>
      <div class="actions">
        <?php if ($s['delivered_at']): ?><span class="badge badge-success">Entregado</span>
        <?php elseif ($s['dispatched_at']): ?><span class="badge badge-warning">Listo para reparto</span>
        <?php else: ?><span class="badge badge-info">En preparación</span><?php endif; ?>
      </div>
    </div>
    <div class="card-body">
      <div class="big"><?= e(fdatetime($s['scheduled_at'])) ?></div>
      <div><strong><?= e($s['institution_name']) ?></strong><span class="cell-sub"><?= e(trim(($s['institution_address'] ?? '') . ', ' . ($s['institution_city'] ?? ''), ', ')) ?></span></div>
      <div>Caja <span class="mono"><?= e($s['box_code'] ?? '—') ?></span> · <?= e($s['box_name'] ?? '') ?></div>
      <?php if (can('logistics.manage')): ?><div class="muted">Chofer: <?= e($s['driver_name'] ?? '—') ?></div><?php endif; ?>
      <?php if ($s['logistics_notes']): ?><div class="alert alert-info"><?= nl2br(e($s['logistics_notes'])) ?></div><?php endif; ?>
      <?php if ($s['dispatched_at'] && !$s['delivered_at'] && can('delivery.confirm', 'logistics.manage')): ?>
        <form method="post" action="<?= url('/admin/cirugias/' . $s['id'] . '/entrega') ?>" data-confirm="¿Confirmás que el material fue entregado?">
          <?= csrf_field() ?>
          <button class="btn btn-success btn-lg btn-block" type="submit"><?= icon('check') ?> Confirmar material entregado</button>
        </form>
      <?php elseif ($s['delivered_at']): ?>
        <p class="muted">Entregado el <?= e(fdatetime($s['delivered_at'])) ?></p>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>
</div>
