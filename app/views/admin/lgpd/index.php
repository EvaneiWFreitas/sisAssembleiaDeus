<?php
$podeEditar = \App\Auth::hasPermission('lgpd', 'editar');
$base = url('/admin/modulos/lgpd');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$query = array_filter([
    'titular_tipo' => $valorFiltro('titular_tipo'),
    'finalidade' => $valorFiltro('finalidade'),
    'consentido' => $valorFiltro('consentido'),
    'de' => $valorFiltro('de'),
    'ate' => $valorFiltro('ate'),
    'q' => $valorFiltro('q'),
    'limite' => (string) ($filtros['limite'] ?? ''),
], static fn (string $item): bool => $item !== '' && $item !== '0');
$queryString = $query === [] ? '' : '?' . http_build_query($query);
$rotuloTipo = static function (string $tipo): string {
    return match ($tipo) {
        'membro' => 'Membro',
        'obreiro' => 'Obreiro',
        'contato' => 'Contato',
        default => ucfirst($tipo),
    };
};
?>
<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h5 class="mb-0">Consentimentos e retenção de dados</h5>
      <div class="small text-muted">Registre aceites, recusas e a política de retenção aplicável aos registros do sistema.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e($base . '/imprimir' . $queryString) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir</a>
      <a class="btn btn-sm btn-outline-success rounded-pill" href="<?= e($base . '/exportar' . $queryString) ?>"><i class="fa-solid fa-file-csv me-1"></i>Exportar CSV</a>
      <?php if ($podeEditar): ?>
        <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Novo consentimento</a>
      <?php endif; ?>
    </div>
  </div>

  <form class="row g-2 align-items-center" method="get" action="<?= e($base) ?>">
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="titular_tipo" aria-label="Tipo de titular">
        <option value="">Todos os titulares</option>
        <?php foreach (['membro', 'obreiro', 'contato'] as $tipo): ?>
          <option value="<?= e($tipo) ?>" <?= $selecionado($valorFiltro('titular_tipo'), $tipo) ?>><?= e($rotuloTipo($tipo)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="finalidade" aria-label="Finalidade">
        <option value="">Todas as finalidades</option>
        <?php foreach ($finalidadesLgpd as $finalidade): ?>
          <option value="<?= e((string) $finalidade['finalidade']) ?>" <?= $selecionado($valorFiltro('finalidade'), (string) $finalidade['finalidade']) ?>><?= e((string) $finalidade['finalidade']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="consentido" aria-label="Situação">
        <option value="">Todas as situações</option>
        <option value="1" <?= $selecionado($valorFiltro('consentido'), '1') ?>>Consentido</option>
        <option value="0" <?= $selecionado($valorFiltro('consentido'), '0') ?>>Não consentido</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="de" value="<?= e($valorFiltro('de')) ?>" aria-label="Data inicial">
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="ate" value="<?= e($valorFiltro('ate')) ?>" aria-label="Data final">
    </div>
    <div class="col-8 col-md-3">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Buscar titular, finalidade ou IP" aria-label="Busca livre">
    </div>
    <div class="col-4 col-md-2">
      <select class="form-select form-select-sm" name="limite" aria-label="Quantidade de registros">
        <?php foreach ([50, 100, 200, 500] as $limite): ?>
          <option value="<?= e((string) $limite) ?>" <?= $selecionado($filtros['limite'] ?? 100, $limite) ?>><?= e((string) $limite) ?> registros</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-2 d-flex gap-1">
      <button class="btn btn-sm btn-dark w-100" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>" title="Limpar filtros"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>
</div>

<div class="row g-2 mb-3">
  <?php
  $cards = [
      'Registros encontrados' => $lgpdEstatisticas['total'] ?? 0,
      'Consentimentos ativos' => $lgpdEstatisticas['consentidos'] ?? 0,
      'Recusas registradas' => $lgpdEstatisticas['recusados'] ?? 0,
      'Finalidades distintas' => $lgpdEstatisticas['finalidades'] ?? 0,
  ];
  ?>
  <?php foreach ($cards as $rotulo => $valor): ?>
    <div class="col-6 col-lg-3">
      <div class="card-admin h-100">
        <div class="small text-muted"><?= e($rotulo) ?></div>
        <div class="fs-5 fw-semibold"><?= e(number_format((int) $valor, 0, ',', '.')) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="mb-0">Política de retenção</h5>
      <div class="small text-muted">Prazo de manutenção dos registros de auditoria do sistema.</div>
    </div>
    <div class="small text-muted">
      Registros de auditoria: <?= e(number_format((int) ($auditoriaResumo['registros'] ?? 0), 0, ',', '.')) ?>
      · Mais antigo: <?= e(!empty($auditoriaResumo['mais_antigo']) ? format_date((string) $auditoriaResumo['mais_antigo']) : '—') ?>
    </div>
  </div>
  <form class="row g-2 align-items-end mt-2" method="post" action="<?= e($base . '/retencao') ?>">
    <?= csrf_field() ?>
    <div class="col-12 col-md-4">
      <label class="form-label small fw-semibold" for="prazo_retencao_auditoria_anos">Prazo em anos</label>
      <input class="form-control form-control-sm" type="number" min="1" max="100" step="1" id="prazo_retencao_auditoria_anos" name="prazo_retencao_auditoria_anos" value="<?= e((string) ($retencao['prazo_retencao_auditoria_anos'] ?? 5)) ?>" <?= $podeEditar ? '' : 'disabled' ?>>
    </div>
    <?php if ($podeEditar): ?>
      <div class="col-12 col-md-2">
        <button class="btn btn-sm btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
      </div>
    <?php endif; ?>
  </form>
</div>

<div class="card-admin">
  <h5 class="mb-3">Registros de consentimento</h5>
  <div class="table-responsive">
    <table class="table table-sm admin-table mb-0">
      <thead>
        <tr>
          <th>Data e hora</th>
          <th>Titular</th>
          <th>Tipo</th>
          <th>Finalidade</th>
          <th>Situação</th>
          <th>IP</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($registrosLgpd === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum consentimento encontrado para os filtros selecionados.</td></tr>
        <?php endif; ?>
        <?php foreach ($registrosLgpd as $registro): ?>
          <tr>
            <td class="text-nowrap small"><?= e(format_date((string) $registro['data_consentimento'])) ?></td>
            <td>
              <div class="small fw-semibold"><?= e((string) ($registro['titular_nome'] ?? 'Titular #' . (int) $registro['titular_id'])) ?></div>
              <div class="small text-muted">#<?= e((int) $registro['titular_id']) ?></div>
            </td>
            <td class="small"><?= e($rotuloTipo((string) $registro['titular_tipo'])) ?></td>
            <td class="small"><?= e((string) $registro['finalidade']) ?></td>
            <td><span class="badge <?= (int) $registro['consentido'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $registro['consentido'] === 1 ? 'Consentido' : 'Não consentido' ?></span></td>
            <td class="small text-muted"><?= e((string) ($registro['ip'] ?? '—')) ?></td>
            <td class="text-end">
              <?php if ($podeEditar): ?>
                <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e($base . '/' . (int) $registro['id'] . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
                <form class="d-inline" method="post" action="<?= e($base . '/' . (int) $registro['id'] . '/excluir') ?>" onsubmit="return confirm('Excluir este registro de consentimento?');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                </form>
              <?php else: ?>
                <span class="small text-muted">Somente leitura</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
