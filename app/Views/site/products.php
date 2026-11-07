<?php
$lines = [
    ['bone', 'Osteosíntesis', 'Sistemas completos para fijación de fracturas, con placas anatómicas y de bloqueo angular.', ['Placas LCP', 'Placas tercio de caña', 'Tornillos corticales', 'Tornillos esponjosos', 'Tornillos canulados', 'Agujas de Kirschner']],
    ['activity', 'Enclavado endomedular', 'Clavos de fémur, tibia y húmero con sistemas de bloqueo proximal y distal.', ['Clavo femoral', 'Clavo tibial', 'Clavo humeral', 'Clavo cefalomedular']],
    ['joint', 'Reemplazos articulares', 'Prótesis primarias y de revisión para cadera y rodilla, cementadas y no cementadas.', ['Cadera total', 'Cadera parcial', 'Rodilla total PS / CR', 'Cemento óseo', 'Revisión']],
    ['spine', 'Columna', 'Fijación transpedicular, artrodesis e implantes intersomáticos para columna cervical y lumbar.', ['Tornillos pediculares', 'Barras de titanio', 'Cajas intersomáticas', 'Placas cervicales']],
    ['scope', 'Artroscopía y medicina deportiva', 'Implantes y descartables para reparación de partes blandas.', ['Anclajes con sutura', 'Tornillos interferenciales', 'Botones corticales', 'Shaver y radiofrecuencia']],
    ['layers', 'Insumos y descartables', 'Todo lo necesario para completar el procedimiento.', ['Cemento óseo', 'Sustitutos óseos', 'Hojas de sierra', 'Mechas y guías']],
];
?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Productos</p>
    <h1>Líneas de producto</h1>
    <p>Implantes y prótesis de fabricantes reconocidos, con registro ANMAT y el instrumental necesario para cada técnica.</p>
  </div>
</section>

<section class="section">
  <div class="container narrow">
    <?php foreach ($lines as [$icon, $name, $desc, $tags]): ?>
      <article class="product-line reveal">
        <div class="feature-icon"><?= icon($icon) ?></div>
        <div>
          <h2><?= e($name) ?></h2>
          <p><?= e($desc) ?></p>
          <div class="tags"><?php foreach ($tags as $tag): ?><span class="tag"><?= e($tag) ?></span><?php endforeach; ?></div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="cta-band reveal">
      <div>
        <h2>¿Buscás un sistema en particular?</h2>
        <p>Consultanos disponibilidad y armado de caja para tu procedimiento.</p>
      </div>
      <a class="btn btn-light btn-lg" href="<?= url('/contacto') ?>">Consultar</a>
    </div>
  </div>
</section>
