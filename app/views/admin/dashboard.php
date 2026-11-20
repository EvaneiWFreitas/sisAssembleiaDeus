<?php
$acessoRapido = [];
foreach ($modulos as $chave => $modulo) {
    if ($chave === 'dashboard' || count($acessoRapido) >= 6) {
        continue;
    }
    if (\App\AdminRepository::permitido($modulo, $permissoes)) {
        $acessoRapido[] = [$chave, $modulo];
    }
}
$verAuditoria = \App\AdminRepository::permitido(['modulo' => 'auditoria', 'acao' => 'ver'], $permissoes);
$indicadores = [
    ['Membros ativos', $estatisticas['membros_ativos'] ?? 0, 'fa-users'],
    ['Obreiros ativos', $estatisticas['obreiros_ativos'] ?? 0, 'fa-user-tie'],
    ['Usuários ativos', $estatisticas['usuarios_ativos'] ?? 0, 'fa-user-gear'],
    ['Eventos futuros', $estatisticas['eventos_futuros'] ?? 0, 'fa-calendar-days'],
    ['Mensagens não lidas', $estatisticas['mensagens_nao_lidas'] ?? 0, 'fa-envelope'],
    ['Ações em 7 dias', $estatisticas['auditoria_7d'] ?? 0, 'fa-list-check'],
];
?>
<div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2">
  <div>
    <strong>Olá, <?= e((string) ($usuario['nome'] ?? 'usuário')) ?>!</strong>
    <span class="text-secondary">Você está autenticado como <?= e($perfilNome) ?>.</span>
  </div>
  <a class="btn btn-sm btn-outline-dark rounded-pill" href="<?= e(url('/admin/perfil')) ?>">Ver meu perfil</a>
</div>

<div class="row g-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-6 col-lg-4 col-xl-2">
      <div class="card-admin stat-card">
        <span class="stat-icon"><i class="fa-solid <?= e($indicador[2]) ?>"></i></span>
        <div>
          <div class="stat-value"><?= e((int) $indicador[1]) ?></div>
          <div class="stat-label"><?= e($indicador[0]) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-8">
    <div class="card-admin h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0">Acesso rápido</h2>
        <span class="text-muted small">Módulos liberados para seu perfil</span>
      </div>
      <div class="row g-3">
        <?php foreach ($acessoRapido as [$chave, $modulo]): ?>
          <div class="col-sm-6">
            <a class="module-tile" href="<?= e(url('/admin/modulos/' . $chave)) ?>">
              <i class="fa-solid <?= e($modulo['icone']) ?> text-warning"></i>
              <strong><?= e($modulo['nome']) ?></strong>
              <small class="d-block"><?= e($modulo['descricao']) ?></small>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-admin h-100">
      <h2 class="h6 mb-3">Informações do sistema</h2>
      <ul class="list-unstyled small mb-0">
        <li class="mb-2"><strong>Ambiente:</strong> <?= e(config('app.env')) ?></li>
        <li class="mb-2"><strong>PHP:</strong> <?= e(PHP_VERSION) ?></li>
        <li class="mb-2"><strong>Perfil:</strong> <?= e($perfilNome) ?></li>
        <li class="mb-2"><strong>Permissões:</strong> <?= e(count($permissoes)) ?></li>
        <li><strong>Sessão:</strong> <span class="text-truncate d-inline-block align-middle" style="max-width: 150px;"><?= e(\App\Session::id()) ?></span></li>
      </ul>
    </div>
  </div>
</div>

<?php if ($verAuditoria): ?>
  <div class="row g-3 mt-1">
    <div class="col-12">
      <div class="card-admin">
        <h2 class="h6 mb-3">Atividade recente</h2>
        <?php if ($atividade === []): ?>
          <p class="text-muted small mb-0">Nenhuma atividade registrada ainda.</p>
        <?php else: ?>
          <?php foreach ($atividade as $item): ?>
            <div class="activity-item">
              <strong><?= e((string) ($item['acao'] ?? '')) ?> · <?= e((string) ($item['modulo'] ?? '')) ?></strong>
              <small>
                <?= e((string) ($item['usuario'] ?? 'Sistema')) ?>
                · <?= e(format_date((string) ($item['created_at'] ?? ''), 'd/m/Y H:i')) ?>
                <?php if (($item['entidade'] ?? '') !== ''): ?> · <?= e((string) $item['entidade']) ?><?php endif; ?>
              </small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
