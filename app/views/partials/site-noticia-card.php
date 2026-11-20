<?php
$tituloNoticia = (string) ($noticia['titulo'] ?? 'Notícia');
$slugNoticia = (string) ($noticia['slug'] ?? '');
$resumoNoticia = (string) ($noticia['resumo'] ?? '');
if ($resumoNoticia === '') {
    $conteudo = (string) ($noticia['conteudo'] ?? '');
    $resumoNoticia = mb_strlen($conteudo) > 150 ? mb_substr($conteudo, 0, 147) . '...' : $conteudo;
}
$dataNoticia = (string) ($noticia['publicada_em'] ?? $noticia['created_at'] ?? '');
$imagemNoticia = \App\SiteRepository::imagem((string) ($noticia['imagem'] ?? ''), 'assets/img/galeria-placeholder.svg');
?>
<article class="card card-site card-noticia">
  <a href="<?= e(url('noticias/' . $slugNoticia)) ?>" class="d-block">
    <img class="card-image" src="<?= e($imagemNoticia) ?>" alt="<?= e($tituloNoticia) ?>" loading="lazy">
  </a>
  <div class="card-body d-flex flex-column flex-grow-1">
    <span class="card-meta"><i class="fa-regular fa-calendar me-1"></i><?= e(format_date($dataNoticia)) ?></span>
    <h3 class="card-title h5 mt-2">
      <a class="text-decoration-none" href="<?= e(url('noticias/' . $slugNoticia)) ?>"><?= e($tituloNoticia) ?></a>
    </h3>
    <p class="text-secondary small mb-3"><?= e($resumoNoticia) ?></p>
    <a class="mt-auto fw-semibold small text-decoration-none" href="<?= e(url('noticias/' . $slugNoticia)) ?>">
      Ler notícia <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
  </div>
</article>
