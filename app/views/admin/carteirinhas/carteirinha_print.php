<?php
$ministerio = \App\DocumentoRepository::configuracao('ministerio_nome', 'Assembleia de Deus');
$cidade = \App\DocumentoRepository::configuracao('ministerio_cidade', '');
$estado = \App\DocumentoRepository::configuracao('ministerio_estado', '');
$modelo = preg_replace('/[^a-z]/', '', (string) ($carteirinha['modelo'] ?? 'classico')) ?: 'classico';
$codigo = (string) ($carteirinha['codigo_validacao'] ?? '');
$validade = (string) ($carteirinha['validade'] ?? '');
?>
<style>
  .carteirinha-paper { border: 2px solid #0b2545; min-height: 300px; padding: 24px; position: relative; }
  .carteirinha-paper.carteirinha-moderno { border-color: #c9a227; border-width: 4px; }
  .carteirinha-paper.carteirinha-horizontal { min-height: 240px; }
  .carteirinha-paper.carteirinha-vertical { min-height: 420px; text-align: center; }
  .carteirinha-paper.carteirinha-institucional { border-top: 12px solid #0b2545; }
  .carteirinha-paper.carteirinha-qrcode { border-color: #0b2545; border-width: 3px; }
  .carteirinha-cabecalho { align-items: center; border-bottom: 1px solid #cbd5e1; display: flex; gap: 12px; padding-bottom: 12px; }
  .carteirinha-cabecalho img { height: 42px; width: 42px; }
  .carteirinha-cabecalho strong { color: #0b2545; display: block; font-size: 1rem; }
  .carteirinha-cabecalho small { color: #64748b; letter-spacing: .08em; text-transform: uppercase; }
  .carteirinha-titulo { color: #0b2545; font-size: 1.2rem; margin: 18px 0 4px; }
  .carteirinha-subtitulo { color: #64748b; font-size: .78rem; margin-bottom: 18px; }
  .carteirinha-nome { font-size: 1.4rem; font-weight: 700; margin-bottom: 4px; }
  .carteirinha-linha { border-bottom: 1px solid #e2e8f0; display: flex; gap: 10px; margin-bottom: 8px; padding-bottom: 8px; }
  .carteirinha-linha span { color: #64748b; font-size: .7rem; min-width: 92px; text-transform: uppercase; }
  .carteirinha-linha strong { font-size: .9rem; }
  .carteirinha-codigo { bottom: 20px; color: #64748b; font-size: .72rem; position: absolute; right: 24px; text-align: right; }
  .carteirinha-qr { align-items: center; bottom: 18px; display: flex; flex-direction: column; gap: 6px; position: absolute; right: 24px; }
  .carteirinha-qr #qrcode { background: #fff; height: 92px; padding: 5px; width: 92px; }
  .carteirinha-qr small { color: #64748b; font-size: .62rem; }
  .carteirinha-assinatura { border-top: 1px solid #0f172a; bottom: 22px; left: 24px; min-width: 220px; padding-top: 4px; position: absolute; text-align: center; }
  .carteirinha-assinatura span { color: #64748b; display: block; font-size: .64rem; }
</style>

<section class="carteirinha-paper carteirinha-<?= e($modelo) ?>">
  <header class="carteirinha-cabecalho">
    <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
    <div>
      <strong><?= e($ministerio !== '' ? $ministerio : 'Assembleia de Deus') ?></strong>
      <small>Carteirinha de <?= e(\App\CarteirinhaRepository::rotuloTipo((string) ($carteirinha['tipo'] ?? 'membro'))) ?></small>
    </div>
  </header>

  <h1 class="carteirinha-titulo">Identificação eclesiástica</h1>
  <p class="carteirinha-subtitulo">Documento pessoal intransferível</p>

  <div class="carteirinha-nome"><?= e((string) ($carteirinha['pessoa_nome'] ?? '—')) ?></div>
  <div class="carteirinha-linha"><span>Código</span><strong><?= e((string) $carteirinha['pessoa_codigo']) ?></strong></div>
  <div class="carteirinha-linha"><span>Congregação</span><strong><?= e((string) ($carteirinha['congregacao_nome'] ?? '—')) ?></strong></div>
  <div class="carteirinha-linha"><span>Validade</span><strong><?= e($validade !== '' ? format_date($validade) : 'Indeterminada') ?></strong></div>
  <div class="carteirinha-linha"><span>Via</span><strong><?= (int) ($carteirinha['segunda_via'] ?? 0) === 1 ? 'Segunda via' : 'Primeira via' ?></strong></div>

  <?php if ($modelo === 'qrcode'): ?>
    <div class="carteirinha-qr">
      <div id="qrcode" data-codigo="<?= e($codigo) ?>"></div>
      <small><?= e($codigo) ?></small>
    </div>
  <?php else: ?>
    <div class="carteirinha-codigo">Validação: <?= e($codigo) ?></div>
  <?php endif; ?>

  <div class="carteirinha-assinatura">
    <?php $assinaturaImagem = $assinaturaPastor ?? \App\SiteRepository::assinaturaPastor(); ?>
    <?php if ($assinaturaImagem !== ''): ?>
      <img src="<?= e($assinaturaImagem) ?>" alt="Assinatura do pastor" style="height:34px;max-width:100%;object-fit:contain;">
    <?php endif; ?>
    <span>Pastor responsável</span>
  </div>
</section>

<footer class="print-footer">
  <span><?= e($ministerio !== '' ? $ministerio : 'Assembleia de Deus') ?><?= $cidade !== '' ? ' · ' . e($cidade) : '' ?></span>
  <span><?= e($estado) ?> · Emitida em <?= e(format_date((string) ($carteirinha['data_emissao'] ?? ''), 'd/m/Y')) ?></span>
</footer>

<?php if ($modelo === 'qrcode'): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    window.addEventListener('load', () => {
      const alvo = document.getElementById('qrcode');
      if (!alvo || typeof QRCode !== 'function') { return; }
      new QRCode(alvo, { text: alvo.dataset.codigo, width: 92, height: 92, colorDark: '#0b2545', colorLight: '#ffffff' });
    });
  </script>
<?php endif; ?>
