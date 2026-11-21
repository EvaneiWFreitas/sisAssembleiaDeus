<?php
$competencia = (string) ($filtros['competencia'] ?? '');
$tipoFiltro = (string) ($filtros['tipo'] ?? '');
$tituloFiltro = $competencia !== '' ? 'Competência ' . $competencia : 'Todas as competências';
if ($tipoFiltro === 'entrada') {
    $tituloFiltro .= ' · Entradas';
} elseif ($tipoFiltro === 'saida') {
    $tituloFiltro .= ' · Saídas';
}
?>
<header class="print-top">
  <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · Relatório financeiro</small>
  </div>
</header>

<section class="diploma-paper">
  <p class="diploma-eyebrow">Assembleia de Deus · Ministério Unção do Espírito</p>
  <h1>Relatório Financeiro</h1>
  <p class="text-center text-muted mb-4"><?= e($tituloFiltro) ?></p>

  <div class="row g-3 mb-4">
    <div class="col-3 text-center"><strong>Lançamentos</strong><div><?= e((string) ($estatisticas['total'] ?? 0)) ?></div></div>
    <div class="col-3 text-center"><strong>Entradas</strong><div><?= e(format_money((float) ($estatisticas['entradas'] ?? 0))) ?></div></div>
    <div class="col-3 text-center"><strong>Saídas</strong><div><?= e(format_money((float) ($estatisticas['saidas'] ?? 0))) ?></div></div>
    <div class="col-3 text-center"><strong>Saldo</strong><div><?= e(format_money((float) ($estatisticas['saldo'] ?? 0))) ?></div></div>
  </div>

  <table class="table table-sm">
    <thead><tr><th>Tipo</th><th>Categoria</th><th class="text-end">Lançamentos</th><th class="text-end">Total</th></tr></thead>
    <tbody>
      <?php if ($relatorio === []): ?>
        <tr><td colspan="4" class="text-center text-muted">Nenhum dado para o filtro selecionado.</td></tr>
      <?php endif; ?>
      <?php foreach ($relatorio as $linha): ?>
        <tr>
          <td><?= (string) $linha['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></td>
          <td><?= e((string) $linha['categoria_nome']) ?></td>
          <td class="text-end"><?= e((int) $linha['quantidade']) ?></td>
          <td class="text-end"><?= e(format_money((float) $linha['total'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<footer class="print-footer">
  <span>sisAssembleiaDeus</span>
  <span>Documento gerado em <?= e(date('d/m/Y H:i')) ?></span>
</footer>
