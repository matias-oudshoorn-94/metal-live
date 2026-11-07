<div class="container narrow delivery-box">
  <div class="form-card">
    <p class="eyebrow">Confirmación de entrega</p>
    <h1><?= e(surgery_code($s['id'])) ?> · <?= e($s['institution_name']) ?></h1>
    <dl class="dl">
      <div><dt>Fecha y hora</dt><dd><?= e(fdatetime($s['scheduled_at'])) ?></dd></div>
      <div><dt>Dirección</dt><dd><?= e(trim(($s['institution_address'] ?? '') . ', ' . ($s['institution_city'] ?? ''), ', ') ?: '—') ?></dd></div>
      <div><dt>Caja</dt><dd><?= e(($s['box_code'] ?? '—') . ' ' . ($s['box_name'] ?? '')) ?></dd></div>
      <div><dt>Médico</dt><dd><?= e(person_name($s, 'doctor_')) ?></dd></div>
    </dl>
    <hr>
    <?php if ($s['status'] !== 'activa'): ?>
      <div class="alert alert-warning"><?= icon('alert') ?><span>La cirugía fue suspendida. No es necesario confirmar la entrega.</span></div>
    <?php elseif ($used): ?>
      <div class="alert alert-success"><?= icon('check') ?><span>La entrega de este material ya está confirmada<?= $s['delivered_at'] ? ' (' . e(fdatetime($s['delivered_at'])) . ')' : '' ?>.</span></div>
    <?php else: ?>
      <form method="post" action="<?= url('/entrega/' . $token) ?>">
        <?= csrf_field() ?>
        <p>Confirmá solo cuando el material esté entregado en la institución.</p>
        <button class="btn btn-success btn-lg btn-block" type="submit"><?= icon('check') ?> Confirmar material entregado</button>
      </form>
    <?php endif; ?>
  </div>
</div>
