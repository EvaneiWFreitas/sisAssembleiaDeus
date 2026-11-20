<?php
$base = url('/admin/modulos/financeiro');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$query = array_filter([
    'tipo' => $valorFiltro('tipo'),
    'categoria_id' => $valorFiltro('categoria_id'),
    'congregacao_id' => $valorFiltro('congregacao_id'),
    'competencia' => $valorFiltro('competencia'),
    'fechado' => $valorFiltro('fechado'),
], static fn (string $item): bool => $item !== '');
$printUrl = $base . '/relatorio/imprimir' . ($query === [] ? '' : '?' . http_build_query($query));
?>
<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-2">
      <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>"><i class="fa-solid fa-list me-1"></i>Movimentações</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($base . '/categorias') ?>"><i class="fa-solid fa-tags me-1"></i>Categorias</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($base . '/fechamentos') ?>"><i class="fa-solid fa-calendar-check me-1"></i>Fechamentos</a></li>
      <li class="nav-item"><a class="nav-link active" href="<?= e($base . '/relatorio') ?>"><i class="fa-solid fa-chart-column me-1"></i>Relatório</a></li>
    </ul>
    <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e($printUrl) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir</a>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base . '/relatorio') ?>">
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="tipo" aria-label="Tipo">
        <option value="">Todos os tipos</option>
        <option value="entrada" <?= $selecionado($valorFiltro('tipo'), 'entrada') ?>>Entradas</option>
        <option value="saida" <?= $selecionado($valorFiltro('tipo'), 'saida') ?>>Saídas</option>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="categoria_id" aria-label="Categoria">
        <option value="">Todas as categorias</option>
        <?php foreach ($categorias as $categoria): ?>
          <option value="<?= e((int) $categoria['id']) ?>" <?= $selecionado($valorFiltro('categoria_id'), (int) $categoria['id']) ?>><?= e((string) $categoria['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="congregacao_id" aria-label="Congregação">
        <option value="">Todas as congregações</option>
        <?php foreach ($congregacoes as $congregacao): ?>
          <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($valorFiltro('congregacao_id'), (int) $congregacao['id']) ?>><?= e((string) $congregacao['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="competencia" aria-label="Competência">
        <option value="">Todas</option>
        <?php foreach ($competencias as $competencia): ?>
          <option value="<?= e($competencia) ?>" <?= $selecionado($valorFiltro('competencia'), $competencia) ?>><?= e($competencia) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-8 col-md-1">
      <select class="form-select form-select-sm" name="fechado" aria-label="Situação">
        <option value="">Todas</option>
        <option value="0" <?= $selecionado($valorFiltro('fechado'), '0') ?>>Abertas</option>
        <option value="1" <?= $selecionado($valorFiltro('fechado'), '1') ?>>Fechadas</option>
      </select>
    </div>
    <div class="col-4 col-md-1">
      <button class="btn btn-sm btn-dark w-100" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
  </form>

  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="card-admin stat-card"><span class="stat-icon"><i class="fa-solid fa-receipt"></i></span><div><div class="stat-value fs-6"><?= e((string) ($estatisticas['total'] ?? 0)) ?></div><div class="stat-label">Lançamentos</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card-admin stat-card"><span class="stat-icon"><i class="fa-solid fa-arrow-down"></i></span><div><div class="stat-value fs-6"><?= e(format_money((float) ($estatisticas['entradas'] ?? 0))) ?></div><div class="stat-label">Entradas</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card-admin stat-card"><span class="stat-icon"><i class="fa-solid fa-arrow-up"></i></span><div><div class="stat-value fs-6"><?= e(format_money((float) ($estatisticas['saidas'] ?? 0))) ?></div><div class="stat-label">Saídas</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card-admin stat-card"><span class="stat-icon"><i class="fa-solid fa-scale-balanced"></i></span><div><div class="stat-value fs-6"><?= e(format_money((float) ($estatisticas['saldo'] ?? 0))) ?></div><div class="stat-label">Saldo</div></div></div></div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead><tr><th>Tipo</th><th>Categoria</th><th class="text-end">Lançamentos</th><th class="text-end">Total</th></tr></thead>
      <tbody>
        <?php if ($relatorio === []): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Nenhum dado para o filtro selecionado.</td></tr>
        <?php endif; ?>
        <?php foreach ($relatorio as $linha): ?>
          <tr>
            <td><span class="badge rounded-pill <?= (string) $linha['tipo'] === 'entrada' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (string) $linha['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></span></td>
            <td><?= e((string) $linha['categoria_nome']) ?></td>
            <td class="text-end small"><?= e((int) $linha['quantidade']) ?></td>
            <td class="text-end small fw-semibold"><?= e(format_money((float) $linha['total'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
