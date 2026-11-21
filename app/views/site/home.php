<?php
$proximoEvento = $eventos[0] ?? null;
$diasSemana = [
    'domingo' => 'Domingo',
    'segunda' => 'Segunda-feira',
    'terca' => 'Terça-feira',
    'quarta' => 'Quarta-feira',
    'quinta' => 'Quinta-feira',
    'sexta' => 'Sexta-feira',
    'sabado' => 'Sábado',
];
$resumoPalavra = (string) ($palavra['texto'] ?? '');
if (mb_strlen($resumoPalavra) > 360) {
    $resumoPalavra = mb_substr($resumoPalavra, 0, 357) . '...';
}

$eyebrowCarrossel = (string) ($homeEyebrow ?? 'Bem-vindo à família');
$descricaoCarrossel = (string) ($homeDescricao ?? 'Um lugar de fé, esperança e comunhão. Acompanhe nossos cultos, eventos e notícias, e faça parte da família da igreja.');
$slidesCarrossel = [];
foreach (($carrossel ?? []) as $slideCarrossel) {
    $imagemCarrossel = trim((string) ($slideCarrossel['imagem'] ?? ''));
    if ($imagemCarrossel === '') {
        continue;
    }
    $slidesCarrossel[] = [
        'titulo' => trim((string) ($slideCarrossel['titulo'] ?? '')) !== '' ? (string) $slideCarrossel['titulo'] : (string) $nomeIgreja,
        'descricao' => trim((string) ($slideCarrossel['descricao'] ?? '')) !== '' ? (string) $slideCarrossel['descricao'] : $descricaoCarrossel,
        'imagem' => str_starts_with($imagemCarrossel, 'uploads/') ? upload_url($imagemCarrossel) : $imagemCarrossel,
    ];
}
if ($slidesCarrossel === []) {
    $slidesCarrossel[] = [
        'titulo' => (string) $nomeIgreja,
        'descricao' => $descricaoCarrossel,
        'imagem' => asset('img/hero-igreja.svg'),
    ];
}
$totalSlidesCarrossel = count($slidesCarrossel);
?>
<section class="hero hero-carousel">
  <div class="carousel slide carousel-fade" id="carrosselHome" data-bs-ride="carousel" data-bs-interval="6500" data-bs-pause="hover">
    <?php if ($totalSlidesCarrossel > 1): ?>
      <div class="carousel-indicators">
        <?php for ($indicadorCarrossel = 0; $indicadorCarrossel < $totalSlidesCarrossel; $indicadorCarrossel++): ?>
          <button type="button" data-bs-target="#carrosselHome" data-bs-slide-to="<?= $indicadorCarrossel ?>" class="<?= $indicadorCarrossel === 0 ? 'active' : '' ?>" <?= $indicadorCarrossel === 0 ? 'aria-current="true"' : '' ?> aria-label="Slide <?= $indicadorCarrossel + 1 ?>"></button>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

    <div class="carousel-inner">
      <?php foreach ($slidesCarrossel as $indiceCarrossel => $slideCarrossel): ?>
        <div class="carousel-item hero-slide <?= $indiceCarrossel === 0 ? 'active' : '' ?>" style="background-image: linear-gradient(90deg, rgba(6, 24, 48, 0.94) 0%, rgba(6, 24, 48, 0.82) 45%, rgba(6, 24, 48, 0.55) 100%), url('<?= e($slideCarrossel['imagem']) ?>');">
          <div class="container">
            <div class="row align-items-center g-4">
              <div class="col-lg-7">
                <span class="hero-eyebrow"><?= e($eyebrowCarrossel) ?></span>
                <h1><?= e($slideCarrossel['titulo']) ?></h1>
                <p><?= e($slideCarrossel['descricao']) ?></p>
                <div class="hero-actions">
                  <a class="btn btn-site" href="<?= e(url('/cultos')) ?>"><i class="fa-solid fa-calendar-days me-2"></i>Ver programação</a>
                  <a class="btn btn-outline-site" href="<?= e(url('/contato')) ?>"><i class="fa-solid fa-comments me-2"></i>Fale conosco</a>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="verse-box">
                  <p>“<?= e($versiculo['texto'] ?? '') ?>”</p>
                  <span>— <?= e($versiculo['referencia'] ?? '') ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalSlidesCarrossel > 1): ?>
      <button class="carousel-control-prev" type="button" data-bs-target="#carrosselHome" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Anterior</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carrosselHome" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Próximo</span>
      </button>
    <?php endif; ?>
  </div>
</section>

<section class="info-strip">
  <div class="container">
    <div class="row text-center">
      <div class="col-md-4 info-item">
        <i class="fa-solid fa-church fa-2x text-warning"></i>
        <h3>Cultos toda a semana</h3>
        <p>Louvor, Palavra e comunhão para todas as idades.</p>
      </div>
      <div class="col-md-4 info-item">
        <i class="fa-solid fa-hands-praying fa-2x text-warning"></i>
        <h3>Vida de oração</h3>
        <p>Momentos dedicados à intercessão e ao cuidado espiritual.</p>
      </div>
      <div class="col-md-4 info-item">
        <i class="fa-solid fa-users fa-2x text-warning"></i>
        <h3>Família acolhedora</h3>
        <p>Um lugar para crescer na fé e caminhar junto.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-lg-5">
        <span class="hero-eyebrow text-warning">Nossa essência</span>
        <h2 class="section-title mt-2">Crescendo juntos na fé</h2>
        <p class="text-secondary">Somos uma comunidade que busca viver o evangelho em todas as áreas da vida, com amor, verdade e compromisso com a missão de Cristo.</p>
        <a class="btn btn-outline-dark mt-3 rounded-pill" href="<?= e(url('/sobre')) ?>">Conheça mais <i class="fa-solid fa-arrow-right ms-2"></i></a>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <div class="col-sm-6">
            <div class="card-site">
              <span class="icon-box"><i class="fa-solid fa-bible"></i></span>
              <h3 class="h6">Palavra de Deus</h3>
              <p class="text-secondary small mb-0">Ensino bíblico fiel e aplicável para o dia a dia.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="card-site">
              <span class="icon-box"><i class="fa-solid fa-heart"></i></span>
              <h3 class="h6">Comunhão</h3>
              <p class="text-secondary small mb-0">Relações verdadeiras e cuidado mútuo entre irmãos.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="card-site">
              <span class="icon-box"><i class="fa-solid fa-hands-praying"></i></span>
              <h3 class="h6">Oração</h3>
              <p class="text-secondary small mb-0">Busca constante a Deus em todos os momentos.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="card-site">
              <span class="icon-box"><i class="fa-solid fa-seedling"></i></span>
              <h3 class="h6">Crescimento</h3>
              <p class="text-secondary small mb-0">Discipulado e formação para cada etapa da vida.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h2 class="section-title mb-0">Próximos eventos</h2>
        <p class="section-lead text-start mt-3 mb-0">Confira o que está chegando e participe conosco.</p>
      </div>
      <a class="fw-semibold text-decoration-none" href="<?= e(url('/eventos')) ?>">Ver todos <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-4">
      <?php foreach ($eventos as $evento): ?>
        <div class="col-md-6 col-lg-4">
          <?= \App\View::partial('partials/site-evento-card', ['evento' => $evento]) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="pastoral-box">
      <div>
        <span class="hero-eyebrow text-warning">Palavra pastoral</span>
        <h2 class="mt-2"><?= e($palavra['titulo'] ?? '') ?></h2>
        <p><?= e($resumoPalavra) ?></p>
        <div class="pastoral-author mt-4">
          <span><?= e($palavra['autor'] ?? '') ?></span>
          <strong><?= e($nomeIgreja) ?></strong>
        </div>
      </div>
      <div class="text-center">
        <img class="rounded-circle img-fluid" src="<?= e(\App\SiteRepository::imagem((string) ($palavra['foto'] ?? ''), 'assets/img/logo-assembleia.svg')) ?>" alt="<?= e($palavra['autor'] ?? 'Pastor') ?>" style="max-width: 190px; background: rgba(255,255,255,.12); padding: 18px;">
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <span class="hero-eyebrow text-warning">Cultos</span>
        <h2 class="section-title mt-2">Nossa programação</h2>
        <p class="section-lead text-start">Reunimos para louvar, orar e ouvir a Palavra. Todos são bem-vindos.</p>
        <?php if ($proximoEvento !== null): ?>
          <div class="card-site mt-4">
            <span class="card-meta">Destaque da semana</span>
            <h3 class="h5 mt-2"><?= e($proximoEvento['titulo'] ?? '') ?></h3>
            <p class="text-secondary small mb-2"><?= e(format_date((string) ($proximoEvento['data_inicio'] ?? ''))) ?> · <?= e((string) ($proximoEvento['horario'] ?? '')) ?></p>
            <p class="mb-0 small"><?= e((string) ($proximoEvento['local'] ?? '')) ?></p>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-7">
        <?php foreach (array_slice($cultos, 0, 4) as $culto): ?>
          <div class="schedule-row">
            <span class="schedule-day"><?= e($diasSemana[(string) ($culto['dia_semana'] ?? '')] ?? ucfirst((string) ($culto['dia_semana'] ?? ''))) ?></span>
            <span class="schedule-time"><?= e(substr((string) ($culto['horario'] ?? ''), 0, 5)) ?></span>
            <div>
              <strong class="d-block"><?= e((string) ($culto['tipo'] ?? 'Culto')) ?></strong>
              <small class="text-muted"><?= e((string) ($culto['descricao'] ?? '')) ?></small>
            </div>
          </div>
        <?php endforeach; ?>
        <a class="btn btn-outline-dark mt-3 rounded-pill" href="<?= e(url('/cultos')) ?>">Ver programação completa</a>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h2 class="section-title mb-0">Últimas notícias</h2>
        <p class="section-lead text-start mt-3 mb-0">Fique por dentro do que acontece na nossa comunidade.</p>
      </div>
      <a class="fw-semibold text-decoration-none" href="<?= e(url('/noticias')) ?>">Todas as notícias <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-4">
      <?php foreach ($noticias as $noticia): ?>
        <div class="col-md-6 col-lg-4">
          <?= \App\View::partial('partials/site-noticia-card', ['noticia' => $noticia]) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h2 class="section-title mb-0">Momentos da igreja</h2>
        <p class="section-lead text-start mt-3 mb-0">Registros da nossa caminhada juntos.</p>
      </div>
      <a class="fw-semibold text-decoration-none" href="<?= e(url('/galeria')) ?>">Ver galeria <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
    <div class="row">
      <?php foreach (array_slice($galeria, 0, 6) as $foto): ?>
        <div class="col-6 col-lg-4">
          <button class="gallery-item" type="button" data-galeria-imagem="<?= e(\App\SiteRepository::imagem((string) ($foto['imagem'] ?? ''))) ?>" data-galeria-titulo="<?= e((string) ($foto['titulo'] ?? 'Foto da igreja')) ?>">
            <img src="<?= e(\App\SiteRepository::imagem((string) ($foto['imagem'] ?? ''))) ?>" alt="<?= e((string) ($foto['titulo'] ?? 'Foto da igreja')) ?>" loading="lazy">
            <span class="gallery-caption"><strong><?= e((string) ($foto['titulo'] ?? '')) ?></strong></span>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt text-center">
  <div class="container">
    <i class="fa-solid fa-church fa-3x text-warning mb-3"></i>
    <h2 class="section-title">Venha fazer parte</h2>
    <p class="section-lead">Há um lugar para você na nossa família. Estamos ansiosos para recebê-lo.</p>
    <a class="btn btn-site mt-2" href="<?= e(url('/contato')) ?>"><i class="fa-solid fa-heart me-2"></i>Entre em contato</a>
  </div>
</section>
