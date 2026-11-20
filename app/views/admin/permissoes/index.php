<div class="row g-3 mb-3">
  <?php foreach ($perfis as $perfil): ?>
    <?php $id = (int) $perfil['id']; ?>
    <div class="col-md-6 col-xl-4">
      <div class="card-admin h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <h2 class="h6 mb-1"><?= e((string) $perfil['nome']) ?></h2>
            <span class="badge text-bg-light border rounded-pill small"><?= e((string) $perfil['slug']) ?></span>
          </div>
          <?php if ((string) $perfil['slug'] === 'superadministrador'): ?>
            <span class="badge text-bg-warning rounded-pill">Fixo</span>
          <?php endif; ?>
        </div>
        <p class="small text-muted my-3"><?= e((string) ($perfil['descricao'] ?? 'Sem descrição')) ?></p>
        <div class="d-flex justify-content-between align-items-center small text-muted">
          <span><i class="fa-solid fa-key me-1"></i><?= e((int) $perfil['total_permissoes']) ?> permissões</span>
          <span><i class="fa-solid fa-users me-1"></i><?= e((int) $perfil['total_usuarios']) ?> usuários</span>
        </div>
        <a class="btn btn-sm btn-outline-dark rounded-pill mt-3 w-100" href="<?= e(url('/admin/modulos/permissoes/' . $id)) ?>">Gerenciar permissões</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card-admin">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h6 mb-0">Catálogo de permissões</h2>
    <span class="badge text-bg-dark rounded-pill"><?= e($totalPermissoes) ?> permissões</span>
  </div>
  <div class="table-responsive">
    <table class="table admin-table align-middle mb-0">
      <thead><tr><th>Módulo</th><th>Permissões disponíveis</th></tr></thead>
      <tbody>
        <?php foreach ($catalogo as $nomeModulo => $permissoes): ?>
          <tr>
            <td class="fw-semibold"><?= e((string) $nomeModulo) ?></td>
            <td>
              <?php foreach ($permissoes as $permissao): ?>
                <span class="badge text-bg-light border rounded-pill small me-1 mb-1" title="<?= e($permissao['descricao']) ?>">
                  <?= e($permissao['rotulo']) ?>
                </span>
              <?php endforeach; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
