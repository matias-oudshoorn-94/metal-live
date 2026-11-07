<tr>
  <td>
    <select name="items[<?= e($index) ?>][material_id]" data-material aria-label="Material">
      <option value="">— Ítem libre —</option>
      <?php foreach ($materials as $m): ?>
        <option value="<?= (int) $m['id'] ?>" data-name="<?= e($m['name']) ?>" data-price="<?= e($m['price']) ?>"<?= selected($item['material_id'] ?? '', $m['id']) ?>><?= e($m['code'] . ' · ' . $m['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </td>
  <td><input name="items[<?= e($index) ?>][description]" value="<?= e($item['description'] ?? '') ?>" maxlength="255" data-desc aria-label="Descripción"></td>
  <td class="col-qty"><input name="items[<?= e($index) ?>][quantity]" type="number" min="1" step="1" value="<?= e($item['quantity'] ?? 1) ?>" data-qty aria-label="Cantidad"></td>
  <td class="col-price"><input name="items[<?= e($index) ?>][unit_price]" type="number" min="0" step="0.01" value="<?= e($item['unit_price'] ?? '') ?>" data-price aria-label="Precio unitario"></td>
  <td class="col-act"><button class="icon-btn" type="button" data-remove-row title="Quitar" aria-label="Quitar ítem"><?= icon('trash') ?></button></td>
</tr>
