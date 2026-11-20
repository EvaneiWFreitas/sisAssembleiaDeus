<?php
$base = url('/admin/modulos/backup');
$valorFiltro = static function (string $campo) use ($backupFiltros): string {
    return (string) ($backupFiltros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$formatarBytes = static function (mixed $bytes): string {
    $bytes = (float) $bytes;
    if ($bytes <= 0) {
        return '0 B';
    }
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $indice = 0;
    while ($bytes >= 1024 && $indice < count($unidades) - 1) {
        $bytes /= 1024;
        $indice++;
    }
    return number_format($bytes, $indice === 0 ? 0 : 1, ',', '.') . ' ' . $unidades[$indice];
};
$rotuloStatus = static function (string $status): string {
    return match ($status) {
        'concluido' => 'Concluído',
        'falhado' => 'Falhado',
        'gerando' => 'Gerando',
        default => ucfirst($status),
    };
};
$classeStatus = static function (string $status): string {
    return match ($status) {
        'concluido' => 'text-bg-success',
        'falhado' => 'text-bg-danger',
        'gerando' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
};
$query = array_filter([
    'de' => $valorFiltro('de'),
    'ate' => $valorFiltro('ate'),
    'status' => $valorFiltro('status'),
    'q' => $valorFiltro('q'),
    'limite' => (string) ($backupFiltros['limite'] ?? ''),
], static fn (string $item): bool => $item !== '' && $item !== '0');
$queryString = $query === [] ? '' : '?' . http_build_query($query);
?>
<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h5 class="mb-0">Cópias de segurança do banco de dados</h5>
      <div class="small text-muted">Gere, acompanhe e baixe dumps SQL armazenados fora do acesso HTTP público.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e($base . '/imprimir' . $queryString) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir</a>
      <a class="btn btn-sm btn-outline-success rounded-pill" href="<?= e($base . '/exportar' . $queryString) ?>"><i class="fa-solid fa-file-csv me-1"></i>Exportar CSV</a>
      <form method="post" action="<?= e($base . '/gerar') ?>" onsubmit="return confirm('Gerar um novo backup do banco de dados?');">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-warning rounded-pill" type="submit"><i class="fa-solid fa-database me-1"></i>Gerar backup</button>
      </form>
    </div>
  </div>

  <form class="row g-2 align-items-center" method="get" action="<?= e($base) ?>">
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="de" value="<?= e($valorFiltro('de')) ?>" aria-label="Data inicial">
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="ate" value="<?= e($valorFiltro('ate')) ?>" aria-label="Data final">
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="status" aria-label="Situação">
        <option value="">Todas as situações</option>
        <?php foreach (['concluido', 'falhado', 'gerando'] as $status): ?>
          <option value="<?= e($status) ?>" <?= $selecionado($valorFiltro('status'), $status) ?>><?= e($rotuloStatus($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Buscar arquivo ou usuário" aria-label="Busca livre">
    </div>
    <div class="col-4 col-md-1">
      <select class="form-select form-select-sm" name="limite" aria-label="Quantidade de registros">
        <?php foreach ([50, 100, 200, 500] as $limite): ?>
          <option value="<?= e((string) $limite) ?>" <?= $selecionado($backupFiltros['limite'] ?? 100, $limite) ?>><?= e((string) $limite) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-8 col-md-2 d-flex gap-1">
      <button class="btn btn-sm btn-dark w-100" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>" title="Limpar filtros"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>
</div>

<div class="row g-2 mb-3">
  <?php
  $cardsBackup = [
      'Backups encontrados' => $backupEstatisticas['total'] ?? 0,
      'Concluídos' => $backupEstatisticas['concluidos'] ?? 0,
      'Falhados' => $backupEstatisticas['falhados'] ?? 0,
      'Espaço utilizado' => $formatarBytes($backupEstatisticas['bytes'] ?? 0),
  ];
  ?>
  <?php foreach ($cardsBackup as $rotulo => $valor): ?>
    <div class="col-6 col-lg-3">
      <div class="card-admin h-100">
        <div class="small text-muted"><?= e($rotulo) ?></div>
        <div class="fs-5 fw-semibold"><?= e((string) $valor) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card-admin">
  <h5 class="mb-3">Histórico de backups</h5>
  <div class="table-responsive">
    <table class="table table-sm admin-table mb-0">
      <thead>
        <tr>
          <th>Arquivo</th>
          <th>Gerado em</th>
          <th>Gerado por</th>
          <th>Tabelas</th>
          <th>Tamanho</th>
          <th>Duração</th>
          <th>Situação</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($registrosBackup === []): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Nenhum backup encontrado para os filtros selecionados.</td></tr>
        <?php endif; ?>
        <?php foreach ($registrosBackup as $registro): ?>
          <?php $status = (string) $registro['status']; ?>
          <tr>
            <td class="small fw-semibold text-break"><?= e((string) $registro['nome_arquivo']) ?></td>
            <td class="text-nowrap small"><?= e(format_date((string) $registro['gerado_em'], 'd/m/Y H:i')) ?></td>
            <td class="small"><?= e((string) ($registro['usuario_nome'] ?? '—')) ?></td>
            <td class="small"><?= e(number_format((int) $registro['tabelas'], 0, ',', '.')) ?></td>
            <td class="small"><?= e($formatarBytes($registro['tamanho_bytes'])) ?></td>
            <td class="small text-muted"><?= e(number_format((int) $registro['duracao_ms'], 0, ',', '.')) ?> ms</td>
            <td><span class="badge <?= e($classeStatus($status)) ?>"><?= e($rotuloStatus($status)) ?></span></td>
            <td class="text-end">
              <?php if ($status === 'concluido'): ?>
                <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e($base . '/download/' . (int) $registro['id']) ?>" title="Baixar"><i class="fa-solid fa-download"></i></a>
              <?php else: ?>
                <span class="small text-muted">—</span>
              <?php endif; ?>
              <?php if (!empty($registro['observacoes'])): ?>
                <span class="small text-danger" title="<?= e((string) $registro['observacoes']) ?>"><i class="fa-solid fa-circle-info"></i></span>
              <?php endif; ?>
              <form class="d-inline" method="post" action="<?= e($base . '/' . (int) $registro['id'] . '/excluir') ?>" onsubmit="return confirm('Excluir este backup e seu arquivo?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
