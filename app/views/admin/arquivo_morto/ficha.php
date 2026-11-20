<header class="print-top">
  <img src="<?= e(asset('img/logo-assembleia.svg')) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · Arquivo Morto</small>
  </div>
</header>

<div class="print-title">
  <h1>Ficha histórica do Arquivo Morto</h1>
  <p>Documento gerado em <?= e(date('d/m/Y H:i')) ?></p>
</div>

<?php if ((string) ($registro['foto'] ?? '') !== ''): ?>
  <img src="<?= e(upload_url((string) $registro['foto'])) ?>" alt="Foto do obreiro" style="border-radius:8px;float:right;height:96px;object-fit:cover;width:96px;">
<?php endif; ?>

<div class="print-grid">
  <div class="print-field print-full"><span>Obreiro</span><strong><?= e((string) $registro['nome']) ?></strong></div>
  <div class="print-field"><span>Código</span><strong><?= e((string) $registro['codigo']) ?></strong></div>
  <div class="print-field"><span>Situação</span><strong><?= e(ucfirst((string) $registro['situacao'])) ?></strong></div>
  <div class="print-field"><span>Cargo</span><strong><?= e((string) ($registro['cargo_nome'] ?? '') !== '' ? (string) $registro['cargo_nome'] : '—') ?></strong></div>
  <div class="print-field"><span>Congregação</span><strong><?= e((string) ($registro['congregacao_nome'] ?? '') !== '' ? (string) $registro['congregacao_nome'] : '—') ?></strong></div>
  <div class="print-field"><span>Data do desligamento</span><strong><?= e(format_date((string) $registro['data_desligamento'])) ?></strong></div>
  <div class="print-field"><span>Responsável pelo registro</span><strong><?= e((string) ($registro['responsavel_nome'] ?? '') !== '' ? (string) $registro['responsavel_nome'] : '—') ?></strong></div>
  <div class="print-field"><span>Restaurado em</span><strong><?= e($registro['restaurado_em'] !== null ? format_date((string) $registro['restaurado_em'], 'd/m/Y H:i') : 'Não restaurado') ?></strong></div>
  <div class="print-field print-full"><span>Motivo</span><strong><?= e((string) $registro['motivo']) ?></strong></div>
  <div class="print-field print-full"><span>Observações</span><strong><?= e((string) ($registro['observacoes'] ?? '') !== '' ? (string) $registro['observacoes'] : '—') ?></strong></div>
</div>

<footer class="print-footer">
  <span>sisAssembleiaDeus</span>
  <span>Documento interno · Arquivo Morto</span>
</footer>
