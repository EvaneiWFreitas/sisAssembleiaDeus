<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-site"><a href="<?= e(url('/')) ?>">Início</a> / Notícias</div>
    <h1>Notícias</h1>
    <p>Acompanhe as novidades, testemunhos e informações da nossa comunidade.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <?php foreach ($noticias as $noticia): ?>
        <div class="col-md-6 col-lg-4">
          <?= \App\View::partial('partials/site-noticia-card', ['noticia' => $noticia]) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
