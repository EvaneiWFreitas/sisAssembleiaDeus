<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? $nomeIgreja) ?> | <?= e($nomeIgreja) ?></title>
  <meta name="description" content="<?= e($descricao ?? 'Portal institucional com cultos, eventos, notícias e informações da igreja.') ?>">
  <link rel="icon" href="<?= e(asset('img/logo-assembleia.svg')) ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(asset('css/site.css')) ?>" rel="stylesheet">
  <style>
    :root {
      --site-primary: <?= e($cores['primaria']) ?>;
      --site-secondary: <?= e($cores['secundaria']) ?>;
      --site-bg: <?= e($cores['fundo']) ?>;
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <i class="fa-solid fa-location-dot me-1"></i>
        <span><?= e($endereco) ?></span>
      </div>
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <?php if ($telefone !== ''): ?>
          <a href="tel:<?= e(preg_replace('/\D/', '', $telefone)) ?>"><i class="fa-solid fa-phone me-1"></i><?= e($telefone) ?></a>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
          <a href="mailto:<?= e($email) ?>"><i class="fa-solid fa-envelope me-1"></i><?= e($email) ?></a>
        <?php endif; ?>
        <?php if ($whatsappLink !== ''): ?>
          <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp me-1"></i>WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?= \App\View::partial('partials/site-navbar', get_defined_vars()) ?>

  <main>
    <?= $conteudo ?>
  </main>

  <?= \App\View::partial('partials/site-footer', get_defined_vars()) ?>

  <div class="lightbox" id="galeriaLightbox" aria-hidden="true">
    <div class="lightbox-content">
      <button class="lightbox-close" type="button" data-lightbox-fechar aria-label="Fechar imagem">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <img id="galeriaLightboxImagem" alt="" hidden>
      <div class="lightbox-caption fw-semibold" id="galeriaLightboxTitulo"></div>
    </div>
  </div>

  <button class="back-to-top" id="back-to-top" type="button" aria-label="Voltar ao topo">
    <i class="fa-solid fa-arrow-up"></i>
  </button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(asset('js/site.js')) ?>"></script>
</body>
</html>
