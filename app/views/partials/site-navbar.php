<header class="site-header">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-3" href="<?= e(url('/')) ?>">
        <span class="brand-mark">
          <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
        </span>
        <span>
          <span class="brand-title d-block"><?= e($nomeIgreja) ?></span>
          <span class="brand-subtitle d-block">Cristo é a nossa esperança</span>
        </span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNavbar" aria-controls="siteNavbar" aria-expanded="false" aria-label="Abrir menu">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="siteNavbar">
        <?php
        $itens = [
            'inicio' => ['/', 'Início'],
            'sobre' => ['/sobre', 'Sobre'],
            'cultos' => ['/cultos', 'Cultos'],
            'eventos' => ['/eventos', 'Eventos'],
            'noticias' => ['/noticias', 'Notícias'],
            'galeria' => ['/galeria', 'Galeria'],
            'contato' => ['/contato', 'Contato'],
        ];
        ?>
        <ul class="navbar-nav ms-auto site-nav align-items-lg-center">
          <?php foreach ($itens as $chave => $item): ?>
            <li class="nav-item">
              <a class="nav-link <?= $rotaAtiva === $chave ? 'active' : '' ?>" href="<?= e(url($item[0])) ?>"><?= e($item[1]) ?></a>
            </li>
          <?php endforeach; ?>
          <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
            <a class="btn btn-site btn-sm" href="<?= e(url('/login')) ?>">
              <i class="fa-solid fa-lock me-1"></i>Área restrita
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>
