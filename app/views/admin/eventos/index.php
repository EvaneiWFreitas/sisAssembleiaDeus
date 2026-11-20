<?php
$podeCriar = \App\Auth::hasPermission('eventos', 'criar');
$podeEditar = \App\Auth::hasPermission('eventos', 'editar');
$podeExcluir = \App\Auth::hasPermission('eventos', 'excluir');
$podePublicar = \App\Auth::hasPermission('eventos', 'publicar');
$base = url('/admin/modulos/eventos');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$indicadores = [
    ['Eventos', (string) ($estatisticas['total'] ?? 0), 'fa-calendar-days'],
    ['Publicados', (string) ($estatisticas['publicados'] ?? 0), 'fa-circle-check'],
    ['Rascunhos', (string) ($estatisticas['rascunhos'] ?? 0), 'fa-file-pen'],
    ['Em destaque', (string) ($estatisticas['destaques'] ?? 0), 'fa-star'],
    ['Próximos', (string) ($estatisticas['futuros'] ?? 0), 'fa-clock'],
];
$periodos = ['futuros' => 'Próximos', 'passados' => 'Realizados'];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-6 col-lg-2">
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
      <h5 class="mb-0">Agenda institucional</h5>
      <div class="small text-muted">Eventos cadastrados e sua situação de publicação no site.</div>
    </div>
    <?php if ($podeCriar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Novo evento</a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-md-4">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Título, descrição, local ou slug" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="tipo" aria-label="Tipo">
        <option value="">Todos os tipos</option>
        <?php foreach (\App\EventoRepository::TIPOS as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('tipo'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="publicado" aria-label="Publicação">
        <option value="">Todas as situações</option>
        <option value="1" <?= $selecionado($valorFiltro('publicado'), '1') ?>>Publicados</option>
        <option value="0" <?= $selecionado($valorFiltro('publicado'), '0') ?>>Rascunhos</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="periodo" aria-label="Período">
        <option value="">Todos os períodos</option>
        <?php foreach ($periodos as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('periodo'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-1">
      <select class="form-select form-select-sm" name="destaque" aria-label="Destaque">
        <option value="">Todos</option>
        <option value="1" <?= $selecionado($valorFiltro('destaque'), '1') ?>>Destaques</option>
      </select>
    </div>
    <div class="col-12 col-md-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Evento</th><th>Tipo</th><th>Início</th><th>Local</th><th>Responsável</th><th>Site</th><th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($eventos === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum evento encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($eventos as $evento): ?>
          <?php $id = (int) $evento['id']; ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e((string) $evento['titulo']) ?></div>
              <div class="small text-muted"><?= e((string) $evento['slug']) ?></div>
            </td>
            <td><?= e(\App\EventoRepository::rotuloTipo((string) $evento['tipo'])) ?></td>
            <td>
              <?= e(format_date((string) $evento['data_inicio'], 'd/m/Y H:i')) ?>
              <?php if (!empty($evento['horario'])): ?>
                <div class="small text-muted"><?= e((string) $evento['horario']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e((string) ($evento['local'] ?? '—')) ?></td>
            <td><?= e((string) ($evento['responsavel_nome'] ?? '—')) ?></td>
            <td>
              <?php if ((int) $evento['publicado'] === 1): ?>
                <span class="badge text-bg-success">Publicado</span>
              <?php else: ?>
                <span class="badge text-bg-secondary">Rascunho</span>
              <?php endif; ?>
              <?php if ((int) $evento['destaque'] === 1): ?>
                <span class="badge text-bg-warning"><i class="fa-solid fa-star me-1"></i>Destaque</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="d-flex justify-content-end gap-1 flex-wrap">
                <?php if ($podeEditar): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
                <?php endif; ?>
                <?php if ($podePublicar): ?>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/publicar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-success" type="submit" title="Alternar publicação"><i class="fa-solid fa-eye<?= (int) $evento['publicado'] === 1 ? '-slash' : '' ?>"></i></button>
                  </form>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/destaque') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-warning" type="submit" title="Alternar destaque"><i class="fa-solid fa-star<?= (int) $evento['destaque'] === 1 ? '-half-stroke' : '' ?>"></i></button>
                  </form>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Excluir este evento?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
