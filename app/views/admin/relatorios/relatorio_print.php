<?php
$competencia = (string) ($filtros['competencia'] ?? '');
$congregacaoId = (int) ($filtros['congregacao_id'] ?? 0);
$modulo = (string) ($filtros['modulo'] ?? '');
$nomeCongregacao = 'Todas';
foreach ($congregacoes as $congregacao) {
    if ((int) $congregacao['id'] === $congregacaoId) {
        $nomeCongregacao = (string) $congregacao['nome'];
        break;
    }
}
$tituloFiltro = 'Competência ' . ($competencia !== '' ? $competencia : 'todas') . ' · Congregação ' . $nomeCongregacao;
if ($modulo !== '') {
    $tituloFiltro .= ' · Módulo ' . $modulo;
}
$moeda = ['Entradas', 'Saídas', 'Saldo'];
$formatar = static function (string $rotulo, mixed $valor) use ($moeda): string {
    if (in_array($rotulo, $moeda, true)) {
        return format_money((float) $valor);
    }
    return number_format((int) $valor, 0, ',', '.');
};
?>
<header class="print-top">
  <img src="<?= e(asset('img/logo-assembleia.svg')) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · Relatórios operacionais</small>
  </div>
</header>

<section class="diploma-paper">
  <p class="diploma-eyebrow">Assembleia de Deus · Ministério Unção do Espírito</p>
  <h1>Relatório Operacional</h1>
  <p class="text-center text-muted mb-4"><?= e($tituloFiltro) ?></p>

  <?php foreach ($secoes as $tituloSecao => $itens): ?>
    <h2 class="h6 mt-4 mb-2"><?= e($tituloSecao) ?></h2>
    <table class="table table-sm">
      <tbody>
        <?php foreach ($itens as $rotulo => $valor): ?>
          <tr><th><?= e($rotulo) ?></th><td class="text-end"><?= e($formatar($rotulo, $valor)) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>

  <h2 class="h6 mt-4 mb-2">Financeiro por categoria</h2>
  <table class="table table-sm">
    <thead><tr><th>Categoria</th><th>Tipo</th><th class="text-end">Lançamentos</th><th class="text-end">Total</th></tr></thead>
    <tbody>
      <?php if ($categorias === []): ?>
        <tr><td colspan="4" class="text-center text-muted">Nenhum lançamento.</td></tr>
      <?php endif; ?>
      <?php foreach ($categorias as $linha): ?>
        <tr>
          <td><?= e((string) $linha['categoria']) ?></td>
          <td><?= (string) $linha['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></td>
          <td class="text-end"><?= e((int) $linha['quantidade']) ?></td>
          <td class="text-end"><?= e(format_money((float) $linha['total'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 class="h6 mt-4 mb-2">Auditoria por módulo</h2>
  <table class="table table-sm">
    <thead><tr><th>Módulo</th><th class="text-end">Ações</th></tr></thead>
    <tbody>
      <?php if ($modulosAuditoria === []): ?>
        <tr><td colspan="2" class="text-center text-muted">Nenhuma ação registrada.</td></tr>
      <?php endif; ?>
      <?php foreach ($modulosAuditoria as $linha): ?>
        <tr><td><?= e((string) $linha['modulo']) ?></td><td class="text-end"><?= e((int) $linha['total']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<footer class="print-footer">
  <span>sisAssembleiaDeus</span>
  <span>Documento gerado em <?= e(date('d/m/Y H:i')) ?></span>
</footer>
