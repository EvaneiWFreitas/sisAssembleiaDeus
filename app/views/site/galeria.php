<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-site"><a href="<?= e(url('/')) ?>">Início</a> / Galeria</div>
    <h1>Galeria de fotos</h1>
    <p>Registros dos encontros, celebrações e momentos que marcam a nossa caminhada.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row">
      <?php foreach ($galeria as $foto): ?>
        <?php $imagem = \App\SiteRepository::imagem((string) ($foto['imagem'] ?? '')); ?>
        <div class="col-6 col-md-4 col-lg-3">
          <button class="gallery-item" type="button" data-galeria-imagem="<?= e($imagem) ?>" data-galeria-titulo="<?= e((string) ($foto['titulo'] ?? 'Foto da igreja')) ?>">
            <img src="<?= e($imagem) ?>" alt="<?= e((string) ($foto['titulo'] ?? 'Foto da igreja')) ?>" loading="lazy">
            <span class="gallery-caption">
              <strong><?= e((string) ($foto['titulo'] ?? '')) ?></strong>
              <small><?= e((string) ($foto['descricao'] ?? '')) ?></small>
            </span>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
