<?php
$ministerio = \App\DocumentoRepository::configuracao('ministerio_nome', 'Assembleia de Deus');
$dados = isset($dados) && is_array($dados) ? $dados : [];
$dadosJuridicos = [];
foreach (\App\DocumentoRepository::dadosJuridicos() as $campo => $rotulo) {
    $valor = (string) ($dados[$campo] ?? '');
    $dadosJuridicos[$campo] = $valor !== '' ? $valor : \App\DocumentoRepository::valorJuridicoPadrao($campo);
}
$nomeTimbrado = $dadosJuridicos['razao_social'] !== ''
    ? $dadosJuridicos['razao_social']
    : ($ministerio !== '' ? $ministerio : 'Assembleia de Deus');
$enderecoPartes = [];
foreach (['ministerio_endereco', 'ministerio_numero', 'ministerio_complemento', 'ministerio_bairro', 'ministerio_cidade', 'ministerio_estado', 'ministerio_cep'] as $chaveEndereco) {
    $valorEndereco = \App\DocumentoRepository::configuracao($chaveEndereco, '');
    if ($valorEndereco !== '') {
        $enderecoPartes[] = $valorEndereco;
    }
}
$enderecoCompleto = implode(', ', $enderecoPartes);
$documentosTimbrado = [];
foreach (['cnpj' => 'CNPJ', 'inscricao_estadual' => 'IE', 'inscricao_municipal' => 'IM', 'alvara_funcionamento' => 'Alvará', 'registro_estatuto' => 'Estatuto'] as $campo => $rotuloCurto) {
    if ($dadosJuridicos[$campo] !== '') {
        $documentosTimbrado[] = $rotuloCurto . ': ' . $dadosJuridicos[$campo];
    }
}
$assinaturas = array_values(array_filter(array_map('trim', explode(',', (string) ($convite['assinaturas'] ?? ''))), static fn (string $item): bool => $item !== ''));
$modelo = preg_replace('/[^a-z]/', '', (string) ($convite['modelo'] ?? 'classico')) ?: 'classico';
?>
<style>
  .convite-paper { border: 3px double #c9a227; min-height: 560px; padding: 38px; position: relative; }
  .convite-paper.convite-moderno { border: 0; border-left: 10px solid #0b2545; }
  .convite-paper.convite-institucional { border: 1px solid #0b2545; border-top: 8px solid #0b2545; }
  .print-top .timbrado { flex: 1; }
  .timbrado-endereco { color: #475569; font-size: .72rem; margin-top: 3px; }
  .timbrado-documentos { color: #64748b; font-size: .7rem; line-height: 1.4; margin-top: 3px; }
  .print-documentos { color: #64748b; font-size: .72rem; line-height: 1.4; margin-top: 8px; }
  .convite-eyebrow { color: #64748b; font-size: .76rem; letter-spacing: .14em; text-transform: uppercase; }
  .convite-paper h1 { color: #0b2545; font-size: 1.7rem; margin: 8px 0 26px; }
  .convite-texto { font-size: 1rem; line-height: 1.7; margin: 0 0 14px; }
  .convite-destaque { font-size: 1.15rem; font-weight: 700; }
  .convite-dados { border-left: 3px solid #c9a227; margin: 24px 0; padding-left: 16px; }
  .convite-dados div { margin-bottom: 7px; }
  .convite-dados span { color: #64748b; display: inline-block; font-size: .72rem; min-width: 92px; text-transform: uppercase; }
  .convite-numero { color: #64748b; font-size: .78rem; margin-top: 30px; }
  .convite-assinaturas { display: flex; flex-wrap: wrap; gap: 42px; margin-top: 36px; }
  .convite-assinaturas div { min-width: 220px; text-align: center; }
  .convite-assinaturas span { border-top: 1px solid #0f172a; display: block; height: 30px; }
  .convite-assinaturas small { color: #64748b; }
</style>
<header class="print-top">
  <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
  <div class="timbrado">
    <strong><?= e($nomeTimbrado) ?></strong>
    <small>sisAssembleiaDeus · Correspondência eclesiástica</small>
    <?php if ($enderecoCompleto !== ''): ?>
      <div class="timbrado-endereco"><?= e($enderecoCompleto) ?></div>
    <?php endif; ?>
    <?php if ($documentosTimbrado !== []): ?>
      <div class="timbrado-documentos"><?= e(implode(' · ', $documentosTimbrado)) ?></div>
    <?php endif; ?>
  </div>
</header>

<section class="convite-paper convite-<?= e($modelo) ?>">
  <p class="convite-eyebrow">Convite pastoral</p>
  <h1>Carta-convite</h1>

  <p class="convite-texto">À</p>
  <p class="convite-texto convite-destaque"><?= e((string) ($convite['convidado'] ?? '')) ?></p>
  <?php if ((string) ($convite['igreja_convidada'] ?? '') !== ''): ?>
    <p class="convite-texto"><?= e((string) $convite['igreja_convidada']) ?></p>
  <?php endif; ?>
  <?php if ((string) ($convite['endereco'] ?? '') !== ''): ?>
    <p class="convite-texto" style="white-space: pre-line"><?= e((string) $convite['endereco']) ?></p>
  <?php endif; ?>

  <p class="convite-texto" style="margin-top: 26px">
    A paz do Senhor Jesus Cristo! Temos a alegria de convidar <?= e((string) ($convite['convidado'] ?? '')) ?>
    para participar de <strong><?= e((string) ($convite['nome_evento'] ?? '')) ?></strong>,
    promovido por <?= e($nomeTimbrado) ?>.
  </p>

  <div class="convite-dados">
    <div><span>Evento</span><strong><?= e((string) ($convite['nome_evento'] ?? '')) ?></strong></div>
    <div><span>Data</span><strong><?= e(format_date((string) ($convite['data_evento'] ?? ''), 'd/m/Y H:i')) ?></strong></div>
    <div><span>Local</span><strong><?= e((string) ($convite['local'] ?? '') !== '' ? (string) $convite['local'] : 'A ser confirmado') ?></strong></div>
    <?php if ((string) ($convite['pastor_responsavel'] ?? '') !== ''): ?>
      <div><span>Responsável</span><strong><?= e((string) $convite['pastor_responsavel']) ?></strong></div>
    <?php endif; ?>
  </div>

  <p class="convite-texto">Sua presença será uma grande bênção para nós. Aguardamos com alegria!</p>

  <div class="convite-numero">Registro nº <?= e((string) ($convite['numero'] ?? '')) ?> · Emitido em <?= e(format_date((string) ($convite['emitido_em'] ?? ''), 'd/m/Y')) ?></div>

  <div class="convite-assinaturas">
    <?php $assinaturaImagem = $assinaturaPastor ?? \App\SiteRepository::assinaturaPastor(); ?>
    <?php if ($assinaturaImagem !== ''): ?>
      <div><img src="<?= e($assinaturaImagem) ?>" alt="Assinatura do pastor" style="height:48px;max-width:100%;object-fit:contain;"><small>Pastor responsável</small></div>
    <?php elseif ($assinaturas === []): ?>
      <div><span></span><small>Pastor responsável</small></div>
    <?php else: ?>
      <?php foreach ($assinaturas as $ass): ?>
        <div><span></span><small><?= e($ass) ?></small></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<footer class="print-footer">
  <span><?= e($nomeTimbrado) ?></span>
  <span><?= e($enderecoCompleto) ?></span>
</footer>
<?php if ($documentosTimbrado !== []): ?>
  <div class="print-documentos">Documentos: <?= e(implode(' · ', $documentosTimbrado)) ?></div>
<?php endif; ?>
