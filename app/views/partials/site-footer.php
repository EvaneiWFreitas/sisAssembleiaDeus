<footer class="site-footer">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="brand-mark">
            <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
          </span>
          <span class="brand-title text-white d-block"><?= e($nomeIgreja) ?></span>
        </div>
        <p class="small">
          <?= e(\App\SiteRepository::config('mensagem_rodape', 'Uma comunidade comprometida com a Palavra de Deus, a oração e o cuidado com cada pessoa.')) ?>
        </p>
        <div class="d-flex gap-2">
          <?php if (($redes['facebook'] ?? '') !== ''): ?>
            <a class="btn btn-outline-light btn-sm rounded-circle" href="<?= e($redes['facebook']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <?php endif; ?>
          <?php if (($redes['instagram'] ?? '') !== ''): ?>
            <a class="btn btn-outline-light btn-sm rounded-circle" href="<?= e($redes['instagram']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <?php endif; ?>
          <?php if (($redes['youtube'] ?? '') !== ''): ?>
            <a class="btn btn-outline-light btn-sm rounded-circle" href="<?= e($redes['youtube']) ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
          <?php endif; ?>
          <?php if (($redes['telegram'] ?? '') !== ''): ?>
            <a class="btn btn-outline-light btn-sm rounded-circle" href="<?= e($redes['telegram']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <h5>Contato</h5>
        <p class="small mb-2"><i class="fa-solid fa-location-dot me-2"></i><?= e($endereco) ?></p>
        <?php if ($telefone !== ''): ?>
          <p class="small mb-2"><i class="fa-solid fa-phone me-2"></i><?= e($telefone) ?></p>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
          <p class="small mb-2"><i class="fa-solid fa-envelope me-2"></i><?= e($email) ?></p>
        <?php endif; ?>
        <?php if ($pastorPresidente !== ''): ?>
          <p class="small mb-0"><i class="fa-solid fa-user me-2"></i><?= e($pastorPresidente) ?></p>
        <?php endif; ?>
      </div>

      <div class="col-lg-4">
        <h5>Links rápidos</h5>
        <ul class="list-unstyled small mb-0">
          <li class="mb-2"><a href="<?= e(url('/sobre')) ?>">Sobre a igreja</a></li>
          <li class="mb-2"><a href="<?= e(url('/cultos')) ?>">Programação de cultos</a></li>
          <li class="mb-2"><a href="<?= e(url('/eventos')) ?>">Próximos eventos</a></li>
          <li class="mb-2"><a href="<?= e(url('/galeria')) ?>">Galeria de fotos</a></li>
          <li class="mb-2"><a href="<?= e(url('/contato')) ?>">Fale conosco</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 small">
      <span>© <span id="ano-atual"><?= e(date('Y')) ?></span> <?= e($nomeIgreja) ?>. Todos os direitos reservados.</span>
      <span>Feito para fortalecer a comunidade.</span>
    </div>
  </div>
</footer>
