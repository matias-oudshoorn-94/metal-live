<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <p class="eyebrow">Ayuda</p>
      <h1>Preguntas frecuentes</h1>
    </div>
    <?= App\Core\View::partial('partials/hero_art', ['class' => 'page-hero-art']) ?>
  </div>
</section>

<section class="section">
  <div class="container narrow">
    <div class="faq">
      <details class="reveal" open>
        <summary>¿Quiénes somos?</summary>
        <div>
          <p>Somos una empresa argentina líder en la producción y distribución de implantes traumatológicos. Nos impulsa un compromiso inquebrantable: ofrecer soluciones integrales que combinen seguridad, eficacia y vanguardia para profesionales y pacientes.</p>
          <p>Con una base sólida en la mejora continua y un firme propósito de crecimiento, no solo aspiramos a seguir siendo líderes en el mercado sino también ser referentes en la vanguardia de la traumatología, marcando el ritmo de un futuro con soluciones más avanzadas y accesibles para todos.</p>
        </div>
      </details>
      <details class="reveal">
        <summary>¿Dónde estamos?</summary>
        <div><p><a href="<?= e(site_map_url()) ?>" target="_blank" rel="noopener"><?= e(site_info('address')) ?></a>.</p></div>
      </details>
      <details class="reveal">
        <summary>Obras sociales y prepagas</summary>
        <div><p>Comercializamos nuestros productos a todas las coberturas médicas del país.</p></div>
      </details>
      <details class="reveal">
        <summary>¿Cómo obtengo un presupuesto?</summary>
        <div>
          <p>Enviá una fotografía de tu receta médica y tu número de contacto por WhatsApp al <a href="<?= e(site_whatsapp_url('¡Hola! Quiero solicitar un presupuesto')) ?>" target="_blank" rel="noopener"><?= e(site_info('whatsapp_label')) ?></a> o por correo electrónico a <a href="mailto:<?= e(site_info('email')) ?>"><?= e(site_info('email')) ?></a>, y un asesor comercial se comunicará a la brevedad.</p>
        </div>
      </details>
      <details class="reveal">
        <summary>Medios de pago</summary>
        <div><p>Efectivo y transferencia bancaria.</p></div>
      </details>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="cta-band reveal">
      <div>
        <h2>¿Tenés otra consulta?</h2>
        <p>Dejanos tu consulta y te contactaremos cuanto antes.</p>
      </div>
      <a class="btn btn-light btn-lg" href="<?= url('/contacto') ?>">Contactanos</a>
    </div>
  </div>
</section>
