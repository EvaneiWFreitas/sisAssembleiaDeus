<?php
$podeImprimir = \App\Auth::hasPermission('arquivo_morto', 'imprimir');
$podeRestaurar = \App\Auth::hasPermission('arquivo_morto', 'restaurar');
$podeTransferir = \App\Auth::hasPermission('arquivo_morto', 'transferir');

$cores = [
    'ativo' => 'success',
    'licenciado' => 'warning',
    'desligado' => 'secondary',
];

$indicadores = [
    ['Total', (int) ($estatisticas['total'] ?? 0), 'fa-box-archive'],
    ['Pendentes', (int) ($estatisticas['pendentes'] ?? 0), 'fa-hourglass-half'],
    ['Restaurados', (int) ($estatisticas['restaurados'] ?? 0), 'fa-rotate-left'],
    ['Desligados', (int) ($estatisticas['desligados'] ?? 0), 'fa-circle-xmark'],
    ['Licenciados', (int) ($estatisticas['licenciados'] ?? 0), 'fa-circle-pause'],
];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-6 col-lg-3">
      <div class="card-admin stat-card">
        <span class="stat-icon"><i class="fa-solid <?= e($indicador[2]) ?>"></i></span>
        <div>
          <div class="stat-value"><?= e($indicador[1]) ?></div>
          <div class="stat-label"><?= e($indicador[0]) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form class="row g-2 align-items-center w-100" method="get" action="<?= e(url('/admin/modulos/arquivo_morto')) ?>">
      <div class="col-12 col-md-4">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= e((string) $filtros['q']) ?>" placeholder="Nome, código ou motivo" aria-label="Buscar no Arquivo Morto">
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select form-select-sm" name="situacao" aria-label="Situação">
          <option value="">Todas as situações</option>
          <?php foreach (['desligado' => 'Desligado', 'licenciado' => 'Licenciado', 'ativo' => 'Ativo'] as $chave => $rotulo): ?>
            <option value="<?= e($chave) ?>" <?= (string) $filtros['situacao'] === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select form-select-sm" name="restaurado" aria-label="Restaurado">
          <option value="">Todos</option>
          <option value="0" <?= (string) $filtros['restaurado'] === '0' ? 'selected' : '' ?>>Não restaurados</option>
          <option value="1" <?= (string) $filtros['restaurado'] === '1' ? 'selected' : '' ?>>Restaurados</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <input class="form-control form-control-sm" type="date" name="data_inicio" value="<?= e((string) $filtros['data_inicio']) ?>" aria-label="Data inicial">
      </div>
      <div class="col-6 col-md-1">
        <input class="form-control form-control-sm" type="date" name="data_fim" value="<?= e((string) $filtros['data_fim']) ?>" aria-label="Data final">
      </div>
      <div class="col-12 col-md-1 d-flex gap-1">
        <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/arquivo_morto')) ?>"><i class="fa-solid fa-rotate-left"></i></a>
      </div>
    </form>
  </div>

  <?php if ($podeTransferir): ?>
    <div class="d-flex justify-content-end mb-3">
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e(url('/admin/modulos/arquivo_morto/arquivar')) ?>"><i class="fa-solid fa-box-archive me-1"></i>Arquivar obreiro</a>
    </div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Obreiro</th>
          <th>Situação</th>
          <th>Arquivamento</th>
          <th>Responsável</th>
          <th>Restauração</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($registros === []): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Nenhum registro encontrado no Arquivo Morto.</td></tr>
        <?php endif; ?>
        <?php foreach ($registros as $registro): ?>
          <?php
          $id = (int) $registro['id'];
          $situacao = (string) $registro['situacao'];
          $cor = $cores[$situacao] ?? 'secondary';
          $restaurado = $registro['restaurado_em'] !== null;
          $foto = (string) ($registro['foto'] ?? '');
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($foto !== ''): ?>
                  <img class="admin-thumb" src="<?= e(upload_url($foto)) ?>" alt="">
                <?php else: ?>
                  <span class="admin-avatar"><?= e(\App\AdminRepository::iniciais((string) $registro['nome'])) ?></span>
                <?php endif; ?>
                <div>
                  <strong class="d-block"><?= e((string) $registro['nome']) ?></strong>
                  <small class="text-muted"><?= e((string) $registro['codigo']) ?> · <?= e((string) ($registro['cargo_nome'] ?? '')) ?></small>
                </div>
              </div>
            </td>
            <td><span class="badge text-bg-<?= e($cor) ?> rounded-pill"><?= e(ucfirst($situacao)) ?></span></td>
            <td>
              <span class="d-block small"><?= e(format_date((string) $registro['data_desligamento'])) ?></span>
              <small class="text-muted"><?= e((string) ($registro['congregacao_nome'] ?? '')) ?></small>
            </td>
            <td class="small"><?= e((string) ($registro['responsavel_nome'] ?? '—')) ?></td>
            <td>
              <?php if ($restaurado): ?>
                <span class="badge text-bg-success rounded-pill">Restaurado</span>
                <small class="d-block text-muted"><?= e(format_date((string) $registro['restaurado_em'], 'd/m/Y H:i')) ?></small>
              <?php else: ?>
                <span class="badge text-bg-warning rounded-pill">Pendente</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <?php if ($podeImprimir): ?>
                  <a class="btn btn-outline-primary" href="<?= e(url('/admin/modulos/arquivo_morto/' . $id . '/ficha')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print"></i> Ficha</a>
                <?php endif; ?>
                <?php if ($podeRestaurar && !$restaurado): ?>
                  <form method="post" action="<?= e(url('/admin/modulos/arquivo_morto/' . $id . '/restaurar')) ?>" onsubmit="return confirm('Deseja restaurar este obreiro e reativá-lo?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-success" type="submit"><i class="fa-solid fa-rotate-left"></i> Restaurar</button>
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
