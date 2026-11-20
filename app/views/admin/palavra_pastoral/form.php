<?php
$acao = $mensagem === null ? url('/admin/modulos/palavra_pastoral') : url('/admin/modulos/palavra_pastoral/' . (int) $mensagem['id']);
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
$fotoAtual = (string) ($mensagem['foto'] ?? $valor('foto'));
?>
<form id="form-palavra" method="post" action="<?= e($acao) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card-admin">
        <h5 class="mb-3">Conteúdo da mensagem</h5>
        <div class="mb-3">
          <label class="form-label" for="titulo">Título <span class="text-danger">*</span></label>
          <input class="form-control <?= $erroCampo('titulo') ?>" id="titulo" name="titulo" value="<?= e($valor('titulo')) ?>" maxlength="180" required>
          <?php if ($mensagemErro('titulo') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('titulo')) ?></div><?php endif; ?>
        </div>
        <div class="mb-0">
          <label class="form-label" for="texto">Texto</label>
          <textarea class="form-control <?= $erroCampo('texto') ?>" id="texto" name="texto" rows="12" maxlength="10000"><?= e($valor('texto')) ?></textarea>
          <div class="form-text">Um trecho da mensagem é exibido na página inicial.</div>
          <?php if ($mensagemErro('texto') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('texto')) ?></div><?php endif; ?>
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
              <option value="<?= e((string) (int) $autor['id']) ?>" <?= $selecionado($valor('autor_id'), (int) $autor['id']) ?>><?= e((string) $autor['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($mensagemErro('autor_id') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('autor_id')) ?></div><?php endif; ?>
        </div>
        <input type="hidden" name="publicada" value="0">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="publicada" name="publicada" value="1" <?= $valor('publicada') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="publicada">Publicar no site</label>
        </div>
        <div class="form-text">Ao publicar, as demais mensagens são automaticamente despublicadas.</div>
      </div>

      <div class="card-admin">
        <h5 class="mb-3">Foto do pastor</h5>
        <input type="hidden" name="foto" value="<?= e($fotoAtual) ?>">
        <?php if ($fotoAtual !== ''): ?>
          <div class="ratio ratio-1x1 rounded overflow-hidden mb-3 bg-light">
            <img class="w-100 h-100 object-fit-cover" src="<?= e(upload_url($fotoAtual)) ?>" alt="Foto atual">
          </div>
        <?php endif; ?>
        <input class="form-control <?= $erroCampo('foto') ?>" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
        <?php if ($mensagemErro('foto') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('foto')) ?></div><?php endif; ?>
        <div class="form-text">JPG, PNG, WEBP ou GIF até <?= e((string) config('security.upload_max_mb', 5)) ?> MB.</div>
        <?php if ($fotoAtual !== ''): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="remover_foto" name="remover_foto" value="1">
            <label class="form-check-label" for="remover_foto">Remover foto atual</label>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-12">
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i><?= $mensagem === null ? 'Cadastrar mensagem' : 'Salvar alterações' ?></button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/palavra_pastoral')) ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
