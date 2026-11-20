<?php
$acao = $culto === null ? url('/admin/modulos/programacao') : url('/admin/modulos/programacao/' . (int) $culto['id']);
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
<form id="form-programacao" method="post" action="<?= e($acao) ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card-admin">
        <h5 class="mb-3">Dados do culto</h5>
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label" for="dia_semana">Dia da semana</label>
            <select class="form-select <?= $erroCampo('dia_semana') ?>" id="dia_semana" name="dia_semana" required>
              <option value="">Selecione</option>
              <?php foreach (\App\ProgramacaoRepository::DIAS as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $valor('dia_semana') === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($mensagemErro('dia_semana') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('dia_semana')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label" for="horario">Horário</label>
            <input class="form-control <?= $erroCampo('horario') ?>" type="time" id="horario" name="horario" value="<?= e($valor('horario')) ?>" required>
            <?php if ($mensagemErro('horario') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('horario')) ?></div><?php endif; ?>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label" for="ordem">Ordem de exibição</label>
            <input class="form-control <?= $erroCampo('ordem') ?>" type="number" min="0" step="1" id="ordem" name="ordem" value="<?= e($valor('ordem')) ?>">
            <div class="form-text">Menores valores aparecem primeiro no dia.</div>
            <?php if ($mensagemErro('ordem') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('ordem')) ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="tipo">Tipo de culto</label>
            <input class="form-control <?= $erroCampo('tipo') ?>" id="tipo" name="tipo" value="<?= e($valor('tipo')) ?>" maxlength="80" placeholder="Ex.: Culto da Família">
            <?php if ($mensagemErro('tipo') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('tipo')) ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="descricao">Descrição</label>
            <input class="form-control <?= $erroCampo('descricao') ?>" id="descricao" name="descricao" value="<?= e($valor('descricao')) ?>" maxlength="255" placeholder="Breve descrição do encontro">
            <?php if ($mensagemErro('descricao') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('descricao')) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card-admin mb-3">
        <h5 class="mb-3">Local e exibição</h5>
        <div class="mb-3">
          <label class="form-label" for="congregacao_id">Congregação</label>
          <select class="form-select <?= $erroCampo('congregacao_id') ?>" id="congregacao_id" name="congregacao_id">
            <option value="">Global (sem congregação)</option>
            <?php foreach ($congregacoes as $congregacao): ?>
              <option value="<?= e((string) (int) $congregacao['id']) ?>" <?= $valor('congregacao_id') === (string) (int) $congregacao['id'] ? 'selected' : '' ?>><?= e((string) $congregacao['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">A opção global aparece em todas as congregações.</div>
          <?php if ($mensagemErro('congregacao_id') !== ''): ?><div class="invalid-feedback d-block"><?= e($mensagemErro('congregacao_id')) ?></div><?php endif; ?>
        </div>
        <input type="hidden" name="ativo" value="0">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" <?= $valor('ativo') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="ativo">Exibir no site</label>
        </div>
        <div class="form-text">Horários inativos ficam salvos, mas não aparecem na programação pública.</div>
      </div>
    </div>

    <div class="col-12">
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i><?= $culto === null ? 'Adicionar horário' : 'Salvar alterações' ?></button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/programacao')) ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
