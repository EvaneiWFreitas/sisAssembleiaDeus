<?php
$edicao = $registro !== null;
$acao = $edicao
    ? url('/admin/modulos/sacramentos/' . $tipo . '/' . (int) $registro['id'])
    : url('/admin/modulos/sacramentos/' . $tipo);
$rotuloTipo = $tipo === 'batismo' ? 'batismo' : 'consagração';
$dataCampo = $tipo === 'batismo' ? 'data_batismo' : 'data_consagracao';
$numeroCampo = $tipo === 'batismo' ? 'numero_certificado' : 'numero_diploma';
$pessoaCampo = $tipo === 'batismo' ? 'membro_id' : 'obreiro_id';
$pastorCampo = $tipo === 'batismo' ? 'pastor_celebrante_id' : 'pastor_presidente_id';
$rotuloPessoa = $tipo === 'batismo' ? 'Membro batizado' : 'Obreiro consagrado';
$rotuloNumero = $tipo === 'batismo' ? 'Número do certificado' : 'Número do diploma';
$rotuloPastor = $tipo === 'batismo' ? 'Pastor celebrante' : 'Pastor presidente';
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$semPessoas = $tipo === 'batismo' ? $membros === [] : $obreiros === [];
?>
<form method="post" action="<?= e($acao) ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <?php if ($semPessoas): ?>
        <div class="alert alert-warning">Cadastre <?= e($tipo === 'batismo' ? 'um membro' : 'um obreiro') ?> antes de registrar este sacramento.</div>
      <?php endif; ?>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Dados do sacramento</h2>
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label" for="<?= e($pessoaCampo) ?>"><?= e($rotuloPessoa) ?></label>
            <select class="form-select <?= isset($erros[$pessoaCampo]) ? 'is-invalid' : '' ?>" id="<?= e($pessoaCampo) ?>" name="<?= e($pessoaCampo) ?>" required <?= $semPessoas ? 'disabled' : '' ?>>
              <option value="">Selecione...</option>
              <?php $opcoes = $tipo === 'batismo' ? $membros : $obreiros; ?>
              <?php foreach ($opcoes as $opcao): ?>
                <option value="<?= e((int) $opcao['id']) ?>" <?= $selecionado($opcao['id'], $valor($pessoaCampo)) ?>><?= e((string) $opcao['nome']) ?> · <?= e((string) $opcao['codigo']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo($pessoaCampo) ?>
          </div>
          <div class="col-md-5">
            <label class="form-label" for="<?= e($dataCampo) ?>">Data</label>
            <input class="form-control <?= isset($erros[$dataCampo]) ? 'is-invalid' : '' ?>" id="<?= e($dataCampo) ?>" name="<?= e($dataCampo) ?>" type="date" value="<?= e($valor($dataCampo)) ?>" required>
            <?= $erroCampo($dataCampo) ?>
          </div>
          <?php if ($tipo === 'consagracao'): ?>
            <div class="col-md-6">
              <label class="form-label" for="cargo_id">Cargo ministerial</label>
              <select class="form-select <?= isset($erros['cargo_id']) ? 'is-invalid' : '' ?>" id="cargo_id" name="cargo_id" required <?= $cargos === [] ? 'disabled' : '' ?>>
                <option value="">Selecione...</option>
                <?php foreach ($cargos as $cargo): ?>
                  <option value="<?= e((int) $cargo['id']) ?>" <?= $selecionado($cargo['id'], $valor('cargo_id')) ?>><?= e((string) $cargo['nome']) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $erroCampo('cargo_id') ?>
            </div>
          <?php endif; ?>
          <div class="col-md-6">
            <label class="form-label" for="local">Local</label>
            <input class="form-control <?= isset($erros['local']) ? 'is-invalid' : '' ?>" id="local" name="local" value="<?= e($valor('local')) ?>" maxlength="200">
            <?= $erroCampo('local') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="<?= e($pastorCampo) ?>"><?= e($rotuloPastor) ?></label>
            <select class="form-select <?= isset($erros[$pastorCampo]) ? 'is-invalid' : '' ?>" id="<?= e($pastorCampo) ?>" name="<?= e($pastorCampo) ?>">
              <option value="">Não informado</option>
              <?php foreach ($obreiros as $obreiro): ?>
                <option value="<?= e((int) $obreiro['id']) ?>" <?= $selecionado($obreiro['id'], $valor($pastorCampo)) ?>><?= e((string) $obreiro['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo($pastorCampo) ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="<?= e($numeroCampo) ?>"><?= e($rotuloNumero) ?></label>
            <input class="form-control <?= isset($erros[$numeroCampo]) ? 'is-invalid' : '' ?>" id="<?= e($numeroCampo) ?>" name="<?= e($numeroCampo) ?>" value="<?= e($valor($numeroCampo)) ?>" maxlength="60">
            <?= $erroCampo($numeroCampo) ?>
          </div>
        </div>
      </div>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Observações</h2>
        <textarea class="form-control <?= isset($erros['observacoes']) ? 'is-invalid' : '' ?>" id="observacoes" name="observacoes" rows="4" maxlength="1000"><?= e($valor('observacoes')) ?></textarea>
        <?= $erroCampo('observacoes') ?>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-dark rounded-pill" type="submit" <?= $semPessoas || ($tipo === 'consagracao' && $cargos === []) ? 'disabled' : '' ?>><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/sacramentos?tipo=' . $tipo)) ?>">Voltar</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin">
        <h2 class="h6 mb-3">Resumo</h2>
        <dl class="row small mb-3">
          <dt class="col-6">Tipo</dt><dd class="col-6 text-end"><?= e(ucfirst($rotuloTipo)) ?></dd>
          <dt class="col-6">Número sugerido</dt><dd class="col-6 text-end"><?= e($valor($numeroCampo) !== '' ? $valor($numeroCampo) : '—') ?></dd>
        </dl>
        <?php if ($edicao): ?>
          <dl class="row small mb-3">
            <dt class="col-6">Criado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $registro['created_at'], 'd/m/Y H:i')) ?></dd>
            <dt class="col-6">Atualizado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $registro['updated_at'], 'd/m/Y H:i')) ?></dd>
          </dl>
          <?php if (\App\Auth::hasPermission('sacramentos', 'editar')): ?>
            <div class="d-grid">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/modulos/sacramentos/' . $tipo . '/' . (int) $registro['id'] . '/diploma')) ?>"><i class="fa-solid fa-award me-1"></i>Emitir diploma</a>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-info small mb-0">O número sugerido pode ser alterado livremente antes de salvar.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</form>
