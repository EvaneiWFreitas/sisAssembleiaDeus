<?php
$base = url('/admin/modulos/auditoria');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$query = array_filter([
    'de' => $valorFiltro('de'),
    'ate' => $valorFiltro('ate'),
    'usuario_id' => (string) ($filtros['usuario_id'] ?? ''),
    'acao' => $valorFiltro('acao'),
    'modulo' => $valorFiltro('modulo'),
    'entidade' => $valorFiltro('entidade'),
    'q' => $valorFiltro('q'),
    'limite' => (string) ($filtros['limite'] ?? ''),
], static fn (string $item): bool => $item !== '' && $item !== '0');
$queryString = $query === [] ? '' : '?' . http_build_query($query);
$acaoClasse = static function (string $acao): string {
    return match ($acao) {
        'criar' => 'text-bg-success',
        'editar' => 'text-bg-primary',
        'excluir' => 'text-bg-danger',
        'publicar' => 'text-bg-info',
        default => 'text-bg-secondary',
    };
};
?>
<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h5 class="mb-0">Trilha de ações</h5>
      <div class="small text-muted">Consulte quem realizou cada alteração no sistema, quando e em qual módulo.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e($base . '/imprimir' . $queryString) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir</a>
      <a class="btn btn-sm btn-outline-success rounded-pill" href="<?= e($base . '/exportar' . $queryString) ?>"><i class="fa-solid fa-file-csv me-1"></i>Exportar CSV</a>
    </div>
  </div>

  <form class="row g-2 align-items-center" method="get" action="<?= e($base) ?>">
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="de" value="<?= e($valorFiltro('de')) ?>" aria-label="Data inicial">
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="ate" value="<?= e($valorFiltro('ate')) ?>" aria-label="Data final">
    </div>
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="usuario_id" aria-label="Usuário">
        <option value="">Todos os usuários</option>
        <?php foreach ($usuarios as $usuario): ?>
          <option value="<?= e((int) $usuario['id']) ?>" <?= $selecionado($filtros['usuario_id'] ?? 0, (int) $usuario['id']) ?>><?= e((string) $usuario['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="acao" aria-label="Ação">
        <option value="">Todas as ações</option>
        <?php foreach ($acoes as $acao): ?>
          <option value="<?= e((string) $acao['valor']) ?>" <?= $selecionado($valorFiltro('acao'), (string) $acao['valor']) ?>><?= e(\App\AuditoriaRepository::rotuloAcao((string) $acao['valor'])) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="modulo" aria-label="Módulo">
        <option value="">Todos os módulos</option>
        <?php foreach ($modulosAuditoria as $modulo): ?>
          <option value="<?= e((string) $modulo['valor']) ?>" <?= $selecionado($valorFiltro('modulo'), (string) $modulo['valor']) ?>><?= e((string) $modulo['valor']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="entidade" aria-label="Entidade">
        <option value="">Todas as entidades</option>
        <?php foreach ($entidades as $entidade): ?>
          <option value="<?= e((string) $entidade['valor']) ?>" <?= $selecionado($valorFiltro('entidade'), (string) $entidade['valor']) ?>><?= e((string) $entidade['valor']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-8 col-md-3">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Buscar por usuário, ação ou entidade" aria-label="Busca livre">
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
      'Registros encontrados' => $estatisticas['total'] ?? 0,
      'Ações em 7 dias' => $estatisticas['ultimos_7_dias'] ?? 0,
      'Módulos com registros' => $estatisticas['modulos'] ?? 0,
      'Usuários envolvidos' => $estatisticas['usuarios'] ?? 0,
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

<div class="card-admin">
  <h5 class="mb-3">Registros de auditoria</h5>
  <div class="table-responsive">
    <table class="table table-sm admin-table mb-0">
      <thead>
        <tr>
          <th>Data e hora</th>
          <th>Usuário</th>
          <th>Ação</th>
          <th>Módulo</th>
          <th>Entidade</th>
          <th>IP</th>
          <th class="text-end">Detalhes</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($registros === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum registro encontrado para os filtros selecionados.</td></tr>
        <?php endif; ?>
        <?php foreach ($registros as $registro): ?>
          <?php $acao = (string) $registro['acao']; ?>
          <tr>
            <td class="text-nowrap small"><?= e(format_date((string) $registro['created_at'])) ?></td>
            <td>
              <div class="small fw-semibold"><?= e((string) ($registro['usuario_nome'] ?? 'Sistema')) ?></div>
              <div class="small text-muted"><?= e((string) ($registro['usuario_email'] ?? '')) ?></div>
            </td>
            <td><span class="badge <?= e($acaoClasse($acao)) ?>"><?= e(\App\AuditoriaRepository::rotuloAcao($acao)) ?></span></td>
            <td class="small"><?= e((string) $registro['modulo']) ?></td>
            <td class="small">
              <?= e((string) ($registro['entidade'] ?? '—')) ?>
              <?php if (!empty($registro['entidade_id'])): ?>
                <span class="text-muted">#<?= e((int) $registro['entidade_id']) ?></span>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?= e((string) ($registro['ip'] ?? '—')) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e($base . '/detalhe/' . (int) $registro['id'] . $queryString) ?>"><i class="fa-solid fa-eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
