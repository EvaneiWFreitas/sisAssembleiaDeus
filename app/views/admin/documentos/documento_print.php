<?php
$ministerio = \App\DocumentoRepository::configuracao('ministerio_nome', 'Assembleia de Deus');
$tipo = (string) ($documento['tipo'] ?? '');
$dados = \App\DocumentoRepository::dadosDocumento($documento);
$rotulo = \App\DocumentoRepository::rotuloTipo($tipo);
$nome = '';
foreach (['convidado', 'nome', 'titulo'] as $campo) {
    if (isset($dados[$campo]) && (string) $dados[$campo] !== '') {
        $nome = (string) $dados[$campo];
        break;
    }
}
$campos = [
    'Data' => isset($dados['data']) ? format_date((string) $dados['data']) : '',
    'Local' => (string) ($dados['local'] ?? ''),
    'Responsável' => (string) ($dados['pastor'] ?? $dados['pastor_responsavel'] ?? ''),
    'Cargo' => (string) ($dados['cargo'] ?? ''),
    'Assinaturas' => (string) ($dados['assinaturas'] ?? ''),
];
$campos = array_filter($campos, static fn (string $valor): bool => $valor !== '');
?>
<header class="print-top">
  <img src="<?= e(asset('img/logo-assembleia.svg')) ?>" alt="">
  <div>
    <strong><?= e($ministerio !== '' ? $ministerio : 'Assembleia de Deus') ?></strong>
    <small>sisAssembleiaDeus · Documentos emitidos</small>
  </div>
</header>

<section class="print-title">
  <h1><?= e($rotulo) ?></h1>
  <p>Registro nº <?= e((string) ($documento['numero'] ?? '')) ?> · Emitido em <?= e(format_date((string) ($documento['emitido_em'] ?? ''), 'd/m/Y H:i')) ?></p>
</section>

<div class="print-grid">
  <div class="print-field print-full">
    <span>Referência</span>
    <strong><?= e($nome !== '' ? $nome : 'Registro #' . (string) ($documento['referencia_id'] ?? '')) ?></strong>
  </div>
  <?php foreach ($campos as $rotuloCampo => $valorCampo): ?>
    <div class="print-field <?= $rotuloCampo === 'Assinaturas' ? 'print-full' : '' ?>">
      <span><?= e($rotuloCampo) ?></span>
      <strong><?= e($valorCampo) ?></strong>
    </div>
  <?php endforeach; ?>
  <?php if ((string) ($documento['modelo'] ?? '') !== ''): ?>
    <div class="print-field">
      <span>Modelo</span>
      <strong><?= e(\App\DocumentoRepository::rotuloModelo((string) $documento['modelo'])) ?></strong>
    </div>
  <?php endif; ?>
  <div class="print-field">
    <span>Emitido por</span>
    <strong><?= e((string) ($documento['emitido_por_nome'] ?? '—')) ?></strong>
  </div>
</div>

<footer class="print-footer">
  <span>sisAssembleiaDeus</span>
  <span>Documento eclesiástico · <?= e(date('d/m/Y')) ?></span>
</footer>
