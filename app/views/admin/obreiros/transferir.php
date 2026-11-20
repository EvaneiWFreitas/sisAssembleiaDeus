<?php
$acao = url('/admin/modulos/obreiros/' . (int) $obreiro['id'] . '/transferir');
$selecionado = static function (mixed $valorCampo, mixed $atual): string {
    return (string) $valorCampo === (string) $atual ? 'selected' : '';
};
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card-admin mb-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h2 class="h6 mb-1"><?= e((string) $obreiro['nome']) ?></h2>
          <p class="small text-muted mb-0">
            Código <?= e((string) $obreiro['codigo']) ?> · Cargo <?= e((string) $obreiro['cargo_nome']) ?> ·
            Congregação atual <?= e((string) $obreiro['congregacao_nome']) ?>
          </p>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/obreiros')) ?>">Voltar</a>
      </div>
    </div>

    <form class="card-admin" method="post" action="<?= e($acao) ?>">
      <?= csrf_field() ?>
      <h2 class="h6 mb-3">Dados da transferência</h2>

      <?php if ($congregacoes === []): ?>
        <div class="alert alert-warning">Cadastre ao menos uma congregação para registrar uma transferência.</div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="congregacao_atual">Congregação atual</label>
        <input class="form-control" id="congregacao_atual" value="<?= e((string) $obreiro['congregacao_nome']) ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label" for="congregacao_id">Nova congregação</label>
        <select class="form-select <?= isset($erros['congregacao_id']) ? 'is-invalid' : '' ?>" id="congregacao_id" name="congregacao_id" required <?= $congregacoes === [] ? 'disabled' : '' ?>>
          <option value="">Selecione...</option>
          <?php foreach ($congregacoes as $congregacao): ?>
            <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($congregacao['id'], $valores['congregacao_id'] ?? '') ?>><?= e((string) $congregacao['nome']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($erros['congregacao_id'])): ?><div class="invalid-feedback d-block"><?= e($erros['congregacao_id']) ?></div><?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label" for="descricao">Descrição (opcional)</label>
        <textarea class="form-control <?= isset($erros['descricao']) ? 'is-invalid' : '' ?>" id="descricao" name="descricao" rows="3" maxlength="500"><?= e((string) ($valores['descricao'] ?? '')) ?></textarea>
        <?php if (isset($erros['descricao'])): ?><div class="invalid-feedback d-block"><?= e($erros['descricao']) ?></div><?php endif; ?>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-dark rounded-pill" type="submit" <?= $congregacoes === [] ? 'disabled' : '' ?>><i class="fa-solid fa-arrows-rotate me-1"></i>Registrar transferência</button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/obreiros/' . (int) $obreiro['id'] . '/editar')) ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>
