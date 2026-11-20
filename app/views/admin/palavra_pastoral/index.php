<?php
$podeEditar = \App\Auth::hasPermission('palavra_pastoral', 'editar');
$base = url('/admin/modulos/palavra_pastoral');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$indicadores = [
    ['Mensagens', (string) ($estatisticas['total'] ?? 0), 'fa-book-bible'],
    ['Publicadas', (string) ($estatisticas['publicadas'] ?? 0), 'fa-circle-check'],
    ['Rascunhos', (string) ($estatisticas['rascunhos'] ?? 0), 'fa-file-pen'],
];
?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base) ?>"><i class="fa-solid fa-book-bible me-1"></i>Mensagens</a>
  <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e($base . '/versiculos') ?>"><i class="fa-solid fa-quote-right me-1"></i>Versículos em destaque</a>
</div>

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
      <h5 class="mb-0">Mensagens pastorais</h5>
      <div class="small text-muted">A mensagem publicada aparece na página inicial do site.</div>
    </div>
    <?php if ($podeEditar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Nova mensagem</a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-md-8">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Título, texto ou autor" aria-label="Buscar">
    </div>
    <div class="col-8 col-md-3">
      <select class="form-select form-select-sm" name="publicada" aria-label="Situação">
        <option value="">Todas as situações</option>
        <option value="1" <?= $selecionado($valorFiltro('publicada'), '1') ?>>Publicadas</option>
        <option value="0" <?= $selecionado($valorFiltro('publicada'), '0') ?>>Rascunhos</option>
      </select>
    </div>
    <div class="col-4 col-md-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <?php if ($mensagens === []): ?>
    <div class="text-center text-muted py-5">Nenhuma mensagem encontrada.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle admin-table mb-0">
        <thead>
          <tr>
            <th>Título</th>
            <th>Autor</th>
            <th>Cadastro</th>
            <th>Situação</th>
            <?php if ($podeEditar): ?><th class="text-end">Ações</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($mensagens as $mensagem): ?>
            <?php $id = (int) $mensagem['id']; ?>
            <tr>
              <td class="fw-semibold"><?= e((string) $mensagem['titulo']) ?></td>
              <td><?= e((string) ($mensagem['autor_nome'] ?? '—')) ?></td>
              <td><?= e(format_date((string) $mensagem['created_at'])) ?></td>
              <td><span class="badge <?= (int) $mensagem['publicada'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $mensagem['publicada'] === 1 ? 'Publicada' : 'Rascunho' ?></span></td>
              <?php if ($podeEditar): ?>
                <td class="text-end">
                  <?php if ((int) $mensagem['publicada'] === 1): ?>
                    <a class="btn btn-sm btn-outline-info" href="<?= e(url('/')) ?>" target="_blank" rel="noopener noreferrer" title="Ver no site"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                  <?php endif; ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/publicar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-<?= (int) $mensagem['publicada'] === 1 ? 'secondary' : 'success' ?>" type="submit" title="Alternar publicação"><i class="fa-solid fa-power-off"></i></button>
                  </form>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Excluir esta mensagem?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
