<?php /** @var int|string $index  @var array $materials  @var array $byMaterial */ ?>
<select name="items[<?= e((string) $index) ?>][material_id]" data-combobox data-combobox-code data-placeholder="Buscar material por código o nombre" aria-label="Material">
  <option value="">Seleccionar material…</option>
  <?php foreach ($materials as $m): ?>
    <option value="<?= (int) $m['id'] ?>"><?= e($m['code'] . ' · ' . $m['name']) ?> (depósito <?= (int) $m['stock_qty'] ?> · cliente <?= (int) ($byMaterial[$m['id']] ?? 0) ?>)</option>
  <?php endforeach; ?>
</select>
