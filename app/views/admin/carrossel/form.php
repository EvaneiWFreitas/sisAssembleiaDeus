<?php
$acao = $foto === null ? url('/admin/modulos/carrossel') : url('/admin/modulos/carrossel/' . (int) $foto['id']);
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? 'is-invalid' : '';
};
$mensagemErro = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? (string) $erros[$campo] : '';
};
$imagemAtual = (string) ($foto['imagem'] ?? $valor('imagem'));
?>
<form id="form-carrossel" method="post" action="<?= e($acao) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card-admin">
        <h5 class="mb-3">Dados da imagem de fundo</h5>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="titulo">Título do slide</label>
            <input class="form-control <?= $erroCampo('titulo') ?>" id="titulo" name="titulo" value="<?= e($valor('titulo')) ?>" maxlength="150" placeholder="Ex.: Nossa igreja">
            <?php if ($mensagemErro('titulo') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('titulo')) ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="descricao">Descrição do slide</label>
            <input class="form-control <?= $erroCampo('descricao') ?>" id="descricao" name="descricao" value="<?= e($valor('descricao')) ?>" maxlength="255" placeholder="Breve texto deste slide">
            <?php if ($mensagemErro('descricao') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('descricao')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label" for="ordem">Ordem de exibição</label>
            <input class="form-control <?= $erroCampo('ordem') ?>" type="number" min="0" step="1" id="ordem" name="ordem" value="<?= e($valor('ordem')) ?>">
            <div class="form-text">Menores valores aparecem primeiro.</div>
            <?php if ($mensagemErro('ordem') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('ordem')) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card-admin mb-3">
        <h5 class="mb-3">Exibição</h5>
        <input type="hidden" name="ativo" value="0">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" <?= $valor('ativo') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="ativo">Exibir no carrossel</label>
        </div>
        <div class="form-text">Imagens inativas ficam salvas, mas não aparecem na página inicial.</div>
      </div>

      <div class="card-admin">
        <h5 class="mb-3">Imagem de fundo</h5>
        <?php if ($imagemAtual !== ''): ?>
          <input type="hidden" name="imagem" value="<?= e($imagemAtual) ?>">
          <div class="ratio ratio-16x9 rounded overflow-hidden mb-3 bg-light">
            <img class="w-100 h-100 object-fit-cover" src="<?= e(upload_url($imagemAtual)) ?>" alt="Imagem atual">
          </div>
        <?php endif; ?>
        <input class="form-control <?= $erroCampo('imagem') ?>" type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp,image/gif" <?= $foto === null ? 'required' : '' ?>>
        <?php if ($mensagemErro('imagem') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('imagem')) ?></div><?php endif; ?>
        <div class="form-text">
          JPG, PNG, WEBP ou GIF até <?= e((string) config('security.upload_max_mb', 5)) ?> MB.
          <?= $foto === null ? 'Selecione uma imagem para cadastrar.' : 'Deixe vazio para manter a imagem atual.' ?>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i><?= $foto === null ? 'Adicionar imagem de fundo' : 'Salvar alterações' ?></button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/carrossel')) ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
