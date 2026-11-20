<?php
$podePublicar = \App\Auth::hasPermission('noticias', 'publicar');
$acao = $noticia === null ? url('/admin/modulos/noticias') : url('/admin/modulos/noticias/' . (int) $noticia['id']);
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? 'is-invalid' : '';
};
$mensagemErro = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? (string) $erros[$campo] : '';
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$imagemAtual = (string) ($noticia['imagem'] ?? $valor('imagem'));
$slug = (string) ($noticia['slug'] ?? '');
?>
<form id="form-noticia" method="post" action="<?= e($acao) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card-admin">
        <h5 class="mb-3">Conteúdo da notícia</h5>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="titulo">Título <span class="text-danger">*</span></label>
            <input class="form-control <?= $erroCampo('titulo') ?>" id="titulo" name="titulo" value="<?= e($valor('titulo')) ?>" maxlength="180" required>
            <?php if ($mensagemErro('titulo') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('titulo')) ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="resumo">Resumo</label>
            <textarea class="form-control <?= $erroCampo('resumo') ?>" id="resumo" name="resumo" rows="3" maxlength="500"><?= e($valor('resumo')) ?></textarea>
            <div class="form-text">Exibido nos cartões do site. Se vazio, o site usa um trecho do conteúdo.</div>
            <?php if ($mensagemErro('resumo') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('resumo')) ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="conteudo">Conteúdo</label>
            <textarea class="form-control <?= $erroCampo('conteudo') ?>" id="conteudo" name="conteudo" rows="10" maxlength="10000"><?= e($valor('conteudo')) ?></textarea>
            <div class="form-text">Use linhas em branco para separar os parágrafos na página da notícia.</div>
            <?php if ($mensagemErro('conteudo') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('conteudo')) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card-admin mb-3">
        <h5 class="mb-3">Publicação</h5>
        <div class="mb-3">
          <label class="form-label" for="autor_id">Autor</label>
          <select class="form-select <?= $erroCampo('autor_id') ?>" id="autor_id" name="autor_id">
            <option value="0">Não informar</option>
            <?php foreach ($autores as $autor): ?>
              <option value="<?= e((int) $autor['id']) ?>" <?= $selecionado($valor('autor_id'), (int) $autor['id']) ?>><?= e((string) $autor['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($mensagemErro('autor_id') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('autor_id')) ?></div><?php endif; ?>
        </div>
        <?php if ($podePublicar): ?>
          <input type="hidden" name="publicada" value="0">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="publicada" name="publicada" value="1" <?= $valor('publicada') === '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="publicada">Publicar no site</label>
          </div>
        <?php else: ?>
          <div class="alert alert-info small mb-0">Seu perfil pode consultar notícias, mas não alterar a publicação.</div>
        <?php endif; ?>
        <?php if ($slug !== ''): ?>
          <div class="mt-3 small text-muted">Slug: <span class="fw-semibold"><?= e($slug) ?></span></div>
        <?php endif; ?>
        <?php if ((int) ($noticia['publicada'] ?? 0) === 1): ?>
          <a class="btn btn-sm btn-outline-info mt-3" href="<?= e(url('/noticias/' . $slug)) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Ver publicação</a>
        <?php endif; ?>
      </div>

      <div class="card-admin">
        <h5 class="mb-3">Imagem de capa</h5>
        <input type="hidden" name="imagem" value="<?= e($imagemAtual) ?>">
        <input class="form-control <?= $erroCampo('imagem') ?>" type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp,image/gif">
        <?php if ($mensagemErro('imagem') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('imagem')) ?></div><?php endif; ?>
        <div class="form-text">JPG, PNG, WEBP ou GIF até <?= e((string) config('security.upload_max_mb', 5)) ?> MB.</div>
        <?php if ($imagemAtual !== ''): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="remover_imagem" name="remover_imagem" value="1">
            <label class="form-check-label" for="remover_imagem">Remover imagem atual</label>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-12">
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i><?= $noticia === null ? 'Cadastrar notícia' : 'Salvar alterações' ?></button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/noticias')) ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
