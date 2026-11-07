<?php
/** @var int $total @var int $perPage */
$pages = (int) ceil($total / max(1, $perPage));
$current = max(1, (int) ($_GET['page'] ?? 1));
if ($pages <= 1) {
    return;
}
$link = function (int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
};
?>
<nav class="pagination" aria-label="Paginación">
  <span class="muted"><?= number_format($total, 0, ',', '.') ?> resultados</span>
  <div class="pagination-links">
    <?php if ($current > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e($link($current - 1)) ?>">Anterior</a><?php endif; ?>
    <span class="pagination-current">Página <?= $current ?> de <?= $pages ?></span>
    <?php if ($current < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= e($link($current + 1)) ?>">Siguiente</a><?php endif; ?>
  </div>
</nav>
