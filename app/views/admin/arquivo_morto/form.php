<?php
$acao = url('/admin/modulos/arquivo_morto/arquivar/' . (int) $obreiro['id']);
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
?>
<form method="post" action="<?= e($acao) ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Dados do arquivamento</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="data_desligamento">Data do desligamento</label>
            <input class="form-control <?= isset($erros['data_desligamento']) ? 'is-invalid' : '' ?>" id="data_desligamento" name="data_desligamento" type="date" value="<?= e($valor('data_desligamento')) ?>" required>
            <?= $erroCampo('data_desligamento') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="situacao">Nova situação</label>
            <select class="form-select <?= isset($erros['situacao']) ? 'is-invalid' : '' ?>" id="situacao" name="situacao" required>
              <option value="">Selecione...</option>
              <option value="licenciado" <?= $selecionado($valor('situacao'), 'licenciado') ?>>Licenciado</option>
              <option value="desligado" <?= $selecionado($valor('situacao'), 'desligado') ?>>Desligado</option>
            </select>
            <?= $erroCampo('situacao') ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="motivo">Motivo</label>
            <textarea class="form-control <?= isset($erros['motivo']) ? 'is-invalid' : '' ?>" id="motivo" name="motivo" rows="4" maxlength="1000" required><?= e($valor('motivo')) ?></textarea>
            <?= $erroCampo('motivo') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="responsavel_id">Responsável pelo registro</label>
            <select class="form-select <?= isset($erros['responsavel_id']) ? 'is-invalid' : '' ?>" id="responsavel_id" name="responsavel_id">
              <?php foreach ($responsaveis as $usuario): ?>
                <option value="<?= e((int) $usuario['id']) ?>" <?= $selecionado($valor('responsavel_id'), $usuario['id']) ?>><?= e((string) $usuario['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('responsavel_id') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="observacoes">Observações</label>
            <input class="form-control <?= isset($erros['observacoes']) ? 'is-invalid' : '' ?>" id="observacoes" name="observacoes" value="<?= e($valor('observacoes')) ?>" maxlength="1000">
            <?= $erroCampo('observacoes') ?>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-dark rounded-pill" type="submit"><i class="fa-solid fa-box-archive me-1"></i>Confirmar arquivamento</button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/arquivo_morto')) ?>">Cancelar</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin">
        <h2 class="h6 mb-3">Obreiro selecionado</h2>
        <div class="d-flex align-items-center gap-3 mb-3">
          <?php if ((string) ($obreiro['foto'] ?? '') !== ''): ?>
            <img class="admin-thumb-lg" src="<?= e(upload_url((string) $obreiro['foto'])) ?>" alt="Foto do obreiro">
          <?php else: ?>
            <span class="admin-avatar admin-avatar-lg"><i class="fa-solid fa-user-tie"></i></span>
          <?php endif; ?>
          <div>
            <strong class="d-block"><?= e((string) $obreiro['nome']) ?></strong>
            <small class="text-muted"><?= e((string) $obreiro['codigo']) ?></small>
          </div>
        </div>
        <dl class="row small mb-0">
          <dt class="col-6">Cargo</dt><dd class="col-6 text-end"><?= e((string) ($obreiro['cargo_nome'] ?? '')) ?></dd>
          <dt class="col-6">Congregação</dt><dd class="col-6 text-end"><?= e((string) ($obreiro['congregacao_nome'] ?? '')) ?></dd>
          <dt class="col-6">Situação atual</dt><dd class="col-6 text-end"><?= e(ucfirst((string) $obreiro['situacao'])) ?></dd>
        </dl>
        <div class="alert alert-warning small mt-3 mb-0">Ao arquivar, a situação do obreiro será atualizada no cadastro principal.</div>
      </div>
    </div>
  </div>
</form>
