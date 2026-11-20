<?php
$podeCriar = \App\Auth::hasPermission('galeria', 'criar');
$podeEditar = \App\Auth::hasPermission('galeria', 'editar');
$podeExcluir = \App\Auth::hasPermission('galeria', 'excluir');
$base = url('/admin/modulos/galeria');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$indicadores = [
    ['Fotografias', (string) ($estatisticas['total'] ?? 0), 'fa-images'],
    ['Ativas', (string) ($estatisticas['ativos'] ?? 0), 'fa-circle-check'],
    ['Inativas', (string) ($estatisticas['inativos'] ?? 0), 'fa-circle-pause'],
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
      <h5 class="mb-0">Fotografias da comunidade</h5>
      <div class="small text-muted">As fotos ativas aparecem na galeria do site e na página inicial.</div>
    </div>
    <?php if ($podeCriar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Adicionar foto</a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-md-7">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Título ou descrição" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-4">
      <select class="form-select form-select-sm" name="ativo" aria-label="Situação">
        <option value="">Todas as situações</option>
        <option value="1" <?= $selecionado($valorFiltro('ativo'), '1') ?>>Ativas</option>
        <option value="0" <?= $selecionado($valorFiltro('ativo'), '0') ?>>Inativas</option>
      </select>
    </div>
    <div class="col-6 col-md-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <?php if ($fotos === []): ?>
    <div class="text-center text-muted py-5">Nenhuma fotografia encontrada.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($fotos as $foto): ?>
        <?php $id = (int) $foto['id']; ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
          <div class="card-admin h-100 p-0 overflow-hidden">
            <div class="ratio ratio-4x3 bg-light">
              <img class="w-100 h-100 object-fit-cover" src="<?= e(upload_url((string) $foto['imagem'])) ?>" alt="<?= e((string) ($foto['titulo'] ?? 'Foto da igreja')) ?>" loading="lazy">
            </div>
            <div class="p-3">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="fw-semibold"><?= e((string) ($foto['titulo'] ?? 'Foto da igreja')) ?></div>
                <span class="badge <?= (int) $foto['ativo'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $foto['ativo'] === 1 ? 'Ativa' : 'Inativa' ?></span>
              </div>
              <?php if ((string) ($foto['descricao'] ?? '') !== ''): ?>
                <div class="small text-muted mt-1"><?= e((string) $foto['descricao']) ?></div>
              <?php endif; ?>
              <div class="small text-muted mt-1">Ordem: <?= e((string) (int) $foto['ordem']) ?></div>
              <div class="d-flex flex-wrap gap-1 mt-3">
                <?php if ((int) $foto['ativo'] === 1): ?>
                  <a class="btn btn-sm btn-outline-info" href="<?= e(url('/galeria')) ?>" target="_blank" rel="noopener noreferrer" title="Ver no site"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                <?php endif; ?>
                <?php if ($podeEditar): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/ativo') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-<?= (int) $foto['ativo'] === 1 ? 'secondary' : 'success' ?>" type="submit" title="Alternar situação"><i class="fa-solid fa-power-off"></i></button>
                  </form>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Excluir esta fotografia?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
