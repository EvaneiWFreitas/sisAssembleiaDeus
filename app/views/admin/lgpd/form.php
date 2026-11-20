<?php
$ehEdicao = is_array($registro ?? null);
$valor = static function (string $campo, string $padrao = '') use ($valores): string {
    return (string) ($valores[$campo] ?? $padrao);
};
$erro = static function (string $campo) use ($erros): string {
    return (string) ($erros[$campo] ?? '');
};
$dataLocal = static function (string $valor): string {
    $valor = trim($valor);
    if ($valor === '') {
        return date('Y-m-d\TH:i');
    }
    $timestamp = strtotime($valor);

    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
};
$rotuloTipo = static function (string $tipo): string {
    return match ($tipo) {
        'membro' => 'Membros',
        'obreiro' => 'Obreiros',
        'contato' => 'Contatos recebidos',
        default => ucfirst($tipo),
    };
};
$consentido = $valor('consentido', '1') === '0' ? '0' : '1';
?>
<a class="btn btn-sm btn-outline-secondary rounded-pill mb-3" href="<?= e($voltar) ?>"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>

<div class="card-admin">
  <h5 class="mb-3"><?= $ehEdicao ? 'Editar consentimento #' . (int) $registro['id'] : 'Novo consentimento' ?></h5>
  <form method="post" action="<?= e($acao) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold" for="titular_tipo">Tipo de titular</label>
        <select class="form-select form-select-sm <?= $erro('titular_tipo') !== '' ? 'is-invalid' : '' ?>" id="titular_tipo" name="titular_tipo">
          <?php foreach (['membro', 'obreiro', 'contato'] as $tipo): ?>
            <option value="<?= e($tipo) ?>" <?= $valor('titular_tipo', 'membro') === $tipo ? 'selected' : '' ?>><?= e($rotuloTipo($tipo)) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($erro('titular_tipo') !== ''): ?><span class="field-error"><?= e($erro('titular_tipo')) ?></span><?php endif; ?>
      </div>
      <div class="col-12 col-md-8">
        <label class="form-label small fw-semibold" for="titular_id">Titular</label>
        <select class="form-select form-select-sm <?= $erro('titular_id') !== '' ? 'is-invalid' : '' ?>" id="titular_id" name="titular_id">
          <option value="">Selecione o titular</option>
          <?php foreach ($titulares as $tipo => $itens): ?>
            <optgroup label="<?= e($rotuloTipo($tipo)) ?>">
              <?php foreach ($itens as $item): ?>
                <option data-tipo="<?= e($tipo) ?>" value="<?= e((int) $item['id']) ?>" <?= (string) $valor('titular_id') === (string) (int) $item['id'] ? 'selected' : '' ?>>
                  <?= e((string) $item['nome']) ?><?= (string) ($item['email'] ?? '') !== '' ? ' · ' . e((string) $item['email']) : '' ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
        <?php if ($erro('titular_id') !== ''): ?><span class="field-error"><?= e($erro('titular_id')) ?></span><?php endif; ?>
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold" for="finalidade">Finalidade</label>
        <input class="form-control form-control-sm <?= $erro('finalidade') !== '' ? 'is-invalid' : '' ?>" type="text" id="finalidade" name="finalidade" value="<?= e($valor('finalidade')) ?>" placeholder="Ex.: Comunicação de avisos e eventos">
        <?php if ($erro('finalidade') !== ''): ?><span class="field-error"><?= e($erro('finalidade')) ?></span><?php endif; ?>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold" for="consentido">Situação</label>
        <select class="form-select form-select-sm" id="consentido" name="consentido">
          <option value="1" <?= $consentido === '1' ? 'selected' : '' ?>>Consentido</option>
          <option value="0" <?= $consentido === '0' ? 'selected' : '' ?>>Não consentido</option>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold" for="data_consentimento">Data e hora</label>
        <input class="form-control form-control-sm <?= $erro('data_consentimento') !== '' ? 'is-invalid' : '' ?>" type="datetime-local" id="data_consentimento" name="data_consentimento" value="<?= e($dataLocal($valor('data_consentimento'))) ?>">
        <?php if ($erro('data_consentimento') !== ''): ?><span class="field-error"><?= e($erro('data_consentimento')) ?></span><?php endif; ?>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold" for="ip">IP de origem</label>
        <input class="form-control form-control-sm <?= $erro('ip') !== '' ? 'is-invalid' : '' ?>" type="text" id="ip" name="ip" value="<?= e($valor('ip')) ?>" placeholder="Opcional">
        <?php if ($erro('ip') !== ''): ?><span class="field-error"><?= e($erro('ip')) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e($voltar) ?>">Cancelar</a>
      <button class="btn btn-sm btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
    </div>
  </form>
</div>

<script>
(function () {
  const tipo = document.getElementById('titular_tipo');
  const titular = document.getElementById('titular_id');
  if (!tipo || !titular) return;
  const filtrar = () => {
    for (const opcao of titular.options) {
      if (opcao.dataset.tipo) opcao.hidden = opcao.dataset.tipo !== tipo.value;
    }
    if (titular.selectedOptions.length > 0 && titular.selectedOptions[0].hidden) {
      titular.value = '';
    }
  };
  tipo.addEventListener('change', filtrar);
  filtrar();
})();
</script>
