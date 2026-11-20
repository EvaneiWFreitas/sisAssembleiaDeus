<?php
$podePublicar = \App\Auth::hasPermission('eventos', 'publicar');
$acao = $evento === null ? url('/admin/modulos/eventos') : url('/admin/modulos/eventos/' . (int) $evento['id']);
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
$fotoAtual = (string) ($evento['foto'] ?? $valor('foto'));
$slug = (string) ($evento['slug'] ?? '');
?>
<form id="form-evento" method="post" action="<?= e($acao) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12">
      <div class="card-admin">
        <h5 class="mb-3">Dados do evento</h5>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="titulo">Título <span class="text-danger">*</span></label>
            <input class="form-control <?= $erroCampo('titulo') ?>" id="titulo" name="titulo" value="<?= e($valor('titulo')) ?>" maxlength="180" required>
            <?php if ($mensagemErro('titulo') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('titulo')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-lg-8">
            <label class="form-label" for="descricao">Descrição</label>
            <textarea class="form-control <?= $erroCampo('descricao') ?>" id="descricao" name="descricao" rows="4" maxlength="2000"><?= e($valor('descricao')) ?></textarea>
            <?php if ($mensagemErro('descricao') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('descricao')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-lg-4">
            <label class="form-label" for="tipo">Tipo <span class="text-danger">*</span></label>
            <select class="form-select <?= $erroCampo('tipo') ?>" id="tipo" name="tipo" required>
              <?php foreach (\App\EventoRepository::TIPOS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($valor('tipo'), $chave) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($mensagemErro('tipo') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('tipo')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="data_inicio">Início <span class="text-danger">*</span></label>
            <input class="form-control <?= $erroCampo('data_inicio') ?>" type="datetime-local" id="data_inicio" name="data_inicio" value="<?= e($valor('data_inicio')) ?>" required>
            <?php if ($mensagemErro('data_inicio') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('data_inicio')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="data_fim">Término</label>
            <input class="form-control <?= $erroCampo('data_fim') ?>" type="datetime-local" id="data_fim" name="data_fim" value="<?= e($valor('data_fim')) ?>">
            <?php if ($mensagemErro('data_fim') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('data_fim')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label" for="horario">Horário de destaque</label>
            <input class="form-control <?= $erroCampo('horario') ?>" id="horario" name="horario" value="<?= e($valor('horario')) ?>" placeholder="18:00" maxlength="50">
            <?php if ($mensagemErro('horario') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('horario')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label" for="local">Local</label>
            <input class="form-control <?= $erroCampo('local') ?>" id="local" name="local" value="<?= e($valor('local')) ?>" placeholder="Igreja Matriz" maxlength="200">
            <?php if ($mensagemErro('local') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('local')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label" for="responsavel_id">Responsável</label>
            <select class="form-select <?= $erroCampo('responsavel_id') ?>" id="responsavel_id" name="responsavel_id">
              <option value="0">Não informar</option>
              <?php foreach ($responsaveis as $responsavel): ?>
                <option value="<?= e((int) $responsavel['id']) ?>" <?= $selecionado($valor('responsavel_id'), (int) $responsavel['id']) ?>><?= e((string) $responsavel['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($mensagemErro('responsavel_id') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('responsavel_id')) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card-admin">
        <h5 class="mb-3">Imagem do evento</h5>
        <input type="hidden" name="foto" value="<?= e($fotoAtual) ?>">
        <input class="form-control <?= $erroCampo('foto') ?>" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
        <?php if ($mensagemErro('foto') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('foto')) ?></div><?php endif; ?>
        <div class="form-text">JPG, PNG, WEBP ou GIF até <?= e((string) config('security.upload_max_mb', 5)) ?> MB. Se vazio, o site usa a imagem padrão.</div>
        <?php if ($fotoAtual !== ''): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="remover_foto" name="remover_foto" value="1">
            <label class="form-check-label" for="remover_foto">Remover imagem atual</label>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card-admin h-100">
        <h5 class="mb-3">Publicação</h5>
        <?php if ($podePublicar): ?>
          <input type="hidden" name="publicado" value="0">
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="publicado" name="publicado" value="1" <?= $valor('publicado') === '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="publicado">Publicar no site</label>
          </div>
          <input type="hidden" name="destaque" value="0">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="destaque" name="destaque" value="1" <?= $valor('destaque') === '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="destaque">Destacar na home</label>
          </div>
        <?php else: ?>
          <div class="alert alert-info small mb-0">Seu perfil pode cadastrar e editar, mas não publicar eventos. A publicação permanece sob responsabilidade da coordenação.</div>
        <?php endif; ?>
        <?php if ($slug !== ''): ?>
          <div class="mt-3 small text-muted">Slug: <span class="fw-semibold"><?= e($slug) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-12">
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i><?= $evento === null ? 'Cadastrar evento' : 'Salvar alterações' ?></button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/eventos')) ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
