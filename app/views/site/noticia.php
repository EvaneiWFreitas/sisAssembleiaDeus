<?php
$dataPublicacao = (string) ($noticia['publicada_em'] ?? $noticia['created_at'] ?? '');
$imagemDestaque = \App\SiteRepository::imagem((string) ($noticia['imagem'] ?? ''), 'assets/img/hero-igreja.svg');
$conteudo = (string) ($noticia['conteudo'] ?? $noticia['resumo'] ?? '');
?>
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-site"><a href="<?= e(url('/')) ?>">Início</a> / <a href="<?= e(url('/noticias')) ?>">Notícias</a> / <?= e((string) ($noticia['titulo'] ?? '')) ?></div>
    <h1><?= e((string) ($noticia['titulo'] ?? 'Notícia')) ?></h1>
    <p><i class="fa-regular fa-calendar me-2"></i><?= e(format_date($dataPublicacao)) ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <img class="img-fluid rounded-4 w-100 mb-4" src="<?= e($imagemDestaque) ?>" alt="<?= e((string) ($noticia['titulo'] ?? '')) ?>">
        <?php if (($noticia['resumo'] ?? '') !== ''): ?>
          <p class="lead"><?= e((string) $noticia['resumo']) ?></p>
        <?php endif; ?>
        <div class="article-content">
          <?php foreach (preg_split('/\R{2,}/', $conteudo) ?: [] as $paragrafo): ?>
            <?php if (trim($paragrafo) !== ''): ?>
              <p><?= e(trim($paragrafo)) ?></p>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <a class="btn btn-outline-dark rounded-pill mt-4" href="<?= e(url('/noticias')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Voltar para notícias</a>
      </div>
    </div>
  </div>
</section>
