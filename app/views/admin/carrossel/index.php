<?php
$podeCriar = \App\Auth::hasPermission('carrossel', 'criar');
$podeEditar = \App\Auth::hasPermission('carrossel', 'editar');
$podeExcluir = \App\Auth::hasPermission('carrossel', 'excluir');
$podeVer = \App\Auth::hasPermission('carrossel', 'ver');
$base = url('/admin/modulos/carrossel');
$indicadores = [
    ['Imagens de fundo', (string) ($estatisticas['total'] ?? 0), 'fa-images'],
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

<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h5 class="mb-0">Carrossel da página inicial</h5>
      <div class="small text-muted">Gerencie as imagens de fundo e os textos exibidos em “Bem-vindo à família”.</div>
    </div>
    <?php if ($podeCriar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Adicionar nova imagem de fundo</a>
    <?php endif; ?>
  </div>

  <?php if ($podeEditar): ?>
    <form method="post" action="<?= e($base . '/textos') ?>" class="row g-3 border-top pt-3">
      <?= csrf_field() ?>
      <div class="col-12 col-lg-4">
        <label class="form-label small fw-semibold" for="home_eyebrow">Texto em destaque</label>
        <input class="form-control form-control-sm" type="text" id="home_eyebrow" name="home_eyebrow" value="<?= e((string) ($textos['home_eyebrow'] ?? '')) ?>" maxlength="150">
      </div>
      <div class="col-12 col-lg-8">
        <label class="form-label small fw-semibold" for="home_descricao">Descrição do carrossel</label>
        <input class="form-control form-control-sm" type="text" id="home_descricao" name="home_descricao" value="<?= e((string) ($textos['home_descricao'] ?? '')) ?>" maxlength="255">
      </div>
      <div class="col-12 d-flex justify-content-end">
        <button class="btn btn-sm btn-outline-primary rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar textos</button>
      </div>
    </form>
  <?php else: ?>
    <div class="border-top pt-3 small text-muted">
      <strong>Destaque:</strong> <?= e((string) ($textos['home_eyebrow'] ?? '')) ?><br>
      <strong>Descrição:</strong> <?= e((string) ($textos['home_descricao'] ?? '')) ?>
    </div>
  <?php endif; ?>
</div>

<div class="card-admin">
  <h5 class="mb-3">Imagens de fundo</h5>
  <?php if ($fotos === []): ?>
    <div class="text-center text-muted py-5">Nenhuma imagem de fundo cadastrada. O site está usando a imagem padrão.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($fotos as $foto): ?>
        <?php $id = (int) $foto['id']; ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
          <div class="card-admin h-100 p-0 overflow-hidden">
            <div class="ratio ratio-16x9 bg-light">
              <img class="w-100 h-100 object-fit-cover" src="<?= e(upload_url((string) $foto['imagem'])) ?>" alt="<?= e((string) ($foto['titulo'] ?? 'Imagem de fundo')) ?>" loading="lazy">
            </div>
            <div class="p-3">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="fw-semibold"><?= e((string) ($foto['titulo'] ?? 'Imagem de fundo')) ?></div>
                <span class="badge <?= (int) $foto['ativo'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $foto['ativo'] === 1 ? 'Ativa' : 'Inativa' ?></span>
              </div>
              <?php if ((string) ($foto['descricao'] ?? '') !== ''): ?>
                <div class="small text-muted mt-1"><?= e((string) $foto['descricao']) ?></div>
              <?php endif; ?>
              <div class="small text-muted mt-1">Ordem: <?= e((string) (int) $foto['ordem']) ?></div>
              <div class="d-flex flex-wrap gap-1 mt-3">
                <?php if ($podeVer): ?>
                  <a class="btn btn-sm btn-outline-info" href="<?= e(url('/')) ?>" target="_blank" rel="noopener noreferrer" title="Ver no site"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                <?php endif; ?>
                <?php if ($podeEditar): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/ativo') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-<?= (int) $foto['ativo'] === 1 ? 'secondary' : 'success' ?>" type="submit" title="Alternar situação"><i class="fa-solid fa-power-off"></i></button>
                  </form>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Excluir esta imagem de fundo?');">
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
