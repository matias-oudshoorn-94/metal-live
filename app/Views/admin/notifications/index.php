<?php $labels = ['pendiente' => 'info', 'reintentar' => 'warning', 'enviado' => 'success', 'fallido' => 'danger']; ?>
<div class="page-head">
  <div>
    <h2>Avisos</h2>
    <p class="subtitle">Motor único de avisos. Canales activos: <?= $channels ? e(implode(', ', $channels)) : 'ninguno' ?> · Modo de correo: <span class="mono"><?= e(env('MAIL_DRIVER', 'log')) ?></span></p>
  </div>
  <nav class="chips actions">
    <a class="chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= url('/admin/avisos') ?>">Todos</a>
    <?php foreach ($labels as $k => $_): ?><a class="chip<?= $status === $k ? ' is-active' : '' ?>" href="<?= url('/admin/avisos', ['status' => $k]) ?>"><?= e(ucfirst($k)) ?></a><?php endforeach; ?>
  </nav>
</div>
<section class="card">
  <?php if (!$rows): ?>
    <div class="empty"><?= icon('bell') ?>No hay avisos registrados.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Fecha</th><th>Evento</th><th>Canal</th><th>Destinatario</th><th>Asunto</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $n): ?>
        <tr>
          <td class="nowrap"><?= e(fdatetime($n['created_at'])) ?></td>
          <td class="mono"><?= e($n['event']) ?></td>
          <td><?= e($n['channel']) ?></td>
          <td><?= e($n['user_name'] ?? '') ?><span class="cell-sub"><?= e($n['recipient']) ?></span></td>
          <td><?= e($n['subject']) ?><?php if ($n['surgery_id']): ?> <a href="<?= url('/admin/cirugias/' . $n['surgery_id']) ?>"><?= e(surgery_code($n['surgery_id'])) ?></a><?php endif; ?><?php if ($n['last_error']): ?><span class="cell-sub field-error"><?= e($n['last_error']) ?></span><?php endif; ?></td>
          <td><span class="badge badge-<?= $labels[$n['status']] ?? 'muted' ?>"><?= e(ucfirst($n['status'])) ?></span><span class="cell-sub"><?= (int) $n['attempts'] ?> intento(s)</span></td>
          <td class="actions-cell">
            <?php if ($n['status'] !== 'enviado'): ?>
              <form method="post" action="<?= url('/admin/avisos/' . $n['id'] . '/reintentar') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Reintentar</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
