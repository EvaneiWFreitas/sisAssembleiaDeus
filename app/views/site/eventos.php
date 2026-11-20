<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-site"><a href="<?= e(url('/')) ?>">Início</a> / Eventos</div>
    <h1>Próximos eventos</h1>
    <p>Participe das atividades, campanhas e celebrações da nossa igreja.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <?php foreach ($eventos as $evento): ?>
        <div class="col-md-6 col-lg-4">
          <?= \App\View::partial('partials/site-evento-card', ['evento' => $evento]) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
