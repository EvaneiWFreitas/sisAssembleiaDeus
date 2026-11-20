<?php
$edicao = $convite !== null;
$acao = $edicao ? url('/admin/modulos/documentos/' . (int) $convite['id']) : url('/admin/modulos/documentos');
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$pastorPadrao = \App\DocumentoRepository::configuracao('pastor_presidente', '');
?>
<form method="post" action="<?= e($acao) ?>" id="form-documento">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card-admin mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <h2 class="h6 mb-0">Dados do convite</h2>
          <?php if ($edicao): ?>
            <span class="badge text-bg-light">Registro nº <?= e((string) $convite['numero']) ?></span>
          <?php endif; ?>
        </div>
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label" for="convidado">Convidado</label>
            <input class="form-control <?= isset($erros['convidado']) ? 'is-invalid' : '' ?>" id="convidado" name="convidado" value="<?= e($valor('convidado')) ?>" maxlength="180" required>
            <?= $erroCampo('convidado') ?>
          </div>
          <div class="col-md-5">
            <label class="form-label" for="igreja_convidada">Igreja convidada</label>
            <input class="form-control <?= isset($erros['igreja_convidada']) ? 'is-invalid' : '' ?>" id="igreja_convidada" name="igreja_convidada" value="<?= e($valor('igreja_convidada')) ?>" maxlength="180">
            <?= $erroCampo('igreja_convidada') ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="endereco">Endereço do convidado</label>
            <textarea class="form-control <?= isset($erros['endereco']) ? 'is-invalid' : '' ?>" id="endereco" name="endereco" rows="2" maxlength="1000"><?= e($valor('endereco')) ?></textarea>
            <?= $erroCampo('endereco') ?>
          </div>
          <div class="col-md-7">
            <label class="form-label" for="nome_evento">Nome do evento</label>
            <input class="form-control <?= isset($erros['nome_evento']) ? 'is-invalid' : '' ?>" id="nome_evento" name="nome_evento" value="<?= e($valor('nome_evento')) ?>" maxlength="180" required>
            <?= $erroCampo('nome_evento') ?>
          </div>
          <div class="col-md-5">
            <label class="form-label" for="data_evento">Data e hora do evento</label>
            <input class="form-control <?= isset($erros['data_evento']) ? 'is-invalid' : '' ?>" id="data_evento" name="data_evento" type="datetime-local" value="<?= e($valor('data_evento')) ?>" required>
            <?= $erroCampo('data_evento') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="local">Local</label>
            <input class="form-control <?= isset($erros['local']) ? 'is-invalid' : '' ?>" id="local" name="local" value="<?= e($valor('local')) ?>" maxlength="200" placeholder="Ex.: Templo central">
            <?= $erroCampo('local') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="pastor_responsavel">Pastor responsável</label>
            <input class="form-control <?= isset($erros['pastor_responsavel']) ? 'is-invalid' : '' ?>" id="pastor_responsavel" name="pastor_responsavel" value="<?= e($valor('pastor_responsavel') !== '' ? $valor('pastor_responsavel') : $pastorPadrao) ?>" maxlength="180">
            <?= $erroCampo('pastor_responsavel') ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Emissão</h2>
        <div class="mb-3">
          <label class="form-label" for="modelo">Modelo</label>
          <select class="form-select <?= isset($erros['modelo']) ? 'is-invalid' : '' ?>" id="modelo" name="modelo" required>
            <?php foreach (\App\DocumentoRepository::MODELOS as $chave => $rotulo): ?>
              <option value="<?= e($chave) ?>" <?= $selecionado($valor('modelo') !== '' ? $valor('modelo') : 'classico', $chave) ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
          <?= $erroCampo('modelo') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="assinaturas">Assinaturas</label>
          <textarea class="form-control <?= isset($erros['assinaturas']) ? 'is-invalid' : '' ?>" id="assinaturas" name="assinaturas" rows="3" maxlength="1000" placeholder="Ex.: Pastor Presidente, Secretário(a)"><?= e($valor('assinaturas')) ?></textarea>
          <div class="form-text">Separe os nomes por vírgula para exibir mais de uma assinatura.</div>
          <?= $erroCampo('assinaturas') ?>
        </div>
        <?php if ($edicao): ?>
          <div class="small text-muted">
            <div><strong>Emitido em:</strong> <?= e(format_date((string) $convite['emitido_em'], 'd/m/Y H:i')) ?></div>
            <div><strong>Por:</strong> <?= e((string) ($convite['emitido_por_nome'] ?? '—')) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <div class="d-grid gap-2">
        <button class="btn btn-dark rounded-pill" type="submit"><i class="fa-solid fa-paper-plane me-1"></i><?= $edicao ? 'Salvar e reemitir' : 'Emitir convite' ?></button>
        <?php if ($edicao): ?>
          <a class="btn btn-outline-primary rounded-pill" href="<?= e(url('/admin/modulos/documentos/' . (int) $convite['id'] . '/imprimir')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir atual</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/documentos')) ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
