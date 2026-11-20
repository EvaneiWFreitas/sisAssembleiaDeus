<div class="auth-card">
  <div class="auth-brand">
    <img src="<?= e(asset('img/logo-assembleia.svg')) ?>" alt="">
    <div>
      <strong><?= e($nomeIgreja ?? 'Assembleia de Deus') ?></strong>
      <small>sisAssembleiaDeus</small>
    </div>
  </div>

  <h1>Acessar painel</h1>
  <p>Entre com sua conta para gerenciar o sistema.</p>

  <?php if ($aviso !== ''): ?>
    <div class="alert alert-info small"><i class="fa-solid fa-circle-info me-2"></i><?= e($aviso) ?></div>
  <?php endif; ?>
  <?php if ($erro !== ''): ?>
    <div class="alert alert-danger small"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($erro) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/login')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label" for="email">E-mail</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
        <input class="form-control" id="email" name="email" type="email" autocomplete="username" required maxlength="190" value="<?= e($email) ?>" placeholder="seu@email.com">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label" for="senha">Senha</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
        <input class="form-control" id="senha" name="senha" type="password" autocomplete="current-password" required minlength="8" placeholder="Sua senha">
      </div>
    </div>
    <button class="btn btn-dark w-100 rounded-pill mt-2" type="submit">
      <i class="fa-solid fa-right-to-bracket me-2"></i>Entrar
    </button>
  </form>

  <div class="text-center mt-4">
    <a class="small text-decoration-none" href="<?= e(url('/')) ?>"><i class="fa-solid fa-arrow-left me-1"></i>Voltar ao site</a>
  </div>
</div>
