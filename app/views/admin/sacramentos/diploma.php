<?php
$acao = url('/admin/modulos/sacramentos/' . $tipo . '/' . (int) $registro['id'] . '/diploma');
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$numeroCampo = $tipo === 'batismo' ? 'numero_certificado' : 'numero_diploma';
$dataCampo = $tipo === 'batismo' ? 'data_batismo' : 'data_consagracao';
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card-admin mb-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h2 class="h6 mb-1"><?= e((string) $registro['pessoa_nome']) ?></h2>
          <p class="small text-muted mb-0">
            <?= e(ucfirst($tipo === 'batismo' ? 'batismo' : 'consagração')) ?> em
            <?= e(format_date((string) $registro[$dataCampo])) ?> ·
            <?= e((string) ($registro[$numeroCampo] ?? '') !== '' ? (string) $registro[$numeroCampo] : 'Sem número') ?>
          </p>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/sacramentos?tipo=' . $tipo)) ?>">Voltar</a>
      </div>
    </div>

    <form class="card-admin" method="post" action="<?= e($acao) ?>">
      <?= csrf_field() ?>
      <h2 class="h6 mb-3">Configuração do diploma</h2>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="modelo">Modelo</label>
          <select class="form-select <?= isset($erros['modelo']) ? 'is-invalid' : '' ?>" id="modelo" name="modelo" required>
            <option value="classico" <?= $selecionado($valor('modelo'), 'classico') ?>>Clássico</option>
            <option value="moderno" <?= $selecionado($valor('modelo'), 'moderno') ?>>Moderno</option>
            <option value="institucional" <?= $selecionado($valor('modelo'), 'institucional') ?>>Institucional</option>
          </select>
          <?= $erroCampo('modelo') ?>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="assinaturas">Assinaturas</label>
          <input class="form-control <?= isset($erros['assinaturas']) ? 'is-invalid' : '' ?>" id="assinaturas" name="assinaturas" value="<?= e($valor('assinaturas')) ?>" maxlength="1000" placeholder="Ex.: Pastor Presidente, Secretário(a)">
          <?= $erroCampo('assinaturas') ?>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-dark rounded-pill" type="submit"><i class="fa-solid fa-award me-1"></i><?= $diploma === null ? 'Emitir diploma' : 'Reemitir diploma' ?></button>
        <?php if ($diploma !== null): ?>
          <a class="btn btn-outline-primary rounded-pill" href="<?= e(url('/admin/modulos/sacramentos/' . $tipo . '/' . (int) $registro['id'] . '/diploma/imprimir')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir atual</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
