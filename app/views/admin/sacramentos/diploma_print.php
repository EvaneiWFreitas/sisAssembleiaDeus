<?php
$modelo = (string) ($diploma['modelo'] ?? 'classico');
$assinaturas = array_values(array_filter(array_map('trim', explode(',', (string) ($diploma['assinaturas'] ?? ''))), static fn (string $item): bool => $item !== ''));
$numero = (string) ($diploma[$tipo === 'batismo' ? 'numero_certificado' : 'numero_diploma'] ?? '');
$nome = (string) ($diploma[$tipo === 'batismo' ? 'batizado_nome' : 'obreiro_nome'] ?? '');
$data = (string) ($diploma[$tipo === 'batismo' ? 'data_batismo' : 'data_consagracao'] ?? '');
$pastor = (string) ($diploma[$tipo === 'batismo' ? 'pastor_celebrante' : 'pastor_presidente'] ?? '');
$cargo = (string) ($diploma['cargo_ministerial'] ?? '');
?>
<header class="print-top">
  <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · Diplomas e certificados</small>
  </div>
</header>

<section class="diploma-paper diploma-<?= e(preg_replace('/[^a-z]/', '', $modelo) ?: 'classico') ?>">
  <div class="diploma-selo"><i class="fa-solid fa-cross"></i></div>
  <p class="diploma-eyebrow">Assembleia de Deus · Ministério Unção do Espírito</p>
  <h1><?= $tipo === 'batismo' ? 'Certificado de Batismo' : 'Diploma de Consagração' ?></h1>

  <p class="diploma-texto">
    <?php if ($tipo === 'batismo'): ?>
      Certificamos que
    <?php else: ?>
      Certificamos que ao obreiro
    <?php endif; ?>
  </p>
  <p class="diploma-nome"><?= e($nome) ?></p>
  <p class="diploma-texto">
    <?php if ($tipo === 'batismo'): ?>
      foi batizado(a) em águas, segundo o testemunho de nossa fé, no dia
    <?php else: ?>
      foi consagrado(a) para o cargo ministerial de <strong><?= e($cargo !== '' ? $cargo : '—') ?></strong>, no dia
    <?php endif; ?>
    <strong><?= e(format_date($data)) ?></strong>,
    <?= e((string) ($diploma['local'] ?? '') !== '' ? 'em ' . (string) $diploma['local'] : 'em nossa congregação') ?>.
  </p>
  <?php if ($pastor !== ''): ?>
    <p class="diploma-texto">Celebrado por <strong><?= e($pastor) ?></strong>.</p>
  <?php endif; ?>

  <div class="diploma-numero">Registro nº <?= e($numero !== '' ? $numero : '—') ?></div>

  <div class="diploma-assinaturas">
    <?php $assinaturaImagem = $assinaturaPastor ?? \App\SiteRepository::assinaturaPastor(); ?>
    <?php if ($assinaturaImagem !== ''): ?>
      <div><img src="<?= e($assinaturaImagem) ?>" alt="Assinatura do pastor" style="height:48px;max-width:100%;object-fit:contain;"><small>Celebrante / Presidente</small></div>
    <?php elseif ($assinaturas === []): ?>
      <div><span></span><small>Celebrante / Presidente</small></div>
    <?php else: ?>
      <?php foreach ($assinaturas as $ass): ?>
        <div><span></span><small><?= e($ass) ?></small></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<footer class="print-footer">
  <span>sisAssembleiaDeus</span>
  <span>Documento eclesiástico · <?= e(date('d/m/Y')) ?></span>
</footer>
