<?php
$podeEditar = \App\Auth::hasPermission('programacao', 'editar');
$base = url('/admin/modulos/programacao');
$dias = \App\ProgramacaoRepository::DIAS;
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$indicadores = [
    ['Horários cadastrados', (string) ($estatisticas['total'] ?? 0), 'fa-clock'],
    ['Ativos no site', (string) ($estatisticas['ativos'] ?? 0), 'fa-circle-check'],
    ['Inativos', (string) ($estatisticas['inativos'] ?? 0), 'fa-circle-pause'],
];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-12 col-md-4">
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
    <div>
      <h5 class="mb-0">Cultos e horários da semana</h5>
      <div class="small text-muted">Os horários ativos aparecem em /cultos e na página inicial.</div>
    </div>
    <?php if ($podeEditar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Novo horário</a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-lg-4">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Tipo, descrição ou congregação" aria-label="Buscar">
    </div>
    <div class="col-6 col-lg-2">
      <select class="form-select form-select-sm" name="dia_semana" aria-label="Dia da semana">
        <option value="">Todos os dias</option>
        <?php foreach ($dias as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('dia_semana'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <select class="form-select form-select-sm" name="ativo" aria-label="Situação">
        <option value="">Todas as situações</option>
        <option value="1" <?= $selecionado($valorFiltro('ativo'), '1') ?>>Ativos</option>
        <option value="0" <?= $selecionado($valorFiltro('ativo'), '0') ?>>Inativos</option>
      </select>
    </div>
    <div class="col-8 col-lg-3">
      <select class="form-select form-select-sm" name="congregacao_id" aria-label="Congregação">
        <option value="">Todas as congregações</option>
        <option value="global" <?= $selecionado($valorFiltro('congregacao_id'), 'global') ?>>Global (sem congregação)</option>
        <?php foreach ($congregacoes as $congregacao): ?>
          <option value="<?= e((string) (int) $congregacao['id']) ?>" <?= $selecionado($valorFiltro('congregacao_id'), (int) $congregacao['id']) ?>><?= e((string) $congregacao['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-4 col-lg-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <?php if ($horarios === []): ?>
    <div class="text-center text-muted py-5">Nenhum horário encontrado.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle admin-table mb-0">
        <thead>
          <tr>
            <th>Dia</th>
            <th>Horário</th>
            <th>Tipo</th>
            <th>Descrição</th>
            <th>Congregação</th>
            <th>Ordem</th>
            <th>Situação</th>
            <?php if ($podeEditar): ?><th class="text-end">Ações</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($horarios as $horario): ?>
            <?php $id = (int) $horario['id']; ?>
            <tr>
              <td class="fw-semibold"><?= e(\App\ProgramacaoRepository::rotuloDia((string) $horario['dia_semana'])) ?></td>
              <td><?= e(substr((string) $horario['horario'], 0, 5)) ?></td>
              <td><?= e((string) ($horario['tipo'] ?? '—')) ?></td>
              <td class="small text-muted"><?= e((string) ($horario['descricao'] ?? '—')) ?></td>
              <td class="small"><?= e((string) ($horario['congregacao_nome'] ?? 'Global')) ?></td>
              <td><?= e((string) (int) $horario['ordem']) ?></td>
              <td><span class="badge <?= (int) $horario['ativo'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $horario['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></span></td>
              <?php if ($podeEditar): ?>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/ativo') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-<?= (int) $horario['ativo'] === 1 ? 'secondary' : 'success' ?>" type="submit" title="Alternar situação"><i class="fa-solid fa-power-off"></i></button>
                  </form>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Excluir este horário?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
