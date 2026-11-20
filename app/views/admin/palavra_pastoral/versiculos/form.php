<?php
$acao = $versiculo === null ? url('/admin/modulos/palavra_pastoral/versiculos') : url('/admin/modulos/palavra_pastoral/versiculos/' . (int) $versiculo['id']);
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? 'is-invalid' : '';
};
$mensagemErro = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? (string) $erros[$campo] : '';
};
?>
<form id="form-versiculo" method="post" action="<?= e($acao) ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card-admin">
        <h5 class="mb-3">Versículo</h5>
        <div class="mb-3">
          <label class="form-label" for="referencia">Referência <span class="text-danger">*</span></label>
          <input class="form-control <?= $erroCampo('referencia') ?>" id="referencia" name="referencia" value="<?= e($valor('referencia')) ?>" maxlength="120" required placeholder="Ex.: João 3:16">
          <?php if ($mensagemErro('referencia') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('referencia')) ?></div><?php endif; ?>
        </div>
        <div class="mb-0">
          <label class="form-label" for="texto">Texto <span class="text-danger">*</span></label>
          <textarea class="form-control <?= $erroCampo('texto') ?>" id="texto" name="texto" rows="6" maxlength="2000" required><?= e($valor('texto')) ?></textarea>
          <?php if ($mensagemErro('texto') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('texto')) ?></div><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card-admin">
        <h5 class="mb-3">Exibição</h5>
        <div class="mb-3">
          <label class="form-label" for="ordem">Ordem</label>
          <input class="form-control <?= $erroCampo('ordem') ?>" type="number" min="0" step="1" id="ordem" name="ordem" value="<?= e($valor('ordem')) ?>">
          <div class="form-text">Menores valores aparecem primeiro.</div>
          <?php if ($mensagemErro('ordem') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('ordem')) ?></div><?php endif; ?>
        </div>
        <input type="hidden" name="ativo" value="0">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" <?= $valor('ativo') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="ativo">Exibir no site</label>
        </div>
        <div class="form-text">Apenas o primeiro versículo ativo é exibido.</div>
      </div>
    </div>

    <div class="col-12">
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i><?= $versiculo === null ? 'Cadastrar versículo' : 'Salvar alterações' ?></button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/palavra_pastoral/versiculos')) ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
