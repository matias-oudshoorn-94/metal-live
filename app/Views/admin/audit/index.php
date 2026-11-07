<div class="page-head">
  <h2>Auditoría</h2>
  <div class="actions"><input type="search" data-table-filter="#audit" placeholder="Filtrar…" aria-label="Filtrar"></div>
</div>
<section class="card">
  <div class="table-wrap">
    <table class="table table-compact" id="audit">
      <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>Detalle</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $a): ?>
        <tr>
          <td class="nowrap"><?= e(fdatetime($a['created_at'])) ?></td>
          <td><?= e($a['user_name'] ?? 'Sistema') ?></td>
          <td class="mono"><?= e($a['action']) ?></td>
          <td><?php if ($a['entity'] === 'surgeries' && $a['entity_id']): ?><a href="<?= url('/admin/cirugias/' . $a['entity_id']) ?>"><?= e(surgery_code($a['entity_id'])) ?></a><?php else: ?><?= e(($a['entity'] ?? '') . ($a['entity_id'] ? ' #' . $a['entity_id'] : '')) ?><?php endif; ?></td>
          <td><small><?= e(mb_substr((string) $a['detail'], 0, 140)) ?></small></td>
          <td class="mono"><?= e($a['ip']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
