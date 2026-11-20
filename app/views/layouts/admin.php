<?php
$grupos = [];
foreach ($modulos as $chave => $modulo) {
    if (!\App\AdminRepository::permitido($modulo, $permissoes)) {
        continue;
    }
    $grupos[(string) $modulo['grupo']][] = [$chave, $modulo];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Painel') ?> | sisAssembleiaDeus</title>
  <link rel="icon" href="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(asset('css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
  <aside class="admin-sidebar" id="adminSidebar">
    <a class="admin-brand" href="<?= e(url('/admin')) ?>">
      <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
      <span>
        <strong>sisAssembleiaDeus</strong>
        <small>Painel administrativo</small>
      </span>
    </a>

    <nav class="admin-nav">
      <?php foreach ($grupos as $nomeGrupo => $itens): ?>
        <div class="admin-nav-group"><?= e($nomeGrupo) ?></div>
        <?php foreach ($itens as [$chave, $modulo]): ?>
          <?php $destino = $chave === 'dashboard' ? url('/admin') : url('/admin/modulos/' . $chave); ?>
          <a class="admin-nav-link <?= $ativa === $chave ? 'active' : '' ?>" href="<?= e($destino) ?>">
            <i class="fa-solid <?= e($modulo['icone']) ?>"></i>
            <span><?= e($modulo['nome']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-footer">
      Versão 1.0 · Etapa 26<br>
      © <span><?= e(date('Y')) ?></span> sisAssembleiaDeus
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button" aria-label="Abrir menu">
        <i class="fa-solid fa-bars"></i>
      </button>

      <div class="ms-auto d-flex align-items-center gap-3">
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/')) ?>" target="_blank" rel="noopener noreferrer">
          <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Ver site
        </a>

        <div class="dropdown">
          <button class="btn btn-light d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="admin-avatar"><?= e(\App\AdminRepository::iniciais((string) ($usuario['nome'] ?? ''))) ?></span>
            <span class="d-none d-md-inline small fw-semibold"><?= e((string) ($usuario['nome'] ?? 'Usuário')) ?></span>
            <i class="fa-solid fa-chevron-down small"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= e(url('/admin/perfil')) ?>"><i class="fa-solid fa-user me-2"></i>Meu perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="<?= e(url('/logout')) ?>">
                <?= csrf_field() ?>
                <button class="dropdown-item text-danger" type="submit"><i class="fa-solid fa-right-from-bracket me-2"></i>Sair</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </header>

    <main class="admin-content">
      <?php foreach (['sucesso' => 'success', 'erro' => 'danger'] as $chave => $classe): ?>
        <?php $mensagem = \App\Session::takeFlash($chave); ?>
        <?php if (is_string($mensagem) && $mensagem !== ''): ?>
          <div class="alert alert-<?= e($classe) ?> alert-dismissible fade show" role="alert">
            <?= e($mensagem) ?>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Fechar"></button>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if (($titulo ?? '') !== ''): ?>
        <div class="admin-page-title">
          <h1><?= e($titulo) ?></h1>
          <?php if (($subtitulo ?? '') !== ''): ?><p><?= e($subtitulo) ?></p><?php endif; ?>
        </div>
      <?php endif; ?>

      <?= $conteudo ?>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
