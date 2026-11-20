<?php
$podeEditar = \App\Auth::hasPermission('obreiros', 'editar');
$podeExcluir = \App\Auth::hasPermission('obreiros', 'excluir');
$podeTransferir = \App\Auth::hasPermission('obreiros', 'transferir');
$podeHistorico = \App\Auth::hasPermission('obreiros', 'historico');
$podeImprimir = \App\Auth::hasPermission('obreiros', 'imprimir');
$podeArquivar = \App\Auth::hasPermission('arquivo_morto', 'transferir');

$cores = [
    'ativo' => 'success',
    'licenciado' => 'warning',
    'desligado' => 'secondary',
];

$indicadores = [
    ['Total', (int) ($estatisticas['total'] ?? 0), 'fa-user-tie'],
    ['Ativos', (int) ($estatisticas['ativos'] ?? 0), 'fa-circle-check'],
    ['Licenciados', (int) ($estatisticas['licenciados'] ?? 0), 'fa-circle-pause'],
    ['Desligados', (int) ($estatisticas['desligados'] ?? 0), 'fa-circle-xmark'],
    ['Excluídos', (int) ($estatisticas['excluidos'] ?? 0), 'fa-trash-can'],
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
    <form class="row g-2 align-items-center w-100" method="get" action="<?= e(url('/admin/modulos/obreiros')) ?>">
      <div class="col-12 col-md-4">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= e((string) $filtros['q']) ?>" placeholder="Nome, código, CPF ou registro" aria-label="Buscar obreiros">
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select form-select-sm" name="situacao" aria-label="Situação">
          <option value="">Todas as situações</option>
          <?php foreach (['ativo' => 'Ativo', 'licenciado' => 'Licenciado', 'desligado' => 'Desligado'] as $chave => $rotulo): ?>
            <option value="<?= e($chave) ?>" <?= (string) $filtros['situacao'] === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select form-select-sm" name="cargo_id" aria-label="Cargo">
          <option value="">Todos os cargos</option>
          <?php foreach ($cargos as $cargo): ?>
            <option value="<?= e((int) $cargo['id']) ?>" <?= (int) $filtros['cargo_id'] === (int) $cargo['id'] ? 'selected' : '' ?>><?= e((string) $cargo['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select form-select-sm" name="congregacao_id" aria-label="Congregação">
          <option value="">Todas as congregações</option>
          <?php foreach ($congregacoes as $congregacao): ?>
            <option value="<?= e((int) $congregacao['id']) ?>" <?= (int) $filtros['congregacao_id'] === (int) $congregacao['id'] ? 'selected' : '' ?>><?= e((string) $congregacao['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-1">
        <div class="form-check form-switch small">
          <input class="form-check-input" type="checkbox" name="excluidos" value="1" id="excluidos" <?= $filtros['excluidos'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="excluidos">Excluídos</label>
        </div>
      </div>
      <div class="col-12 col-md-1 d-flex gap-1">
        <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/obreiros')) ?>"><i class="fa-solid fa-rotate-left"></i></a>
      </div>
    </form>
  </div>

  <?php if (\App\Auth::hasPermission('obreiros', 'criar')): ?>
    <div class="d-flex justify-content-end mb-3">
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e(url('/admin/modulos/obreiros/novo')) ?>"><i class="fa-solid fa-plus me-1"></i>Novo obreiro</a>
    </div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Obreiro</th>
          <th>Cargo</th>
          <th>Congregação</th>
          <th>Contato</th>
          <th>Consagração</th>
          <th>Situação</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($obreiros === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum obreiro encontrado para os filtros informados.</td></tr>
        <?php endif; ?>
        <?php foreach ($obreiros as $obreiro): ?>
          <?php
          $id = (int) $obreiro['id'];
          $foto = (string) ($obreiro['foto'] ?? '');
          $situacaoObreiro = (string) $obreiro['situacao'];
          $cor = $cores[$situacaoObreiro] ?? 'secondary';
          $arquivado = in_array($id, $arquivadosIds ?? [], true);
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($foto !== ''): ?>
                  <img class="admin-thumb" src="<?= e(upload_url($foto)) ?>" alt="">
                <?php else: ?>
                  <span class="admin-avatar"><?= e(\App\AdminRepository::iniciais((string) $obreiro['nome'])) ?></span>
                <?php endif; ?>
                <div>
                  <strong class="d-block"><?= e((string) $obreiro['nome']) ?></strong>
                  <small class="text-muted"><?= e((string) $obreiro['codigo']) ?></small>
                  <?php if (!empty($obreiro['deleted_at'])): ?><span class="badge text-bg-danger rounded-pill">Excluído</span><?php endif; ?>
                </div>
              </div>
            </td>
            <td><span class="badge text-bg-light"><?= e((string) $obreiro['cargo_nome']) ?></span></td>
            <td>
              <span class="d-block"><?= e((string) $obreiro['congregacao_nome']) ?></span>
              <?php if (!empty($obreiro['matriz_nome'])): ?><small class="text-muted"><?= e((string) $obreiro['matriz_nome']) ?></small><?php endif; ?>
            </td>
            <td>
              <span class="d-block small"><?= e((string) ($obreiro['telefone'] ?? '') !== '' ? (string) $obreiro['telefone'] : (string) ($obreiro['whatsapp'] ?? '')) ?></span>
              <small class="text-muted"><?= e((string) ($obreiro['email'] ?? '') !== '' ? (string) $obreiro['email'] : '—') ?></small>
            </td>
            <td class="small"><?= e(format_date($obreiro['data_consagracao'] ?? null)) ?></td>
            <td><span class="badge text-bg-<?= e($cor) ?> rounded-pill"><?= e(ucfirst($situacaoObreiro)) ?></span></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <?php if ($podeEditar): ?>
                  <a class="btn btn-outline-dark" href="<?= e(url('/admin/modulos/obreiros/' . $id . '/editar')) ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                <?php endif; ?>
                <?php if ($podeTransferir): ?>
                  <a class="btn btn-outline-warning" href="<?= e(url('/admin/modulos/obreiros/' . $id . '/transferir')) ?>" title="Transferir"><i class="fa-solid fa-arrows-rotate"></i></a>
                <?php endif; ?>
                <?php if ($podeArquivar && empty($obreiro['deleted_at']) && !$arquivado): ?>
                  <a class="btn btn-outline-secondary" href="<?= e(url('/admin/modulos/arquivo_morto/arquivar/' . $id)) ?>" title="Arquivar"><i class="fa-solid fa-box-archive"></i></a>
                <?php endif; ?>
                <?php if ($podeImprimir): ?>
                  <a class="btn btn-outline-primary" href="<?= e(url('/admin/modulos/obreiros/' . $id . '/ficha')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print"></i></a>
                <?php endif; ?>
                <?php if ($podeHistorico): ?>
                  <a class="btn btn-outline-info" href="<?= e(url('/admin/modulos/obreiros/' . $id . '/historico')) ?>" title="Histórico"><i class="fa-solid fa-clock-rotate-left"></i></a>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <?php if (empty($obreiro['deleted_at'])): ?>
                    <form method="post" action="<?= e(url('/admin/modulos/obreiros/' . $id . '/excluir')) ?>" onsubmit="return confirm('Deseja mover este obreiro para os excluídos?');">
                      <?= csrf_field() ?>
                      <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-trash-can"></i></button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= e(url('/admin/modulos/obreiros/' . $id . '/restaurar')) ?>" onsubmit="return confirm('Deseja restaurar este obreiro?');">
                      <?= csrf_field() ?>
                      <button class="btn btn-outline-success" type="submit"><i class="fa-solid fa-rotate-left"></i></button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
