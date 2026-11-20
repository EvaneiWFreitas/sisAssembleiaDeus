<?php
$podeLancar = \App\Auth::hasPermission('financeiro', 'lancar');
$podeEditar = \App\Auth::hasPermission('financeiro', 'editar');
$podeExcluir = \App\Auth::hasPermission('financeiro', 'excluir');
$podeFechar = \App\Auth::hasPermission('financeiro', 'fechar');
$podeRelatorio = \App\Auth::hasPermission('financeiro', 'relatorio');
$base = url('/admin/modulos/financeiro');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$rotulosOrigem = ['geral' => 'Geral', 'dizimo' => 'Dízimo', 'oferta' => 'Oferta'];
$indicadores = [
    ['Movimentações', (string) ($estatisticas['total'] ?? 0), 'fa-arrow-right-arrow-left'],
    ['Entradas', format_money((float) ($estatisticas['entradas'] ?? 0)), 'fa-arrow-down'],
    ['Saídas', format_money((float) ($estatisticas['saidas'] ?? 0)), 'fa-arrow-up'],
    ['Saldo', format_money((float) ($estatisticas['saldo'] ?? 0)), 'fa-scale-balanced'],
];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-6 col-lg-3">
      <div class="card-admin stat-card">
        <span class="stat-icon"><i class="fa-solid <?= e($indicador[2]) ?>"></i></span>
        <div>
          <div class="stat-value fs-6"><?= e($indicador[1]) ?></div>
          <div class="stat-label"><?= e($indicador[0]) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-2">
      <li class="nav-item"><a class="nav-link active" href="<?= e($base) ?>"><i class="fa-solid fa-list me-1"></i>Movimentações</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($base . '/categorias') ?>"><i class="fa-solid fa-tags me-1"></i>Categorias</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($base . '/fechamentos') ?>"><i class="fa-solid fa-calendar-check me-1"></i>Fechamentos</a></li>
      <?php if ($podeRelatorio): ?>
        <li class="nav-item"><a class="nav-link" href="<?= e($base . '/relatorio') ?>"><i class="fa-solid fa-chart-column me-1"></i>Relatório</a></li>
      <?php endif; ?>
    </ul>
    <?php if ($podeLancar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Nova movimentação</a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-md-3">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Descrição, categoria ou pessoa" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="tipo" aria-label="Tipo">
        <option value="">Todos os tipos</option>
        <option value="entrada" <?= $selecionado($valorFiltro('tipo'), 'entrada') ?>>Entradas</option>
        <option value="saida" <?= $selecionado($valorFiltro('tipo'), 'saida') ?>>Saídas</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="origem" aria-label="Origem">
        <option value="">Todas as origens</option>
        <?php foreach ($rotulosOrigem as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('origem'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="categoria_id" aria-label="Categoria">
        <option value="">Todas as categorias</option>
        <?php foreach ($categorias as $categoria): ?>
          <option value="<?= e((int) $categoria['id']) ?>" <?= $selecionado($valorFiltro('categoria_id'), (int) $categoria['id']) ?>><?= e((string) $categoria['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="congregacao_id" aria-label="Congregação">
        <option value="">Todas</option>
        <?php foreach ($congregacoes as $congregacao): ?>
          <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($valorFiltro('congregacao_id'), (int) $congregacao['id']) ?>><?= e((string) $congregacao['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="month" name="competencia" value="<?= e($valorFiltro('competencia')) ?>" aria-label="Competência">
    </div>
    <div class="col-4 col-md-2">
      <select class="form-select form-select-sm" name="fechado" aria-label="Situação">
        <option value="">Todas</option>
        <option value="0" <?= $selecionado($valorFiltro('fechado'), '0') ?>>Abertas</option>
        <option value="1" <?= $selecionado($valorFiltro('fechado'), '1') ?>>Fechadas</option>
      </select>
    </div>
    <div class="col-8 col-md-2 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Data</th><th>Descrição</th><th>Origem</th><th>Categoria</th><th>Congregação</th>
          <th>Tipo</th><th class="text-end">Valor</th><th>Situação</th><th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($registros === []): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma movimentação financeira encontrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($registros as $registro): ?>
          <?php
          $id = (int) $registro['id'];
          $origem = (string) ($registro['origem'] ?? 'geral');
          $pessoa = $origem === 'dizimo'
              ? (string) ($registro['dizimista_nome'] ?? 'Dízimo')
              : ($origem === 'oferta' ? (string) ($registro['ofertante_nome'] ?? 'Oferta') : '—');
          ?>
          <tr>
            <td class="small"><?= e(format_date((string) $registro['data_movimentacao'])) ?></td>
            <td>
              <strong class="d-block"><?= e((string) $registro['descricao']) ?></strong>
              <small class="text-muted"><?= e($pessoa) ?> · <?= e((string) ($registro['responsavel_nome'] ?? '—')) ?></small>
            </td>
            <td><span class="badge text-bg-light"><?= e($rotulosOrigem[$origem] ?? 'Geral') ?></span></td>
            <td class="small"><?= e((string) ($registro['categoria_nome'] ?? '—')) ?></td>
            <td class="small"><?= e((string) ($registro['congregacao_nome'] ?? 'Global')) ?></td>
            <td><span class="badge rounded-pill <?= (string) $registro['tipo'] === 'entrada' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (string) $registro['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></span></td>
            <td class="text-end small fw-semibold"><?= e(format_money((float) $registro['valor'])) ?></td>
            <td>
              <?php if ((int) $registro['fechado'] === 1): ?>
                <span class="badge text-bg-secondary rounded-pill">Fechado</span>
              <?php else: ?>
                <span class="badge text-bg-warning rounded-pill">Aberto</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <?php if ($podeEditar): ?>
                  <a class="btn btn-outline-dark" href="<?= e($base . '/' . $id . '/editar') ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <form method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Deseja excluir esta movimentação?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-trash-can"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($podeFechar): ?>
    <div class="mt-3 small text-muted">
      <i class="fa-solid fa-circle-info me-1"></i>Use a aba <a href="<?= e($base . '/fechamentos') ?>">Fechamentos</a> para consolidar uma competência.
    </div>
  <?php endif; ?>
</div>
