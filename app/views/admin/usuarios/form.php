<?php
$edicao = $usuarioFormulario !== null;
$acao = $edicao ? url('/admin/modulos/usuarios/' . (int) $usuarioFormulario['id']) : url('/admin/modulos/usuarios');
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $valor, mixed $atual): string {
    return (string) $valor === (string) $atual ? 'selected' : '';
};
?>
<form method="post" action="<?= e($acao) ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Dados da conta</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="nome">Nome completo</label>
            <input class="form-control <?= isset($erros['nome']) ? 'is-invalid' : '' ?>" id="nome" name="nome" value="<?= e($valores['nome'] ?? '') ?>" maxlength="150" required>
            <?= $erroCampo('nome') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control <?= isset($erros['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= e($valores['email'] ?? '') ?>" maxlength="190" required>
            <?= $erroCampo('email') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="perfil_id">Perfil de acesso</label>
            <select class="form-select <?= isset($erros['perfil_id']) ? 'is-invalid' : '' ?>" id="perfil_id" name="perfil_id" required>
              <option value="">Selecione...</option>
              <?php foreach ($perfis as $perfil): ?>
                <option value="<?= e((int) $perfil['id']) ?>" <?= $selecionado($perfil['id'], $valores['perfil_id'] ?? '') ?>><?= e((string) $perfil['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('perfil_id') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ativo">Status</label>
            <select class="form-select <?= isset($erros['ativo']) ? 'is-invalid' : '' ?>" id="ativo" name="ativo">
              <option value="1" <?= $selecionado(1, $valores['ativo'] ?? 1) ?>>Ativo</option>
              <option value="0" <?= $selecionado(0, $valores['ativo'] ?? 1) ?>>Inativo</option>
            </select>
            <?= $erroCampo('ativo') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="senha"><?= $edicao ? 'Nova senha (opcional)' : 'Senha inicial' ?></label>
            <input class="form-control <?= isset($erros['senha']) ? 'is-invalid' : '' ?>" id="senha" name="senha" type="password" minlength="8" autocomplete="new-password" <?= $edicao ? '' : 'required' ?>>
            <?= $erroCampo('senha') ?>
            <div class="form-text">Mínimo de 8 caracteres. Deixe em branco para manter a senha atual.</div>
          </div>
          <div class="col-12">
            <label class="form-label" for="observacoes">Observações</label>
            <textarea class="form-control <?= isset($erros['observacoes']) ? 'is-invalid' : '' ?>" id="observacoes" name="observacoes" rows="3" maxlength="500"><?= e($valores['observacoes'] ?? '') ?></textarea>
            <?= $erroCampo('observacoes') ?>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-dark rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/usuarios')) ?>">Voltar</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin">
        <h2 class="h6 mb-3">Informações</h2>
        <?php if ($edicao): ?>
            <dl class="row small mb-0">
            <dt class="col-5">Criado em</dt><dd class="col-7"><?= e(format_date((string) $usuarioFormulario['created_at'], 'd/m/Y H:i')) ?></dd>
            <dt class="col-5">Último acesso</dt><dd class="col-7"><?= e(format_date($usuarioFormulario['ultimo_acesso'], 'd/m/Y H:i')) ?></dd>
            <dt class="col-5">Tentativas</dt><dd class="col-7"><?= e((int) $usuarioFormulario['tentativas_falhas']) ?></dd>
            <dt class="col-5">Bloqueado até</dt><dd class="col-7"><?= e(format_date($usuarioFormulario['bloqueado_ate'], 'd/m/Y H:i')) ?></dd>
          </dl>
        <?php else: ?>
          <p class="small text-muted mb-0">Após salvar, o usuário receberá o perfil selecionado e estará pronto para acessar o painel.</p>
        <?php endif; ?>
      </div>

      <?php if ($edicao): ?>
        <div class="card-admin mt-3">
          <h2 class="h6 mb-3">Atividade recente</h2>
          <?php if ($historico === []): ?>
            <p class="small text-muted mb-0">Nenhuma atividade registrada.</p>
          <?php else: ?>
            <?php foreach ($historico as $item): ?>
              <div class="activity-item">
                <strong><?= e((string) $item['acao']) ?> · <?= e((string) $item['modulo']) ?></strong>
                <small><?= e(format_date((string) $item['created_at'], 'd/m/Y H:i')) ?></small>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</form>
