<?php
$podeCriar = \App\Auth::hasPermission('noticias', 'criar');
$podeEditar = \App\Auth::hasPermission('noticias', 'editar');
$podeExcluir = \App\Auth::hasPermission('noticias', 'excluir');
$podePublicar = \App\Auth::hasPermission('noticias', 'publicar');
$base = url('/admin/modulos/noticias');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$indicadores = [
    ['Notícias', (string) ($estatisticas['total'] ?? 0), 'fa-newspaper'],
    ['Publicadas', (string) ($estatisticas['publicadas'] ?? 0), 'fa-circle-check'],
    ['Rascunhos', (string) ($estatisticas['rascunhos'] ?? 0), 'fa-file-pen'],
    ['Com imagem', (string) ($estatisticas['com_imagem'] ?? 0), 'fa-image'],
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
    <div>
      <h5 class="mb-0">Publicações do site</h5>
      <div class="small text-muted">Gerencie notícias, rascunhos e publicação institucional.</div>
    </div>
    <?php if ($podeCriar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Nova notícia</a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-md-6">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Título, resumo, conteúdo ou slug" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="publicada" aria-label="Publicação">
        <option value="">Todas as situações</option>
        <option value="1" <?= $selecionado($valorFiltro('publicada'), '1') ?>>Publicadas</option>
        <option value="0" <?= $selecionado($valorFiltro('publicada'), '0') ?>>Rascunhos</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="autor_id" aria-label="Autor">
        <option value="">Todos os autores</option>
        <?php foreach ($autores as $autor): ?>
          <option value="<?= e((int) $autor['id']) ?>" <?= $selecionado($valorFiltro('autor_id'), (int) $autor['id']) ?>><?= e((string) $autor['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Notícia</th><th>Autor</th><th>Publicação</th><th>Atualização</th><th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($noticias === []): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma notícia encontrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($noticias as $noticia): ?>
          <?php $id = (int) $noticia['id']; ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e((string) $noticia['titulo']) ?></div>
              <div class="small text-muted"><?= e((string) $noticia['slug']) ?></div>
              <?php if ((string) ($noticia['resumo'] ?? '') !== ''): ?>
                <div class="small mt-1"><?= e(mb_substr((string) $noticia['resumo'], 0, 110)) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e((string) ($noticia['autor_nome'] ?? '—')) ?></td>
            <td>
              <?php if ((int) $noticia['publicada'] === 1): ?>
                <span class="badge text-bg-success">Publicada</span>
                <div class="small text-muted"><?= e(format_date((string) ($noticia['publicada_em'] ?? $noticia['created_at']), 'd/m/Y H:i')) ?></div>
              <?php else: ?>
                <span class="badge text-bg-secondary">Rascunho</span>
              <?php endif; ?>
            </td>
            <td><?= e(format_date((string) $noticia['updated_at'], 'd/m/Y H:i')) ?></td>
            <td class="text-end">
              <div class="d-flex justify-content-end gap-1 flex-wrap">
                <?php if ((int) $noticia['publicada'] === 1): ?>
                  <a class="btn btn-sm btn-outline-info" href="<?= e(url('/noticias/' . (string) $noticia['slug'])) ?>" target="_blank" rel="noopener noreferrer" title="Ver no site"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                <?php endif; ?>
                <?php if ($podeEditar): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
                <?php endif; ?>
                <?php if ($podePublicar): ?>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/publicar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-success" type="submit" title="Alternar publicação"><i class="fa-solid fa-eye<?= (int) $noticia['publicada'] === 1 ? '-slash' : '' ?>"></i></button>
                  </form>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Excluir esta notícia?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
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
