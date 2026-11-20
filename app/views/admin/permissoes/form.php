<?php $perfilId = (int) $perfil['id']; ?>
<?php if ($bloqueado): ?>
  <div class="alert alert-warning">
    <i class="fa-solid fa-shield-halved me-2"></i>
    O perfil <strong>Superadministrador</strong> possui todas as permissões e não pode ser alterado.
  </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/modulos/permissoes/' . $perfilId)) ?>">
  <?= csrf_field() ?>
  <div class="card-admin mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h2 class="h6 mb-1"><?= e((string) $perfil['nome']) ?></h2>
        <span class="small text-muted"><?= e((string) $perfil['slug']) ?></span>
      </div>
      <span class="badge text-bg-dark rounded-pill"><?= e(count($selecionadas)) ?> permissões ativas</span>
    </div>
  </div>

  <div class="row g-3">
    <?php foreach ($catalogo as $nomeModulo => $permissoes): ?>
      <div class="col-lg-6">
        <div class="card-admin h-100">
          <h2 class="h6 mb-3"><?= e((string) $nomeModulo) ?></h2>
          <div class="row g-2">
            <?php foreach ($permissoes as $permissao): ?>
              <?php
                $chave = $permissao['modulo'] . '.' . $permissao['acao'];
                $marcado = in_array($chave, $selecionadas, true) || $bloqueado;
              ?>
              <div class="col-sm-6">
                <div class="form-check border rounded-3 p-2 h-100">
                  <input class="form-check-input" type="checkbox" name="permissoes[]" value="<?= e($permissao['id']) ?>"
                         id="perm-<?= e($permissao['id']) ?>" <?= $marcado ? 'checked' : '' ?> <?= $bloqueado ? 'disabled' : '' ?>>
                  <label class="form-check-label small" for="perm-<?= e($permissao['id']) ?>">
                    <strong><?= e($permissao['rotulo']) ?></strong><br>
                    <span class="text-muted"><?= e($permissao['descricao']) ?></span>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex flex-wrap gap-2 mt-3">
    <?php if (!$bloqueado): ?>
      <button class="btn btn-dark rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar permissões</button>
    <?php endif; ?>
    <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/permissoes')) ?>">Voltar</a>
  </div>
</form>
