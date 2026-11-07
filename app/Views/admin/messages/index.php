<div class="page-head"><h2>Mensajes de contacto</h2></div>
<?php if (!$rows): ?>
  <section class="card"><div class="empty"><?= icon('mail') ?>No hay mensajes.</div></section>
<?php endif; ?>
<div class="grid grid-2">
<?php foreach ($rows as $m): ?>
  <section class="card">
    <div class="card-head">
      <h3><?= icon('mail') ?> <?= e($m['name']) ?></h3>
      <div class="actions">
        <?php if (!$m['read_at']): ?>
          <span class="badge badge-info">Nuevo</span>
          <form method="post" action="<?= url('/admin/mensajes/' . $m['id'] . '/leido') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Marcar leído</button></form>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-body">
      <p class="muted"><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? ' · ' . e($m['phone']) : '' ?><?= $m['company'] ? ' · ' . e($m['company']) : '' ?> · <?= e(fdatetime($m['created_at'])) ?></p>
      <p><?= nl2br(e($m['message'])) ?></p>
    </div>
  </section>
<?php endforeach; ?>
</div>
