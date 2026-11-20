<?php
$podeExportar = \App\Auth::hasPermission('relatorios', 'exportar');
$podeImprimir = \App\Auth::hasPermission('relatorios', 'imprimir');
$base = url('/admin/modulos/relatorios');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$query = array_filter([
    'competencia' => $valorFiltro('competencia'),
    'congregacao_id' => $valorFiltro('congregacao_id'),
    'modulo' => $valorFiltro('modulo'),
], static fn (string $item): bool => $item !== '' && $item !== '0');
$queryString = $query === [] ? '' : '?' . http_build_query($query);
$moeda = ['Entradas', 'Saídas', 'Saldo'];
$formatar = static function (string $rotulo, mixed $valor) use ($moeda): string {
    if (in_array($rotulo, $moeda, true)) {
        return format_money((float) $valor);
    }
    return number_format((int) $valor, 0, ',', '.');
};
?>
<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h5 class="mb-0">Relatórios operacionais</h5>
      <div class="small text-muted">Indicadores consolidados das áreas financeira, cadastral, de conteúdo e auditoria.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?php if ($podeImprimir): ?>
        <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e($base . '/imprimir' . $queryString) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir</a>
      <?php endif; ?>
      <?php if ($podeExportar): ?>
        <a class="btn btn-sm btn-outline-success rounded-pill" href="<?= e($base . '/exportar' . $queryString) ?>"><i class="fa-solid fa-file-csv me-1"></i>Exportar CSV</a>
      <?php endif; ?>
    </div>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="competencia" aria-label="Competência">
        <option value="">Todas as competências</option>
        <?php foreach ($competencias as $competencia): ?>
          <option value="<?= e($competencia) ?>" <?= $selecionado($valorFiltro('competencia'), $competencia) ?>><?= e($competencia) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-4">
      <select class="form-select form-select-sm" name="congregacao_id" aria-label="Congregação">
        <option value="">Todas as congregações</option>
        <?php foreach ($congregacoes as $congregacao): ?>
          <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($valorFiltro('congregacao_id'), (int) $congregacao['id']) ?>><?= e((string) $congregacao['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-8 col-md-4">
      <select class="form-select form-select-sm" name="modulo" aria-label="Módulo da auditoria">
        <option value="">Todos os módulos na auditoria</option>
        <?php foreach ($modulosDisponiveis as $modulo): ?>
          <option value="<?= e($modulo) ?>" <?= $selecionado($valorFiltro('modulo'), $modulo) ?>><?= e($modulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-4 col-md-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <?php if ($query !== []): ?>
    <div class="small text-muted">Filtros ativos: competência, congregação e módulo de auditoria impactam apenas as seções compatíveis.</div>
  <?php endif; ?>
</div>

<?php foreach ($secoes as $tituloSecao => $itens): ?>
  <div class="card-admin mb-3">
    <h5 class="mb-3"><?= e($tituloSecao) ?></h5>
    <div class="row g-2">
      <?php foreach ($itens as $rotulo => $valor): ?>
        <div class="col-6 col-lg-4 col-xl-3">
          <div class="border rounded p-3 h-100">
            <div class="small text-muted"><?= e($rotulo) ?></div>
            <div class="fw-semibold fs-6"><?= e($formatar($rotulo, $valor)) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card-admin h-100">
      <h5 class="mb-3">Financeiro por categoria</h5>
      <div class="table-responsive">
        <table class="table table-sm admin-table mb-0">
          <thead><tr><th>Categoria</th><th>Tipo</th><th class="text-end">Lançamentos</th><th class="text-end">Total</th></tr></thead>
          <tbody>
            <?php if ($categorias === []): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">Nenhum lançamento para os filtros selecionados.</td></tr>
            <?php endif; ?>
            <?php foreach ($categorias as $linha): ?>
              <tr>
                <td><?= e((string) $linha['categoria']) ?></td>
                <td><span class="badge <?= (string) $linha['tipo'] === 'entrada' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (string) $linha['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></span></td>
                <td class="text-end"><?= e((int) $linha['quantidade']) ?></td>
                <td class="text-end fw-semibold"><?= e(format_money((float) $linha['total'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-admin h-100">
      <h5 class="mb-3">Auditoria por módulo</h5>
      <div class="table-responsive">
        <table class="table table-sm admin-table mb-0">
          <thead><tr><th>Módulo</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php if ($modulosAuditoria === []): ?>
              <tr><td colspan="2" class="text-center text-muted py-4">Nenhuma ação registrada.</td></tr>
            <?php endif; ?>
            <?php foreach ($modulosAuditoria as $linha): ?>
              <tr>
                <td><?= e((string) $linha['modulo']) ?></td>
                <td class="text-end fw-semibold"><?= e((int) $linha['total']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
