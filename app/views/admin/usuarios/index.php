<?php
$indicadores = [
    ['Total', $estatisticas['total'] ?? 0, 'fa-users'],
    ['Ativos', $estatisticas['ativos'] ?? 0, 'fa-circle-check'],
    ['Inativos', $estatisticas['inativos'] ?? 0, 'fa-circle-xmark'],
    ['Bloqueados', $estatisticas['bloqueados'] ?? 0, 'fa-lock'],
];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-6 col-lg-3">
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

<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form class="d-flex gap-2" method="get" action="<?= e(url('/admin/modulos/usuarios')) ?>">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($busca) ?>" placeholder="Nome, e-mail ou perfil" aria-label="Buscar usuários">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <?php if ($busca !== ''): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/usuarios')) ?>">Limpar</a><?php endif; ?>
    </form>
    <?php if (\App\Auth::hasPermission('usuarios', 'criar')): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e(url('/admin/modulos/usuarios/novo')) ?>"><i class="fa-solid fa-plus me-1"></i>Novo usuário</a>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Usuário</th>
          <th>Perfil</th>
          <th>Status</th>
          <th>Último acesso</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($usuarios === []): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($usuarios as $usuario): ?>
          <?php $id = (int) $usuario['id']; ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="admin-avatar"><?= e(\App\AdminRepository::iniciais((string) $usuario['nome'])) ?></span>
                <div>
                  <strong class="d-block"><?= e((string) $usuario['nome']) ?></strong>
                  <small class="text-muted"><?= e((string) $usuario['email']) ?></small>
                </div>
              </div>
            </td>
            <td><?= e((string) ($usuario['perfil_nome'] ?? '—')) ?></td>
            <td>
              <?php if ((int) $usuario['ativo'] === 1): ?>
                <span class="badge text-bg-success rounded-pill">Ativo</span>
              <?php else: ?>
                <span class="badge text-bg-secondary rounded-pill">Inativo</span>
              <?php endif; ?>
              <?php if (!empty($usuario['bloqueado_ate']) && strtotime((string) $usuario['bloqueado_ate']) > time()): ?>
                <span class="badge text-bg-danger rounded-pill">Bloqueado</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?= e(format_date($usuario['ultimo_acesso'], 'd/m/Y H:i')) ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a class="btn btn-outline-dark" href="<?= e(url('/admin/modulos/usuarios/' . $id . '/editar')) ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                <?php if ($id !== \App\Auth::id() && \App\Auth::hasPermission('usuarios', 'excluir')): ?>
                  <form method="post" action="<?= e(url('/admin/modulos/usuarios/' . $id . '/status')) ?>" onsubmit="return confirm('Deseja alterar o status deste usuário?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-<?= (int) $usuario['ativo'] === 1 ? 'danger' : 'success' ?>" type="submit">
                      <i class="fa-solid <?= (int) $usuario['ativo'] === 1 ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                      <?= (int) $usuario['ativo'] === 1 ? 'Desativar' : 'Ativar' ?>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
