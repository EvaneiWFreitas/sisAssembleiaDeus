<?php
$podeCriar = \App\Auth::hasPermission('sacramentos', 'criar');
$podeEditar = \App\Auth::hasPermission('sacramentos', 'editar');
$podeExcluir = \App\Auth::hasPermission('sacramentos', 'excluir');
$base = url('/admin/modulos/sacramentos');
$tipoOutro = $tipo === 'batismo' ? 'consagracao' : 'batismo';
$rotuloOutro = $tipo === 'batismo' ? 'Consagrações' : 'Batismos';
$rotuloNovo = $tipo === 'batismo' ? 'Novo batismo' : 'Nova consagração';

$indicadores = [
    ['Batismos', (int) ($estatisticas['batismos'] ?? 0), 'fa-water'],
    ['Consagrações', (int) ($estatisticas['consagracoes'] ?? 0), 'fa-hands-praying'],
    ['Diplomas de batismo', (int) ($estatisticas['diplomas_batismo'] ?? 0), 'fa-award'],
    ['Diplomas de consagração', (int) ($estatisticas['diplomas_consagracao'] ?? 0), 'fa-scroll'],
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
    <ul class="nav nav-pills gap-2">
      <li class="nav-item">
        <a class="nav-link <?= $tipo === 'batismo' ? 'active' : '' ?>" href="<?= e($base . '?tipo=batismo') ?>"><i class="fa-solid fa-water me-1"></i>Batismos</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tipo === 'consagracao' ? 'active' : '' ?>" href="<?= e($base . '?tipo=consagracao') ?>"><i class="fa-solid fa-hands-praying me-1"></i>Consagrações</a>
      </li>
    </ul>
    <?php if ($podeCriar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e(url('/admin/modulos/sacramentos/' . $tipo . '/novo')) ?>"><i class="fa-solid fa-plus me-1"></i><?= e($rotuloNovo) ?></a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
    <div class="col-12 col-md-5">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e((string) $filtros['q']) ?>" placeholder="Nome, número ou local" aria-label="Buscar sacramentos">
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="number" name="ano" min="1900" max="2100" value="<?= e((string) $filtros['ano']) ?>" placeholder="Ano" aria-label="Ano">
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="diploma" aria-label="Diploma">
        <option value="">Todos</option>
        <option value="1" <?= (string) $filtros['diploma'] === '1' ? 'selected' : '' ?>>Com diploma</option>
        <option value="0" <?= (string) $filtros['diploma'] === '0' ? 'selected' : '' ?>>Sem diploma</option>
      </select>
    </div>
    <div class="col-12 col-md-2 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base . '?tipo=' . $tipo) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <?php if ($tipo === 'batismo'): ?>
            <th>Batizado</th><th>Data</th><th>Local</th><th>Celebrante</th><th>Certificado</th>
          <?php else: ?>
            <th>Obreiro</th><th>Cargo</th><th>Data</th><th>Local</th><th>Presidente</th>
          <?php endif; ?>
          <th>Diploma</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($registros === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum registro de <?= e($tipo === 'batismo' ? 'batismo' : 'consagração') ?> encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($registros as $registro): ?>
          <?php
          $id = (int) $registro['id'];
          $temDiploma = !empty($registro['diploma_id']);
          $numero = (string) ($registro[$tipo === 'batismo' ? 'numero_certificado' : 'numero_diploma'] ?? '—');
          ?>
          <tr>
            <td>
              <strong class="d-block"><?= e((string) $registro['pessoa_nome']) ?></strong>
              <small class="text-muted"><?= e((string) ($registro['pessoa_codigo'] ?? '')) ?></small>
            </td>
            <?php if ($tipo === 'consagracao'): ?>
              <td><span class="badge text-bg-light"><?= e((string) ($registro['cargo_nome'] ?? '')) ?></span></td>
            <?php endif; ?>
            <td class="small"><?= e(format_date((string) $registro[$tipo === 'batismo' ? 'data_batismo' : 'data_consagracao'])) ?></td>
            <td class="small"><?= e((string) ($registro['local'] ?? '') !== '' ? (string) $registro['local'] : '—') ?></td>
            <td class="small"><?= e((string) ($registro['pastor_nome'] ?? '') !== '' ? (string) $registro['pastor_nome'] : '—') ?></td>
            <?php if ($tipo === 'batismo'): ?>
              <td class="small"><?= e($numero) ?></td>
            <?php endif; ?>
            <td>
              <?php if ($temDiploma): ?>
                <span class="badge text-bg-success rounded-pill">Emitido</span>
              <?php else: ?>
                <span class="badge text-bg-warning rounded-pill">Pendente</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <?php if ($podeEditar): ?>
                  <a class="btn btn-outline-dark" href="<?= e(url('/admin/modulos/sacramentos/' . $tipo . '/' . $id . '/editar')) ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                  <a class="btn btn-outline-primary" href="<?= e(url('/admin/modulos/sacramentos/' . $tipo . '/' . $id . '/diploma')) ?>"><i class="fa-solid fa-award"></i> <?= $temDiploma ? 'Diploma' : 'Emitir' ?></a>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <form method="post" action="<?= e(url('/admin/modulos/sacramentos/' . $tipo . '/' . $id . '/excluir')) ?>" onsubmit="return confirm('Deseja excluir este registro?');">
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
</div>
