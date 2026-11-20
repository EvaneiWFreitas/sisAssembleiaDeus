<?php
$podeEmitir = \App\Auth::hasPermission('documentos', 'emitir');
$podeExcluir = \App\Auth::hasPermission('documentos', 'excluir');
$base = url('/admin/modulos/documentos');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$indicadores = [
    ['Convites', (string) ($estatisticas['convites'] ?? 0), 'fa-envelope-open-text'],
    ['Documentos', (string) ($estatisticas['documentos'] ?? 0), 'fa-file-lines'],
    ['Cartas registradas', (string) ($estatisticas['cartas'] ?? 0), 'fa-paper-plane'],
    ['Diplomas registrados', (string) ($estatisticas['diplomas'] ?? 0), 'fa-award'],
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
      <li class="nav-item"><a class="nav-link active" href="<?= e($base) ?>"><i class="fa-solid fa-envelope-open-text me-1"></i>Convites</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($base . '/emitidos') ?>"><i class="fa-solid fa-file-lines me-1"></i>Documentos emitidos</a></li>
    </ul>
    <?php if ($podeEmitir): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Novo convite</a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-md-4">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Número, convidado ou evento" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="modelo" aria-label="Modelo">
        <option value="">Todos os modelos</option>
        <?php foreach (\App\DocumentoRepository::MODELOS as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('modelo'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="de" value="<?= e($valorFiltro('de')) ?>" aria-label="Data inicial">
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="ate" value="<?= e($valorFiltro('ate')) ?>" aria-label="Data final">
    </div>
    <div class="col-6 col-md-2 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Número</th><th>Convidado</th><th>Evento</th><th>Data</th><th>Modelo</th><th>Emitido por</th><th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($convites === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum convite encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($convites as $convite): ?>
          <?php $id = (int) $convite['id']; ?>
          <tr>
            <td><span class="fw-semibold"><?= e((string) $convite['numero']) ?></span></td>
            <td><?= e((string) $convite['convidado']) ?></td>
            <td>
              <?= e((string) $convite['nome_evento']) ?>
              <?php if ((string) ($convite['local'] ?? '') !== ''): ?>
                <div class="small text-muted"><?= e((string) $convite['local']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e(format_date((string) $convite['data_evento'], 'd/m/Y H:i')) ?></td>
            <td><?= e(\App\DocumentoRepository::rotuloModelo((string) $convite['modelo'])) ?></td>
            <td><?= e((string) ($convite['emitido_por_nome'] ?? '—')) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id . '/imprimir') ?>" target="_blank" rel="noopener noreferrer" title="Imprimir"><i class="fa-solid fa-print"></i></a>
              <?php if ($podeEmitir): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($base . '/' . $id . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
              <?php endif; ?>
              <?php if ($podeExcluir): ?>
                <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Cancelar este convite e seu registro de emissão?');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger" type="submit" title="Cancelar"><i class="fa-solid fa-trash"></i></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
