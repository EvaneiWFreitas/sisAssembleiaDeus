<div class="row g-3">
  <div class="col-lg-5">
    <div class="card-admin text-center h-100">
      <span class="admin-avatar mx-auto mb-3" style="width: 76px; height: 76px; font-size: 1.3rem;">
        <?= e(\App\AdminRepository::iniciais((string) ($usuario['nome'] ?? ''))) ?>
      </span>
      <h2 class="h5 mb-1"><?= e((string) ($usuario['nome'] ?? 'Usuário')) ?></h2>
      <p class="text-secondary small mb-3"><?= e((string) ($usuario['email'] ?? '')) ?></p>
      <span class="badge text-bg-dark rounded-pill"><?= e($perfilNome) ?></span>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card-admin h-100">
      <h2 class="h6 mb-3">Dados da conta</h2>
      <dl class="row small mb-0">
        <dt class="col-sm-4">ID</dt>
        <dd class="col-sm-8"><?= e((int) ($usuario['id'] ?? 0)) ?></dd>
        <dt class="col-sm-4">Nome</dt>
        <dd class="col-sm-8"><?= e((string) ($usuario['nome'] ?? '')) ?></dd>
        <dt class="col-sm-4">E-mail</dt>
        <dd class="col-sm-8"><?= e((string) ($usuario['email'] ?? '')) ?></dd>
        <dt class="col-sm-4">Perfil</dt>
        <dd class="col-sm-8"><?= e($perfilNome) ?></dd>
        <dt class="col-sm-4">Permissões</dt>
        <dd class="col-sm-8"><?= e(count($permissoes)) ?> liberadas</dd>
        <dt class="col-sm-4">Status</dt>
        <dd class="col-sm-8"><span class="text-success fw-semibold">Ativo</span></dd>
      </dl>
    </div>
  </div>
</div>
