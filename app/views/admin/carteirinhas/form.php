<?php
$edicao = $carteirinha !== null;
$acao = $edicao ? url('/admin/modulos/carteirinhas/' . (int) $carteirinha['id']) : url('/admin/modulos/carteirinhas');
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$tipo = $valor('tipo') !== '' ? $valor('tipo') : 'membro';
$referenciaId = $valor('referencia_id');
$modelo = $valor('modelo') !== '' ? $valor('modelo') : 'classico';
$ministerio = \App\DocumentoRepository::configuracao('ministerio_nome', 'Assembleia de Deus');
?>
<form method="post" action="<?= e($acao) ?>" id="form-carteirinha">
  <?= csrf_field() ?>
  <input type="hidden" id="referencia_id" name="referencia_id" value="<?= e($referenciaId) ?>">
  <input type="hidden" name="segunda_via" value="0">

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card-admin mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <h2 class="h6 mb-0">Dados da carteirinha</h2>
          <?php if ($edicao): ?>
            <span class="badge text-bg-light"><?= e((string) $carteirinha['codigo_validacao']) ?></span>
          <?php endif; ?>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="tipo">Tipo</label>
            <select class="form-select <?= isset($erros['tipo']) ? 'is-invalid' : '' ?>" id="tipo" name="tipo" required>
              <?php foreach (\App\CarteirinhaRepository::TIPOS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($tipo, $chave) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('tipo') ?>
          </div>
          <div class="col-md-8">
            <label class="form-label" for="membro_id">Pessoa</label>
            <select class="form-select <?= isset($erros['referencia_id']) ? 'is-invalid' : '' ?>" id="membro_id" <?= $tipo !== 'membro' ? 'hidden' : '' ?> <?= $membros === [] ? 'disabled' : '' ?>>
              <option value="">Selecione o membro...</option>
              <?php foreach ($membros as $membro): ?>
                <option value="<?= e((int) $membro['id']) ?>" <?= $selecionado($referenciaId, (int) $membro['id']) ?>><?= e((string) $membro['nome']) ?> · <?= e((string) $membro['codigo']) ?></option>
              <?php endforeach; ?>
            </select>
            <select class="form-select <?= isset($erros['referencia_id']) ? 'is-invalid' : '' ?>" id="obreiro_id" <?= $tipo !== 'obreiro' ? 'hidden' : '' ?> <?= $obreiros === [] ? 'disabled' : '' ?>>
              <option value="">Selecione o obreiro...</option>
              <?php foreach ($obreiros as $obreiro): ?>
                <option value="<?= e((int) $obreiro['id']) ?>" <?= $selecionado($referenciaId, (int) $obreiro['id']) ?>><?= e((string) $obreiro['nome']) ?> · <?= e((string) $obreiro['codigo']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('referencia_id') ?>
            <?php if ($membros === [] && $obreiros === []): ?>
              <div class="form-text text-danger">Cadastre um membro ou obreiro antes de emitir.</div>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="modelo">Modelo</label>
            <select class="form-select <?= isset($erros['modelo']) ? 'is-invalid' : '' ?>" id="modelo" name="modelo" required>
              <?php foreach (\App\CarteirinhaRepository::MODELOS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($modelo, $chave) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('modelo') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="validade">Validade</label>
            <input class="form-control <?= isset($erros['validade']) ? 'is-invalid' : '' ?>" id="validade" name="validade" type="date" value="<?= e($valor('validade')) ?>">
            <?= $erroCampo('validade') ?>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" id="segunda_via" name="segunda_via" type="checkbox" value="1" <?= $valor('segunda_via') === '1' ? 'checked' : '' ?>>
              <label class="form-check-label" for="segunda_via">Segunda via</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Emissão</h2>
        <div class="small text-muted mb-3">
          Instituição: <strong><?= e($ministerio !== '' ? $ministerio : 'Assembleia de Deus') ?></strong>
        </div>
        <?php if ($edicao): ?>
          <div class="small text-muted">
            <div><strong>Emitida em:</strong> <?= e(format_date((string) $carteirinha['data_emissao'], 'd/m/Y H:i')) ?></div>
            <div><strong>Por:</strong> <?= e((string) ($carteirinha['emitida_por_nome'] ?? '—')) ?></div>
          </div>
        <?php else: ?>
          <div class="alert alert-info small mb-0">O código de validação será gerado automaticamente.</div>
        <?php endif; ?>
      </div>

      <div class="d-grid gap-2">
        <button class="btn btn-dark rounded-pill" type="submit"><i class="fa-solid fa-id-card me-1"></i><?= $edicao ? 'Salvar carteirinha' : 'Emitir carteirinha' ?></button>
        <?php if ($edicao): ?>
          <a class="btn btn-outline-primary rounded-pill" href="<?= e(url('/admin/modulos/carteirinhas/' . (int) $carteirinha['id'] . '/imprimir')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir atual</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/carteirinhas')) ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
<script>
  (() => {
    const tipo = document.getElementById('tipo');
    const membro = document.getElementById('membro_id');
    const obreiro = document.getElementById('obreiro_id');
    const referencia = document.getElementById('referencia_id');
    const sincronizar = () => {
      const atual = tipo.value === 'obreiro' ? obreiro : membro;
      membro.hidden = tipo.value !== 'membro';
      obreiro.hidden = tipo.value !== 'obreiro';
      referencia.value = atual.value;
    };
    tipo.addEventListener('change', sincronizar);
    membro.addEventListener('change', sincronizar);
    obreiro.addEventListener('change', sincronizar);
    sincronizar();
  })();
</script>
