<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <p class="eyebrow">Nosotros</p>
      <h1>La confianza que necesitás. La solución que esperás.</h1>
      <p>Implantes de alta calidad para acompañar tu experiencia en cada cirugía.</p>
    </div>
    <?= App\Core\View::partial('partials/hero_art', ['class' => 'page-hero-art']) ?>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div class="reveal">
      <h2>Quiénes somos</h2>
      <p class="lead">Somos una empresa argentina líder en la producción y distribución de implantes traumatológicos. Nos impulsa un compromiso inquebrantable: ofrecer soluciones integrales que combinen seguridad, eficacia y vanguardia para profesionales y pacientes.</p>
      <p>Con una base sólida en la mejora continua y un firme propósito de crecimiento, no solo aspiramos a seguir siendo líderes en el mercado sino también ser referentes en la vanguardia de la traumatología, marcando el ritmo de un futuro con soluciones más avanzadas y accesibles para todos.</p>
    </div>
    <div class="values values-stack reveal">
      <?php foreach (site_values() as [, $name, $text]): ?>
        <div class="value"><h3><?= e($name) ?></h3><p><?= e($text) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container narrow">
    <div class="section-head reveal">
      <h2>Nuestra Visión</h2>
    </div>
    <ul class="check-list reveal">
      <li><?= icon('check') ?><span>Nos enfocamos en brindar soluciones innovadoras y personalizadas para satisfacer las necesidades específicas de nuestros clientes en el ámbito de la traumatología.</span></li>
      <li><?= icon('check') ?><span>Nuestro compromiso es con la excelencia y la calidad, garantizando que cada producto y servicio cumpla los más altos estándares.</span></li>
      <li><?= icon('check') ?><span>Buscamos establecer relaciones duraderas con nuestros clientes, basadas en la confianza, la transparencia y el servicio excepcional.</span></li>
    </ul>
  </div>
</section>

<section class="section">
  <div class="container narrow prose reveal">
    <p class="eyebrow">Vocación</p>
    <h2>La vocación detrás de cada recuperación.</h2>
    <p class="lead">Detrás de cada intervención, cada diagnóstico y cada plan de recuperación, hay profesionales que eligieron una carrera exigente, desafiante y profundamente humana.</p>
    <p>La traumatología no es solo una especialidad médica: es una vocación que requiere precisión, conocimiento y, sobre todo, un compromiso genuino con mejorar la calidad de vida de las personas.</p>
    <p>Quienes ejercen esta labor saben que cada decisión impacta directamente en la movilidad, el bienestar y la recuperación de un paciente. Por eso, se preparan, estudian, perfeccionan técnicas y buscan siempre las mejores herramientas para ofrecer soluciones seguras y efectivas. Su trabajo no termina en el quirófano; continúa en el seguimiento, la cercanía y el acompañamiento diario.</p>
    <p>Desde Metal Live ®, reconocemos y valoramos profundamente la labor de estos profesionales. Por eso trabajamos para estar a la altura de su excelencia, brindando productos de alta calidad que acompañen su experiencia y su entrega.</p>
    <blockquote>Porque detrás de cada implante, hay una historia de esfuerzo, precisión y vocación por sanar.</blockquote>
  </div>
</section>
