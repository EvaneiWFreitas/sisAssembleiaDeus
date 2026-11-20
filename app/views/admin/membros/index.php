<?php
$podeEditar = \App\Auth::hasPermission('membros', 'editar');
$podeExcluir = \App\Auth::hasPermission('membros', 'excluir');
$podeHistorico = \App\Auth::hasPermission('membros', 'historico');
$podeImprimir = \App\Auth::hasPermission('membros', 'imprimir');

$cores = [
    'ativo' => 'success',
    'inativo' => 'secondary',
    'transferido' => 'info',
    'falecido' => 'dark',
];

$indicadores = [
    ['Total', (int) ($estatisticas['total'] ?? 0), 'fa-users'],
    ['Ativos', (int) ($estatisticas['ativos'] ?? 0), 'fa-circle-check'],
    ['Inativos', (int) ($estatisticas['inativos'] ?? 0), 'fa-circle-pause'],
    ['Transferidos', (int) ($estatisticas['transferidos'] ?? 0), 'fa-arrow-right-arrow-left'],
    ['Falecidos', (int) ($estatisticas['falecidos'] ?? 0), 'fa-dove'],
    ['Excluídos', (int) ($estatisticas['excluidos'] ?? 0), 'fa-trash-can'],
];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-6 col-lg-2">
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
    <form class="row g-2 align-items-center w-100" method="get" action="<?= e(url('/admin/modulos/membros')) ?>">
      <div class="col-12 col-md-4">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= e((string) $filtros['q']) ?>" placeholder="Nome, código, CPF, e-mail ou telefone" aria-label="Buscar membros">
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select form-select-sm" name="situacao" aria-label="Situação">
          <option value="">Todas as situações</option>
          <?php foreach (['ativo' => 'Ativo', 'inativo' => 'Inativo', 'transferido' => 'Transferido', 'falecido' => 'Falecido'] as $chave => $rotulo): ?>
            <option value="<?= e($chave) ?>" <?= (string) $filtros['situacao'] === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select class="form-select form-select-sm" name="congregacao_id" aria-label="Congregação">
          <option value="">Todas as congregações</option>
          <?php foreach ($congregacoes as $congregacao): ?>
            <option value="<?= e((int) $congregacao['id']) ?>" <?= (int) $filtros['congregacao_id'] === (int) $congregacao['id'] ? 'selected' : '' ?>><?= e((string) $congregacao['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <div class="form-check form-switch small">
          <input class="form-check-input" type="checkbox" name="excluidos" value="1" id="excluidos" <?= $filtros['excluidos'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="excluidos">Excluídos</label>
        </div>
      </div>
      <div class="col-6 col-md-1 d-flex gap-1">
        <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/membros')) ?>"><i class="fa-solid fa-rotate-left"></i></a>
      </div>
    </form>
  </div>

  <?php if (\App\Auth::hasPermission('membros', 'criar')): ?>
    <div class="d-flex justify-content-end mb-3">
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e(url('/admin/modulos/membros/novo')) ?>"><i class="fa-solid fa-plus me-1"></i>Novo membro</a>
    </div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Membro</th>
          <th>CPF</th>
          <th>Contato</th>
          <th>Congregação</th>
          <th>Ingresso</th>
          <th>Situação</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($membros === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum membro encontrado para os filtros informados.</td></tr>
        <?php endif; ?>
        <?php foreach ($membros as $membro): ?>
          <?php
          $id = (int) $membro['id'];
          $foto = (string) ($membro['foto'] ?? '');
          $situacaoMembro = (string) $membro['situacao'];
          $cor = $cores[$situacaoMembro] ?? 'secondary';
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($foto !== ''): ?>
                  <img class="admin-thumb" src="<?= e(upload_url($foto)) ?>" alt="">
                <?php else: ?>
                  <span class="admin-avatar"><?= e(\App\AdminRepository::iniciais((string) $membro['nome'])) ?></span>
                <?php endif; ?>
                <div>
                  <strong class="d-block"><?= e((string) $membro['nome']) ?></strong>
                  <small class="text-muted"><?= e((string) $membro['codigo']) ?></small>
                  <?php if (!empty($membro['deleted_at'])): ?><span class="badge text-bg-danger rounded-pill">Excluído</span><?php endif; ?>
                </div>
              </div>
            </td>
            <td class="small"><?= e((string) ($membro['cpf'] ?? '') !== '' ? (string) $membro['cpf'] : '—') ?></td>
            <td>
              <span class="d-block small"><?= e((string) ($membro['telefone'] ?? '') !== '' ? (string) $membro['telefone'] : (string) ($membro['whatsapp'] ?? '')) ?></span>
              <small class="text-muted"><?= e((string) ($membro['email'] ?? '') !== '' ? (string) $membro['email'] : '—') ?></small>
            </td>
            <td><?= e((string) ($membro['congregacao_nome'] ?? '') !== '' ? (string) $membro['congregacao_nome'] : '—') ?></td>
            <td class="small"><?= e(format_date($membro['data_ingresso'] ?? null)) ?></td>
            <td><span class="badge text-bg-<?= e($cor) ?> rounded-pill"><?= e(ucfirst($situacaoMembro)) ?></span></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <?php if ($podeEditar): ?>
                  <a class="btn btn-outline-dark" href="<?= e(url('/admin/modulos/membros/' . $id . '/editar')) ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                <?php endif; ?>
                <?php if ($podeImprimir): ?>
                  <a class="btn btn-outline-primary" href="<?= e(url('/admin/modulos/membros/' . $id . '/ficha')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print"></i></a>
                <?php endif; ?>
                <?php if ($podeHistorico): ?>
                  <a class="btn btn-outline-info" href="<?= e(url('/admin/modulos/membros/' . $id . '/historico')) ?>" title="Histórico"><i class="fa-solid fa-clock-rotate-left"></i></a>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <?php if (empty($membro['deleted_at'])): ?>
                    <form method="post" action="<?= e(url('/admin/modulos/membros/' . $id . '/excluir')) ?>" onsubmit="return confirm('Deseja mover este membro para os excluídos?');">
                      <?= csrf_field() ?>
                      <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-trash-can"></i></button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= e(url('/admin/modulos/membros/' . $id . '/restaurar')) ?>" onsubmit="return confirm('Deseja restaurar este membro?');">
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
